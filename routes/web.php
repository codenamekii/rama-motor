<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransaksiController;

Route::get('/', function () {
    return view('welcome');
});

// Routes untuk print transaksi
Route::middleware(['auth'])->group(function () {
  // Print Transaksi Masuk
  Route::get('/transaksi-masuk/{transaksiMasuk}/print', [TransaksiController::class, 'printTransaksiMasuk'])
    ->name('transaksi-masuk.print');

  // Print Transaksi Keluar (Struk)
  Route::get('/transaksi-keluar/{transaksiKeluar}/print', [TransaksiController::class, 'printTransaksiKeluar'])
    ->name('transaksi-keluar.print');

  // Export Excel Transaksi Masuk
  Route::post('/transaksi-masuk/export', [TransaksiController::class, 'exportTransaksiMasuk'])
    ->name('transaksi-masuk.export');

  // Export Excel Transaksi Keluar
  Route::post('/transaksi-keluar/export', [TransaksiController::class, 'exportTransaksiKeluar'])
    ->name('transaksi-keluar.export');
});
