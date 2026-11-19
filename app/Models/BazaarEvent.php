<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * ============================================================================
 * MODEL: BAZAAR EVENT (ACARA BAZAR, PESERTA TOKO, QR KEHADIRAN & POIN DOORPRIZE)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `bazaar_events` untuk mengelola:
 *   1. Diskon & voucher acara bazar.
 *   2. Token QR Code & kode singkat kehadiran pintu masuk bazar (`checkin_token`, `short_code`).
 *   3. Konfigurasi poin pengunjung: Hadir di bazar (+50), Scan stand toko (+20),
 *      Transaksi di bazar (+30).
 *   4. Kebijakan pendaftaran mandiri toko peserta (`allow_self_register`).
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
        'checkin_token',
        'short_code',
        'points_checkin',
        'points_stand_visit',
        'points_transaction',
        'allow_self_register',
        'prize_tiers',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent'    => 'integer',
            'max_discount_amount' => 'decimal:2',
            'start_date'          => 'date',
            'end_date'            => 'date',
            'is_active'           => 'boolean',
            'points_checkin'      => 'integer',
            'points_stand_visit'  => 'integer',
            'points_transaction'  => 'integer',
            'allow_self_register' => 'boolean',
            'prize_tiers'         => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BazaarEvent $event) {
            if (empty($event->checkin_token)) {
                $event->checkin_token = Str::random(20);
            }
            if (empty($event->short_code)) {
                $event->short_code = 'BZ-' . Str::upper(Str::random(6));
            }
            $event->points_checkin     ??= 50;
            $event->points_stand_visit ??= 20;
            $event->points_transaction ??= 30;
            $event->allow_self_register ??= true;
        });
    }

    public function ensureTokensExist(): self
    {
        $dirty = false;
        if (empty($this->checkin_token)) {
            $this->checkin_token = Str::random(20);
            $dirty = true;
        }
        if (empty($this->short_code)) {
            $this->short_code = 'BZ-' . Str::upper(Str::random(6));
            $dirty = true;
        }
        if ($dirty) {
            $this->save();
        }
        return $this;
    }

    public function participants(): HasMany
    {
        return $this->hasMany(BazaarStoreParticipant::class, 'bazaar_event_id');
    }

    public function approvedParticipants(): HasMany
    {
        return $this->participants()->where('status', 'approved');
    }

    public function pendingParticipants(): HasMany
    {
        return $this->participants()->where('status', 'pending');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'bazaar_event_id');
    }

    public function visitorPoints(): HasMany
    {
        return $this->hasMany(BazaarVisitorPoint::class, 'bazaar_event_id');
    }

    public function doorprizeWinners(): HasMany
    {
        return $this->hasMany(BazaarDoorprizeWinner::class, 'bazaar_event_id');
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

    /**
     * Mengembalikan map `[store_id => BazaarEvent]` untuk seluruh toko yang disetujui
     * (`status = approved`) pada event bazar yang sedang aktif saat ini.
     * Jika tidak ada acara aktif atau semua acara nonaktif/berakhir, mengembalikan koleksi kosong.
     *
     * @return Collection<int, BazaarEvent>
     */
    public static function activeBazaarMapByStore(): Collection
    {
        $activeEvents = static::activeEvents();
        if ($activeEvents->isEmpty() || ! Schema::hasTable('bazaar_store_participants')) {
            return collect();
        }

        $eventsById = $activeEvents->keyBy('id');
        $participants = BazaarStoreParticipant::where('status', 'approved')
            ->whereIn('bazaar_event_id', $activeEvents->pluck('id'))
            ->get();

        $map = collect();
        foreach ($participants as $part) {
            $ev = $eventsById->get($part->bazaar_event_id);
            if (! $ev) {
                continue;
            }
            $existing = $map->get($part->store_id);
            if (! $existing || (int) $ev->discount_percent > (int) $existing->discount_percent) {
                $map->put($part->store_id, $ev);
            }
        }

        return $map;
    }

    /**
     * Auto-healing kolom `prize_tiers` jika aplikasi dijalankan di server sebelum `php artisan migrate`.
     */
    public static function ensurePrizeTiersColumn(): void
    {
        try {
            if (Schema::hasTable('bazaar_events') && ! Schema::hasColumn('bazaar_events', 'prize_tiers')) {
                Schema::table('bazaar_events', function ($table) {
                    $table->json('prize_tiers')->nullable();
                });
            }
        } catch (\Throwable $e) {
            // Abaikan bila skema sedang terkunci oleh proses migrasi
        }
    }

    /**
     * Normalisasi dan urutkan daftar target hadiah berdasarkan minimal poin (terkecil -> terbesar).
     */
    public static function normalizePrizeTiers(array $rawTiers): array
    {
        $clean = [];
        foreach ($rawTiers as $item) {
            if (! is_array($item)) {
                continue;
            }
            $minPoints = (int) ($item['min_points'] ?? 0);
            $prizeName = trim((string) ($item['prize_name'] ?? ''));
            $notes     = trim((string) ($item['notes'] ?? ''));

            if ($minPoints > 0 && $prizeName !== '') {
                $clean[] = [
                    'min_points' => $minPoints,
                    'prize_name' => $prizeName,
                    'notes'      => $notes,
                ];
            }
        }

        usort($clean, fn (array $a, array $b) => $a['min_points'] <=> $b['min_points']);

        return array_values($clean);
    }

    public function getNormalizedPrizeTiersAttribute(): array
    {
        $raw = $this->prize_tiers;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? static::normalizePrizeTiers($raw) : [];
    }

    public function getHighestMatchedPrize(int $points): ?array
    {
        $matched = null;
        foreach ($this->normalized_prize_tiers as $tier) {
            if ($points >= $tier['min_points']) {
                $matched = $tier;
            }
        }

        return $matched;
    }
}
