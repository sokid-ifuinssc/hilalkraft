<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ============================================================================
 * MODEL: ORDER (TRANSAKSI, PROGRES 4 TAHAP, DISKON & RATING BINTANG)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `orders` yang menyimpan data transaksi antara
 *   Pembeli (`buyer`) dan Toko (`store`), lengkap dengan:
 *   1. Pelacakan Progres Barang 4 Tahap (`progress_status`):
 *      - `pesan`  : Dipesan / Menunggu Konfirmasi
 *      - `kemas`  : Sedang Dikemas oleh Penjual
 *      - `kirim`  : Dikirim / Dalam Perjalanan (atau Siap Diambil di Stand Bazar)
 *      - `sampai` : Barang Sampai / Diterima Pembeli
 *      - `dibatalkan` : Pesanan Dibatalkan
 *   2. Skema Potongan Diskon Jenis Pembeli & Diskon Bazar/Event SMK.
 *   3. Rating Bintang (`buyer_rating` 1..5) & Ulasan (`buyer_review`) setelah transaksi.
 * ============================================================================
 */
class Order extends Model
{
    use HasFactory;

    /**
     * Tiga kecamatan Kabupaten Cirebon yang dilayani Delivery / Antar Langsung
     */
    public const LOCAL_DELIVERY_DISTRICTS = ['arjawinangun', 'susukan', 'kaliwedi'];

