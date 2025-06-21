<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksiKeluar extends Model
{
    use HasFactory;

    protected $fillable = ['transaksi_keluar_id', 'barang_id', 'jumlah', 'harga_jual', 'harga_beli', 'diskon_persen', 'diskon_nominal', 'subtotal'];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_jual' => 'decimal:2',
        'harga_beli' => 'decimal:2',
        'diskon_persen' => 'decimal:2',
        'diskon_nominal' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($detail) {
            // Set harga beli dari barang
            if (!$detail->harga_beli) {
                $detail->harga_beli = $detail->barang->harga_beli;
            }

            $detail->hitungSubtotal();
        });

        static::updating(function ($detail) {
            $detail->hitungSubtotal();
        });

        static::created(function ($detail) {
            // Update stok barang
            $detail->barang->updateStok($detail->jumlah, 'keluar');

            // Update total transaksi
            $detail->transaksiKeluar->hitungTotal();
        });

        static::updated(function ($detail) {
            // Kembalikan stok lama
            $oldJumlah = $detail->getOriginal('jumlah');
            $detail->barang->updateStok($oldJumlah, 'masuk');

            // Kurangi stok baru
            $detail->barang->updateStok($detail->jumlah, 'keluar');

            // Update total transaksi
            $detail->transaksiKeluar->hitungTotal();
        });

        static::deleted(function ($detail) {
            // Kembalikan stok barang
            $detail->barang->updateStok($detail->jumlah, 'masuk');

            // Update total transaksi
            $detail->transaksiKeluar->hitungTotal();
        });
    }

    public function transaksiKeluar(): BelongsTo
    {
        return $this->belongsTo(TransaksiKeluar::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    protected function hitungSubtotal(): void
    {
        $total = $this->jumlah * $this->harga_jual;

        if ($this->diskon_persen > 0) {
            $this->diskon_nominal = $total * ($this->diskon_persen / 100);
        }

        $this->subtotal = $total - $this->diskon_nominal;
    }

    public function getLabaAttribute(): float
    {
        return ($this->harga_jual - $this->harga_beli) * $this->jumlah;
    }

    public function getMarginAttribute(): float
    {
        if ($this->harga_beli == 0) {
            return 0;
        }

        return round((($this->harga_jual - $this->harga_beli) / $this->harga_beli) * 100, 2);
    }
}
