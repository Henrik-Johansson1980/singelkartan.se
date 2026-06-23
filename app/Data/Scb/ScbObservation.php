<?php

declare(strict_types=1);

namespace App\Data\Scb;

use App\Enums\HouseholdStatus;
use App\Enums\Sex;

/** One normalized observation row parsed from a JSON-stat2 dataset. */
readonly class ScbObservation
{
    public function __construct(
        public string $regionCode,
        public int $year,
        public Sex $sex,
        public int $ageMin,
        public int $ageMax,
        public HouseholdStatus $householdStatus,
        public int $count,
    ) {}
}