    protected $fillable = [
        'invoice_number',
        'buyer_id',
        'store_id',
        'subtotal_price',
        'buyer_type_discount_percent',
        'buyer_type_discount_amount',
        'bazaar_event_id',
        'bazaar_voucher_code',
        'bazaar_discount_percent',
        'bazaar_discount_amount',
        'is_bazaar_offline',
        'delivery_method',
        'shipping_cost',
        'destination_kabupaten',
        'destination_kecamatan',
        'destination_desa',
        'courier_name',
        'tracking_number',
        'total_price',
        'status',
        'progress_status',
        'progress_note',
        'notes',
        'shipping_address',
        'buyer_rating',
        'buyer_review',
        'rated_at',
        'packed_at',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_price'              => 'decimal:2',
            'buyer_type_discount_percent' => 'integer',
            'buyer_type_discount_amount'  => 'decimal:2',
            'bazaar_discount_percent'     => 'integer',
            'bazaar_discount_amount'      => 'decimal:2',
            'is_bazaar_offline'           => 'boolean',
            'shipping_cost'               => 'decimal:2',
            'total_price'                 => 'decimal:2',
            'buyer_rating'                => 'integer',
            'rated_at'                    => 'datetime',
            'packed_at'                   => 'datetime',
            'shipped_at'                  => 'datetime',
            'delivered_at'                => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function bazaarEvent(): BelongsTo
    {
        return $this->belongsTo(BazaarEvent::class, 'bazaar_event_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getEffectiveProgressAttribute(): string
    {
        if (! empty($this->progress_status)) {
            return $this->progress_status;
        }

        return match ($this->status) {
            'confirmed' => 'kemas',
            'completed' => 'sampai',
            'cancelled' => 'dibatalkan',
            default     => 'pesan',
        };
    }

    /**
     * Label ramah metode pengiriman / transaksi
     */
    public function getDeliveryMethodLabelAttribute(): string
    {
        return match ($this->delivery_method) {
            'delivery_lokal'    => 'Delivery / Antar Langsung (Arjawinangun, Susukan, Kaliwedi)',
            'shipping_transfer' => 'Transfer Antar (Ekspedisi Luar Wilayah)',
            default             => 'Transaksi Langsung / Datang ke Sekolah',
        };
    }

    public function getDeliveryMethodShortLabelAttribute(): string
    {
        return match ($this->delivery_method) {
            'delivery_lokal'    => '🛵 Antar Langsung',
            'shipping_transfer' => '🚚 Transfer Antar',
            default             => '🏫 Datang ke Sekolah',
        };
    }

    public function getDeliveryBadgeClassAttribute(): string
    {
        return match ($this->delivery_method) {
            'delivery_lokal'    => 'bg-sky-50 text-sky-800 border border-sky-200',
            'shipping_transfer' => 'bg-amber-50 text-amber-800 border border-amber-200',
            default             => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        };
    }

    /**
     * Daftar tahapan progres sesuai skema:
     * - `pickup` (Datang ke Sekolah): 3 Tahap (Dipesan -> Dikemas -> Diterima)
     * - `delivery_lokal` & `shipping_transfer`: 4 Tahap (Dipesan -> Dikemas -> Dikirim -> Diterima)
     */
    public function getProgressStagesAttribute(): array
    {
        if ($this->delivery_method === 'pickup') {
            return [
                1 => ['key' => 'pesan', 'label' => '1. Dipesan', 'desc' => 'Pesanan masuk / Konfirmasi'],
                2 => ['key' => 'kemas', 'label' => '2. Dikemas', 'desc' => 'Disiapkan di SMK Plus Al Hilal'],
                3 => ['key' => 'sampai', 'label' => '3. Diterima', 'desc' => 'Diambil di sekolah & beri ★'],
            ];
        }

        return [
            1 => ['key' => 'pesan', 'label' => '1. Dipesan', 'desc' => 'Pesanan masuk'],
            2 => ['key' => 'kemas', 'label' => '2. Dikemas', 'desc' => 'Sedang dikemas penjual'],
            3 => ['key' => 'kirim', 'label' => '3. Dikirim', 'desc' => $this->delivery_method === 'delivery_lokal' ? 'Dalam perjalanan diantar kurir' : 'Dalam perjalanan ekspedisi'],
            4 => ['key' => 'sampai', 'label' => '4. Diterima', 'desc' => 'Diterima pembeli & beri ★'],
        ];
    }

    public function getProgressStepAttribute(): int
    {
        if ($this->delivery_method === 'pickup') {
            return match ($this->effective_progress) {
                'pesan'      => 1,
                'kemas'      => 2,
                'sampai'     => 3,
                'dibatalkan' => 0,
                default      => 1,
            };
        }

        return match ($this->effective_progress) {
            'pesan'      => 1,
            'kemas'      => 2,
            'kirim'      => 3,
            'sampai'     => 4,
            'dibatalkan' => 0,
            default      => 1,
        };
    }

    public function getProgressLabelAttribute(): string
    {
        if ($this->delivery_method === 'pickup') {
            return match ($this->effective_progress) {
                'pesan'      => 'Dipesan',
                'kemas'      => 'Dikemas (Siap Diambil di Sekolah)',
                'sampai'     => 'Diterima di Sekolah',
                'dibatalkan' => 'Dibatalkan',
                default      => 'Dipesan',
            };
        }

        return match ($this->effective_progress) {
            'pesan'      => 'Dipesan',
            'kemas'      => 'Dikemas',
            'kirim'      => 'Dikirim / Dalam Perjalanan',
            'sampai'     => 'Sampai / Diterima',
            'dibatalkan' => 'Dibatalkan',
            default      => 'Dipesan',
        };
    }

    public function getFormattedShippingCostAttribute(): string
    {
        if ($this->delivery_method === 'shipping_transfer') {
            return 'Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir.';
        }
        if ($this->delivery_method === 'pickup') {
            return 'Gratis (Datang ke Sekolah)';
        }
        $cost = (float) ($this->shipping_cost ?? 0);
        return $cost > 0 ? 'Rp ' . number_format($cost, 0, ',', '.') : 'Gratis (Rp 0)';
    }

    /**
     * Hitung Estimasi Biaya Ongkir Menyesuaikan Skema & Lokasi.
     * Sesuai ketentuan:
     * 1. Datang ke sekolah (pickup) : Rp 0
     * 2. Delivery lokal (Arjawinangun, Susukan, Kaliwedi) : Rp 5.000
     * 3. Transfer antar (luar wilayah) : Biaya ongkir tidak dimunculkan pada tagihan toko
     *    (karena ditanggung pembeli dan langsung dibayarkan kepada kurir).
     */
    public static function calculateEstimatedShipping(?string $method, ?string $kabupaten, ?string $kecamatan): float
    {
        $method = $method ?: 'pickup';
        if ($method === 'pickup') {
            return 0.0;
        }

        if ($method === 'delivery_lokal') {
            // Khusus 3 kecamatan lokal Kabupaten Cirebon
            return 5000.0;
        }

        // Metode Transfer Antar (Luar Wilayah): biaya ongkir tidak dimunculkan di sistem toko
        // Keterangan: Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir.
        return 0.0;
    }

    public function getOrderCodeAttribute(): string
    {
        return (string) ($this->invoice_number ?: ('HK-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT)));
    }

    public function getBuyerPhoneAttribute(): string
    {
        return (string) ($this->attributes['buyer_phone'] ?? $this->buyer?->phone ?? '-');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        $sub = (float) ($this->subtotal_price > 0 ? $this->subtotal_price : $this->total_price);
        return 'Rp ' . number_format($sub, 0, ',', '.');
    }

    public function getFormattedSubtotalPriceAttribute(): string
    {
        return $this->formatted_subtotal;
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return $this->formatted_total;
    }

    public function getTotalDiscountAmountAttribute(): float
    {
        return (float) ($this->buyer_type_discount_amount ?? 0) + (float) ($this->bazaar_discount_amount ?? 0);
    }

    public function getFormattedTotalDiscountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_discount_amount, 0, ',', '.');
    }
}
