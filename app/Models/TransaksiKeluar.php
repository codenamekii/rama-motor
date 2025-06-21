<?php
// File: app/Models/TransaksiKeluar.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransaksiKeluar extends Model
{
    use HasFactory;

    protected $fillable = ['no_transaksi', 'tanggal_transaksi', 'pelanggan_id', 'nama_pelanggan', 'jenis_pembayaran', 'tanggal_jatuh_tempo', 'status_pembayaran', 'total_harga', 'diskon_persen', 'diskon_nominal', 'ppn_persen', 'ppn_nominal', 'biaya_lain', 'total_bayar', 'jumlah_dibayar', 'kembalian', 'sisa_piutang', 'keterangan', 'user_id'];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'total_harga' => 'decimal:2',
        'diskon_persen' => 'decimal:2',
        'diskon_nominal' => 'decimal:2',
        'ppn_persen' => 'decimal:2',
        'ppn_nominal' => 'decimal:2',
        'biaya_lain' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'jumlah_dibayar' => 'decimal:2',
        'kembalian' => 'decimal:2',
        'sisa_piutang' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaksi) {
            if (empty($transaksi->no_transaksi)) {
                $transaksi->no_transaksi = self::generateNoTransaksi();
            }

            // Set user_id otomatis
            if (empty($transaksi->user_id)) {
                $transaksi->user_id = Auth::id();
            }
        });

        static::created(function ($transaksi) {
            // Update statistik pelanggan jika ada
            if ($transaksi->pelanggan_id) {
                $transaksi->pelanggan->updateStatistik();
            }
        });

        static::updated(function ($transaksi) {
            // Update statistik pelanggan jika ada
            if ($transaksi->pelanggan_id) {
                $transaksi->pelanggan->updateStatistik();
            }
        });
    }

    public static function generateNoTransaksi(): string
    {
        $prefix = 'TK';
        $date = now()->format('Ymd');

        $latest = self::whereDate('created_at', now()->toDateString())
            ->latest('id')
            ->first();

        if ($latest) {
            $lastNumber = intval(substr($latest->no_transaksi, -4));
            $number = $lastNumber + 1;
        } else {
            $number = 1;
        }

        return $prefix . $date . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailTransaksiKeluar::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('no_transaksi', 'like', "%{$search}%")
                ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                ->orWhereHas('pelanggan', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
        });
    }

    public function scopePeriode(Builder $query, ?string $dari, ?string $sampai): Builder
    {
        if ($dari) {
            $query->whereDate('tanggal_transaksi', '>=', $dari);
        }

        if ($sampai) {
            $query->whereDate('tanggal_transaksi', '<=', $sampai);
        }

        return $query;
    }

    public function scopeBelumLunas(Builder $query): Builder
    {
        return $query->whereIn('status_pembayaran', ['Belum Lunas', 'Sebagian']);
    }

    public function scopeLunas(Builder $query): Builder
    {
        return $query->where('status_pembayaran', 'Lunas');
    }

    public function scopeJatuhTempo(Builder $query): Builder
    {
        return $query->where('tanggal_jatuh_tempo', '<=', now())->whereIn('status_pembayaran', ['Belum Lunas', 'Sebagian']);
    }

    public function hitungTotal(): void
    {
        $totalHarga = $this->details->sum('subtotal');

        // Hitung diskon
        if ($this->diskon_persen > 0) {
            $this->diskon_nominal = $totalHarga * ($this->diskon_persen / 100);
        }

        $totalSetelahDiskon = $totalHarga - $this->diskon_nominal;

        // Hitung PPN
        if ($this->ppn_persen > 0) {
            $this->ppn_nominal = $totalSetelahDiskon * ($this->ppn_persen / 100);
        }

        // Total bayar
        $this->total_harga = $totalHarga;
        $this->total_bayar = $totalSetelahDiskon + $this->ppn_nominal + $this->biaya_lain;

        // Update sisa piutang dan kembalian
        $this->updatePembayaran();

        $this->save();
    }

    public function updatePembayaran(): void
    {
        if ($this->jenis_pembayaran === 'Cash') {
            // Untuk cash, hitung kembalian
            if ($this->jumlah_dibayar >= $this->total_bayar) {
                $this->kembalian = $this->jumlah_dibayar - $this->total_bayar;
                $this->sisa_piutang = 0;
                $this->status_pembayaran = 'Lunas';
            } else {
                $this->kembalian = 0;
                $this->sisa_piutang = $this->total_bayar - $this->jumlah_dibayar;
                $this->status_pembayaran = 'Belum Lunas';
            }
        } else {
            // Untuk non-cash, tidak ada kembalian
            $this->kembalian = 0;
            $this->sisa_piutang = $this->total_bayar - $this->jumlah_dibayar;

            // Update status pembayaran
            if ($this->sisa_piutang <= 0) {
                $this->status_pembayaran = 'Lunas';
                $this->sisa_piutang = 0;
            } elseif ($this->jumlah_dibayar > 0) {
                $this->status_pembayaran = 'Sebagian';
            } else {
                $this->status_pembayaran = 'Belum Lunas';
            }
        }
    }

    public function bayar(float $jumlah): void
    {
        $this->jumlah_dibayar += $jumlah;
        $this->updatePembayaran();
        $this->save();
    }

    public function getNamaPelangganFinalAttribute(): string
    {
        return $this->pelanggan_id ? $this->pelanggan->nama : ($this->nama_pelanggan ?: 'Umum');
    }

    public function getTotalLabaAttribute(): float
    {
        return $this->details->sum(function ($detail) {
            return ($detail->harga_jual - $detail->harga_beli) * $detail->jumlah;
        });
    }

    public function isOverdue(): bool
    {
        return $this->tanggal_jatuh_tempo && $this->tanggal_jatuh_tempo->isPast() && $this->status_pembayaran !== 'Lunas';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status_pembayaran) {
            'Lunas' => 'success',
            'Sebagian' => 'warning',
            'Belum Lunas' => 'danger',
            default => 'secondary',
        };
    }
}
