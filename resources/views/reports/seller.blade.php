{{--
============================================================================
VIEW: LAPORAN PENJUALAN PEMILIK TOKO (reports/seller.blade.php)
============================================================================
Fungsi:
  Laporan penjualan toko untuk evaluasi & perbaikan: omzet, pesanan, barang
  terjual, rating, tren grafik, status pesanan, jenis pembeli, produk
  terlaris / paling disukai / dilihat, saran evaluasi otomatis, dan rincian
  transaksi. Dapat difilter harian, bulanan, tahunan, atau rentang tanggal.
============================================================================
--}}
@extends('layouts.panel')

@section('title', 'Laporan Penjualan — ' . $store->store_name)
@section('panel_title', 'Laporan Penjualan')

@section('panel')
@php $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Laporan Penjualan</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">{{ $store->store_name }} — pantau hasil penjualan untuk evaluasi dan perbaikan toko.</p>
    </div>
</div>

<x-report-filter :action="route('seller.reports')" :range="$range" />

{{-- KPI --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    @include('reports.partials.kpi', ['icon' => '💰', 'label' => 'Omzet', 'value' => $rp($kpi['revenue']), 'growth' => $kpi['revenue_growth'], 'hint' => 'vs periode lalu'])
    @include('reports.partials.kpi', ['icon' => '🧾', 'label' => 'Pesanan', 'value' => number_format($kpi['orders']), 'growth' => $kpi['orders_growth'], 'hint' => $kpi['cancelled'] . ' dibatalkan'])
    @include('reports.partials.kpi', ['icon' => '📦', 'label' => 'Barang terjual', 'value' => number_format($kpi['items_sold']) . ' pcs', 'hint' => 'Rata-rata ' . $rp($kpi['avg_order']) . '/pesanan'])
    @include('reports.partials.kpi', ['icon' => '⭐', 'label' => 'Rating pembeli', 'value' => $kpi['rating'] !== null ? $kpi['rating'] . ' / 5' : '—', 'hint' => $kpi['rating_count'] . ' ulasan · diskon ' . $rp($kpi['discount_given'])])
</div>

{{-- Saran evaluasi --}}
@if(count($insights) > 0)
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-3">
        <h2 class="text-sm font-extrabold">💡 Catatan evaluasi & perbaikan</h2>
        <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach($insights as $tip)
                <li class="rounded-xl px-3.5 py-2.5 text-xs leading-relaxed {{ $tip['type'] === 'good' ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-800' }}">
                    {{ $tip['type'] === 'good' ? '✅' : '⚠️' }} {{ $tip['text'] }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Grafik --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">📈 Tren omzet & jumlah pesanan</h2>
        <div class="h-64 sm:h-72"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">🚚 Status pesanan</h2>
        <div class="h-64 sm:h-72"><canvas id="chartStatus"></canvas></div>
    </div>
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">🏆 Produk terjual vs disukai</h2>
        <div class="h-64 sm:h-72"><canvas id="chartProducts"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3">👥 Jenis pembeli</h2>
        <div class="h-64 sm:h-72"><canvas id="chartBuyers"></canvas></div>
    </div>
</div>

{{-- Performa produk --}}
<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3">
        <h2 class="text-sm font-extrabold">🎨 Performa produk pada periode ini</h2>
        <p class="text-[11px] text-slate-500">Konversi = terjual ÷ dilihat. Produk yang banyak dilihat tapi konversinya rendah perlu diperbaiki foto, deskripsi, atau harganya.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">Produk</th>
                    <th class="text-right px-3 py-2.5 font-bold">Terjual</th>
                    <th class="text-right px-3 py-2.5 font-bold">Omzet</th>
                    <th class="text-right px-3 py-2.5 font-bold">👁️ Dilihat</th>
                    <th class="text-right px-3 py-2.5 font-bold">❤️ Suka</th>
                    <th class="text-right px-3 py-2.5 font-bold">💬 Komentar</th>
                    <th class="text-right px-3 py-2.5 font-bold">Konversi</th>
                    <th class="text-right px-5 py-2.5 font-bold">Stok</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($productStats as $p)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-5 py-2.5 font-semibold text-[#0F291E] min-w-[10rem]">{{ $p->product_name }}</td>
                        <td class="px-3 py-2.5 text-right font-bold">{{ number_format($p->period_sold) }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $rp($p->period_revenue) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format($p->period_views) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format($p->period_likes) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ number_format($p->period_comments) }}</td>
                        <td class="px-3 py-2.5 text-right">{{ $p->conversion !== null ? $p->conversion . '%' : '—' }}</td>
                        <td class="px-5 py-2.5 text-right {{ $p->stock <= 3 ? 'text-rose-600 font-bold' : '' }}">{{ $p->stock }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Rincian transaksi --}}
<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-extrabold">🧾 Rincian transaksi ({{ $orders->count() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">Tanggal</th>
                    <th class="text-left px-3 py-2.5 font-bold">Invoice</th>
                    <th class="text-left px-3 py-2.5 font-bold">Pembeli</th>
                    <th class="text-left px-3 py-2.5 font-bold">Produk</th>
                    <th class="text-right px-3 py-2.5 font-bold">Diskon</th>
                    <th class="text-right px-3 py-2.5 font-bold">Total</th>
                    <th class="text-left px-3 py-2.5 font-bold">Status</th>
                    <th class="text-left px-5 py-2.5 font-bold">Rating</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $o)
                    <tr class="hover:bg-slate-50/60 align-top">
                        <td class="px-5 py-2.5 whitespace-nowrap text-slate-500">{{ $o->created_at->translatedFormat('d M Y, H:i') }}</td>
                        <td class="px-3 py-2.5 font-mono text-[11px] whitespace-nowrap">{{ $o->order_code }}</td>
                        <td class="px-3 py-2.5 min-w-[8rem]">
                            <span class="font-semibold">{{ $o->buyer?->name ?? '-' }}</span>
                            <span class="block text-[10px] text-slate-400">{{ \App\Models\Store::getBuyerSubtypeLabel($o->buyer) }}</span>
                        </td>
                        <td class="px-3 py-2.5 min-w-[10rem]">
                            @foreach($o->items as $item)
                                <span class="block">{{ $item->product?->product_name ?? 'Produk' }} <span class="text-slate-400">×{{ $item->quantity }}</span></span>
                            @endforeach
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap text-amber-700">{{ $o->total_discount_amount > 0 ? '-' . $rp($o->total_discount_amount) : '—' }}</td>
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

    new Chart(document.getElementById('chartProducts'), {
        type: 'bar',
        data: {
            labels: c.topProducts.labels,
            datasets: [
                { label: 'Terjual', data: c.topProducts.sold, backgroundColor: '#0B6E4F', borderRadius: 6 },
                { label: 'Disukai', data: c.topProducts.likes, backgroundColor: '#EC4899', borderRadius: 6 },
            ],
        },
        options: { indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    new Chart(document.getElementById('chartBuyers'), {
        type: 'pie',
        data: { labels: c.buyerTypes.labels.length ? c.buyerTypes.labels : ['Belum ada'], datasets: [{ data: c.buyerTypes.values.length ? c.buyerTypes.values : [1], backgroundColor: c.buyerTypes.values.length ? P : ['#E2E8F0'] }] },
        options: { plugins: { legend: { position: 'bottom' } } },
    });
})();
</script>
@endpush

