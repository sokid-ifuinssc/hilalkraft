<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductLike;
use App\Models\Store;
use App\Models\User;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ============================================================================
 * CONTROLLER: REPORT CONTROLLER (LAPORAN PER ROLE + FILTER TANGGAL)
 * ============================================================================
 * Fungsi:
 *   Menyajikan laporan berbentuk grafik & tabel detail untuk setiap role:
 *   1. Penjual (`seller`)  : laporan penjualan, barang terjual, produk terlaris,
 *      produk paling disukai/dilihat/dikomentari, rating, dan saran evaluasi.
 *   2. Pembeli (`buyer`)   : rincian transaksi pribadi, total belanja & hemat.
 *   3. Admin & Super Admin : rekap keseluruhan marketplace (omzet, pesanan,
 *      pengguna baru, kunjungan, rekap per toko).
 *   Semua laporan dapat difilter harian, bulanan, tahunan, atau rentang tanggal
 *   bebas (dari tanggal ... sampai tanggal ...) dan diunduh sebagai CSV.
 * Algoritma:
 *   1. `resolveRange()` menerjemahkan parameter filter menjadi rentang [from, to]
 *      serta satuan pengelompokan grafik (hari / bulan / tahun).
 *   2. `buckets()` membuat label sumbu-X, lalu data dijumlahkan per bucket.
 *   3. Periode sebelumnya (panjang sama) dihitung untuk persentase pertumbuhan.
 * ============================================================================
 */
