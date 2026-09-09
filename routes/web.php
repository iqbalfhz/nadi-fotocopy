<?php

use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Layar POS wajib di belakang auth (§7.1).
Route::middleware(['auth', 'verified'])->prefix('pos')->name('pos.')->group(function () {
    Route::livewire('kasir', 'pages::pos.kasir')->name('kasir');
    Route::livewire('riwayat', 'pages::pos.riwayat')->name('riwayat');
    Route::get('struk/{transaction}', [ReceiptController::class, 'show'])->name('struk');
});

require __DIR__.'/settings.php';
