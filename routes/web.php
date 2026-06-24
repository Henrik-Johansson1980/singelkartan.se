<?php

declare(strict_types=1);

use App\Http\Controllers\OgImageController;
use App\Http\Middleware\RequiresPremium;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/og-image', OgImageController::class)->name('og-image');

// Premium / white-label stub — token-gated, no payment in MVP
Route::view('/embed', 'layouts.white-label')
    ->middleware(RequiresPremium::class)
    ->name('embed');
