<?php
// File: app/Models/Pelanggan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Pelanggan extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'jenis_kelamin', 'telepon', 'email', 'alamat', 'kota', 'provinsi', 'kode_pos', 'tanggal_bergabung', 'total_pembelian', 'jumlah_transaksi', 'catatan', 'is_active'];

    protected $casts = [
        'tanggal_bergabung' => 'date',
        'total_pembelian' => 'decimal:2',
        'jumlah_transaksi' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pelanggan) {
            if (empty($pelanggan->kode)) {
                $pelanggan->kode = self::generateKode();
            }
            if (empty($pelanggan->tanggal_bergabung)) {
                $pelanggan->tanggal_bergabung = now();
            }
        });
    }

    public static function generateKode(): string
    {
        $latest = self::latest('id')->first();
        $number = $latest ? intval(substr($latest->kode, 3)) + 1 : 1;

        return 'PLG' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function transaksiKeluars(): HasMany
    {
        return $this->hasMany(TransaksiKeluar::class);
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
                ->orWhere('nama', 'like', "%{$search}%")
                ->orWhere('telepon', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([$this->alamat, $this->kota, $this->provinsi, $this->kode_pos ? "({$this->kode_pos})" : null]);

        return implode(', ', $parts);
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    public function updateStatistik(): void
    {
        $transaksi = $this->transaksiKeluars()->where('status_pembayaran', '!=', 'Belum Lunas')->get();

        $this->update([
            'total_pembelian' => $transaksi->sum('total_bayar'),
            'jumlah_transaksi' => $transaksi->count(),
        ]);
    }
}
