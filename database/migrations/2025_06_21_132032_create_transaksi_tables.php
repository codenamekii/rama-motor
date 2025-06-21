<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel Transaksi Masuk (Pembelian dari Pemasok)
        Schema::create('transaksi_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi', 50)->unique();
            $table->date('tanggal_transaksi');
            $table->foreignId('pemasok_id')->constrained('pemasoks');
            $table->string('no_faktur_supplier')->nullable();
            $table->enum('jenis_pembayaran', ['Cash', 'Transfer', 'Kredit'])->default('Cash');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->enum('status_pembayaran', ['Lunas', 'Belum Lunas', 'Sebagian'])->default('Belum Lunas');
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('ppn_persen', 5, 2)->default(0);
            $table->decimal('ppn_nominal', 15, 2)->default(0);
            $table->decimal('biaya_lain', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->decimal('jumlah_dibayar', 15, 2)->default(0);
            $table->decimal('sisa_hutang', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->constrained('users'); // Yang input
            $table->timestamps();

            // Indexes
            $table->index('no_transaksi');
            $table->index('tanggal_transaksi');
            $table->index('pemasok_id');
            $table->index('status_pembayaran');
        });

        // Tabel Detail Transaksi Masuk
        Schema::create('detail_transaksi_masuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_masuk_id')->constrained('transaksi_masuks')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs');
            $table->integer('jumlah');
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->date('tanggal_expired')->nullable();
            $table->string('no_batch')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('transaksi_masuk_id');
            $table->index('barang_id');
        });

        // Tabel Transaksi Keluar (Penjualan ke Pelanggan)
        Schema::create('transaksi_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi', 50)->unique();
            $table->date('tanggal_transaksi');
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggans');
            $table->string('nama_pelanggan')->nullable(); // Untuk pelanggan umum
            $table->enum('jenis_pembayaran', ['Cash', 'Transfer', 'Kredit', 'Debit'])->default('Cash');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->enum('status_pembayaran', ['Lunas', 'Belum Lunas', 'Sebagian'])->default('Lunas');
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('ppn_persen', 5, 2)->default(0);
            $table->decimal('ppn_nominal', 15, 2)->default(0);
            $table->decimal('biaya_lain', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->decimal('jumlah_dibayar', 15, 2)->default(0);
            $table->decimal('kembalian', 15, 2)->default(0);
            $table->decimal('sisa_piutang', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->constrained('users'); // Kasir/Yang input
            $table->timestamps();

            // Indexes
            $table->index('no_transaksi');
            $table->index('tanggal_transaksi');
            $table->index('pelanggan_id');
            $table->index('status_pembayaran');
        });

        // Tabel Detail Transaksi Keluar
        Schema::create('detail_transaksi_keluars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_keluar_id')->constrained('transaksi_keluars')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs');
            $table->integer('jumlah');
            $table->decimal('harga_jual', 15, 2);
            $table->decimal('harga_beli', 15, 2); // Untuk hitung laba
            $table->decimal('diskon_persen', 5, 2)->default(0);
            $table->decimal('diskon_nominal', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            // Indexes
            $table->index('transaksi_keluar_id');
            $table->index('barang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi_keluars');
        Schema::dropIfExists('transaksi_keluars');
        Schema::dropIfExists('detail_transaksi_masuks');
        Schema::dropIfExists('transaksi_masuks');
    }
};
