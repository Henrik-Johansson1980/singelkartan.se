<?php

declare(strict_types=1);

use App\Enums\HouseholdStatus;
use App\Enums\Sex;
use App\Models\Demographic;
use App\Models\Region;
use App\Models\ScoreCache;
use App\Services\SingleScoreService;

beforeEach(function (): void {
    config([
        'singelkartan.min_count_threshold' => 20,
        'singelkartan.shrinkage_k' => 50,
        'singelkartan.sync_year' => 2025,
    ]);
});

it('returns an empty collection when no demographics exist', function (): void {
    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    expect($result)->toBeEmpty();
});

it('excludes regions below the min-count threshold', function (): void {
    $region = Region::factory()->create();

    // 5 singles + 10 cohabiting = 15 total — below min_count of 20
    Demographic::factory()->for($region)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 5, 'year' => 2025]);
    Demographic::factory()->for($region)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 10, 'year' => 2025]);

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    expect($result)->toBeEmpty();
});

it('includes regions that meet the min-count threshold', function (): void {
    $region = Region::factory()->create();

    // 10 singles + 20 cohabiting = 30 total — above min_count of 20
    Demographic::factory()->for($region)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 10, 'year' => 2025]);
    Demographic::factory()->for($region)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 20, 'year' => 2025]);

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    expect($result)->toHaveCount(1);
});

it('computes single_ratio and counts correctly', function (): void {
    $region = Region::factory()->create();

    // 50 singles + 50 cohabiting = 100 total → ratio 0.5
    Demographic::factory()->for($region)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
    Demographic::factory()->for($region)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);
    $entry = $result->first();

    expect($entry->single_count)->toBe(50)
        ->and($entry->total_count)->toBe(100)
        ->and($entry->single_ratio)->toBe(0.5);
});

it('applies bayesian shrinkage so small populations move toward the global mean', function (): void {
    // Large region: 500 singles out of 1000 — raw ratio 0.5
    $largeRegion = Region::factory()->create(['name' => 'Stockholm']);
    Demographic::factory()->for($largeRegion)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 500, 'year' => 2025]);
    Demographic::factory()->for($largeRegion)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 500, 'year' => 2025]);

    // Small region: 25 singles out of 25 — raw ratio 1.0, but population is small
    $smallRegion = Region::factory()->create(['name' => 'Småstad']);
    Demographic::factory()->for($smallRegion)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 25, 'year' => 2025]);

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    $large = $result->firstWhere(fn (ScoreCache $r): bool => $r->region->name === 'Stockholm');
    $small = $result->firstWhere(fn (ScoreCache $r): bool => $r->region->name === 'Småstad');

    // Large region (1000 obs) has negligible shrinkage — score is close to 50
    expect($large->score)->toBeBetween(48.0, 52.0);
    // Small region (25 obs) is strongly pulled toward the global mean (~51.5),
    // so its score is well below its raw 100, but still above the large region's ~50
    expect($small->score)->toBeGreaterThan($large->score);
    expect($small->score)->toBeLessThan(80.0);
});

it('orders results by score descending', function (): void {
    $highRegion = Region::factory()->create();
    $lowRegion = Region::factory()->create();

    // high: 80 singles out of 100
    Demographic::factory()->for($highRegion)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 80, 'year' => 2025]);
    Demographic::factory()->for($highRegion)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 20, 'year' => 2025]);

    // low: 20 singles out of 100
    Demographic::factory()->for($lowRegion)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 20, 'year' => 2025]);
    Demographic::factory()->for($lowRegion)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 80, 'year' => 2025]);

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    expect($result->first()->region_id)->toBe($highRegion->id)
        ->and($result->last()->region_id)->toBe($lowRegion->id);
});

it('persists computed scores to score_cache', function (): void {
    $region = Region::factory()->create();

    Demographic::factory()->for($region)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
    Demographic::factory()->for($region)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);

    app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    expect(ScoreCache::count())->toBe(1);
    $cached = ScoreCache::first();
    expect($cached->sex)->toBe(Sex::Female)
        ->and($cached->age_min)->toBe(25)
        ->and($cached->age_max)->toBe(34);
});

it('upserts rather than duplicating when called twice for the same filters', function (): void {
    $region = Region::factory()->create();

    Demographic::factory()->for($region)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
    Demographic::factory()->for($region)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);

    $service = app(SingleScoreService::class);
    $service->computeTopRegions(Sex::Female, 25, 34);
    $service->computeTopRegions(Sex::Female, 25, 34);

    expect(ScoreCache::count())->toBe(1);
});

it('only queries demographics matching the requested sex', function (): void {
    $femaleRegion = Region::factory()->create();
    $maleRegion = Region::factory()->create();

    Demographic::factory()->for($femaleRegion)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
    Demographic::factory()->for($femaleRegion)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);

    Demographic::factory()->for($maleRegion)->singlePerson()->forSex(Sex::Male)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
    Demographic::factory()->for($maleRegion)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Male)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34);

    expect($result)->toHaveCount(1)
        ->and($result->first()->region_id)->toBe($femaleRegion->id);
});

it('getTopRegions reads from cache without touching demographics', function (): void {
    $region = Region::factory()->create();
    ScoreCache::factory()->for($region)->create([
        'sex' => Sex::Female,
        'age_min' => 25,
        'age_max' => 34,
        'score' => 77.5,
    ]);

    // No demographics seeded — if the service recomputed it would return empty
    $result = app(SingleScoreService::class)->getTopRegions(Sex::Female, 25, 34);

    expect($result)->toHaveCount(1)
        ->and($result->first()->score)->toBe(77.5);
});

it('respects the limit parameter', function (): void {
    $regions = Region::factory()->count(5)->create();

    foreach ($regions as $region) {
        Demographic::factory()->for($region)->singlePerson()->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
        Demographic::factory()->for($region)->state(['household_status' => HouseholdStatus::Cohabiting])->forSex(Sex::Female)->forAgeRange(25, 29)->create(['count' => 50, 'year' => 2025]);
    }

    $result = app(SingleScoreService::class)->computeTopRegions(Sex::Female, 25, 34, limit: 3);

    expect($result)->toHaveCount(3);
});