class ReportController extends Controller
{
    private const STATUS_LABELS = [
        'pesan'      => 'Dipesan',
        'kemas'      => 'Dikemas',
        'kirim'      => 'Dikirim',
        'sampai'     => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    /* ------------------------------------------------------------------ */
    /* 1. LAPORAN PENJUAL                                                  */
    /* ------------------------------------------------------------------ */
    public function seller(Request $request): View|StreamedResponse|RedirectResponse
    {
        $store = $request->user()->store;
        if (! $store) {
            return redirect()->route('seller.dashboard')
                ->with('error', 'Toko belum tersedia. Lengkapi profil toko terlebih dahulu.');
        }

        $range   = $this->resolveRange($request);
        $buckets = $this->buckets($range);

        $orders = Order::with(['buyer:id,name,user_subtype,phone', 'items.product:id,product_name'])
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->latest()
            ->get();

        if ($request->query('export') === 'csv') {
            return $this->exportOrders($orders, 'laporan-penjualan-' . $store->slug, true);
        }

        $valid     = $orders->reject(fn (Order $o) => $o->effective_progress === 'dibatalkan');
        $prevValid = $this->previousOrders($range, fn ($q) => $q->where('store_id', $store->id));

        $itemsSold = (int) $valid->sum(fn (Order $o) => $o->items->sum('quantity'));
        $revenue   = (float) $valid->sum('total_price');
        $ratings   = $orders->pluck('buyer_rating')->filter(fn ($r) => (int) $r > 0);

        $kpi = [
            'revenue'        => $revenue,
            'revenue_growth' => $this->growth($revenue, (float) $prevValid->sum('total_price')),
            'orders'         => $valid->count(),
            'orders_growth'  => $this->growth($valid->count(), $prevValid->count()),
            'items_sold'     => $itemsSold,
            'avg_order'      => $valid->count() > 0 ? $revenue / $valid->count() : 0,
            'discount_given' => (float) $valid->sum(fn (Order $o) => $o->total_discount_amount),
            'rating'         => $ratings->isNotEmpty() ? round((float) $ratings->avg(), 1) : null,
            'rating_count'   => $ratings->count(),
            'completed'      => $orders->where('effective_progress', 'sampai')->count(),
            'cancelled'      => $orders->count() - $valid->count(),
        ];

        // Grafik tren omzet & jumlah pesanan
        $trend = $this->trendSeries($valid, $buckets, $range['group']);

        // Statistik per produk pada periode terpilih
        $products   = $store->products()->get(['id', 'product_name', 'price', 'stock', 'views_count', 'likes_count', 'image']);
        $productIds = $products->pluck('id');
        $likes      = $this->countByProduct(ProductLike::query(), $productIds, $range);
        $views      = $this->countByProduct(VisitorLog::query(), $productIds, $range);
        $comments   = $this->countByProduct(ProductComment::query(), $productIds, $range);

        $soldPerProduct = [];
        foreach ($valid as $order) {
            foreach ($order->items as $item) {
                $soldPerProduct[$item->product_id]['qty']     = ($soldPerProduct[$item->product_id]['qty'] ?? 0) + (int) $item->quantity;
                $soldPerProduct[$item->product_id]['revenue'] = ($soldPerProduct[$item->product_id]['revenue'] ?? 0) + (float) $item->subtotal;
            }
        }

        $productStats = $products->map(function (Product $p) use ($soldPerProduct, $likes, $views, $comments) {
            $p->period_sold     = (int) ($soldPerProduct[$p->id]['qty'] ?? 0);
            $p->period_revenue  = (float) ($soldPerProduct[$p->id]['revenue'] ?? 0);
            $p->period_likes    = (int) ($likes[$p->id] ?? 0);
            $p->period_views    = (int) ($views[$p->id] ?? 0);
            $p->period_comments = (int) ($comments[$p->id] ?? 0);
            $p->conversion      = $p->period_views > 0 ? round($p->period_sold / $p->period_views * 100, 1) : null;

            return $p;
        })->sortByDesc('period_sold')->values();

        $buyerTypes = $valid->groupBy(fn (Order $o) => Store::getBuyerSubtypeLabel($o->buyer))
            ->map->count()->sortDesc();

        $insights = $this->sellerInsights($kpi, $productStats);

        return view('reports.seller', [
            'store'        => $store,
            'range'        => $range,
            'kpi'          => $kpi,
            'orders'       => $orders,
            'productStats' => $productStats,
            'insights'     => $insights,
            'charts'       => [
                'trend'      => $trend,
                'status'     => $this->statusSeries($orders),
                'buyerTypes' => ['labels' => $buyerTypes->keys()->values(), 'values' => $buyerTypes->values()],
                'topProducts' => [
                    'labels' => $productStats->take(7)->pluck('product_name')->values(),
                    'sold'   => $productStats->take(7)->pluck('period_sold')->values(),
                    'likes'  => $productStats->take(7)->pluck('period_likes')->values(),
                ],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 2. LAPORAN PEMBELI                                                  */
    /* ------------------------------------------------------------------ */
    public function buyer(Request $request): View|StreamedResponse
    {
        $user    = $request->user();
        $range   = $this->resolveRange($request);
        $buckets = $this->buckets($range);

        $orders = Order::with(['store:id,store_name,slug', 'items.product:id,product_name,slug'])
            ->where('buyer_id', $user->id)
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->latest()
            ->get();

        if ($request->query('export') === 'csv') {
            return $this->exportOrders($orders, 'riwayat-belanja-' . now()->format('Ymd'), false);
        }

        $valid     = $orders->reject(fn (Order $o) => $o->effective_progress === 'dibatalkan');
        $prevValid = $this->previousOrders($range, fn ($q) => $q->where('buyer_id', $user->id));
        $spent     = (float) $valid->sum('total_price');

        $favoriteStores = $valid->groupBy(fn (Order $o) => $o->store?->store_name ?? 'Toko')
            ->map(fn (Collection $g) => ['orders' => $g->count(), 'total' => (float) $g->sum('total_price')])
            ->sortByDesc('total');

        $kpi = [
            'spent'        => $spent,
            'spent_growth' => $this->growth($spent, (float) $prevValid->sum('total_price')),
            'orders'       => $valid->count(),
            'items'        => (int) $valid->sum(fn (Order $o) => $o->items->sum('quantity')),
            'saved'        => (float) $valid->sum(fn (Order $o) => $o->total_discount_amount),
            'in_progress'  => $orders->whereIn('effective_progress', ['pesan', 'kemas', 'kirim'])->count(),
            'completed'    => $orders->where('effective_progress', 'sampai')->count(),
            'unrated'      => $orders->where('effective_progress', 'sampai')->filter(fn ($o) => ! $o->buyer_rating)->count(),
        ];

        return view('reports.buyer', [
            'range'          => $range,
            'kpi'            => $kpi,
            'orders'         => $orders,
            'favoriteStores' => $favoriteStores,
            'charts'         => [
                'trend'  => $this->trendSeries($valid, $buckets, $range['group']),
                'status' => $this->statusSeries($orders),
                'stores' => [
                    'labels' => $favoriteStores->keys()->take(6)->values(),
                    'values' => $favoriteStores->take(6)->pluck('total')->values(),
                ],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 3. LAPORAN REKAP ADMIN & SUPER ADMIN                                */
    /* ------------------------------------------------------------------ */
    public function admin(Request $request): View|StreamedResponse
    {
        $range   = $this->resolveRange($request);
        $buckets = $this->buckets($range);
        $between = [$range['from'], $range['to']];

        $orders = Order::with(['buyer:id,name,user_subtype,phone', 'store:id,store_name,student_class,seller_type', 'items:id,order_id,product_id,quantity,subtotal', 'items.product:id,product_name'])
            ->whereBetween('created_at', $between)
            ->latest()
            ->get();

        if ($request->query('export') === 'csv') {
            return $this->exportOrders($orders, 'rekap-transaksi-hilalkraf', true, true);
        }

        $valid     = $orders->reject(fn (Order $o) => $o->effective_progress === 'dibatalkan');
        $prevValid = $this->previousOrders($range, fn ($q) => $q);
        $revenue   = (float) $valid->sum('total_price');
        $ratings   = $orders->pluck('buyer_rating')->filter(fn ($r) => (int) $r > 0);

        $newUsers  = User::whereBetween('created_at', $between)->get(['id', 'role', 'created_at']);
        $visitRows = VisitorLog::whereBetween('created_at', $between)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->get();
        $totalVisits = (int) $visitRows->sum('c');

        $kpi = [
            'revenue'        => $revenue,
            'revenue_growth' => $this->growth($revenue, (float) $prevValid->sum('total_price')),
            'orders'         => $valid->count(),
            'orders_growth'  => $this->growth($valid->count(), $prevValid->count()),
            'items_sold'     => (int) $valid->sum(fn (Order $o) => $o->items->sum('quantity')),
            'discount_given' => (float) $valid->sum(fn (Order $o) => $o->total_discount_amount),
            'new_users'      => $newUsers->count(),
            'new_stores'     => Store::whereBetween('created_at', $between)->count(),
            'new_products'   => Product::whereBetween('created_at', $between)->count(),
            'visits'         => $totalVisits,
            'rating'         => $ratings->isNotEmpty() ? round((float) $ratings->avg(), 1) : null,
            'active_stores'  => $valid->pluck('store_id')->unique()->count(),
            'total_stores'   => Store::count(),
        ];

        // Grafik pengguna baru per role (stacked)
        $roleLabels = ['buyer' => 'Pembeli', 'seller' => 'Penjual', 'admin' => 'Admin', 'super_admin' => 'Super Admin'];
        $userSeries = [];
        foreach ($roleLabels as $role => $label) {
            $series = array_fill_keys(array_keys($buckets), 0);
            foreach ($newUsers->where('role', $role) as $u) {
                $key = $this->bucketKey(Carbon::parse($u->created_at), $range['group']);
                if (isset($series[$key])) {
                    $series[$key]++;
                }
            }
            $userSeries[] = ['label' => $label, 'data' => array_values($series)];
        }

        $visitSeries = array_fill_keys(array_keys($buckets), 0);
        foreach ($visitRows as $row) {
            $key = $this->bucketKey(Carbon::parse($row->d), $range['group']);
            if (isset($visitSeries[$key])) {
                $visitSeries[$key] += (int) $row->c;
            }
        }

        // Rekap per toko
        $visitsPerStore = VisitorLog::whereBetween('created_at', $between)
            ->whereNotNull('store_id')
            ->select('store_id', DB::raw('COUNT(*) as c'))
            ->groupBy('store_id')
            ->pluck('c', 'store_id');

        $ordersByStore = $orders->groupBy('store_id');
        $storeRecap = Store::with('owner:id,name')
            ->withCount('products')
            ->get(['id', 'user_id', 'store_name', 'slug', 'student_class', 'seller_type', 'is_verified'])
            ->map(function (Store $s) use ($ordersByStore, $visitsPerStore) {
                $all   = $ordersByStore->get($s->id, collect());
                $ok    = $all->reject(fn (Order $o) => $o->effective_progress === 'dibatalkan');
                $rates = $all->pluck('buyer_rating')->filter(fn ($r) => (int) $r > 0);

                $s->period_orders  = $ok->count();
                $s->period_items   = (int) $ok->sum(fn (Order $o) => $o->items->sum('quantity'));
                $s->period_revenue = (float) $ok->sum('total_price');
                $s->period_rating  = $rates->isNotEmpty() ? round((float) $rates->avg(), 1) : null;
                $s->period_visits  = (int) ($visitsPerStore[$s->id] ?? 0);

                return $s;
            })
            ->sortByDesc('period_revenue')
            ->values();

        // Produk terlaris seluruh marketplace
        $soldPerProduct = [];
        foreach ($valid as $order) {
            foreach ($order->items as $item) {
                $soldPerProduct[$item->product_id] = ($soldPerProduct[$item->product_id] ?? 0) + (int) $item->quantity;
            }
        }
        arsort($soldPerProduct);
        $topProductIds = array_slice(array_keys($soldPerProduct), 0, 10, true);
        $topProducts = Product::with('store:id,store_name')
            ->whereIn('id', $topProductIds)
            ->get(['id', 'store_id', 'product_name', 'price', 'likes_count', 'views_count'])
            ->map(function (Product $p) use ($soldPerProduct) {
                $p->period_sold = $soldPerProduct[$p->id] ?? 0;
                return $p;
            })
            ->sortByDesc('period_sold')
            ->values();

        $buyerTypes = $valid->groupBy(fn (Order $o) => Store::getBuyerSubtypeLabel($o->buyer))
            ->map->count()->sortDesc();

        return view('reports.admin', [
            'range'       => $range,
            'kpi'         => $kpi,
            'orders'      => $orders,
            'storeRecap'  => $storeRecap,
            'topProducts' => $topProducts,
            'charts'      => [
                'trend'      => $this->trendSeries($valid, $buckets, $range['group']),
                'status'     => $this->statusSeries($orders),
                'users'      => ['labels' => array_values($buckets), 'datasets' => $userSeries],
                'visits'     => ['labels' => array_values($buckets), 'values' => array_values($visitSeries)],
                'buyerTypes' => ['labels' => $buyerTypes->keys()->values(), 'values' => $buyerTypes->values()],
                'stores'     => [
                    'labels'  => $storeRecap->take(8)->pluck('store_name')->values(),
                    'revenue' => $storeRecap->take(8)->pluck('period_revenue')->values(),
                ],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* HELPER: FILTER TANGGAL & PENGELOMPOKAN                              */
    /* ------------------------------------------------------------------ */

    /**
     * Menerjemahkan parameter filter (`preset`, `date`, `month`, `year`,
     * `from`, `to`, `group`) menjadi rentang tanggal & satuan grafik.
     */
    private function resolveRange(Request $request): array
    {
        Carbon::setLocale('id');
        $now    = now();
        $preset = (string) $request->query('preset', 'this_month');

        $parse = function (?string $value, Carbon $fallback, string $format = 'Y-m-d'): Carbon {
            try {
                return $value ? Carbon::createFromFormat($format, $value) : $fallback->copy();
            } catch (\Throwable) {
                return $fallback->copy();
            }
        };

        switch ($preset) {
            case 'today':
                [$from, $to] = [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
                break;
            case '7d':
                [$from, $to] = [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()];
                break;
            case '30d':
                [$from, $to] = [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
                break;
            case 'last_month':
                $lm = $now->copy()->subMonthNoOverflow();
                [$from, $to] = [$lm->copy()->startOfMonth(), $lm->copy()->endOfMonth()];
                break;
            case 'this_year':
                [$from, $to] = [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
                break;
            case 'day':
                $d = $parse($request->query('date'), $now);
                [$from, $to] = [$d->copy()->startOfDay(), $d->copy()->endOfDay()];
                break;
            case 'month':
                $m = $parse(($request->query('month') ?: $now->format('Y-m')) . '-01', $now);
                [$from, $to] = [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()];
                break;
            case 'year':
                $y = (int) $request->query('year', $now->year);
                $y = ($y >= 2000 && $y <= 2100) ? $y : $now->year;
                [$from, $to] = [Carbon::create($y)->startOfYear(), Carbon::create($y)->endOfYear()];
                break;
            case 'custom':
                $from = $parse($request->query('from'), $now->copy()->startOfMonth())->startOfDay();
                $to   = $parse($request->query('to'), $now)->endOfDay();
                if ($from->gt($to)) {
                    [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                }
                break;
            default:
                $preset = 'this_month';
                [$from, $to] = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }

        $days  = (int) floor(abs($from->diffInDays($to))) + 1;
        $group = (string) $request->query('group', 'auto');
        if (! in_array($group, ['day', 'month', 'year'], true)) {
            $group = $days <= 62 ? 'day' : ($days <= 731 ? 'month' : 'year');
        }

        $groupLabel = ['day' => 'per hari', 'month' => 'per bulan', 'year' => 'per tahun'][$group];

        return [
            'preset'      => $preset,
            'from'        => $from,
            'to'          => $to,
            'days'        => $days,
            'group'       => $group,
            'group_label' => $groupLabel,
            'label'       => $from->isSameDay($to)
                ? $from->translatedFormat('d F Y')
                : $from->translatedFormat('d M Y') . ' – ' . $to->translatedFormat('d M Y'),
        ];
    }

    /** Label sumbu-X grafik, key => label. */
    private function buckets(array $range): array
    {
        $unit   = $range['group'];
        $cursor = $range['from']->copy()->startOf($unit);
        $labels = [];
        $guard  = 0;

        while ($cursor->lte($range['to']) && $guard++ < 400) {
            $labels[$this->bucketKey($cursor, $unit)] = match ($unit) {
                'day'   => $cursor->translatedFormat('d M'),
                'month' => $cursor->translatedFormat('M Y'),
                default => $cursor->format('Y'),
            };
            $cursor->add(1, $unit);
        }

        return $labels;
    }

    private function bucketKey(Carbon $date, string $group): string
    {
        return $date->format(['day' => 'Y-m-d', 'month' => 'Y-m', 'year' => 'Y'][$group]);
    }

    private function trendSeries(Collection $orders, array $buckets, string $group): array
    {
        $revenue = array_fill_keys(array_keys($buckets), 0);
        $count   = array_fill_keys(array_keys($buckets), 0);

        foreach ($orders as $order) {
            $key = $this->bucketKey($order->created_at, $group);
            if (isset($revenue[$key])) {
                $revenue[$key] += (float) $order->total_price;
                $count[$key]++;
            }
        }

        return [
            'labels'  => array_values($buckets),
            'revenue' => array_values($revenue),
            'orders'  => array_values($count),
        ];
    }

    private function statusSeries(Collection $orders): array
    {
        $counts = [];
        foreach (self::STATUS_LABELS as $key => $label) {
            $counts[$label] = $orders->where('effective_progress', $key)->count();
        }

        return ['labels' => array_keys($counts), 'values' => array_values($counts)];
    }

    private function countByProduct($query, Collection $productIds, array $range): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        return $query->whereIn('product_id', $productIds)
            ->whereBetween('created_at', [$range['from'], $range['to']])
            ->select('product_id', DB::raw('COUNT(*) as c'))
            ->groupBy('product_id')
            ->pluck('c', 'product_id');
    }

    /** Pesanan valid pada periode sebelumnya dengan panjang yang sama. */
    private function previousOrders(array $range, callable $scope): Collection
    {
        $prevTo   = $range['from']->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($range['days'] - 1)->startOfDay();

        return $scope(Order::query())
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->get(['id', 'total_price', 'status', 'progress_status'])
            ->reject(fn (Order $o) => $o->effective_progress === 'dibatalkan');
    }

    private function growth(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous == 0.0) {
            return (float) $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** Saran evaluasi otomatis untuk penjual. */
    private function sellerInsights(array $kpi, Collection $productStats): array
    {
        $tips = [];

        if ($kpi['orders'] === 0) {
            $tips[] = ['type' => 'warn', 'text' => 'Belum ada transaksi pada periode ini. Coba bagikan link toko ke WhatsApp/Instagram dan ikut acara bazar SMK.'];
        }

        if ($best = $productStats->firstWhere('period_sold', '>', 0)) {
            $tips[] = ['type' => 'good', 'text' => "Produk terlaris: {$best->product_name} ({$best->period_sold} terjual). Pastikan stoknya selalu tersedia."];
        }

        if ($liked = $productStats->sortByDesc('period_likes')->firstWhere('period_likes', '>', 0)) {
            $tips[] = ['type' => 'good', 'text' => "Paling disukai: {$liked->product_name} ({$liked->period_likes} suka). Cocok dijadikan produk unggulan/promo."];
        }

        foreach ($productStats->filter(fn ($p) => $p->period_views >= 5 && $p->period_sold === 0)->take(3) as $p) {
            $tips[] = ['type' => 'warn', 'text' => "{$p->product_name} dilihat {$p->period_views}× tapi belum terjual. Perbaiki foto, deskripsi, atau pertimbangkan harga/diskon."];
        }

        foreach ($productStats->filter(fn ($p) => $p->stock <= 3)->take(3) as $p) {
            $tips[] = ['type' => 'warn', 'text' => "Stok {$p->product_name} tinggal {$p->stock}. Segera tambah stok."];
        }

        if ($kpi['rating'] !== null && $kpi['rating'] < 4) {
            $tips[] = ['type' => 'warn', 'text' => "Rata-rata rating {$kpi['rating']}/5. Tingkatkan kualitas kemasan dan kecepatan respon WhatsApp."];
        }

        if ($kpi['revenue_growth'] !== null) {
            $tips[] = $kpi['revenue_growth'] >= 0
                ? ['type' => 'good', 'text' => "Omzet naik {$kpi['revenue_growth']}% dibanding periode sebelumnya. Pertahankan!"]
                : ['type' => 'warn', 'text' => 'Omzet turun ' . abs($kpi['revenue_growth']) . '% dibanding periode sebelumnya. Evaluasi promosi dan variasi produk.'];
        }

        return $tips;
    }

    /** Ekspor transaksi ke CSV (bisa dibuka di Excel). */
    private function exportOrders(Collection $orders, string $filename, bool $withBuyer, bool $withStore = false): StreamedResponse
    {
        return response()->streamDownload(function () use ($orders, $withBuyer, $withStore) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $header = ['Tanggal', 'Invoice'];
            if ($withStore) {
                $header[] = 'Toko';
            }
            if ($withBuyer) {
                array_push($header, 'Pembeli', 'Jenis Pembeli');
            } else {
                $header[] = 'Toko';
            }
            array_push($header, 'Produk', 'Jumlah Barang', 'Subtotal', 'Diskon', 'Total', 'Status', 'Rating');
            fputcsv($out, $header, ';');

            foreach ($orders as $o) {
                $row = [$o->created_at->format('Y-m-d H:i'), $o->order_code];
                if ($withStore) {
                    $row[] = $o->store?->store_name;
                }
                if ($withBuyer) {
                    array_push($row, $o->buyer?->name, Store::getBuyerSubtypeLabel($o->buyer));
                } else {
                    $row[] = $o->store?->store_name;
                }
                array_push(
                    $row,
                    $o->items->map(fn ($i) => ($i->product?->product_name ?? 'Produk') . ' x' . $i->quantity)->implode(', '),
                    $o->items->sum('quantity'),
                    (float) ($o->subtotal_price > 0 ? $o->subtotal_price : $o->total_price),
                    $o->total_discount_amount,
                    (float) $o->total_price,
                    $o->progress_label,
                    $o->buyer_rating ?: ''
                );
                fputcsv($out, $row, ';');
            }

            fclose($out);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
