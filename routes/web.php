<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\NeedsController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('needs', NeedsController::class)
        ->except(['create', 'show', 'edit'])
        ->parameter('needs', 'need');
    Route::patch('needs/{need}/status', [NeedsController::class, 'updateStatus'])->name('needs.status');
});

require __DIR__.'/settings.php';
