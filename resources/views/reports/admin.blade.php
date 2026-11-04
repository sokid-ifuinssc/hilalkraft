{{--
============================================================================
VIEW: LAPORAN & REKAP ADMIN / SUPER ADMIN (reports/admin.blade.php)
============================================================================
Fungsi:
  Rekap keseluruhan marketplace hilalkraf: omzet, pesanan, barang terjual,
  pengguna/toko/produk baru, kunjungan, rating; grafik tren, status pesanan,
  jenis pembeli, pengguna baru per role, kunjungan; rekap per toko, produk
  terlaris, dan seluruh transaksi. Filter harian/bulanan/tahunan/rentang.
============================================================================
--}}
@extends('layouts.panel')

@section('title', 'Laporan & Rekap Marketplace — hilalkraf')
@section('panel_title', 'Laporan & Rekap')

@section('panel')
@php $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); @endphp

<div>
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Laporan & Rekap Marketplace</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Rekapitulasi seluruh toko, transaksi, dan pengguna hilalkraf SMK Plus Al Hilal.</p>
</div>

<x-report-filter :action="route('admin.reports')" :range="$range" />

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    @include('reports.partials.kpi', ['icon' => '💰', 'label' => 'Total omzet', 'value' => $rp($kpi['revenue']), 'growth' => $kpi['revenue_growth'], 'hint' => 'vs periode lalu'])
    @include('reports.partials.kpi', ['icon' => '🧾', 'label' => 'Pesanan', 'value' => number_format($kpi['orders']), 'growth' => $kpi['orders_growth'], 'hint' => number_format($kpi['items_sold']) . ' barang terjual'])
    @include('reports.partials.kpi', ['icon' => '🏪', 'label' => 'Toko aktif berjualan', 'value' => $kpi['active_stores'] . ' / ' . $kpi['total_stores'], 'hint' => '+' . $kpi['new_stores'] . ' toko baru'])
    @include('reports.partials.kpi', ['icon' => '⭐', 'label' => 'Rating rata-rata', 'value' => $kpi['rating'] !== null ? $kpi['rating'] . ' / 5' : '—', 'hint' => 'Diskon diberikan ' . $rp($kpi['discount_given'])])
    @include('reports.partials.kpi', ['icon' => '👥', 'label' => 'Pengguna baru', 'value' => number_format($kpi['new_users'])])
    @include('reports.partials.kpi', ['icon' => '🎨', 'label' => 'Produk baru', 'value' => number_format($kpi['new_products'])])
    @include('reports.partials.kpi', ['icon' => '👁️', 'label' => 'Kunjungan tercatat', 'value' => number_format($kpi['visits'])])
    @include('reports.partials.kpi', ['icon' => '🛒', 'label' => 'Rata-rata/pesanan', 'value' => $rp($kpi['orders'] > 0 ? $kpi['revenue'] / $kpi['orders'] : 0)])
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">📈 Tren omzet & pesanan</h2>
        <div class="h-64 sm:h-72"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">🚚 Status pesanan</h2>
        <div class="h-64 sm:h-72"><canvas id="chartStatus"></canvas></div>
    </div>
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">🏆 Omzet per toko (8 teratas)</h2>
        <div class="h-64 sm:h-72"><canvas id="chartStores"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">👥 Jenis pembeli</h2>
        <div class="h-64 sm:h-72"><canvas id="chartBuyers"></canvas></div>
    </div>
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">🆕 Pengguna baru per role</h2>
        <div class="h-64"><canvas id="chartUsers"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">👁️ Kunjungan</h2>
        <div class="h-64"><canvas id="chartVisits"></canvas></div>
    </div>
</div>

