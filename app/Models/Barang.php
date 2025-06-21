<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Barang extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'jenis_barang_id', 'satuan_barang_id', 'merk_barang_id', 'deskripsi', 'harga_beli', 'harga_jual', 'stok', 'stok_minimal', 'lokasi_penyimpanan', 'gambar', 'is_active'];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'stok' => 'integer',
        'stok_minimal' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($barang) {
            if (empty($barang->kode)) {
                $barang->kode = self::generateKode();
            }
        });
    }

    /**
     * Generate kode barang otomatis
     */
    public static function generateKode(): string
    {
        $latest = self::latest('id')->first();
        $number = $latest ? intval(substr($latest->kode, 3)) + 1 : 1;

        return 'BRG' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Relasi ke Jenis Barang
     */
    public function jenisBarang(): BelongsTo
    {
        return $this->belongsTo(JenisBarang::class);
    }

    /**
     * Relasi ke Satuan Barang
     */
    public function satuanBarang(): BelongsTo
    {
        return $this->belongsTo(SatuanBarang::class);
    }

    /**
     * Relasi ke Merk Barang
     */
    public function merkBarang(): BelongsTo
    {
        return $this->belongsTo(MerkBarang::class);
    }

    /**
     * Relasi ke Detail Transaksi Masuk
     */
    public function detailTransaksiMasuks(): HasMany
    {
        return $this->hasMany(DetailTransaksiMasuk::class);
    }

    /**
     * Relasi ke Detail Transaksi Keluar
     */
    public function detailTransaksiKeluars(): HasMany
    {
        return $this->hasMany(DetailTransaksiKeluar::class);
    }

    /**
     * Scope untuk barang aktif
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk barang dengan stok menipis
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stok', '<=', 'stok_minimal');
    }

    /**
     * Scope untuk barang habis
     */
    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stok', 0);
    }

    /**
     * Scope pencarian
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('kode', 'like', "%{$search}%")
                ->orWhere('nama', 'like', "%{$search}%")
                ->orWhereHas('jenisBarang', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                })
                ->orWhereHas('merkBarang', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Get margin keuntungan
     */
    public function getMarginAttribute(): float
    {
        if ($this->harga_beli == 0) {
            return 0;
        }

        return round((($this->harga_jual - $this->harga_beli) / $this->harga_beli) * 100, 2);
    }

    /**
     * Get nilai stok
     */
    public function getNilaiStokAttribute(): float
    {
        return $this->stok * $this->harga_beli;
    }

    /**
     * Check apakah stok menipis
     */
    public function isLowStock(): bool
    {
        return $this->stok <= $this->stok_minimal;
    }

    /**
     * Check apakah stok habis
     */
    public function isOutOfStock(): bool
    {
        return $this->stok === 0;
    }

    /**
     * Update stok barang
     */
    public function updateStok(int $jumlah, string $type = 'masuk'): void
    {
        if ($type === 'masuk') {
            $this->increment('stok', $jumlah);
        } else {
            $this->decrement('stok', $jumlah);
        }
    }

    /**
     * Get status stok label
     */
    public function getStokStatusAttribute(): array
    {
        if ($this->isOutOfStock()) {
            return [
                'label' => 'Habis',
                'color' => 'danger',
            ];
        } elseif ($this->isLowStock()) {
            return [
                'label' => 'Menipis',
                'color' => 'warning',
            ];
        }

        return [
            'label' => 'Tersedia',
            'color' => 'success',
        ];
    }
}
