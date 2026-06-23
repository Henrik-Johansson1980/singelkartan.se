<?php

declare(strict_types=1);

return [
    'scb_base_url' => env('SCB_BASE_URL', 'https://api.scb.se/OV0104/v2beta/api/v2'),

    'min_count_threshold' => (int) env('SINGELKARTAN_MIN_COUNT', 20),

    'shrinkage_k' => (int) env('SINGELKARTAN_SHRINKAGE_K', 50),

    'sync_year' => (int) env('SINGELKARTAN_SYNC_YEAR', 2025),
];
