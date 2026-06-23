<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Scb\JsonStatDataset;
use App\Data\Scb\JsonStatDimension;
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
    private const int MAX_VALUES_PER_REQUEST = 100_000;

    private const string TABLE_HOUSEHOLD = 'HushallT08CKM';

    private const string TABLE_POPULATION = 'BefolkningCKM';

    private const int CHUNK_SIZE = 50;

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('singelkartan.scb_base_url'), '/');
    }

    /**
     * Fetch single-person household counts from HushallT08CKM.
     *
     * @param  list<string>  $regionCodes  Kommunkoder e.g. ['0114', '0115']
     * @param  list<string>  $ageCodes     SCB age codes e.g. ['20-24', '25-29']
     * @param  list<Sex>     $sexes
     * @return Collection<int, ScbObservation>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetchHouseholdData(array $regionCodes, array $ageCodes, array $sexes): Collection
    {
        return $this->fetchInChunks(
            tableId: self::TABLE_HOUSEHOLD,
            regionCodes: $regionCodes,
            ageCodes: $ageCodes,
            sexes: $sexes,
            householdStatuses: HouseholdStatus::cases(),
        );
    }

    /**
     * Fetch population counts from BefolkningCKM.
     *
     * @param  list<string>  $regionCodes
     * @param  list<string>  $ageCodes
     * @param  list<Sex>     $sexes
     * @return Collection<int, ScbObservation>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetchPopulationData(array $regionCodes, array $ageCodes, array $sexes): Collection
    {
        return $this->fetchInChunks(
            tableId: self::TABLE_POPULATION,
            regionCodes: $regionCodes,
            ageCodes: $ageCodes,
            sexes: $sexes,
            householdStatuses: [],
        );
    }

    /**
     * @param  list<string>          $regionCodes
     * @param  list<string>          $ageCodes
     * @param  list<Sex>             $sexes
     * @param  list<HouseholdStatus> $householdStatuses
     * @return Collection<int, ScbObservation>
     *
     * @throws ConnectionException
     * @throws RequestException
     */
    private function fetchInChunks(
        string $tableId,
        array $regionCodes,
        array $ageCodes,
        array $sexes,
        array $householdStatuses,
    ): Collection {
        $results = collect();

        foreach (array_chunk($regionCodes, self::CHUNK_SIZE) as $chunk) {
            $payload = $this->buildPayload(
                regionCodes: $chunk,
                ageCodes: $ageCodes,
                sexes: $sexes,
                householdStatuses: $householdStatuses,
            );

            $dataset = $this->post($tableId, $payload);
            $observations = $this->parseJsonStat($dataset);

            $results = $results->merge($observations);
        }

        return $results;
    }

    /**
     * @param  list<string>          $regionCodes
     * @param  list<string>          $ageCodes
     * @param  list<Sex>             $sexes
     * @param  list<HouseholdStatus> $householdStatuses
     */
    private function buildPayload(
        array $regionCodes,
        array $ageCodes,
        array $sexes,
        array $householdStatuses,
    ): ScbQueryPayload {
        $selection = [
            [
                'variableCode' => 'Region',
                'valueCodes' => $regionCodes,
            ],
            [
                'variableCode' => 'Alder',
                'valueCodes' => $ageCodes,
            ],
            [
                'variableCode' => 'Kon',
                'valueCodes' => array_map(fn (Sex $sex): string => $sex->scbCode(), $sexes),
            ],
        ];

        if ($householdStatuses !== []) {
            $selection[] = [
                'variableCode' => 'HushallsstallningT8',
                'valueCodes' => array_map(
                    fn (HouseholdStatus $s): string => $s->scbCode(),
                    $householdStatuses
                ),
            ];
        }

        return new ScbQueryPayload(selection: $selection);
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function post(string $tableId, ScbQueryPayload $payload): JsonStatDataset
    {
        $url = "{$this->baseUrl}/tables/{$tableId}/data";

        $response = Http::retry(3, 200)
            ->timeout(30)
            ->withHeaders(['Accept' => 'application/json'])
            ->post($url, $payload->toArray());

        $response->throw();

        return JsonStatDataset::fromArray((array) $response->json());
    }

    /**
     * Parse a JSON-stat2 dataset into normalized observations.
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
        $ageDim = $dataset->dimensions['Alder'] ?? null;
        $sexDim = $dataset->dimensions['Kon'] ?? null;
        $statusDim = $dataset->dimensions['HushallsstallningT8'] ?? null;

        if ($regionDim === null || $ageDim === null || $sexDim === null) {
            return $observations;
        }

        $valueIndex = 0;

        foreach ($regionDim->categoryIds as $regionCode) {
            foreach ($ageDim->categoryIds as $ageCode) {
                foreach ($sexDim->categoryIds as $sexCode) {
                    if ($statusDim !== null) {
                        foreach ($statusDim->categoryIds as $statusCode) {
                            $value = $dataset->values[$valueIndex++] ?? null;
                            if ($value !== null) {
                                $observation = $this->buildObservation(
                                    $regionCode, $ageCode, $sexCode, $statusCode, (int) $value
                                );
                                if ($observation !== null) {
                                    $observations->push($observation);
                                }
                            }
                        }
                    } else {
                        $value = $dataset->values[$valueIndex++] ?? null;
                        if ($value !== null) {
                            $observation = $this->buildObservation(
                                $regionCode, $ageCode, $sexCode, null, (int) $value
                            );
                            if ($observation !== null) {
                                $observations->push($observation);
                            }
                        }
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
        if ($sex === null) {
            return null;
        }

        $householdStatus = $statusCode !== null
            ? $this->mapHouseholdStatusCode($statusCode)
            : HouseholdStatus::Other;

        if ($householdStatus === null) {
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
            'EP' => HouseholdStatus::SinglePerson,
            'SB' => HouseholdStatus::Cohabiting,
            'OV' => HouseholdStatus::Other,
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
