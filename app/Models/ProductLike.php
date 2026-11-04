<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: PRODUCT_LIKE (DATA SUKA / FAVORIT PRODUK)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `product_likes` untuk mencatat interaksi suka (like)
 *   pengunjung pada produk, yang dihitung pada grafik analitik toko terpopuler.
 *
 * Algoritma:
 *   Menyimpan `product_id`, `user_id` (jika login), dan `ip_address` untuk
 *   mencegah duplikasi like berulang dari pengunjung yang sama.
 * ============================================================================
 */
class ProductLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'ip_address',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

