<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RegionLevel;
use App\Enums\Sex;
use App\Models\Demographic;
use App\Models\Region;
use App\Models\ScoreCache;
use App\Services\ScbClient;
use App\Services\SingleScoreService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;

#[Signature('singelkartan:sync {--dry-run : Fetch and parse but do not write to database} {--deso : Also sync DeSO areas}')]
#[Description('Sync demographic data from SCB PxWebApi 2 into the demographics table.')]
class SingelkartanSync extends Command
{
    /** Standard 5-year age groups used in SCB TAB6689. */
    private const array AGE_CODES = [
        '20-24', '25-29', '30-34', '35-39', '40-44',
        '45-49', '50-54', '55-59', '60-64', '65-69',
    ];

    public function handle(ScbClient $client): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $year = (int) config('singelkartan.sync_year');

        $this->info("Syncing SCB data for year {$year}".($isDryRun ? ' (dry run)' : '').'...');

        try {
            $this->syncHouseholdData($client, $isDryRun);

            if ((bool) $this->option('deso')) {
                $this->syncDesoData($client, $isDryRun);
            }
        } catch (ConnectionException $e) {
            $this->error('SCB API connection failed: '.$e->getMessage());

            return Command::FAILURE;
        } catch (RequestException $e) {
            $this->error('SCB API request failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Sync complete.');

        return Command::SUCCESS;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function syncHouseholdData(ScbClient $client, bool $isDryRun): void
    {
        $this->info('Fetching household data from SCB...');

        $observations = $client->fetchHouseholdData(
            regionCodes: $this->kommunerCodes(),
            ageCodes: self::AGE_CODES,
            sexes: [Sex::Male, Sex::Female],
        );

        $this->info("Received {$observations->count()} observations.");

        if ($isDryRun) {
            return;
        }

        $this->upsertRegions();

        $regionMap = Region::where('level', RegionLevel::Kommun->value)
            ->pluck('id', 'code');

        $rows = $observations
            ->filter(fn ($obs): bool => $regionMap->has($obs->regionCode))
            ->map(fn ($obs): array => [
                'region_id' => $regionMap->get($obs->regionCode),
                'year' => $obs->year,
                'sex' => $obs->sex->value,
                'age_min' => $obs->ageMin,
                'age_max' => $obs->ageMax,
                'household_status' => $obs->householdStatus->value,
                'count' => $obs->count,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ])
            ->values()
            ->all();

        $this->info('Upserting '.count($rows).' demographic rows...');

        foreach (array_chunk($rows, 500) as $chunk) {
            Demographic::upsert(
                $chunk,
                uniqueBy: ['region_id', 'year', 'sex', 'age_min', 'age_max', 'household_status'],
                update: ['count', 'updated_at'],
            );
        }

        $this->info('Clearing stale kommuner score cache...');
        ScoreCache::whereHas('region', fn ($q) => $q->where('level', RegionLevel::Kommun->value))->delete();

        $this->info('Pre-computing scores for standard age/sex combinations...');
        $this->computeStandardScores();
    }

    private function computeStandardScores(): void
    {
        $service = app(SingleScoreService::class);
        $standardRanges = [
            [20, 24], [25, 29], [30, 34], [35, 39], [40, 44],
            [45, 49], [50, 54], [55, 59], [60, 64], [65, 69],
            [20, 29], [25, 34], [30, 39], [35, 44], [40, 49],
        ];

        foreach ([Sex::Female, Sex::Male] as $sex) {
            foreach ($standardRanges as [$ageMin, $ageMax]) {
                $service->computeTopRegions($sex, $ageMin, $ageMax, 290);
                $this->line("  Computed {$sex->value} {$ageMin}–{$ageMax}");
            }
        }
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function syncDesoData(ScbClient $client, bool $isDryRun): void
    {
        $this->info('Fetching DeSO household data from SCB (TAB6568)...');

        $year = (int) config('singelkartan.sync_year');
        $minCount = (int) config('singelkartan.min_count_threshold', 20);

        // All 6 160 DeSO 2025 codes with required suffix
        $desoCodes = array_map(
            fn (string $code): string => $code.'_DeSO2025',
            $this->loadDesoCodes(),
        );

        $observations = $client->fetchDesoData($desoCodes, $year);
        $this->info("Received {$observations->count()} DeSO observations.");

        if ($isDryRun) {
            return;
        }

        // Build municipality name map from existing Kommun regions
        $municipalityNames = Region::where('level', RegionLevel::Kommun->value)
            ->pluck('name', 'code');

        // Upsert DeSO Region records
        $regionRows = $observations->map(fn ($obs): array => [
            'code' => $obs->desoCode,
            'name' => $municipalityNames->get($obs->kommunCode, $obs->kommunCode),
            'level' => RegionLevel::Deso->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ])->values()->all();

        foreach (array_chunk($regionRows, 500) as $chunk) {
            Region::upsert($chunk, uniqueBy: ['code'], update: ['name', 'updated_at']);
        }

        // Map code → region_id
        $regionMap = Region::where('level', RegionLevel::Deso->value)->pluck('id', 'code');

        // Compute global mean for Bayesian shrinkage
        $qualified = $observations->filter(fn ($obs): bool => $obs->totalCount >= $minCount);
        $globalTotal = $qualified->sum('totalCount');
        $globalSingles = $qualified->sum('singleCount');
        $globalMean = $globalTotal > 0 ? $globalSingles / $globalTotal : 0.0;
        $shrinkageK = (int) config('singelkartan.shrinkage_k', 50);

        $scoreRows = $qualified
            ->filter(fn ($obs): bool => $regionMap->has($obs->desoCode))
            ->map(fn ($obs): array => [
                'region_id' => $regionMap->get($obs->desoCode),
                'sex' => Sex::All->value,
                'age_min' => 0,
                'age_max' => 0,
                'single_count' => $obs->singleCount,
                'total_count' => $obs->totalCount,
                'single_ratio' => $obs->totalCount > 0
                    ? round($obs->singleCount / $obs->totalCount, 6)
                    : 0.0,
                'score' => $this->computeDesoScore($obs->singleCount, $obs->totalCount, $globalMean, $shrinkageK),
                'computed_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ])
            ->values()
            ->all();

        $this->info('Upserting '.count($scoreRows).' DeSO score rows...');

        foreach (array_chunk($scoreRows, 500) as $chunk) {
            ScoreCache::upsert(
                $chunk,
                uniqueBy: ['region_id', 'sex', 'age_min', 'age_max'],
                update: ['single_count', 'total_count', 'single_ratio', 'score', 'computed_at', 'updated_at'],
            );
        }
    }

    private function computeDesoScore(int $singleCount, int $totalCount, float $globalMean, int $shrinkageK): float
    {
        if ($totalCount === 0) {
            return 0.0;
        }

        $ratio = $singleCount / $totalCount;
        $weight = $totalCount / ($totalCount + $shrinkageK);
        $shrunk = $ratio * $weight + $globalMean * (1 - $weight);

        return round(min(100.0, $shrunk * 100), 2);
    }

    /** @return list<string> */
    private function loadDesoCodes(): array
    {
        $path = public_path('geojson/deso.geojson');

        /** @var array{features: list<array{properties: array{deso: string}}>} $geojson */
        $geojson = json_decode((string) file_get_contents($path), associative: true);

        return array_map(
            fn (array $f): string => $f['properties']['desokod'],
            $geojson['features'],
        );
    }

    /** @return list<string> */
    private function kommunerCodes(): array
    {
        $geojsonPath = public_path('geojson/kommuner.geojson');

        /** @var array{features: list<array{properties: array{id: string}}>} $geojson */
        $geojson = json_decode((string) file_get_contents($geojsonPath), associative: true);

        return array_values(array_map(
            fn (array $f): string => $f['properties']['id'],
            $geojson['features'],
        ));
    }

    private function upsertRegions(): void
    {
        $geojsonPath = public_path('geojson/kommuner.geojson');

        /** @var array{features: list<array{properties: array{id: string, kom_namn: string}}>} $geojson */
        $geojson = json_decode((string) file_get_contents($geojsonPath), associative: true);

        $rows = array_map(fn (array $f): array => [
            'code' => $f['properties']['id'],
            'name' => $f['properties']['kom_namn'],
            'level' => RegionLevel::Kommun->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], $geojson['features']);

        foreach (array_chunk($rows, 100) as $chunk) {
            Region::upsert(
                $chunk,
                uniqueBy: ['code'],
                update: ['name', 'updated_at'],
            );
        }
    }
}
