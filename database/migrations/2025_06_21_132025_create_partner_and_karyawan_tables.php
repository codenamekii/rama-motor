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
        // Tabel Pelanggan
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->date('tanggal_bergabung')->default(now());
            $table->decimal('total_pembelian', 15, 2)->default(0);
            $table->integer('jumlah_transaksi')->default(0);
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('kode');
            $table->index('nama');
            $table->index('telepon');
        });

        // Tabel Pemasok
        Schema::create('pemasoks', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama_perusahaan');
            $table->string('nama_kontak')->nullable();
            $table->string('jabatan_kontak')->nullable();
            $table->string('telepon', 20);
            $table->string('telepon_2', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('alamat');
            $table->string('kota', 100);
            $table->string('provinsi', 100);
            $table->string('kode_pos', 10)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('atas_nama_rekening')->nullable();
            $table->decimal('total_pembelian', 15, 2)->default(0);
            $table->integer('jumlah_transaksi')->default(0);
            $table->text('catatan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('kode');
            $table->index('nama_perusahaan');
        });

        // Tabel Karyawan
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat');
            $table->string('telepon', 20);
            $table->string('email')->nullable();
            $table->enum('status_pernikahan', ['Belum Menikah', 'Menikah', 'Duda', 'Janda'])->default('Belum Menikah');
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('jabatan');
            $table->string('departemen')->nullable();
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->enum('status_karyawan', ['Aktif', 'Non-Aktif', 'Resign', 'Pensiun'])->default('Aktif');
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->string('no_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('foto')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('nik');
            $table->index('nama');
            $table->index('status_karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawans');
        Schema::dropIfExists('pemasoks');
        Schema::dropIfExists('pelanggans');
    }
};
