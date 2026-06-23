<?php

declare(strict_types=1);

namespace App\Enums;

enum RegionLevel: string
{
    case Kommun = 'kommun';
    case Deso = 'deso';

    public function label(): string
    {
        return match ($this) {
            RegionLevel::Kommun => 'Kommun',
            RegionLevel::Deso => 'DeSO',
        };
    }
}
