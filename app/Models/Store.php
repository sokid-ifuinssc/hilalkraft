<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use HasFactory, SoftDeletes;

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
        'status',
        'views_count',
        'discount_pelajar',
        'discount_keluarga',
        'discount_alumni',
        'discount_umum',
        'discount_bazar_extra',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'ewallet_name',
        'ewallet_account_number',
        'ewallet_account_name',
        'payment_instructions',
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
            'deleted_at'           => 'datetime',
        ];
    }

    /**
     * Override bootSoftDeletes agar aman dan resilient (Anti 500 Error):
     * Jika kolom `deleted_at` belum ada di tabel `stores` (misalnya saat baru git pull di server),
     * scope soft-delete tidak dipaksakan sehingga tidak melempar QueryException,
     * dan otomatis melakukan penambahan kolom (auto-healing).
     */
    public static function bootSoftDeletes(): void
    {
        try {
            static $hasDeletedAt = null;
            if ($hasDeletedAt === null) {
                $hasDeletedAt = \Illuminate\Support\Facades\Schema::hasColumn('stores', 'deleted_at');
                if (! $hasDeletedAt && \Illuminate\Support\Facades\Schema::hasTable('stores')) {
                    try {
                        \Illuminate\Support\Facades\Schema::table('stores', function ($table) {
                            $table->softDeletes();
                            if (! \Illuminate\Support\Facades\Schema::hasColumn('stores', 'status')) {
                                $table->string('status', 20)->default('active');
                            }
                        });
                        $hasDeletedAt = true;
                    } catch (\Throwable $migrationError) {
                        $hasDeletedAt = false;
                    }
                }
            }

            if ($hasDeletedAt) {
                static::addGlobalScope(new \Illuminate\Database\Eloquent\SoftDeletingScope);
            }
        } catch (\Throwable $e) {
            // Hindari crash jika skema database belum siap
        }
    }

    /**
     * Cek apakah toko terhapus secara aman tanpa crash jika kolom belum ada.
     */
    public function trashed(): bool
    {
        return array_key_exists('deleted_at', $this->attributes) && ! is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Klasifikasi Status Toko: 'aktif', 'nonaktif', 'terhapus'
     */
    public function getStoreStateAttribute(): string
    {
        if ($this->trashed()) {
            return 'terhapus';
        }
        if ($this->is_verified && ($this->status ?? 'active') === 'active') {
            return 'aktif';
        }
        return 'nonaktif';
    }

    public function getStateBadgeAttribute(): array
    {
        return match ($this->store_state) {
            'aktif'    => ['label' => 'Toko Aktif',    'color' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'nonaktif' => ['label' => 'Toko Non-Aktif', 'color' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'terhapus' => ['label' => 'Toko Terhapus',  'color' => 'bg-rose-100 text-rose-800 border-rose-300'],
            default    => ['label' => 'Status Lain',   'color' => 'bg-slate-100 text-slate-700 border-slate-300'],
        };
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

    public function getHasBankAccountAttribute(): bool
    {
        return ! empty($this->bank_name) && ! empty($this->bank_account_number);
    }

    public function getHasEwalletAccountAttribute(): bool
    {
        return ! empty($this->ewallet_name) && ! empty($this->ewallet_account_number);
    }

    public function getHasPaymentAccountAttribute(): bool
    {
        return $this->has_bank_account || $this->has_ewallet_account;
    }

    public function getPaymentSummaryAttribute(): string
    {
        $parts = [];
        if ($this->has_bank_account) {
            $parts[] = "Bank {$this->bank_name}: {$this->bank_account_number}" . ($this->bank_account_name ? " a.n. {$this->bank_account_name}" : "");
        }
        if ($this->has_ewallet_account) {
            $parts[] = "E-Wallet {$this->ewallet_name}: {$this->ewallet_account_number}" . ($this->ewallet_account_name ? " a.n. {$this->ewallet_account_name}" : "");
        }
        return count($parts) > 0 ? implode(" | ", $parts) : "Hubungi WhatsApp Toko untuk rekening transfer";
    }
}
