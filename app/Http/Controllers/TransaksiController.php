<?php
// File: app/Http/Controllers/TransaksiController.php

namespace App\Http\Controllers;

use App\Models\TransaksiMasuk;
use App\Models\TransaksiKeluar;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransaksiController extends Controller
{
    public function printTransaksiMasuk(TransaksiMasuk $transaksiMasuk)
    {
        $transaksiMasuk->load(['pemasok', 'details.barang.satuanBarang', 'user']);

        $pdf = Pdf::loadView('pdf.transaksi-masuk', compact('transaksiMasuk'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Transaksi-Masuk-' . $transaksiMasuk->no_transaksi . '.pdf');
    }

    public function printTransaksiKeluar(TransaksiKeluar $transaksiKeluar)
    {
        $transaksiKeluar->load(['pelanggan', 'details.barang.satuanBarang', 'user']);

        $pdf = Pdf::loadView('pdf.transaksi-keluar', compact('transaksiKeluar'));
        $pdf->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm x 200mm for receipt

        return $pdf->stream('Struk-' . $transaksiKeluar->no_transaksi . '.pdf');
    }
}
