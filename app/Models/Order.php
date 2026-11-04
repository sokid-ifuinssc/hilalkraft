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

    public function getProgressStepAttribute(): int
    {
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
        return match ($this->effective_progress) {
            'pesan'      => 'Dipesan',
            'kemas'      => 'Dikemas',
            'kirim'      => 'Dikirim / Dalam Perjalanan',
            'sampai'     => 'Sampai / Selesai',
            'dibatalkan' => 'Dibatalkan',
            default      => 'Dipesan',
        };
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
