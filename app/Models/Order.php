<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODEL: ORDER (HEADER TRANSAKSI PEMBELIAN)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `orders` yang menyimpan data transaksi antara
 *   Pembeli (`buyer`) dan Toko Siswa (`store`), termasuk status pesanan
 *   (`pending`, `confirmed`, `completed`, `cancelled`).
 *
 * Algoritma:
 *   1. Menghubungkan header transaksi dengan banyak rincian barang (`OrderItem`)
 *      melalui relasi One-to-Many (`items()`).
 *   2. Menyediakan accessor `getFormattedTotalAttribute()` dengan algoritma
 *      pemformatan ribuan Rupiah.
 * ============================================================================
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'buyer_id',
        'store_id',
        'total_price',
        'status',
        'notes',
        'shipping_address',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Fungsi: Relasi ke akun Pembeli yang membuat pesanan.
     * Algoritma: BelongsTo Lookup (`users.id = orders.buyer_id`).
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Fungsi: Relasi ke Toko Siswa tujuan pesanan.
     * Algoritma: BelongsTo Lookup (`stores.id = orders.store_id`).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Fungsi: Mengambil seluruh item produk di dalam pesanan ini.
     * Algoritma: One-to-Many Lookup (`order_items.order_id = orders.id`).
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Fungsi: Memformat total harga transaksi ke format mata uang Rupiah.
     * Algoritma: Pemformatan angka ribuan dengan `number_format`.
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_price, 0, ',', '.');
    }
}
