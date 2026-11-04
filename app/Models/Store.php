<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * ============================================================================
 * MODEL: STORE (SATU TOKO SATU AKUN + SKEMA DISKON JENIS PEMBELI & BAZAR)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `stores` dengan relasi 1-ke-1 ke `User` (`owner`),
 *   relasi ke `Product`, `Order`, `comments`, dan `productLikes`, serta
 *   menyimpan skema persentase diskon yang diatur oleh Penjual untuk setiap
 *   jenis pembeli:
 *   - `discount_pelajar`     : Diskon khusus Pelajar (%)
 *   - `discount_keluarga`    : Diskon khusus Keluarga SMK Plus Al Hilal (%)
 *   - `discount_alumni`      : Diskon khusus Alumni SMK Plus Al Hilal (%)
 *   - `discount_umum`        : Diskon khusus Pengunjung Umum (%)
 *   - `discount_bazar_extra` : Diskon tambahan toko saat pembeli hadir di Bazar (%)
 *
 * Algoritma:
 *   Method `calculateOrderPricing()` menghitung potongan harga gabungan dari
 *   jenis pembeli (`user_subtype`) dan kehadiran pada acara Bazar/Event SMK
 *   (`BazaarEvent`) secara presisi.
 * ============================================================================
 */
class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'seller_type',
        'student_class',
        'description',
        'contact_number',
        'logo',
        'is_verified',
        'views_count',
        'discount_pelajar',
        'discount_keluarga',
        'discount_alumni',
        'discount_umum',
        'discount_bazar_extra',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'          => 'boolean',
            'views_count'          => 'integer',
            'discount_pelajar'     => 'integer',
            'discount_keluarga'    => 'integer',
            'discount_alumni'      => 'integer',
            'discount_umum'        => 'integer',
            'discount_bazar_extra' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(ProductComment::class, Product::class);
    }

    public function productLikes(): HasManyThrough
    {
        return $this->hasManyThrough(ProductLike::class, Product::class);
    }

    public function getWhatsappUrlAttribute(): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $this->contact_number);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }

        return "https://wa.me/{$clean}";
    }

    public function getSellerTypeLabelAttribute(): string
    {
        return match ($this->seller_type ?? 'siswa') {
            'guru'    => 'Toko Guru / Pembina',
            'jurusan' => 'Toko Unit Jurusan',
            default   => 'Toko Siswa',
        };
    }

    /**
     * Mengambil persentase diskon toko berdasarkan jenis pembeli (`user_subtype`).
     */
    public function getDiscountPercentForBuyer(User|string|null $buyer): int
    {
        if (! $buyer) {
            return 0;
        }

        $subtype = $buyer instanceof User
            ? strtolower((string) ($buyer->user_subtype ?: ($buyer->is_teacher ? 'guru' : 'umum')))
            : strtolower(trim($buyer));

        return match ($subtype) {
            'pelajar', 'siswa' => max(0, min(90, (int) ($this->discount_pelajar ?? 0))),
            'keluarga', 'guru' => max(0, min(90, (int) ($this->discount_keluarga ?? 0))),
            'alumni'           => max(0, min(90, (int) ($this->discount_alumni ?? 0))),
            default            => max(0, min(90, (int) ($this->discount_umum ?? 0))),
        };
    }

    public static function getBuyerSubtypeLabel(User|string|null $buyer): string
    {
        if (! $buyer) {
            return 'Pengunjung Tamu';
        }

        $subtype = $buyer instanceof User
            ? strtolower((string) ($buyer->user_subtype ?: ($buyer->is_teacher ? 'guru' : 'umum')))
            : strtolower(trim($buyer));

        return match ($subtype) {
            'pelajar', 'siswa' => 'Pelajar',
            'keluarga', 'guru' => 'Keluarga SMK Plus Al Hilal',
            'alumni'           => 'Alumni SMK Plus Al Hilal',
            default            => 'Pengunjung Umum',
        };
    }

    /**
     * Menghitung rincian harga pesanan termasuk diskon jenis pembeli & diskon acara bazar.
     */
    public function calculateOrderPricing(
        float $subtotal,
        User|string|null $buyer,
        ?BazaarEvent $bazaarEvent = null,
        bool $attendBazaarOffline = false
    ): array {
        $subtotal = max(0, $subtotal);

        // 1. Diskon Jenis Pembeli (Pelajar / Keluarga SMK / Alumni / Umum)
        $buyerPct = $this->getDiscountPercentForBuyer($buyer);
        $buyerDiscAmount = round($subtotal * ($buyerPct / 100));

        // 2. Diskon Acara Bazar / Event SMK (Jika hadir di bazar atau memakai voucher bazar aktif)
        $bazaarPct = 0;
        $bazaarDiscAmount = 0.0;

        if ($bazaarEvent && $bazaarEvent->isActiveNow()) {
            $bazaarPct = (int) $bazaarEvent->discount_percent + ($attendBazaarOffline ? (int) ($this->discount_bazar_extra ?? 0) : 0);
            $bazaarPct = max(0, min(90, $bazaarPct));
            $bazaarDiscAmount = round($subtotal * ($bazaarPct / 100));

            if ($bazaarEvent->max_discount_amount && $bazaarEvent->max_discount_amount > 0) {
                $bazaarDiscAmount = min($bazaarDiscAmount, (float) $bazaarEvent->max_discount_amount);
            }
        } elseif ($attendBazaarOffline && (int) ($this->discount_bazar_extra ?? 0) > 0) {
            $bazaarPct = max(0, min(90, (int) $this->discount_bazar_extra));
            $bazaarDiscAmount = round($subtotal * ($bazaarPct / 100));
        }

        $totalDiscount = min($subtotal, $buyerDiscAmount + $bazaarDiscAmount);
        $finalTotal = max(0, $subtotal - $totalDiscount);

        return [
            'subtotal_price'              => $subtotal,
            'buyer_type_discount_percent' => $buyerPct,
            'buyer_type_discount_amount'  => $buyerDiscAmount,
            'bazaar_discount_percent'     => $bazaarPct,
            'bazaar_discount_amount'      => $bazaarDiscAmount,
            'total_discount'              => $totalDiscount,
            'total_price'                 => $finalTotal,
        ];
    }
}
