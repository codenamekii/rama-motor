<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Struk - {{ $transaksiKeluar->no_transaksi }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Courier New', monospace;
      font-size: 9px;
      line-height: 1.2;
      width: 58mm;
      margin: 0;
      padding: 2mm;
    }

    .header {
      text-align: center;
      margin-bottom: 2mm;
    }

    .header h3 {
      margin: 0;
      font-size: 11px;
      font-weight: bold;
    }

    .header small {
      font-size: 8px;
    }

    .info {
      margin-bottom: 2mm;
      font-size: 8px;
    }

    .items {
      margin-bottom: 2mm;
    }

    .item {
      margin-bottom: 1mm;
      font-size: 8px;
    }

    .item-name {
      font-size: 9px;
    }

    .item-detail {
      display: flex;
      justify-content: space-between;
      margin-left: 2mm;
    }

    .totals {
      margin-bottom: 2mm;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      font-size: 8px;
      margin-bottom: 0.5mm;
    }

    .total-row.grand-total {
      font-size: 10px;
      font-weight: bold;
      margin-top: 1mm;
      padding-top: 1mm;
      border-top: 1px dashed #000;
    }

    .text-right {
      text-align: right;
    }

    .text-center {
      text-align: center;
    }

    .dashed {
      border-bottom: 1px dashed #000;
      margin: 2mm 0;
    }

    .footer {
      text-align: center;
      margin-top: 3mm;
      font-size: 8px;
    }

    table {
      width: 100%;
    }

    /* Pastikan tidak ada overflow */
    .currency {
      white-space: nowrap;
    }
  </style>
</head>

<body>
  <div class="header">
    <h3>RAMA MOTOR</h3>
    <small>
      Jl. Rama Motor No. 123<br>
      Telp: (021) 1234567
    </small>
  </div>

  <div class="dashed"></div>

  <div class="info">
    <div>No: {{ $transaksiKeluar->no_transaksi }}</div>
    <div>Tgl: {{ $transaksiKeluar->tanggal_transaksi->format('d/m/Y H:i') }}</div>
    <div>Kasir: {{ $transaksiKeluar->user->name }}</div>
    @if ($transaksiKeluar->pelanggan)
      <div>Pelanggan: {{ $transaksiKeluar->pelanggan->nama }}</div>
    @endif
  </div>

  <div class="dashed"></div>

  <div class="items">
    @foreach ($transaksiKeluar->details as $detail)
      <div class="item">
        <div class="item-name">{{ Str::limit($detail->barang->nama, 30) }}</div>
        <div class="item-detail">
          <span>{{ $detail->jumlah }} {{ $detail->barang->satuanBarang->singkatan }} x
            {{ number_format($detail->harga_jual, 0, ',', '.') }}</span>
          <span class="currency">{{ number_format($detail->subtotal, 0, ',', '.') }}</span>
        </div>
        @if ($detail->diskon_persen > 0)
          <div style="margin-left: 2mm; font-size: 7px;">
            Disc {{ $detail->diskon_persen }}%
          </div>
        @endif
      </div>
    @endforeach
  </div>

  <div class="dashed"></div>

  <div class="totals">
    <div class="total-row">
      <span>Subtotal:</span>
      <span class="currency">{{ number_format($transaksiKeluar->total_harga, 0, ',', '.') }}</span>
    </div>

    @if ($transaksiKeluar->diskon_nominal > 0)
      <div class="total-row">
        <span>Diskon:</span>
        <span class="currency">{{ number_format($transaksiKeluar->diskon_nominal, 0, ',', '.') }}</span>
      </div>
    @endif

    @if ($transaksiKeluar->ppn_nominal > 0)
      <div class="total-row">
        <span>PPN:</span>
        <span class="currency">{{ number_format($transaksiKeluar->ppn_nominal, 0, ',', '.') }}</span>
      </div>
    @endif

    <div class="total-row grand-total">
      <span>TOTAL:</span>
      <span class="currency">{{ number_format($transaksiKeluar->total_bayar, 0, ',', '.') }}</span>
    </div>

    <div class="total-row">
      <span>Bayar:</span>
      <span class="currency">{{ number_format($transaksiKeluar->jumlah_dibayar, 0, ',', '.') }}</span>
    </div>

    @if ($transaksiKeluar->kembalian > 0)
      <div class="total-row">
        <span>Kembali:</span>
        <span class="currency">{{ number_format($transaksiKeluar->kembalian, 0, ',', '.') }}</span>
      </div>
    @endif
  </div>

  <div class="dashed"></div>

  <div class="footer">
    <strong>*** TERIMA KASIH ***</strong><br>
    Barang yang sudah dibeli<br>
    tidak dapat dikembalikan
  </div>
</body>

</html>
