<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Livewire\Leaderboard;
use App\Models\Region;
use App\Models\ScoreCache;
use Livewire\Livewire;

it('renders successfully with default props', function (): void {
    Livewire::test(Leaderboard::class)
        ->assertStatus(200);
});

it('shows the empty state when no score cache exists', function (): void {
    Livewire::test(Leaderboard::class)
        ->assertSee('Inga data hittades');
});

it('displays ranked regions from the score cache', function (): void {
    $region = Region::factory()->create(['name' => 'Testköping']);
    ScoreCache::factory()->for($region)->create([
        'sex' => Sex::Female,
        'age_min' => 25,
        'age_max' => 34,
        'score' => 65.0,
        'single_count' => 300,
        'total_count' => 500,
    ]);

    Livewire::test(Leaderboard::class)
        ->assertSee('Testköping')
        ->assertSee('300')
        ->assertSee('500');
});

it('updates displayed regions when target-changed event is dispatched', function (): void {
    $femaleRegion = Region::factory()->create(['name' => 'Tjejstad']);
    ScoreCache::factory()->for($femaleRegion)->create([
        'sex' => Sex::Female,
        'age_min' => 25,
        'age_max' => 34,
        'score' => 70.0,
    ]);

    $maleRegion = Region::factory()->create(['name' => 'Killetown']);
    ScoreCache::factory()->for($maleRegion)->create([
        'sex' => Sex::Male,
        'age_min' => 35,
        'age_max' => 44,
        'score' => 65.0,
    ]);

    Livewire::test(Leaderboard::class)
        ->assertSee('Tjejstad')
        ->assertDontSee('Killetown')
        ->dispatch('target-changed', sex: 'male', ageMin: 35, ageMax: 44)
        ->assertDontSee('Tjejstad')
        ->assertSee('Killetown');
});

it('reflects updated sex and age props after target-changed', function (): void {
    Livewire::test(Leaderboard::class)
        ->assertSet('sex', 'female')
        ->assertSet('ageMin', 25)
        ->assertSet('ageMax', 34)
        ->dispatch('target-changed', sex: 'male', ageMin: 30, ageMax: 39)
        ->assertSet('sex', 'male')
        ->assertSet('ageMin', 30)
        ->assertSet('ageMax', 39);
});

it('shows the sex label and age range in the header', function (): void {
    $region = Region::factory()->create();
    ScoreCache::factory()->for($region)->create([
        'sex' => Sex::Female,
        'age_min' => 25,
        'age_max' => 34,
        'score' => 50.0,
    ]);

    Livewire::test(Leaderboard::class)
        ->assertSee('25')
        ->assertSee('34');
});

it('limits the leaderboard to at most 10 entries', function (): void {
    $regions = Region::factory()->count(15)->create();

    foreach ($regions as $index => $region) {
        ScoreCache::factory()->for($region)->create([
            'sex' => Sex::Female,
            'age_min' => 25,
            'age_max' => 34,
            'score' => 50.0 + $index,
        ]);
    }

    Livewire::test(Leaderboard::class)
        ->assertViewHas('topRegions', fn ($regions): bool => $regions->count() === 10);
});

it('falls back to computing scores when cache is empty', function (): void {
    Livewire::test(Leaderboard::class)
        ->assertStatus(200)
        ->assertViewHas('topRegions', fn ($regions): bool => $regions->isEmpty());
});
