<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegionLevel;
use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'level',
        'lat',
        'lng',
        'geojson',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'level' => RegionLevel::class,
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    /** @return HasMany<Demographic, $this> */
    public function demographics(): HasMany
    {
        return $this->hasMany(Demographic::class);
    }

    /** @return HasMany<ScoreCache, $this> */
    public function scoreCache(): HasMany
    {
        return $this->hasMany(ScoreCache::class);
    }
}
