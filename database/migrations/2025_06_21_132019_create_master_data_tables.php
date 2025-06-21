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
        // Tabel Jenis Barang
        Schema::create('jenis_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel Satuan Barang
        Schema::create('satuan_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama', 50);
            $table->string('singkatan', 10);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel Merk Barang
        Schema::create('merk_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->string('negara_asal')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel Barang
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 100)->unique();
            $table->string('nama');
            $table->foreignId('jenis_barang_id')->constrained('jenis_barangs');
            $table->foreignId('satuan_barang_id')->constrained('satuan_barangs');
            $table->foreignId('merk_barang_id')->constrained('merk_barangs');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->integer('stok_minimal')->default(0);
            $table->string('lokasi_penyimpanan')->nullable();
            $table->string('gambar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('kode');
            $table->index('nama');
            $table->index('jenis_barang_id');
            $table->index('merk_barang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');
        Schema::dropIfExists('merk_barangs');
        Schema::dropIfExists('satuan_barangs');
        Schema::dropIfExists('jenis_barangs');
    }
};
