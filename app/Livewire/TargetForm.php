<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\Sex;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TargetForm extends Component
{
    public string $sex = 'female';

    public int $ageMin = 25;

    public int $ageMax = 34;

    public function updatedSex(): void
    {
        $this->dispatch('target-changed', sex: $this->sex, ageMin: $this->ageMin, ageMax: $this->ageMax);
    }

    public function updatedAgeMin(): void
    {
        if ($this->ageMin >= $this->ageMax) {
            $this->ageMin = max(18, $this->ageMax - 1);
        }

        $this->dispatch('target-changed', sex: $this->sex, ageMin: $this->ageMin, ageMax: $this->ageMax);
    }

    public function updatedAgeMax(): void
    {
        if ($this->ageMax <= $this->ageMin) {
            $this->ageMax = min(80, $this->ageMin + 1);
        }

        $this->dispatch('target-changed', sex: $this->sex, ageMin: $this->ageMin, ageMax: $this->ageMax);
    }

    #[Computed]
    public function sexOptions(): array
    {
        return [
            ['value' => 'female', 'label' => 'Kvinnor', 'emoji' => '👩'],
            ['value' => 'male', 'label' => 'Män', 'emoji' => '👨'],
            ['value' => 'all', 'label' => 'Alla', 'emoji' => '🧑'],
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.target-form');
    }
}
