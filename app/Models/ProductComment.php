<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: PRODUCT_COMMENT (KOMENTAR PRODUK OLEH PENGGUNA BERAKUN)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `product_comments` yang menyimpan ulasan/komentar
 *   pengunjung yang sudah memiliki akun terhadap suatu karya produk.
 *
 * Algoritma:
 *   Relasi BelongsTo ke `Product` dan `User` untuk menampilkan nama, kategori
 *   pengunjung (`user_subtype`), dan waktu komentar secara real-time.
 * ============================================================================
 */
class ProductComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'comment',
        'rating',
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

