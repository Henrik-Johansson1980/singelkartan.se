<?php

declare(strict_types=1);

use App\Http\Controllers\OgImageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/og-image', OgImageController::class)->name('og-image');
