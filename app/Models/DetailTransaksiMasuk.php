<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksiMasuk extends Model
{
    use HasFactory;

    protected $fillable = ['transaksi_masuk_id', 'barang_id', 'jumlah', 'harga_beli', 'diskon_persen', 'diskon_nominal', 'subtotal', 'tanggal_expired', 'no_batch'];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_beli' => 'decimal:2',
        'diskon_persen' => 'decimal:2',
        'diskon_nominal' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tanggal_expired' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($detail) {
            $detail->hitungSubtotal();
        });

        static::updating(function ($detail) {
            $detail->hitungSubtotal();
        });

        static::created(function ($detail) {
            // Update stok barang
            $detail->barang->updateStok($detail->jumlah, 'masuk');

            // Update harga beli barang
            $detail->barang->update(['harga_beli' => $detail->harga_beli]);

            // Update total transaksi
            $detail->transaksiMasuk->hitungTotal();
        });

        static::updated(function ($detail) {
            // Update total transaksi
            $detail->transaksiMasuk->hitungTotal();
        });

        static::deleted(function ($detail) {
            // Kurangi stok barang
            $detail->barang->updateStok($detail->jumlah, 'keluar');

            // Update total transaksi
            $detail->transaksiMasuk->hitungTotal();
        });
    }

    public function transaksiMasuk(): BelongsTo
    {
        return $this->belongsTo(TransaksiMasuk::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    protected function hitungSubtotal(): void
    {
        $total = $this->jumlah * $this->harga_beli;

        if ($this->diskon_persen > 0) {
            $this->diskon_nominal = $total * ($this->diskon_persen / 100);
        }

        $this->subtotal = $total - $this->diskon_nominal;
    }

    public function isExpired(): bool
    {
        return $this->tanggal_expired && $this->tanggal_expired->isPast();
    }

    public function isNearExpired(int $days = 30): bool
    {
        if (!$this->tanggal_expired) {
            return false;
        }

        return $this->tanggal_expired->diffInDays(now()) <= $days && !$this->isExpired();
    }
}