{{-- Rekap per toko --}}
<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3">
        <h2 class="text-sm font-extrabold">🏪 Rekap per toko</h2>
        <p class="text-[11px] text-slate-500">Diurutkan dari omzet tertinggi pada periode terpilih.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">#</th>
                    <th class="text-left px-3 py-2.5 font-bold">Toko</th>
                    <th class="text-left px-3 py-2.5 font-bold">Kelas / Jenis</th>
                    <th class="text-right px-3 py-2.5 font-bold">Produk</th>
                    <th class="text-right px-3 py-2.5 font-bold">Pesanan</th>
                    <th class="text-right px-3 py-2.5 font-bold">Terjual</th>
                    <th class="text-right px-3 py-2.5 font-bold">Omzet</th>
                    <th class="text-right px-3 py-2.5 font-bold">Kunjungan</th>
                    <th class="text-right px-5 py-2.5 font-bold">Rating</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($storeRecap as $i => $s)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-2.5 text-slate-400">{{ $i + 1 }}</td>
                        <td class="px-3 py-2.5 min-w-[10rem]">
                            <span class="font-semibold text-[#0F291E]">{{ $s->store_name }}</span>
                            <span class="block text-[10px] text-slate-400">{{ $s->owner?->name }} {{ $s->is_verified ? '· ✔ terverifikasi' : '· menunggu verifikasi' }}</span>
                        </td>
                        <td class="px-3 py-2.5 whitespace-nowrap text-slate-500">{{ $s->student_class ?: '-' }} · {{ ucfirst($s->seller_type ?? 'siswa') }}</td>
                        <td class="px-3 py-2.5 text-right">{{ $s->products_count }}</td>
                        <td class="px-3 py-2.5 text-right font-bold">{{ number_format($s->period_orders) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format($s->period_items) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap font-bold">{{ $rp($s->period_revenue) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format($s->period_visits) }}</td>
                        <td class="px-5 py-2.5 text-right whitespace-nowrap">{{ $s->period_rating !== null ? '★ ' . $s->period_rating : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-slate-400">Belum ada toko.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Produk terlaris --}}
<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3"><h2 class="text-sm font-extrabold">🎨 Produk terlaris</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">Produk</th>
                    <th class="text-left px-3 py-2.5 font-bold">Toko</th>
                    <th class="text-right px-3 py-2.5 font-bold">Harga</th>
                    <th class="text-right px-3 py-2.5 font-bold">Terjual</th>
                    <th class="text-right px-3 py-2.5 font-bold">❤️ Total suka</th>
                    <th class="text-right px-5 py-2.5 font-bold">👁️ Total dilihat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($topProducts as $p)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-2.5 font-semibold min-w-[10rem]">{{ $p->product_name }}</td>
                        <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap">{{ $p->store?->store_name }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $p->formatted_price }}</td>
                        <td class="px-3 py-2.5 text-right font-bold">{{ number_format($p->period_sold) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format($p->likes_count) }}</td>
                        <td class="px-5 py-2.5 text-right">{{ number_format($p->views_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Belum ada produk terjual pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Semua transaksi --}}
<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3"><h2 class="text-sm font-extrabold">🧾 Seluruh transaksi ({{ $orders->count() }})</h2></div>
    <div class="overflow-x-auto max-h-[32rem] overflow-y-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase sticky top-0">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">Tanggal</th>
                    <th class="text-left px-3 py-2.5 font-bold">Invoice</th>
                    <th class="text-left px-3 py-2.5 font-bold">Toko</th>
                    <th class="text-left px-3 py-2.5 font-bold">Pembeli</th>
                    <th class="text-right px-3 py-2.5 font-bold">Barang</th>
                    <th class="text-right px-3 py-2.5 font-bold">Total</th>
                    <th class="text-left px-3 py-2.5 font-bold">Status</th>
                    <th class="text-left px-5 py-2.5 font-bold">Rating</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $o)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-2.5 whitespace-nowrap text-slate-500">{{ $o->created_at->translatedFormat('d M Y, H:i') }}</td>
                        <td class="px-3 py-2.5 font-mono text-[11px] whitespace-nowrap">{{ $o->order_code }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap font-semibold">{{ $o->store?->store_name ?? '-' }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">{{ $o->buyer?->name ?? '-' }} <span class="text-[10px] text-slate-400">({{ \App\Models\Store::getBuyerSubtypeLabel($o->buyer) }})</span></td>
                        <td class="px-3 py-2.5 text-right">{{ $o->items->sum('quantity') }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap font-bold">{{ $rp($o->total_price) }}</td>
                        <td class="px-3 py-2.5">@include('reports.partials.status-badge', ['order' => $o])</td>
                        <td class="px-5 py-2.5 whitespace-nowrap text-amber-500">{{ $o->buyer_rating ? str_repeat('★', $o->buyer_rating) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Tidak ada transaksi pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('panel_scripts')
<script>
(() => {
    const c = @json($charts);
    const P = window.hkPalette;

    new Chart(document.getElementById('chartTrend'), {
        data: {
            labels: c.trend.labels,
            datasets: [
                { type: 'bar', label: 'Omzet', data: c.trend.revenue, backgroundColor: 'rgba(11,110,79,.75)', borderRadius: 6, yAxisID: 'y' },
                { type: 'line', label: 'Pesanan', data: c.trend.orders, borderColor: '#F59E0B', backgroundColor: '#F59E0B', tension: .35, yAxisID: 'y1' },
            ],
        },
        options: {
            interaction: { mode: 'index', intersect: false },
            plugins: { tooltip: { callbacks: { label: (ctx) => ctx.dataset.yAxisID === 'y' ? 'Omzet: ' + hkRupiah(ctx.raw) : 'Pesanan: ' + ctx.raw } } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: (v) => hkRupiah(v) } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } },
            },
        },
    });

    new Chart(document.getElementById('chartStatus'), {
        type: 'doughnut',
        data: { labels: c.status.labels, datasets: [{ data: c.status.values, backgroundColor: ['#94A3B8', '#F59E0B', '#3B82F6', '#0B6E4F', '#EF4444'] }] },
        options: { cutout: '62%', plugins: { legend: { position: 'bottom' } } },
    });

    new Chart(document.getElementById('chartStores'), {
        type: 'bar',
        data: { labels: c.stores.labels, datasets: [{ label: 'Omzet', data: c.stores.revenue, backgroundColor: '#0B6E4F', borderRadius: 6 }] },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => hkRupiah(ctx.raw) } } },
            scales: { x: { beginAtZero: true, ticks: { callback: (v) => hkRupiah(v) } } },
        },
    });

    new Chart(document.getElementById('chartBuyers'), {
        type: 'pie',
        data: { labels: c.buyerTypes.labels.length ? c.buyerTypes.labels : ['Belum ada'], datasets: [{ data: c.buyerTypes.values.length ? c.buyerTypes.values : [1], backgroundColor: c.buyerTypes.values.length ? P : ['#E2E8F0'] }] },
        options: { plugins: { legend: { position: 'bottom' } } },
    });

    new Chart(document.getElementById('chartUsers'), {
        type: 'bar',
        data: { labels: c.users.labels, datasets: c.users.datasets.map((d, i) => ({ ...d, backgroundColor: P[i], borderRadius: 4 })) },
        options: { scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } },
    });

    new Chart(document.getElementById('chartVisits'), {
        type: 'line',
        data: { labels: c.visits.labels, datasets: [{ label: 'Kunjungan', data: c.visits.values, borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,.12)', fill: true, tension: .35 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });
})();
</script>
@endpush

