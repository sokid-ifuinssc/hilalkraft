<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODEL: PRODUCT (PRODUK KARYA + DETEKSI VIEWS IKON MATA, LIKE & KOMENTAR)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan entitas produk karya di tabel `products`, mengatur relasi
 *   ke `Store`, `OrderItem`, `ProductComment` (komentar pengunjung berakun),
 *   dan `ProductLike` (suka pengunjung), serta penghitung `views_count` (ikon mata).
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
        'views_count',
        'likes_count',
    ];

    protected function casts(): array
    {
        return [
            'price'       => 'decimal:2',
            'stock'       => 'integer',
            'is_active'   => 'boolean',
            'views_count' => 'integer',
            'likes_count' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProductComment::class)->latest();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ProductLike::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
    }

    public function getAverageRatingAttribute(): float
    {
        $avg = $this->comments()->avg('rating');
        return $avg ? round((float) $avg, 1) : 5.0;
    }

    /**
     * Scope agar galeri dan pencarian publik HANYA menampilkan produk aktif
     * dari toko yang berstatus aktif, terverifikasi, dan tidak terhapus.
     */
    public function scopeAvailableForPublic($query)
    {
        return $query->where('is_active', true)
            ->whereHas('store', function ($q) {
                $q->where('is_verified', true);
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('stores', 'status')) {
                        $q->where(function ($sq) {
                            $sq->whereNull('status')->orWhere('status', 'active');
                        });
                    }
                } catch (\Throwable $e) {
                    // Resilient jika kolom status belum termigrasi
                }
            });
    }
}
