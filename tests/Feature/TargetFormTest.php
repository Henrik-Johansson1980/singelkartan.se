<?php

declare(strict_types=1);

use App\Livewire\TargetForm;
use Livewire\Livewire;

beforeEach(function (): void {
    config([
        'singelkartan.age_min' => 18,
        'singelkartan.age_max' => 80,
    ]);
});

it('renders successfully', function (): void {
    Livewire::test(TargetForm::class)
        ->assertStatus(200);
});

it('initialises age bounds from config', function (): void {
    Livewire::test(TargetForm::class)
        ->assertSet('ageAbsMin', 18)
        ->assertSet('ageAbsMax', 80);
});

it('has female sex and default age range on mount', function (): void {
    Livewire::test(TargetForm::class)
        ->assertSet('sex', 'female')
        ->assertSet('ageMin', 25)
        ->assertSet('ageMax', 34);
});

it('dispatches target-changed when sex is updated', function (): void {
    Livewire::test(TargetForm::class)
        ->set('sex', 'male')
        ->assertDispatched('target-changed', sex: 'male', ageMin: 25, ageMax: 34);
});

it('dispatches target-changed when ageMin is updated', function (): void {
    Livewire::test(TargetForm::class)
        ->set('ageMin', 30)
        ->assertDispatched('target-changed', sex: 'female', ageMin: 30, ageMax: 34);
});

it('dispatches target-changed when ageMax is updated', function (): void {
    Livewire::test(TargetForm::class)
        ->set('ageMax', 45)
        ->assertDispatched('target-changed', sex: 'female', ageMin: 25, ageMax: 45);
});

it('clamps ageMin below ageMax when they would cross', function (): void {
    Livewire::test(TargetForm::class)
        ->set('ageMin', 34) // equal to ageMax
        ->assertSet('ageMin', 33); // clamped to ageMax - 1
});

it('clamps ageMax above ageMin when they would cross', function (): void {
    // Default ageMin=25; setting ageMax=25 (equal) must clamp to 26
    Livewire::test(TargetForm::class)
        ->set('ageMax', 25)
        ->assertSet('ageMax', 26);
});

it('shows all three sex options', function (): void {
    Livewire::test(TargetForm::class)
        ->assertSee('Kvinnor')
        ->assertSee('Män')
        ->assertSee('Alla');
});

it('has the expected default age range', function (): void {
    Livewire::test(TargetForm::class)
        ->assertSet('ageMin', 25)
        ->assertSet('ageMax', 34);
});

it('shows the absolute age bounds in the track labels', function (): void {
    Livewire::test(TargetForm::class)
        ->assertSeeInOrder(['18', '80']);
});
