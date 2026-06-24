<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Scb\DesoObservation;
use App\Data\Scb\JsonStatDataset;
use App\Data\Scb\ScbObservation;
use App\Data\Scb\ScbQueryPayload;
use App\Enums\HouseholdStatus;
use App\Enums\Sex;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for SCB PxWebApi 2 (JSON-stat2).
 *
 * Max 150 000 values per request — this client stays well below that by
 * chunking region lists when necessary.
 */
class ScbClient
{
    private const string TABLE_HOUSEHOLD = 'TAB6689';

    private const string TABLE_DESO = 'TAB6568';

    private const string DESO_CODELIST = 'vs_DeSO2025';

    private const string DESO_CONTENTS_CODE = '000007Y1';

    private const string HOUSEHOLD_CONTENTS_CODE = '000008AI';

    private const string HOUSEHOLD_SINGLE_CODE = 'Aensm';

    private const string HOUSEHOLD_TOTAL_CODE = 'Apers';

    private const int CHUNK_SIZE = 50;

    private const int DESO_CHUNK_SIZE = 1000;

    private readonly string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('singelkartan.scb_base_url'), '/');
    }

    /**
     * Fetch single-person household counts from TAB6689.
     *
     * Requests Aensm (ensamboende/single) and Apers (total) per region/age/sex.
     * Emits two ScbObservations per combination: SinglePerson + Other (complement).
     *
     * @param  list<string>  $regionCodes  Kommunkoder e.g. ['0114', '0115']
     * @param  list<string>  $ageCodes  SCB age codes e.g. ['20-24', '25-29']
     * @param  list<Sex>  $sexes
     * @return Collection<int, ScbObservation>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetchHouseholdData(array $regionCodes, array $ageCodes, array $sexes): Collection
    {
        return $this->fetchInChunks(
            regionCodes: $regionCodes,
            ageCodes: $ageCodes,
            sexes: $sexes,
        );
    }

    /**
     * Fetch DeSO 2025 household type counts from TAB6568.
     *
     * Returns one observation per DeSO area with pre-aggregated single and total counts.
     * All 6 160 areas fit in a single request (18 480 values, well below 150k limit),
     * but we chunk at 1 000 to keep individual requests small.
     *
     * @param  list<string>  $desoCodes  Full codes including _DeSO2025 suffix
     * @return Collection<int, DesoObservation>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetchDesoData(array $desoCodes, int $year): Collection
    {
        $results = collect();

        foreach (array_chunk($desoCodes, self::DESO_CHUNK_SIZE) as $chunk) {
            $payload = [
                'selection' => [
                    [
                        'variableCode' => 'Region',
                        'codeList' => self::DESO_CODELIST,
                        'valueCodes' => $chunk,
                    ],
                    [
                        'variableCode' => 'Hushallstyp',
                        'valueCodes' => ['ESMB', 'ESUB', 'TOTALT'],
                    ],
                    [
                        'variableCode' => 'ContentsCode',
                        'valueCodes' => [self::DESO_CONTENTS_CODE],
                    ],
                    [
                        'variableCode' => 'Tid',
                        'valueCodes' => [(string) $year],
                    ],
                ],
            ];

            $dataset = $this->post(self::TABLE_DESO, new ScbQueryPayload(selection: $payload['selection']));
            $results = $results->merge($this->parseDesoJsonStat($dataset));
        }

        return $results;
    }

    /**
     * @param  list<string>  $regionCodes
     * @param  list<string>  $ageCodes
     * @param  list<Sex>  $sexes
     * @return Collection<int, ScbObservation>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function fetchInChunks(
        array $regionCodes,
        array $ageCodes,
        array $sexes,
    ): Collection {
        $results = collect();

        foreach (array_chunk($regionCodes, self::CHUNK_SIZE) as $chunk) {
            $payload = $this->buildPayload(
                regionCodes: $chunk,
                ageCodes: $ageCodes,
                sexes: $sexes,
            );

            $dataset = $this->post(self::TABLE_HOUSEHOLD, $payload);
            $observations = $this->parseJsonStat($dataset);

            $results = $results->merge($observations);
        }

        return $results;
    }

    /**
     * Parse a TAB6568 JSON-stat2 response into DesoObservations.
     *
     * Expected dimension order: Region × Hushallstyp × ContentsCode × Tid
     * Value codes requested: ESMB, ESUB, TOTALT (in that order).
     *
     * @return Collection<int, DesoObservation>
     */
    private function parseDesoJsonStat(JsonStatDataset $dataset): Collection
    {
        $observations = collect();

        if ($dataset->values === []) {
            return $observations;
        }

        $regionDim = $dataset->dimensions['Region'] ?? null;
        $typeDim = $dataset->dimensions['Hushallstyp'] ?? null;

        if ($regionDim === null || $typeDim === null) {
            return $observations;
        }

        $typeIds = $typeDim->categoryIds;
        $esmbIdx = array_search('ESMB', $typeIds, true);
        $esubIdx = array_search('ESUB', $typeIds, true);
        $totaltIdx = array_search('TOTALT', $typeIds, true);

        if ($esmbIdx === false || $esubIdx === false || $totaltIdx === false) {
            return $observations;
        }

        $numTypes = count($typeIds);

        foreach ($regionDim->categoryIds as $position => $fullCode) {
            $base = $position * $numTypes;
            $esmb = $dataset->values[$base + $esmbIdx] ?? 0;
            $esub = $dataset->values[$base + $esubIdx] ?? 0;
            $totalt = $dataset->values[$base + $totaltIdx] ?? 0;

            // Strip the _DeSO2025 suffix to get the bare 9-char desokod
            $desoCode = str_replace('_DeSO2025', '', $fullCode);

            // Municipality code = first 4 digits of the desokod
            $kommunCode = substr($desoCode, 0, 4);

            $observations->push(new DesoObservation(
                desoCode: $desoCode,
                kommunCode: $kommunCode,
                singleCount: (int) $esmb + (int) $esub,
                totalCount: (int) $totalt,
            ));
        }

        return $observations;
    }

    /**
     * @param  list<string>  $regionCodes
     * @param  list<string>  $ageCodes
     * @param  list<Sex>  $sexes
     */
    private function buildPayload(
        array $regionCodes,
        array $ageCodes,
        array $sexes,
    ): ScbQueryPayload {
        $year = (int) config('singelkartan.sync_year');

        return new ScbQueryPayload(selection: [
            [
                'variableCode' => 'Region',
                'valueCodes' => $regionCodes,
            ],
            [
                'variableCode' => 'Hushallsstallning',
                'valueCodes' => [self::HOUSEHOLD_SINGLE_CODE, self::HOUSEHOLD_TOTAL_CODE],
            ],
            [
                'variableCode' => 'Alder',
                'valueCodes' => $ageCodes,
            ],
            [
                'variableCode' => 'Kon',
                'valueCodes' => array_map(fn (Sex $sex): string => $sex->scbCode(), $sexes),
            ],
            [
                'variableCode' => 'ContentsCode',
                'valueCodes' => [self::HOUSEHOLD_CONTENTS_CODE],
            ],
            [
                'variableCode' => 'Tid',
                'valueCodes' => [(string) $year],
            ],
        ]);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function post(string $tableId, ScbQueryPayload $payload): JsonStatDataset
    {
        $url = "{$this->baseUrl}/tables/{$tableId}/data?outputFormat=json-stat2";

        $response = Http::retry(3, 200)
            ->timeout(30)
            ->withHeaders(['Accept' => 'application/json'])
            ->post($url, $payload->toArray());

        $response->throw();

        return JsonStatDataset::fromArray((array) $response->json());
    }

    /**
     * Parse a TAB6689 JSON-stat2 dataset into normalized observations.
     *
     * TAB6689 dimension order: Region × Hushallsstallning × Alder × Kon × ContentsCode × Tid.
     * We request Aensm (single) and Apers (total). For each region/age/sex we emit:
     *   - SinglePerson with count = Aensm
     *   - Other with count = Apers − Aensm  (complement so SUM = total)
     *
     * @return Collection<int, ScbObservation>
     */
    private function parseJsonStat(JsonStatDataset $dataset): Collection
    {
        $observations = collect();

        if ($dataset->values === []) {
            return $observations;
        }

        $regionDim = $dataset->dimensions['Region'] ?? null;
        $statusDim = $dataset->dimensions['Hushallsstallning'] ?? null;
        $ageDim = $dataset->dimensions['Alder'] ?? null;
        $sexDim = $dataset->dimensions['Kon'] ?? null;

        if ($regionDim === null || $statusDim === null || $ageDim === null || $sexDim === null) {
            return $observations;
        }

        $valueIndex = 0;

        foreach ($regionDim->categoryIds as $regionCode) {
            /** @var array<string, array<string, array{single: int, total: int}>> $buffer */
            $buffer = [];

            foreach ($statusDim->categoryIds as $statusCode) {
                foreach ($ageDim->categoryIds as $ageCode) {
                    foreach ($sexDim->categoryIds as $sexCode) {
                        $value = (int) ($dataset->values[$valueIndex++] ?? 0);

                        if ($statusCode === self::HOUSEHOLD_SINGLE_CODE) {
                            $buffer[$ageCode][$sexCode]['single'] = $value;
                        } elseif ($statusCode === self::HOUSEHOLD_TOTAL_CODE) {
                            $buffer[$ageCode][$sexCode]['total'] = $value;
                        }
                    }
                }
            }

            foreach ($buffer as $ageCode => $sexData) {
                foreach ($sexData as $sexCode => $counts) {
                    $single = $counts['single'] ?? 0;
                    $total = $counts['total'] ?? 0;
                    $complement = max(0, $total - $single);

                    $obs = $this->buildObservation($regionCode, (string) $ageCode, (string) $sexCode, self::HOUSEHOLD_SINGLE_CODE, $single);
                    if ($obs instanceof ScbObservation) {
                        $observations->push($obs);
                    }

                    $obs = $this->buildObservation($regionCode, (string) $ageCode, (string) $sexCode, 'AensComplement', $complement);
                    if ($obs instanceof ScbObservation) {
                        $observations->push($obs);
                    }
                }
            }
        }

        return $observations;
    }

    private function buildObservation(
        string $regionCode,
        string $ageCode,
        string $sexCode,
        ?string $statusCode,
        int $count,
    ): ?ScbObservation {
        $sex = $this->mapSexCode($sexCode);
        if (! $sex instanceof Sex) {
            return null;
        }

        $householdStatus = $statusCode !== null
            ? $this->mapHouseholdStatusCode($statusCode)
            : HouseholdStatus::Other;

        if (! $householdStatus instanceof HouseholdStatus) {
            return null;
        }

        [$ageMin, $ageMax] = $this->parseAgeCode($ageCode);

        return new ScbObservation(
            regionCode: $regionCode,
            year: (int) config('singelkartan.sync_year'),
            sex: $sex,
            ageMin: $ageMin,
            ageMax: $ageMax,
            householdStatus: $householdStatus,
            count: $count,
        );
    }

    private function mapSexCode(string $code): ?Sex
    {
        return match ($code) {
            '1' => Sex::Male,
            '2' => Sex::Female,
            'TOT' => Sex::All,
            default => null,
        };
    }

    private function mapHouseholdStatusCode(string $code): ?HouseholdStatus
    {
        return match ($code) {
            self::HOUSEHOLD_SINGLE_CODE => HouseholdStatus::SinglePerson,
            'AensComplement' => HouseholdStatus::Other,
            default => null,
        };
    }

    /** @return array{int, int} */
    private function parseAgeCode(string $ageCode): array
    {
        if (str_contains($ageCode, '-')) {
            $parts = explode('-', $ageCode, 2);

            return [(int) $parts[0], (int) ($parts[1] ?? $parts[0])];
        }

        if (str_ends_with($ageCode, '+')) {
            $min = (int) rtrim($ageCode, '+');

            return [$min, 99];
        }

        $age = (int) $ageCode;

        return [$age, $age];
    }
}
