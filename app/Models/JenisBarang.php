<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class JenisBarang extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'deskripsi', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jenisBarang) {
            if (empty($jenisBarang->kode)) {
                $jenisBarang->kode = self::generateKode();
            }
        });
    }

    public static function generateKode(): string
    {
        $latest = self::latest('id')->first();
        $number = $latest ? intval(substr($latest->kode, 2)) + 1 : 1;

        return 'JB' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getJumlahBarangAttribute(): int
    {
        return $this->barangs()->count();
    }
}
