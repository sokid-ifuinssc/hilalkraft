<?php

namespace App\Services;

use App\Models\BazaarDoorprizeWinner;
use App\Models\BazaarEvent;
use App\Models\BazaarStoreParticipant;
use App\Models\BazaarVisitorPoint;
use App\Models\Order;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * ============================================================================
 * SERVICE: BAZAAR SERVICE (KELOLA PESERTA, QR SCAN, POIN, RANKING & DOORPRIZE)
 * ============================================================================
 * Menyediakan seluruh logika bisnis acara bazar secara terpadu:
 *   1. Check-in Kehadiran Pengunjung (Scan QR Event -> +50 poin, 1x per event)
 *   2. Kunjungan Stand Toko (Scan QR Stand -> +20 poin per stand peserta)
 *   3. Poin Transaksi Bazar (Otomatis saat order bazar dibuat, dicabut jika batal)
 *   4. Peringkat / Ranking Toko Khusus per Event Bazar (Kunjungan, Transaksi,
 *      Omzet, View Katalog Periode Bazar, & Rating)
 *   5. Peringkat / Leaderboard Pengunjung & Doorprize Panitia
 * ============================================================================
 */
class BazaarService
{
    /**
     * Mencatat kehadiran pembeli pada acara bazar (maksimal 1x klaim per event).
     *
     * @return array{ok: bool, status: string, points: int, total_points: int, message: string, event: BazaarEvent}
     */
    public function recordEventCheckin(BazaarEvent $event, User $user): array
    {
        if (! $event->isActiveNow()) {
            return [
                'ok'           => false,
                'status'       => 'event_inactive',
                'points'       => 0,
                'total_points' => $this->getUserTotalPoints($event, $user),
                'message'      => "Acara bazar '{$event->title}' sedang tidak aktif atau sudah berakhir.",
                'event'        => $event,
            ];
        }

        $existing = BazaarVisitorPoint::where('bazaar_event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('activity_type', 'event_checkin')
            ->first();

        if ($existing) {
            return [
                'ok'           => true,
                'status'       => 'already_claimed',
                'points'       => 0,
                'total_points' => $this->getUserTotalPoints($event, $user),
                'message'      => "Anda sudah melakukan check-in kehadiran di '{$event->title}' (+{$existing->points} poin sudah tercatat). Lanjutkan kunjungi stand toko peserta untuk menambah poin!",
                'event'        => $event,
            ];
        }

        $pts = max(0, (int) ($event->points_checkin ?? 50));

        BazaarVisitorPoint::create([
            'bazaar_event_id' => $event->id,
            'user_id'         => $user->id,
            'store_id'        => null,
            'order_id'        => null,
            'activity_type'   => 'event_checkin',
            'points'          => $pts,
            'description'     => "Hadir di acara bazar: {$event->title}",
        ]);

        return [
            'ok'           => true,
            'status'       => 'awarded',
            'points'       => $pts,
            'total_points' => $this->getUserTotalPoints($event, $user),
            'message'      => "Selamat! Check-in kehadiran di '{$event->title}' berhasil. Anda memperoleh +{$pts} poin!",
            'event'        => $event,
        ];
    }

    /**
     * Mencatat kunjungan pembeli pada stand toko peserta (maksimal 1x per stand per event).
     *
     * @return array{ok: bool, status: string, points: int, total_points: int, message: string, participant: BazaarStoreParticipant, event: BazaarEvent}
     */
    public function recordStandVisit(BazaarStoreParticipant $participant, User $user): array
    {
        $participant->loadMissing(['bazaarEvent', 'store']);
        $event = $participant->bazaarEvent;
        $store = $participant->store;

        if (! $event || ! $event->isActiveNow()) {
            return [
                'ok'           => false,
                'status'       => 'event_inactive',
                'points'       => 0,
                'total_points' => $event ? $this->getUserTotalPoints($event, $user) : 0,
                'message'      => 'Acara bazar untuk stand ini sedang tidak aktif.',
                'participant'  => $participant,
                'event'        => $event,
            ];
        }

        if ($participant->status !== 'approved') {
            return [
                'ok'           => false,
                'status'       => 'participant_not_approved',
                'points'       => 0,
                'total_points' => $this->getUserTotalPoints($event, $user),
                'message'      => 'Toko ini belum disetujui sebagai peserta resmi bazar.',
                'participant'  => $participant,
                'event'        => $event,
            ];
        }

        if ($store && $store->user_id === $user->id) {
            return [
                'ok'           => false,
                'status'       => 'own_store',
                'points'       => 0,
                'total_points' => $this->getUserTotalPoints($event, $user),
                'message'      => 'Pemilik toko tidak dapat memindai barcode stand tokonya sendiri untuk mendapatkan poin.',
                'participant'  => $participant,
                'event'        => $event,
            ];
        }

        $existing = BazaarVisitorPoint::where('bazaar_event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('store_id', $participant->store_id)
            ->where('activity_type', 'stand_visit')
            ->first();

        if ($existing) {
            $storeName = $store?->store_name ?? 'Stand Toko';
            return [
                'ok'           => true,
                'status'       => 'already_claimed',
                'points'       => 0,
                'total_points' => $this->getUserTotalPoints($event, $user),
                'message'      => "Anda sudah mencatat kunjungan di stand '{$storeName}'. Yuk kunjungi stand peserta lain atau bertransaksi untuk poin tambahan!",
                'participant'  => $participant,
                'event'        => $event,
            ];
        }

        $pts = max(0, (int) ($event->points_stand_visit ?? 20));
        $standLabel = $participant->stand_number ? "Stand {$participant->stand_number} — " : '';
        $storeName = $store?->store_name ?? 'Toko Peserta';

        BazaarVisitorPoint::create([
            'bazaar_event_id' => $event->id,
            'user_id'         => $user->id,
            'store_id'        => $participant->store_id,
            'order_id'        => null,
            'activity_type'   => 'stand_visit',
            'points'          => $pts,
            'description'     => "Kunjungan {$standLabel}{$storeName}",
        ]);

        // Sekalian catat ke VisitorLog toko agar statistik kunjungan toko tetap terintegrasi
        if ($store) {
            VisitorLog::create([
                'user_id'       => $user->id,
                'store_id'      => $store->id,
                'product_id'    => null,
                'visitor_type'  => $user->user_subtype ?? $user->role ?? 'buyer',
                'visitor_name'  => $user->name,
                'origin_region' => $user->kabupaten ?? 'Bazar SMK Plus Al Hilal',
                'ip_address'    => request()?->ip(),
            ]);
            $store->increment('views_count');
        }

        return [
            'ok'           => true,
            'status'       => 'awarded',
            'points'       => $pts,
            'total_points' => $this->getUserTotalPoints($event, $user),
            'message'      => "Berhasil mengunjungi {$standLabel}{$storeName}! Anda mendapat +{$pts} poin kunjungan stand.",
            'participant'  => $participant,
            'event'        => $event,
        ];
    }

    /**
     * Mencatat poin transaksi secara otomatis ketika pesanan bazar dibuat.
     */
    public function recordOrderTransactionPoints(Order $order): ?BazaarVisitorPoint
    {
        if (! $order->bazaar_event_id || $order->effective_progress === 'dibatalkan') {
            return null;
        }

        $order->loadMissing(['bazaarEvent', 'store']);
        $event = $order->bazaarEvent;
        if (! $event || ! $event->isActiveNow()) {
            return null;
        }

        // Jangan berikan poin belanja fiktif jika pemilik toko membeli dari tokonya sendiri
        if ($order->store && $order->store->user_id === $order->buyer_id) {
            return null;
        }

        $existing = BazaarVisitorPoint::where('order_id', $order->id)
            ->where('activity_type', 'bazaar_transaction')
            ->first();

        if ($existing) {
            return $existing;
        }

        $pts = max(0, (int) ($event->points_transaction ?? 30));
        if ($pts === 0) {
            return null;
        }

        $storeName = $order->store?->store_name ?? 'Toko Bazar';

        return BazaarVisitorPoint::create([
            'bazaar_event_id' => $event->id,
            'user_id'         => $order->buyer_id,
            'store_id'        => $order->store_id,
            'order_id'        => $order->id,
            'activity_type'   => 'bazaar_transaction',
            'points'          => $pts,
            'description'     => "Transaksi #{$order->invoice_number} di {$storeName}",
        ]);
    }

    /**
     * Mencabut poin transaksi apabila pesanan dibatalkan (mencegah manipulasi poin).
     */
    public function syncCancelledOrderPoints(Order $order): void
    {
        if ($order->effective_progress === 'dibatalkan') {
            BazaarVisitorPoint::where('order_id', $order->id)
                ->where('activity_type', 'bazaar_transaction')
                ->delete();
        }
    }

    /**
     * Menyelesaikan hasil scan dari URL QR penuh, token panjang, maupun kode pendek manual (BZ-... / ST-...).
     *
     * @return array<string, mixed>
     */
    public function resolveScanInput(string $rawInput, User $user): array
    {
        $clean = trim($rawInput);
        if ($clean === '') {
            return ['ok' => false, 'status' => 'invalid', 'points' => 0, 'total_points' => 0, 'message' => 'Kode atau QR tidak boleh kosong.'];
        }

        // Ekstrak segmen terakhir apabila input berupa URL lengkap (hasil scan kamera)
        if (str_contains($clean, '/')) {
            $path = parse_url($clean, PHP_URL_PATH) ?: $clean;
            $segments = array_values(array_filter(explode('/', trim($path, '/'))));
            if (! empty($segments)) {
                $clean = end($segments);
            }
        }

        $upper = strtoupper($clean);

        // 1. Cek kecocokan Event Bazar (checkin_token atau short_code BZ-...)
        $event = BazaarEvent::where('checkin_token', $clean)
            ->orWhere('short_code', $upper)
            ->first();

        if ($event) {
            return $this->recordEventCheckin($event, $user);
        }

        // 2. Cek kecocokan Stand Toko Peserta (stand_token atau stand_code ST-...)
        $participant = BazaarStoreParticipant::where('stand_token', $clean)
            ->orWhere('stand_code', $upper)
            ->first();

        if ($participant) {
            return $this->recordStandVisit($participant, $user);
        }

        return [
            'ok'           => false,
            'status'       => 'not_found',
            'points'       => 0,
            'total_points' => 0,
            'message'      => "Kode QR / kode check-in '{$clean}' tidak dikenali atau sudah kedaluwarsa.",
        ];
    }

    public function getUserTotalPoints(BazaarEvent $event, User $user): int
    {
        return max(0, (int) BazaarVisitorPoint::where('bazaar_event_id', $event->id)
            ->where('user_id', $user->id)
            ->sum('points'));
    }

    /**
     * Menghitung Ranking Toko KHUSUS untuk Event Bazar ini:
     * Mengukur kunjungan stand (scan QR stand), jumlah transaksi bazar, item terjual,
     * omzet penjualan bazar, kunjungan katalog periode bazar, dan rating bazar.
     */
    public function getStoreRankings(BazaarEvent $event, string $sortBy = 'score'): Collection
    {
        $participants = BazaarStoreParticipant::with(['store.owner', 'store.products'])
            ->where('bazaar_event_id', $event->id)
            ->where('status', 'approved')
            ->get();

        if ($participants->isEmpty()) {
            return collect();
        }

        $storeIds = $participants->pluck('store_id')->filter()->values();

        // Hitung scan kunjungan stand per toko pada bazar ini
        $standVisits = BazaarVisitorPoint::where('bazaar_event_id', $event->id)
            ->where('activity_type', 'stand_visit')
            ->whereIn('store_id', $storeIds)
            ->selectRaw('store_id, count(*) as total')
            ->groupBy('store_id')
            ->pluck('total', 'store_id');

        // Hitung pesanan valid khusus event bazar ini
        $orders = Order::with('items')
            ->where('bazaar_event_id', $event->id)
            ->whereIn('store_id', $storeIds)
            ->get()
            ->reject(fn (Order $o) => $o->effective_progress === 'dibatalkan');

        $ordersByStore = $orders->groupBy('store_id');

        // Hitung kunjungan katalog (VisitorLog) toko selama rentang tanggal acara bazar
        $fromDate = $event->start_date ? Carbon::parse($event->start_date)->startOfDay() : Carbon::parse($event->created_at)->startOfDay();
        $toDate = $event->end_date ? Carbon::parse($event->end_date)->endOfDay() : Carbon::now()->endOfDay();

        $catalogViews = VisitorLog::whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('store_id, count(*) as total')
            ->groupBy('store_id')
            ->pluck('total', 'store_id');

        $rows = $participants->map(function (BazaarStoreParticipant $participant) use ($standVisits, $ordersByStore, $catalogViews) {
            $store = $participant->store;
            if (! $store) {
                return null;
            }

            $storeOrders = $ordersByStore->get($store->id, collect());
            $visitsCount = (int) ($standVisits[$store->id] ?? 0);
            $viewsCount  = (int) ($catalogViews[$store->id] ?? 0);
            $ordersCount = $storeOrders->count();
            $itemsSold   = (int) $storeOrders->sum(fn (Order $o) => $o->items->sum('quantity'));
            $revenue     = (float) $storeOrders->sum('total_price');
            $uniqueBuyers = $storeOrders->pluck('buyer_id')->unique()->count();
            $ratings     = $storeOrders->pluck('buyer_rating')->filter(fn ($r) => (int) $r > 0);
            $avgRating   = $ratings->isNotEmpty() ? round((float) $ratings->avg(), 1) : 0.0;

            // Skor Komposit Bazar: menggabungkan kunjungan stand, transaksi, omzet, view katalog, & rating
            $compositeScore = round(
                ($revenue / 1000) +
                ($ordersCount * 25) +
                ($visitsCount * 15) +
                ($viewsCount * 1) +
                ($avgRating * 10),
                1
            );

            return (object) [
                'participant'         => $participant,
                'store'               => $store,
                'stand_number'        => $participant->stand_number ?: '-',
                'stand_visits_count'  => $visitsCount,
                'catalog_views_count' => $viewsCount,
                'orders_count'        => $ordersCount,
                'items_sold'          => $itemsSold,
                'revenue'             => $revenue,
                'unique_buyers_count' => $uniqueBuyers,
                'avg_rating'          => $avgRating,
                'rating_count'        => $ratings->count(),
                'composite_score'     => $compositeScore,
            ];
        })->filter();

        $sorted = match ($sortBy) {
            'visits'  => $rows->sortByDesc('stand_visits_count'),
            'orders'  => $rows->sortByDesc('orders_count'),
            'revenue' => $rows->sortByDesc('revenue'),
            'views'   => $rows->sortByDesc('catalog_views_count'),
            'rating'  => $rows->sortByDesc('avg_rating'),
            default   => $rows->sortByDesc('composite_score'),
        };

        return $sorted->values()->map(function ($row, $index) {
            $row->rank = $index + 1;
            return $row;
        });
    }

    /**
     * Menghitung Leaderboard / Ranking Pengunjung & Pembeli pada Event Bazar ini untuk Doorprize Panitia.
     */
    public function getVisitorRankings(BazaarEvent $event): Collection
    {
        $pointLogs = BazaarVisitorPoint::with('user')
            ->where('bazaar_event_id', $event->id)
            ->orderBy('created_at')
            ->get();

        if ($pointLogs->isEmpty()) {
            return collect();
        }

        $winnersByUser = BazaarDoorprizeWinner::where('bazaar_event_id', $event->id)
            ->get()
            ->keyBy('user_id');

        $grouped = $pointLogs->groupBy('user_id')->map(function (Collection $logs, $userId) use ($winnersByUser) {
            $user = $logs->first()->user;
            if (! $user) {
                return null;
            }

            $checkinLogs = $logs->where('activity_type', 'event_checkin');
            $standLogs   = $logs->where('activity_type', 'stand_visit');
            $trxLogs     = $logs->where('activity_type', 'bazaar_transaction');
            $bonusLogs   = $logs->where('activity_type', 'bonus_admin');

            return (object) [
                'user'               => $user,
                'checkin_count'      => $checkinLogs->count(),
                'checkin_points'     => (int) $checkinLogs->sum('points'),
                'stand_visits_count' => $standLogs->count(),
                'stand_visit_points' => (int) $standLogs->sum('points'),
                'transactions_count' => $trxLogs->count(),
                'transaction_points' => (int) $trxLogs->sum('points'),
                'bonus_points'       => (int) $bonusLogs->sum('points'),
                'total_points'       => max(0, (int) $logs->sum('points')),
                'last_activity_at'   => $logs->max('created_at'),
                'doorprize_winner'   => $winnersByUser->get($userId),
            ];
        })->filter();

        return $grouped->sort(function ($a, $b) {
            if ($a->total_points !== $b->total_points) {
                return $b->total_points <=> $a->total_points;
            }
            if ($a->transactions_count !== $b->transactions_count) {
                return $b->transactions_count <=> $a->transactions_count;
            }
            if ($a->stand_visits_count !== $b->stand_visits_count) {
                return $b->stand_visits_count <=> $a->stand_visits_count;
            }
            return ($a->last_activity_at?->timestamp ?? 0) <=> ($b->last_activity_at?->timestamp ?? 0);
        })->values()->map(function ($row, $idx) {
            $row->rank = $idx + 1;
            return $row;
        });
    }
}
