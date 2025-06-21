<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SatuanBarang extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'singkatan', 'deskripsi', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($satuanBarang) {
            if (empty($satuanBarang->kode)) {
                $satuanBarang->kode = self::generateKode();
            }
        });
    }

    public static function generateKode(): string
    {
        $latest = self::latest('id')->first();
        $number = $latest ? intval(substr($latest->kode, 3)) + 1 : 1;

        return 'SAT' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->nama} ({$this->singkatan})";
    }
}
