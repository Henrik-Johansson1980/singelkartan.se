<?php

declare(strict_types=1);

return [
    'scb_base_url' => env('SCB_BASE_URL', 'https://api.scb.se/OV0104/v2beta/api/v2'),

    'min_count_threshold' => (int) env('SINGELKARTAN_MIN_COUNT', 20),

    'shrinkage_k' => (int) env('SINGELKARTAN_SHRINKAGE_K', 50),

    'sync_year' => (int) env('SINGELKARTAN_SYNC_YEAR', 2025),

    'age_min' => (int) env('SINGELKARTAN_AGE_MIN', 20),

    'age_max' => (int) env('SINGELKARTAN_AGE_MAX', 69),

    'premium_mode' => (bool) env('SINGELKARTAN_PREMIUM_MODE', false),

    // Bearer token required when premium_mode is true
    'premium_token' => env('SINGELKARTAN_PREMIUM_TOKEN'),
];
