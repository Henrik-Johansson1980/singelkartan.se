<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class TargetForm extends Component
{
    #[Url(history: true, except: 'female')]
    public string $sex = 'female';

    #[Url(history: true, except: 25)]
    public int $ageMin = 25;

    #[Url(history: true, except: 34)]
    public int $ageMax = 34;

    public int $ageAbsMin;

    public int $ageAbsMax;

    public function mount(): void
    {
        $this->ageAbsMin = (int) config('singelkartan.age_min');
        $this->ageAbsMax = (int) config('singelkartan.age_max');
    }

    public function updatedSex(): void
    {
        $this->dispatch('target-changed', sex: $this->sex, ageMin: $this->ageMin, ageMax: $this->ageMax);
    }

    public function updatedAgeMin(): void
    {
        if ($this->ageMin >= $this->ageMax) {
            $this->ageMin = max($this->ageAbsMin, $this->ageMax - 1);
        }

        $this->dispatch('target-changed', sex: $this->sex, ageMin: $this->ageMin, ageMax: $this->ageMax);
    }

    public function updatedAgeMax(): void
    {
        if ($this->ageMax <= $this->ageMin) {
            $this->ageMax = min($this->ageAbsMax, $this->ageMin + 1);
        }

        $this->dispatch('target-changed', sex: $this->sex, ageMin: $this->ageMin, ageMax: $this->ageMax);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function sexOptions(): array
    {
        return [
            ['value' => 'female', 'label' => 'Kvinnor'],
            ['value' => 'male', 'label' => 'Män'],
            ['value' => 'all', 'label' => 'Alla'],
        ];
    }

    public function render(): View
    {
        return view('livewire.target-form');
    }
}
