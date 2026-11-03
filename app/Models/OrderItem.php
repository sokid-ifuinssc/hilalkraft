<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: ORDER_ITEM (RINCIAN ITEM TRANSAKSI)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `order_items` yang menyimpan kuantitas, harga satuan,
 *   dan subtotal setiap produk di dalam sebuah invoice pesanan (`Order`).
 *
 * Algoritma:
 *   Menggunakan relasi BelongsTo ke `Order` dan `Product` untuk memetakan
 *   tabel pivot/detail transaksi secara terstruktur.
 * ============================================================================
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal'   => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
