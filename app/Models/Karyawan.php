<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = ['nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'telepon', 'email', 'status_pernikahan', 'pendidikan_terakhir', 'jabatan', 'tanggal_masuk', 'tanggal_keluar', 'status_karyawan', 'gaji_pokok', 'no_rekening', 'nama_bank', 'foto', 'catatan'];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'tanggal_keluar' => 'date',
        'gaji_pokok' => 'decimal:2',
    ];

    protected $appends = ['umur', 'masa_kerja'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($karyawan) {
            if (empty($karyawan->nik)) {
                $karyawan->nik = self::generateNIK();
            }
        });

        static::deleting(function ($karyawan) {
            // Delete foto when deleting karyawan
            if ($karyawan->foto) {
                Storage::disk('public')->delete($karyawan->foto);
            }
        });
    }

    /**
     * Generate NIK otomatis
     */
    public static function generateNIK(): string
    {
        $year = date('Y');
        $latest = self::whereYear('created_at', $year)->latest('id')->first();

        if ($latest) {
            $lastNumber = intval(substr($latest->nik, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return 'K' . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scope untuk karyawan aktif
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status_karyawan', 'Aktif');
    }

    /**
     * Scope untuk karyawan non-aktif
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereIn('status_karyawan', ['Non-Aktif', 'Resign', 'Pensiun']);
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
            $q->where('nik', 'like', "%{$search}%")
                ->orWhere('nama', 'like', "%{$search}%")
                ->orWhere('jabatan', 'like', "%{$search}%")
                ->orWhere('telepon', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /**
     * Scope berdasarkan jabatan
     */
    public function scopeJabatan(Builder $query, ?string $jabatan): Builder
    {
        if (!$jabatan) {
            return $query;
        }

        return $query->where('jabatan', $jabatan);
    }

    /**
     * Get umur karyawan
     */
    public function getUmurAttribute(): ?int
    {
        if (!$this->tanggal_lahir) {
            return null;
        }

        return $this->tanggal_lahir->age;
    }

    /**
     * Get masa kerja
     */
    public function getMasaKerjaAttribute(): array
    {
        $start = $this->tanggal_masuk;
        $end = $this->tanggal_keluar ?? now();

        $diff = $start->diff($end);

        return [
            'tahun' => $diff->y,
            'bulan' => $diff->m,
            'hari' => $diff->d,
            'text' => $diff->y . ' tahun ' . $diff->m . ' bulan',
        ];
    }

    /**
     * Get jenis kelamin label
     */
    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status_karyawan) {
            'Aktif' => 'success',
            'Non-Aktif' => 'warning',
            'Resign' => 'danger',
            'Pensiun' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get foto URL
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto) {
            return null;
        }

        return Storage::url($this->foto);
    }

    /**
     * Get info bank
     */
    public function getInfoBankAttribute(): ?string
    {
        if (!$this->no_rekening || !$this->nama_bank) {
            return null;
        }

        return "{$this->nama_bank} - {$this->no_rekening}";
    }

    /**
     * Check if karyawan is active
     */
    public function isActive(): bool
    {
        return $this->status_karyawan === 'Aktif';
    }

    /**
     * Resign karyawan
     */
    public function resign(?string $tanggal = null): void
    {
        $this->update([
            'status_karyawan' => 'Resign',
            'tanggal_keluar' => $tanggal ?? now(),
        ]);
    }

    /**
     * Activate karyawan
     */
    public function activate(): void
    {
        $this->update([
            'status_karyawan' => 'Aktif',
            'tanggal_keluar' => null,
        ]);
    }
}
