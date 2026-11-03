<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODEL: PRODUCT (PRODUK KARYA SISWA)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan entitas produk karya siswa di tabel `products`, mengatur
 *   relasi ke `Store` dan `OrderItem`, serta memformat tampilan harga Rupiah.
 *
 * Algoritma (Currency Formatting Accessor):
 *   Pada `getFormattedPriceAttribute()`:
 *   1. Mengambil nilai numerik `price` dan melakukan type-casting ke `float`.
 *   2. Menggunakan algoritma pemisah ribuan `number_format($price, 0, ',', '.')`
 *      untuk mengelompokkan setiap 3 digit dari kanan dengan titik (`.`) dan
 *      menambahkan prefix `'Rp '`.
 * ============================================================================
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'product_name',
        'slug',
        'description',
        'price',
        'stock',
        'image',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'stock'     => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Fungsi: Mengambil data toko pemilik produk.
     * Algoritma: BelongsTo Foreign Key Lookup (`stores.id = products.store_id`).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Fungsi: Mengambil daftar item transaksi yang memuat produk ini.
     * Algoritma: One-to-Many Lookup (`order_items.product_id = products.id`).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Fungsi: Memformat angka harga menjadi teks Rupiah (contoh: Rp 185.000).
     * Algoritma: Pengelompokan 3 digit ribuan menggunakan `number_format`.
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
    }
}
