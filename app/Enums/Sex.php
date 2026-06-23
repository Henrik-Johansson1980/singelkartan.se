<?php

declare(strict_types=1);

namespace App\Enums;

enum Sex: string
{
    case Male = 'male';
    case Female = 'female';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            Sex::Male => 'Män',
            Sex::Female => 'Kvinnor',
            Sex::All => 'Alla',
        };
    }

    public function scbCode(): string
    {
        return match ($this) {
            Sex::Male => '1',
            Sex::Female => '2',
            Sex::All => 'TOT',
        };
    }
}
