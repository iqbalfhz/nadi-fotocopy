<?php

use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\EnsureManagesStore;
use Illuminate\Support\Facades\Route;

// Website publik — tanpa akun, tanpa keranjang (§4.1).
Route::livewire('/', 'pages::public.beranda')->name('home');
Route::livewire('katalog', 'pages::public.katalog')->name('katalog');
Route::livewire('tentang', 'pages::public.tentang')->name('tentang');
Route::livewire('kontak', 'pages::public.kontak')->name('kontak');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Layar POS wajib di belakang auth (§7.1).
Route::middleware(['auth', 'verified'])->prefix('pos')->name('pos.')->group(function () {
    Route::livewire('kasir', 'pages::pos.kasir')->name('kasir');
    Route::livewire('riwayat', 'pages::pos.riwayat')->name('riwayat');
    Route::get('struk/{transaction}', [ReceiptController::class, 'show'])->name('struk');
});

// Panel Admin hanya untuk owner (§7.1).
Route::middleware(['auth', 'verified', EnsureManagesStore::class])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('produk', 'pages::admin.produk')->name('produk');
    Route::livewire('kategori', 'pages::admin.kategori')->name('kategori');
    Route::livewire('pengaturan', 'pages::admin.pengaturan')->name('pengaturan');
    Route::livewire('laporan', 'pages::admin.laporan')->name('laporan');
});

require __DIR__.'/settings.php';
