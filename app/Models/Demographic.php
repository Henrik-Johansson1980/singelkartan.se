<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HouseholdStatus;
use App\Enums\Sex;
use Database\Factories\DemographicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demographic extends Model
{
    /** @use HasFactory<DemographicFactory> */
    use HasFactory;

    protected $fillable = [
        'region_id',
        'year',
        'sex',
        'age_min',
        'age_max',
        'household_status',
        'count',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'household_status' => HouseholdStatus::class,
            'year' => 'integer',
            'age_min' => 'integer',
            'age_max' => 'integer',
            'count' => 'integer',
        ];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
