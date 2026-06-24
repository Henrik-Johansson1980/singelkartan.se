<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\HouseholdStatus;
use App\Enums\RegionLevel;
use App\Enums\Sex;
use App\Models\Region;
use App\Models\ScoreCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private const array SWEDISH_KOMMUNER = [
        ['code' => '0180', 'name' => 'Stockholm', 'lat' => 59.3293, 'lng' => 18.0686],
        ['code' => '1280', 'name' => 'Malmö', 'lat' => 55.6050, 'lng' => 13.0038],
        ['code' => '1480', 'name' => 'Göteborg', 'lat' => 57.7089, 'lng' => 11.9746],
        ['code' => '0380', 'name' => 'Uppsala', 'lat' => 59.8586, 'lng' => 17.6389],
        ['code' => '1281', 'name' => 'Lund', 'lat' => 55.7047, 'lng' => 13.1910],
        ['code' => '0580', 'name' => 'Linköping', 'lat' => 58.4108, 'lng' => 15.6214],
        ['code' => '0680', 'name' => 'Jönköping', 'lat' => 57.7826, 'lng' => 14.1618],
        ['code' => '0880', 'name' => 'Kalmar', 'lat' => 56.6616, 'lng' => 16.3562],
        ['code' => '2280', 'name' => 'Härnösand', 'lat' => 62.6318, 'lng' => 17.9381],
        ['code' => '2480', 'name' => 'Umeå', 'lat' => 63.8258, 'lng' => 20.2630],
        ['code' => '2580', 'name' => 'Luleå', 'lat' => 65.5843, 'lng' => 22.1568],
        ['code' => '0182', 'name' => 'Nacka', 'lat' => 59.3120, 'lng' => 18.1630],
        ['code' => '0184', 'name' => 'Solna', 'lat' => 59.3600, 'lng' => 18.0010],
        ['code' => '0183', 'name' => 'Sundbyberg', 'lat' => 59.3613, 'lng' => 17.9716],
        ['code' => '1482', 'name' => 'Kungälv', 'lat' => 57.8716, 'lng' => 11.9815],
        ['code' => '1283', 'name' => 'Helsingborg', 'lat' => 56.0465, 'lng' => 12.6945],
        ['code' => '1285', 'name' => 'Eslöv', 'lat' => 55.8389, 'lng' => 13.3041],
        ['code' => '1286', 'name' => 'Ystad', 'lat' => 55.4295, 'lng' => 13.8206],
        ['code' => '1860', 'name' => 'Degerfors', 'lat' => 59.2358, 'lng' => 14.4312],
        ['code' => '1880', 'name' => 'Örebro', 'lat' => 59.2753, 'lng' => 15.2134],
        ['code' => '1980', 'name' => 'Västerås', 'lat' => 59.6099, 'lng' => 16.5448],
        ['code' => '2080', 'name' => 'Falun', 'lat' => 60.6065, 'lng' => 15.6355],
        ['code' => '2180', 'name' => 'Gävle', 'lat' => 60.6749, 'lng' => 17.1413],
        ['code' => '2281', 'name' => 'Sundsvall', 'lat' => 62.3913, 'lng' => 17.3069],
        ['code' => '2380', 'name' => 'Östersund', 'lat' => 63.1792, 'lng' => 14.6357],
        ['code' => '1580', 'name' => 'Skövde', 'lat' => 58.3893, 'lng' => 13.8455],
        ['code' => '0760', 'name' => 'Uppvidinge', 'lat' => 56.9016, 'lng' => 15.4956],
        ['code' => '1462', 'name' => 'Lilla Edet', 'lat' => 58.1349, 'lng' => 12.1238],
        ['code' => '0980', 'name' => 'Gotland', 'lat' => 57.4684, 'lng' => 18.4867],
        ['code' => '0126', 'name' => 'Huddinge', 'lat' => 59.2362, 'lng' => 17.9810],
    ];

    public function run(): void
    {
        $this->seedRegionsWithDemographics();
    }

    private function seedRegionsWithDemographics(): void
    {
        $year = (int) config('singelkartan.sync_year', 2025);

        foreach (self::SWEDISH_KOMMUNER as $kommunData) {
            $region = Region::firstOrCreate(
                ['code' => $kommunData['code']],
                [
                    'name' => $kommunData['name'],
                    'level' => RegionLevel::Kommun->value,
                    'lat' => $kommunData['lat'],
                    'lng' => $kommunData['lng'],
                ]
            );

            $this->seedDemographicsForRegion($region->id, $year, $kommunData['name']);
        }

        $this->seedScoreCache($year);
    }

    private function seedDemographicsForRegion(int $regionId, int $year, string $regionName): void
    {
        $isUrban = in_array($regionName, ['Stockholm', 'Göteborg', 'Malmö', 'Uppsala', 'Linköping'], true);
        $singleRateBias = $isUrban ? 1.4 : 1.0;

        $ageGroups = [
            [20, 24], [25, 29], [30, 34], [35, 39], [40, 44],
            [45, 49], [50, 54], [55, 59], [60, 64], [65, 69],
        ];

        $rows = [];
        foreach ([Sex::Male, Sex::Female] as $sex) {
            foreach ($ageGroups as [$ageMin, $ageMax]) {
                $basePopulation = match (true) {
                    $isUrban && $ageMin >= 25 && $ageMin <= 34 => random_int(3000, 12000),
                    $isUrban => random_int(1500, 6000),
                    $ageMin >= 25 && $ageMin <= 34 => random_int(300, 1200),
                    default => random_int(150, 600),
                };

                $singleFraction = min(0.75, (0.30 + (mt_rand(0, 25) / 100)) * $singleRateBias);
                $cohabitingFraction = min(0.50, 0.45 - (mt_rand(0, 15) / 100));
                $otherFraction = 1 - $singleFraction - $cohabitingFraction;

                $singleCount = (int) ($basePopulation * $singleFraction);
                $cohabitingCount = (int) ($basePopulation * $cohabitingFraction);
                $otherCount = $basePopulation - $singleCount - $cohabitingCount;

                $now = Carbon::now()->toDateTimeString();

                foreach ([
                    [HouseholdStatus::SinglePerson, $singleCount],
                    [HouseholdStatus::Cohabiting, $cohabitingCount],
                    [HouseholdStatus::Other, max(0, $otherCount)],
                ] as [$status, $count]) {
                    $rows[] = [
                        'region_id' => $regionId,
                        'year' => $year,
                        'sex' => $sex->value,
                        'age_min' => $ageMin,
                        'age_max' => $ageMax,
                        'household_status' => $status->value,
                        'count' => $count,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('demographics')->upsert(
            $rows,
            uniqueBy: ['region_id', 'year', 'sex', 'age_min', 'age_max', 'household_status'],
            update: ['count', 'updated_at'],
        );
    }

    private function seedScoreCache(int $year): void
    {
        $sexes = [Sex::Male, Sex::Female];
        $ageRanges = [
            [20, 29], [25, 34], [30, 39], [35, 44], [20, 39],
        ];

        foreach ($sexes as $sex) {
            foreach ($ageRanges as [$ageMin, $ageMax]) {
                $rows = DB::table('demographics')
                    ->select([
                        'region_id',
                        DB::raw("SUM(CASE WHEN household_status = 'single_person' THEN `count` ELSE 0 END) as single_count"),
                        DB::raw('SUM(`count`) as total_count'),
                    ])
                    ->where('year', $year)
                    ->where('sex', $sex->value)
                    ->where('age_min', '>=', $ageMin)
                    ->where('age_max', '<=', $ageMax)
                    ->groupBy('region_id')
                    ->get();

                $totalSingles = $rows->sum('single_count');
                $totalPop = $rows->sum('total_count');
                $globalMean = $totalPop > 0 ? $totalSingles / $totalPop : 0.0;

                $cacheRows = $rows
                    ->filter(fn ($row): bool => $row->total_count >= 20)
                    ->map(function ($row) use ($sex, $ageMin, $ageMax, $globalMean): array {
                        $ratio = $row->total_count > 0
                            ? $row->single_count / $row->total_count
                            : 0.0;

                        $k = 50;
                        $weight = $row->total_count / ($row->total_count + $k);
                        $score = round(($ratio * $weight + $globalMean * (1 - $weight)) * 100, 2);

                        $now = Carbon::now()->toDateTimeString();

                        return [
                            'region_id' => $row->region_id,
                            'sex' => $sex->value,
                            'age_min' => $ageMin,
                            'age_max' => $ageMax,
                            'single_ratio' => round($ratio, 6),
                            'single_count' => (int) $row->single_count,
                            'total_count' => (int) $row->total_count,
                            'score' => $score,
                            'computed_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->values()
                    ->all();

                if ($cacheRows !== []) {
                    ScoreCache::upsert(
                        $cacheRows,
                        uniqueBy: ['region_id', 'sex', 'age_min', 'age_max'],
                        update: ['single_count', 'total_count', 'single_ratio', 'score', 'computed_at', 'updated_at'],
                    );
                }
            }
        }
    }
}
