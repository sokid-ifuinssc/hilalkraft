<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MODEL: BAZAAR EVENT (SKEMA DISKON ACARA BAZAR & EVENT SMK PLUS AL HILAL)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `bazaar_events` untuk mengelola program diskon
 *   khusus apabila pembeli berbelanja dan datang langsung pada acara Bazar
 *   SMK Plus Al Hilal atau event resmi sekolah lainnya.
 *
 * Algoritma:
 *   Method `isActiveNow()` memvalidasi status `is_active = true` serta
 *   memastikan tanggal hari ini berada dalam rentang `start_date` s/d `end_date`.
 * ============================================================================
 */
class BazaarEvent extends Model
{
    protected $fillable = [
        'title',
        'voucher_code',
        'description',
        'location',
        'discount_percent',
        'max_discount_amount',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent'    => 'integer',
            'max_discount_amount' => 'decimal:2',
            'start_date'          => 'date',
            'end_date'            => 'date',
            'is_active'           => 'boolean',
        ];
    }

    public function isActiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = Carbon::today();

        if ($this->start_date && $today->lt(Carbon::parse($this->start_date)->startOfDay())) {
            return false;
        }

        if ($this->end_date && $today->gt(Carbon::parse($this->end_date)->endOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * Mengambil daftar seluruh event/bazar yang sedang aktif hari ini.
     *
     * @return Collection<int, BazaarEvent>
     */
    public static function activeEvents(): Collection
    {
        if (! Schema::hasTable('bazaar_events')) {
            return collect();
        }

        return static::where('is_active', true)
            ->orderByDesc('discount_percent')
            ->get()
            ->filter(fn (BazaarEvent $ev) => $ev->isActiveNow())
            ->values();
    }
}

