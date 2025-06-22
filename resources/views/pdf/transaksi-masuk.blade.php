<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Transaksi Masuk - {{ $transaksiMasuk->no_transaksi }}</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
    }

    .header {
      text-align: center;
      margin-bottom: 20px;
    }

    .info-table {
      width: 100%;
      margin-bottom: 20px;
    }

    .info-table td {
      padding: 5px;
    }

    .detail-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .detail-table th,
    .detail-table td {
      border: 1px solid #ddd;
      padding: 8px;
    }

    .detail-table th {
      background-color: #f4f4f4;
    }

    .text-right {
      text-align: right;
    }

    .text-center {
      text-align: center;
    }

    .total-section {
      margin-top: 20px;
    }

    .footer {
      margin-top: 50px;
    }
  </style>
</head>

<body>
  <div class="header">
    <h2>RAMA MOTOR</h2>
    <p>Jl. Rama Motor No. 123<br>Telp: (021) 1234567</p>
    <hr>
    <h3>TRANSAKSI MASUK</h3>
  </div>

  <table class="info-table">
    <tr>
      <td width="50%">
        <strong>No. Transaksi:</strong> {{ $transaksiMasuk->no_transaksi }}<br>
        <strong>Tanggal:</strong> {{ $transaksiMasuk->tanggal_transaksi->format('d/m/Y') }}<br>
        <strong>Pemasok:</strong> {{ $transaksiMasuk->pemasok->nama_perusahaan }}<br>
        <strong>No. Faktur:</strong> {{ $transaksiMasuk->no_faktur_supplier ?? '-' }}
      </td>
      <td width="50%" class="text-right">
        <strong>Jenis Pembayaran:</strong> {{ $transaksiMasuk->jenis_pembayaran }}<br>
        <strong>Status:</strong> {{ $transaksiMasuk->status_pembayaran }}<br>
        @if ($transaksiMasuk->tanggal_jatuh_tempo)
          <strong>Jatuh Tempo:</strong> {{ $transaksiMasuk->tanggal_jatuh_tempo->format('d/m/Y') }}<br>
        @endif
      </td>
    </tr>
  </table>

  <table class="detail-table">
    <thead>
      <tr>
        <th width="5%">No</th>
        <th width="35%">Nama Barang</th>
        <th width="10%">Jumlah</th>
        <th width="10%">Satuan</th>
        <th width="15%">Harga</th>
        <th width="10%">Diskon</th>
        <th width="15%">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($transaksiMasuk->details as $index => $detail)
        <tr>
          <td class="text-center">{{ $index + 1 }}</td>
          <td>{{ $detail->barang->nama }}</td>
          <td class="text-center">{{ $detail->jumlah }}</td>
          <td class="text-center">{{ $detail->barang->satuanBarang->singkatan }}</td>
          <td class="text-right">{{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
          <td class="text-center">{{ $detail->diskon_persen }}%</td>
          <td class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="total-section">
    <table style="width: 40%; float: right;">
      <tr>
        <td>Total Harga:</td>
        <td class="text-right">Rp {{ number_format($transaksiMasuk->total_harga, 0, ',', '.') }}</td>
      </tr>
      @if ($transaksiMasuk->diskon_nominal > 0)
        <tr>
          <td>Diskon ({{ $transaksiMasuk->diskon_persen }}%):</td>
          <td class="text-right">Rp {{ number_format($transaksiMasuk->diskon_nominal, 0, ',', '.') }}</td>
        </tr>
      @endif
      @if ($transaksiMasuk->ppn_nominal > 0)
        <tr>
          <td>PPN ({{ $transaksiMasuk->ppn_persen }}%):</td>
          <td class="text-right">Rp {{ number_format($transaksiMasuk->ppn_nominal, 0, ',', '.') }}</td>
        </tr>
      @endif
      @if ($transaksiMasuk->biaya_lain > 0)
        <tr>
          <td>Biaya Lain:</td>
          <td class="text-right">Rp {{ number_format($transaksiMasuk->biaya_lain, 0, ',', '.') }}</td>
        </tr>
      @endif
      <tr style="font-weight: bold; font-size: 14px;">
        <td>Total Bayar:</td>
        <td class="text-right">Rp {{ number_format($transaksiMasuk->total_bayar, 0, ',', '.') }}</td>
      </tr>
      @if ($transaksiMasuk->status_pembayaran !== 'Lunas')
        <tr>
          <td>Sudah Dibayar:</td>
          <td class="text-right">Rp {{ number_format($transaksiMasuk->jumlah_dibayar, 0, ',', '.') }}</td>
        </tr>
        <tr style="color: red;">
          <td>Sisa Hutang:</td>
          <td class="text-right">Rp {{ number_format($transaksiMasuk->sisa_hutang, 0, ',', '.') }}</td>
        </tr>
      @endif
    </table>
    <div style="clear: both;"></div>
  </div>

  @if ($transaksiMasuk->keterangan)
    <div style="margin-top: 20px;">
      <strong>Keterangan:</strong><br>
      {{ $transaksiMasuk->keterangan }}
    </div>
  @endif

  <div class="footer">
    <table style="width: 100%;">
      <tr>
        <td width="50%" class="text-center">
          <br><br><br><br>
          (_________________)<br>
          Pemasok
        </td>
        <td width="50%" class="text-center">
          {{ now()->format('d/m/Y') }}<br><br><br><br>
          (_________________)<br>
          {{ $transaksiMasuk->user->name }}
        </td>
      </tr>
    </table>
  </div>
</body>

</html>
