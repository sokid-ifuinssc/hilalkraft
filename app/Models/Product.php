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

    public const ALL_DELIVERY_METHODS = ['pickup', 'delivery_lokal', 'shipping_transfer'];

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
        'allowed_delivery_methods',
    ];

    protected function casts(): array
    {
        return [
            'price'                    => 'decimal:2',
            'stock'                    => 'integer',
            'is_active'                => 'boolean',
            'views_count'              => 'integer',
            'likes_count'              => 'integer',
            'allowed_delivery_methods' => 'array',
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
     * Mengembalikan daftar metode transaksi yang diizinkan untuk produk ini.
     * Default jika belum diset: ketiga metode diizinkan.
     */
    public function getEffectiveAllowedDeliveryMethodsAttribute(): array
    {
        $methods = $this->allowed_delivery_methods;
        if (is_array($methods) && count($methods) > 0) {
            return array_values(array_intersect($methods, self::ALL_DELIVERY_METHODS));
        }
        return self::ALL_DELIVERY_METHODS;
    }

    public function isDeliveryMethodAllowed(string $method): bool
    {
        return in_array($method, $this->effective_allowed_delivery_methods, true);
    }

    public function canPickup(): bool
    {
        return $this->isDeliveryMethodAllowed('pickup');
    }

    public function canDeliveryLokal(): bool
    {
        return $this->isDeliveryMethodAllowed('delivery_lokal');
    }

    public function canShippingTransfer(): bool
    {
        return $this->isDeliveryMethodAllowed('shipping_transfer');
    }

    public function getAllowedDeliveryLabelAttribute(): string
    {
        $methods = $this->effective_allowed_delivery_methods;
        if (count($methods) === 3) {
            return 'Melayani Datang Langsung, Delivery & Transfer';
        }

        $labels = [];
        if (in_array('pickup', $methods, true)) {
            $labels[] = 'Datang Langsung';
        }
        if (in_array('delivery_lokal', $methods, true)) {
            $labels[] = 'Delivery Lokal';
        }
        if (in_array('shipping_transfer', $methods, true)) {
            $labels[] = 'Transfer Antar';
        }

        if (count($labels) === 1 && $labels[0] === 'Datang Langsung') {
            return 'Khusus Datang / Ambil di Sekolah';
        }

        return implode(' & ', $labels);
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
