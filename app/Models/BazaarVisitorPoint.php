<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: BAZAAR VISITOR POINT (LOG POIN PENGUNJUNG & PEMBELI ACARA BAZAR)
 * ============================================================================
 * Mencatat setiap perolehan poin pembeli pada suatu acara bazar:
 *   - `event_checkin`       : Scan QR kehadiran di pintu masuk acara (+50 poin)
 *   - `stand_visit`         : Scan QR kunjungan di stand toko peserta (+20 poin)
 *   - `bazaar_transaction`  : Belanja/transaksi resmi pada acara bazar (+30 poin)
 * ============================================================================
 */
class BazaarVisitorPoint extends Model
{
    protected $fillable = [
        'bazaar_event_id',
        'user_id',
        'store_id',
        'order_id',
        'activity_type',
        'points',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    public function bazaarEvent(): BelongsTo
    {
        return $this->belongsTo(BazaarEvent::class, 'bazaar_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function getActivityLabelAttribute(): string
    {
        return match ($this->activity_type) {
            'event_checkin'      => 'Hadir di Acara Bazar',
            'stand_visit'        => 'Kunjungan Stand Toko',
            'bazaar_transaction' => 'Transaksi Belanja Bazar',
            'bonus_admin'        => 'Bonus / Penyesuaian Panitia',
            default              => ucfirst((string) $this->activity_type),
        };
    }
}
