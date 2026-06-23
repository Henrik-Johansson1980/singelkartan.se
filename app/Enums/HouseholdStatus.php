<?php

declare(strict_types=1);

namespace App\Enums;

enum HouseholdStatus: string
{
    case SinglePerson = 'single_person';
    case Cohabiting = 'cohabiting';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            HouseholdStatus::SinglePerson => 'Ensamboende',
            HouseholdStatus::Cohabiting => 'Samboende',
            HouseholdStatus::Other => 'Övriga',
        };
    }

    /** SCB code in HushallT08CKM: "hushållsställning" dimension */
    public function scbCode(): string
    {
        return match ($this) {
            HouseholdStatus::SinglePerson => 'EP',
            HouseholdStatus::Cohabiting => 'SB',
            HouseholdStatus::Other => 'OV',
        };
    }
}
