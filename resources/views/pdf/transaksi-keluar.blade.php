<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Struk - {{ $transaksiKeluar->no_transaksi }}</title>
  <style>
    body {
      font-family: 'Courier New', monospace;
      font-size: 11px;
      width: 80mm;
      margin: 0;
      padding: 5px;
    }

    .header {
      text-align: center;
      margin-bottom: 10px;
    }

    .header h3 {
      margin: 5px 0;
    }

    .info {
      margin-bottom: 10px;
    }

    .detail-table {
      width: 100%;
      margin-bottom: 10px;
    }

    .detail-table td {
      padding: 2px 0;
      vertical-align: top;
    }

    .text-right {
      text-align: right;
    }

    .text-center {
      text-align: center;
    }

    .dashed {
      border-bottom: 1px dashed #000;
      margin: 5px 0;
    }

    .total {
      font-weight: bold;
      font-size: 12px;
    }

    .footer {
      text-align: center;
      margin-top: 20px;
      font-size: 10px;
    }
  </style>
</head>

<body>
  <div class="header">
    <h3>RAMA MOTOR</h3>
    <small>Jl. Rama Motor No. 123<br>Telp: (021) 1234567</small>
  </div>

  <div class="dashed"></div>

  <div class="info">
    No: {{ $transaksiKeluar->no_transaksi }}<br>
    Tgl: {{ $transaksiKeluar->tanggal_transaksi->format('d/m/Y H:i') }}<br>
    Kasir: {{ $transaksiKeluar->user->name }}<br>
    @if ($transaksiKeluar->pelanggan)
      Pelanggan: {{ $transaksiKeluar->pelanggan->nama }}<br>
    @endif
  </div>

  <div class="dashed"></div>

  <table class="detail-table">
    @foreach ($transaksiKeluar->details as $detail)
      <tr>
        <td colspan="3">{{ $detail->barang->nama }}</td>
      </tr>
      <tr>
        <td width="40%">{{ $detail->jumlah }} {{ $detail->barang->satuanBarang->singkatan }} x
          {{ number_format($detail->harga_jual, 0, ',', '.') }}</td>
        <td width="30%">
          @if ($detail->diskon_persen > 0)
            Disc {{ $detail->diskon_persen }}%
          @endif
        </td>
        <td width="30%" class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
      </tr>
    @endforeach
  </table>

  <div class="dashed"></div>

  <table style="width: 100%;">
    <tr>
      <td>Subtotal:</td>
      <td class="text-right">{{ number_format($transaksiKeluar->total_harga, 0, ',', '.') }}</td>
    </tr>
    @if ($transaksiKeluar->diskon_nominal > 0)
      <tr>
        <td>Diskon:</td>
        <td class="text-right">{{ number_format($transaksiKeluar->diskon_nominal, 0, ',', '.') }}</td>
      </tr>
    @endif
    @if ($transaksiKeluar->ppn_nominal > 0)
      <tr>
        <td>PPN:</td>
        <td class="text-right">{{ number_format($transaksiKeluar->ppn_nominal, 0, ',', '.') }}</td>
      </tr>
    @endif
    <tr class="total">
      <td>TOTAL:</td>
      <td class="text-right">{{ number_format($transaksiKeluar->total_bayar, 0, ',', '.') }}</td>
    </tr>
    <tr>
      <td>Bayar:</td>
      <td class="text-right">{{ number_format($transaksiKeluar->jumlah_dibayar, 0, ',', '.') }}</td>
    </tr>
    @if ($transaksiKeluar->kembalian > 0)
      <tr>
        <td>Kembali:</td>
        <td class="text-right">{{ number_format($transaksiKeluar->kembalian, 0, ',', '.') }}</td>
      </tr>
    @endif
  </table>

  <div class="dashed"></div>

  <div class="footer">
    <p>*** TERIMA KASIH ***<br>
      Barang yang sudah dibeli<br>
      tidak dapat dikembalikan</p>
  </div>
</body>

</html>
