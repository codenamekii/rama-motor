<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Pemasok extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama_perusahaan', 'nama_kontak', 'jabatan_kontak', 'telepon', 'telepon_2', 'email', 'alamat', 'kota', 'provinsi', 'kode_pos', 'npwp', 'no_rekening', 'nama_bank', 'atas_nama_rekening', 'total_pembelian', 'jumlah_transaksi', 'catatan', 'is_active'];

    protected $casts = [
        'total_pembelian' => 'decimal:2',
        'jumlah_transaksi' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pemasok) {
            if (empty($pemasok->kode)) {
                $pemasok->kode = self::generateKode();
            }
        });
    }

    public static function generateKode(): string
    {
        $latest = self::latest('id')->first();
        $number = $latest ? intval(substr($latest->kode, 3)) + 1 : 1;

        return 'SUP' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function transaksiMasuks(): HasMany
    {
        return $this->hasMany(TransaksiMasuk::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('kode', 'like', "%{$search}%")
                ->orWhere('nama_perusahaan', 'like', "%{$search}%")
                ->orWhere('nama_kontak', 'like', "%{$search}%")
                ->orWhere('telepon', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([$this->alamat, $this->kota, $this->provinsi, $this->kode_pos ? "({$this->kode_pos})" : null]);

        return implode(', ', $parts);
    }

    public function getInfoBankAttribute(): ?string
    {
        if (!$this->no_rekening || !$this->nama_bank) {
            return null;
        }

        $parts = array_filter([$this->nama_bank, $this->no_rekening, $this->atas_nama_rekening ? "a.n. {$this->atas_nama_rekening}" : null]);

        return implode(' - ', $parts);
    }

    public function getKontakLengkapAttribute(): string
    {
        $parts = array_filter([$this->nama_kontak, $this->jabatan_kontak ? "({$this->jabatan_kontak})" : null]);

        return implode(' ', $parts) ?: '-';
    }

    public function updateStatistik(): void
    {
        $transaksi = $this->transaksiMasuks()->where('status_pembayaran', '!=', 'Belum Lunas')->get();

        $this->update([
            'total_pembelian' => $transaksi->sum('total_bayar'),
            'jumlah_transaksi' => $transaksi->count(),
        ]);
    }

    public function getTotalHutangAttribute(): float
    {
        return $this->transaksiMasuks()
            ->whereIn('status_pembayaran', ['Belum Lunas', 'Sebagian'])
            ->sum('sisa_hutang');
    }
}
