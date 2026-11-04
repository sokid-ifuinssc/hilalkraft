<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: VISITOR_LOG (LOG KUNJUNGAN PRODUK & TOKO)
 * ============================================================================
 * Fungsi:
 *   Mencatat setiap kunjungan (view) pada produk dan toko baik oleh pengunjung
 *   tamu (Guest) maupun pengguna terdaftar (Pelajar, Keluarga SMK Plus Al Hilal,
 *   Alumni, Pengunjung Umum, Siswa, Guru) untuk laporan Admin.
 * ============================================================================
 */
class VisitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'product_id',
        'visitor_type',
        'visitor_name',
        'origin_region',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

