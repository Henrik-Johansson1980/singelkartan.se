<?php

declare(strict_types=1);

use App\Enums\Sex;
use App\Livewire\ResultCard;
use App\Models\Region;
use App\Models\ScoreCache;
use Livewire\Livewire;

it('renders successfully', function (): void {
    Livewire::test(ResultCard::class)
        ->assertStatus(200);
});

it('initialises with default sex and age range', function (): void {
    Livewire::test(ResultCard::class)
        ->assertSet('sex', 'female')
        ->assertSet('ageMin', 25)
        ->assertSet('ageMax', 34);
});

it('shows the top result region name and percentage', function (): void {
    $region = Region::factory()->create(['name' => 'Delningsköping']);
    ScoreCache::factory()->for($region)->create([
        'sex' => Sex::Female,
        'age_min' => 25,
        'age_max' => 34,
        'score' => 80.0,
        'single_count' => 400,
        'total_count' => 500,
    ]);

    Livewire::test(ResultCard::class)
        ->assertSee('Delningsköping')
        ->assertSee('80');
});

it('shows empty state when no results exist', function (): void {
    Livewire::test(ResultCard::class)
        ->assertSee('Inga resultat');
});

it('updates on target-changed event', function (): void {
    $maleRegion = Region::factory()->create(['name' => 'Killeby']);
    ScoreCache::factory()->for($maleRegion)->create([
        'sex' => Sex::Male,
        'age_min' => 35,
        'age_max' => 44,
        'score' => 75.0,
    ]);

    Livewire::test(ResultCard::class)
        ->dispatch('target-changed', sex: 'male', ageMin: 35, ageMax: 44)
        ->assertSet('sex', 'male')
        ->assertSet('ageMin', 35)
        ->assertSet('ageMax', 44)
        ->assertSee('Killeby');
});

it('initialises from url query parameters', function (): void {
    $region = Region::factory()->create(['name' => 'Urlköping']);
    ScoreCache::factory()->for($region)->create([
        'sex' => Sex::Male,
        'age_min' => 30,
        'age_max' => 39,
        'score' => 70.0,
    ]);

    Livewire::withQueryParams(['sex' => 'male', 'ageMin' => 30, 'ageMax' => 39])
        ->test(ResultCard::class)
        ->assertSet('sex', 'male')
        ->assertSet('ageMin', 30)
        ->assertSet('ageMax', 39)
        ->assertSee('Urlköping');
});

it('renders the og-image route successfully', function (): void {
    $region = Region::factory()->create(['name' => 'Bildstad']);
    ScoreCache::factory()->for($region)->create([
        'sex' => Sex::Female,
        'age_min' => 25,
        'age_max' => 34,
        'score' => 60.0,
    ]);

    $this->get(route('og-image', ['sex' => 'female', 'ageMin' => 25, 'ageMax' => 34]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/png');
});

it('renders the og-image route with no data', function (): void {
    $this->get(route('og-image', ['sex' => 'female', 'ageMin' => 25, 'ageMax' => 34]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/png');
});
