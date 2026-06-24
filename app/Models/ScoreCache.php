<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Sex;
use Database\Factories\ScoreCacheFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoreCache extends Model
{
    /** @use HasFactory<ScoreCacheFactory> */
    use HasFactory;

    protected $table = 'score_cache';

    protected $fillable = [
        'region_id',
        'sex',
        'age_min',
        'age_max',
        'single_ratio',
        'single_count',
        'total_count',
        'score',
        'computed_at',
    ];

    /** @return array<string, mixed> */
    #[\Override]
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'age_min' => 'integer',
            'age_max' => 'integer',
            'single_ratio' => 'float',
            'single_count' => 'integer',
            'total_count' => 'integer',
            'score' => 'float',
            'computed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function singlePercentage(): float
    {
        if ($this->total_count === 0) {
            return 0.0;
        }

        return round(($this->single_count / $this->total_count) * 100, 1);
    }
}
