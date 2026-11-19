<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ============================================================================
 * MODEL: BAZAAR DOORPRIZE WINNER (PEMENANG DOORPRIZE PENGUNJUNG BAZAR)
 * ============================================================================
 * Menyimpan penetapan penerima doorprize dari panitia (Admin / Super Admin)
 * berdasarkan peringkat poin tertinggi pengunjung pada acara bazar tertentu.
 * ============================================================================
 */
class BazaarDoorprizeWinner extends Model
{
    protected $fillable = [
        'bazaar_event_id',
        'user_id',
        'prize_name',
        'rank_at_award',
        'total_points_at_award',
        'awarded_by',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'rank_at_award'         => 'integer',
            'total_points_at_award' => 'integer',
            'awarded_at'            => 'datetime',
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

    public function awarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
