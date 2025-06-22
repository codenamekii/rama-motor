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

        // Ukuran kertas thermal printer 80mm (58mm printable area)
        // 58mm = 164.4 points, with height auto
        $pdf->setPaper([0, 0, 164.4, 500], 'portrait');

        // Set margin kecil untuk thermal printer
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);

        return $pdf->stream('Struk-' . $transaksiKeluar->no_transaksi . '.pdf');
    }
}
