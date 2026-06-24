<?php

declare(strict_types=1);

namespace App\Data\Scb;

/** One DeSO area parsed from TAB6568 (no sex/age breakdown). */
readonly class DesoObservation
{
    public function __construct(
        public string $desoCode,
        public string $kommunCode,
        public int $singleCount,
        public int $totalCount,
    ) {}
}
