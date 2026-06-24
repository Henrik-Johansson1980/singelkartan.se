<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class TargetForm extends Component
{
    #[Url(history: true, except: 'kommun')]
    public string $level = 'kommun';

    #[Url(history: true, except: 'female')]
    public string $sex = 'female';

    #[Url(history: true, except: 25)]
    public int $ageMin = 25;

    #[Url(history: true, except: 35)]
    public int $ageMax = 35;

    public int $ageAbsMin;

    public int $ageAbsMax;

    public function mount(): void
    {
        $this->ageAbsMin = (int) config('singelkartan.age_min');
        $this->ageAbsMax = (int) config('singelkartan.age_max');
    }

    public function setLevel(string $level): void
    {
        $this->level = $level;
        $this->dispatchTargetChanged();
    }

    public function updatedSex(): void
    {
        $this->dispatchTargetChanged();
    }

    public function updatedAgeMin(): void
    {
        if ($this->ageMin >= $this->ageMax) {
            $this->ageMin = max($this->ageAbsMin, $this->ageMax - 1);
        }

        $this->dispatchTargetChanged();
    }

    public function updatedAgeMax(): void
    {
        if ($this->ageMax <= $this->ageMin) {
            $this->ageMax = min($this->ageAbsMax, $this->ageMin + 1);
        }

        $this->dispatchTargetChanged();
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

    private function dispatchTargetChanged(): void
    {
        $this->dispatch(
            'target-changed',
            level: $this->level,
            sex: $this->sex,
            ageMin: $this->ageMin,
            ageMax: $this->ageMax,
        );
    }
}
