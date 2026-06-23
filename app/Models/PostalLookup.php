<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PostalLookupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostalLookup extends Model
{
    /** @use HasFactory<PostalLookupFactory> */
    use HasFactory;

    protected $fillable = [
        'postal_code',
        'region_code',
    ];
}
