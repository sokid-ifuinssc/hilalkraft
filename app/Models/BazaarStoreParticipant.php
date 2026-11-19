<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * MODEL: BAZAAR STORE PARTICIPANT (PESERTA TOKO & STAND QR DI EVENT BAZAR)
 * ============================================================================
 * Mengelola keikutsertaan toko dalam suatu acara bazar:
 * - Dipilih langsung oleh Admin / Super Admin -> status `approved`
 * - Diajukan sendiri oleh toko (Seller) -> status `pending` menunggu persetujuan
 * Setiap peserta memiliki `stand_token` dan `stand_code` unik untuk dicetak
 * sebagai Barcode/QR Kunjungan Stand.
 * ============================================================================
 */
class BazaarStoreParticipant extends Model
{
    protected $fillable = [
        'bazaar_event_id',
        'store_id',
        'stand_number',
        'stand_token',
        'stand_code',
        'status',
        'notes',
        'rejection_reason',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BazaarStoreParticipant $participant) {
            if (empty($participant->stand_token)) {
                $participant->stand_token = Str::random(20);
            }
            if (empty($participant->stand_code)) {
                $participant->stand_code = 'ST-' . Str::upper(Str::random(6));
            }
        });
    }

    public function bazaarEvent(): BelongsTo
    {
        return $this->belongsTo(BazaarEvent::class, 'bazaar_event_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Disetujui (Peserta Resmi)',
            'rejected' => 'Ditolak',
            default    => 'Menunggu Persetujuan Admin',
        };
    }
}
