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
    @include('reports.partials.kpi', ['icon' => 'wallet', 'label' => 'Omzet', 'value' => $rp($kpi['revenue']), 'growth' => $kpi['revenue_growth'], 'hint' => 'vs periode lalu'])
    @include('reports.partials.kpi', ['icon' => 'receipt', 'label' => 'Pesanan', 'value' => number_format($kpi['orders']), 'growth' => $kpi['orders_growth'], 'hint' => $kpi['cancelled'] . ' dibatalkan'])
    @include('reports.partials.kpi', ['icon' => 'box', 'label' => 'Barang terjual', 'value' => number_format($kpi['items_sold']) . ' pcs', 'hint' => 'Rata-rata ' . $rp($kpi['avg_order']) . '/pesanan'])
    @include('reports.partials.kpi', ['icon' => 'star', 'label' => 'Rating pembeli', 'value' => $kpi['rating'] !== null ? $kpi['rating'] . ' / 5' : '—', 'hint' => $kpi['rating_count'] . ' ulasan · diskon ' . $rp($kpi['discount_given'])])
</div>

{{-- Saran evaluasi --}}
@if(count($insights) > 0)
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-3">
        <h2 class="text-sm font-extrabold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
            <span>Catatan evaluasi & perbaikan</span>
        </h2>
        <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach($insights as $tip)
                <li class="rounded-xl px-3.5 py-2.5 text-xs leading-relaxed flex items-start gap-2 {{ $tip['type'] === 'good' ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-800' }}">
                    @if($tip['type'] === 'good')
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    @else
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    @endif
                    <span>{{ $tip['text'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Grafik --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
            <span>Tren omzet & jumlah pesanan</span>
        </h2>
        <div class="h-64 sm:h-72"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
            <span>Status pesanan</span>
        </h2>
        <div class="h-64 sm:h-72"><canvas id="chartStatus"></canvas></div>
    </div>
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            <span>Produk terjual vs disukai</span>
        </h2>
        <div class="h-64 sm:h-72"><canvas id="chartProducts"></canvas></div>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span>Jenis pembeli</span>
        </h2>
        <div class="h-64 sm:h-72"><canvas id="chartBuyers"></canvas></div>
    </div>
</div>

{{-- Performa produk --}}
<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3">
        <h2 class="text-sm font-extrabold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            <span>Performa produk pada periode ini</span>
        </h2>
        <p class="text-[11px] text-slate-500">Konversi = terjual ÷ dilihat. Produk yang banyak dilihat tapi konversinya rendah perlu diperbaiki foto, deskripsi, atau harganya.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">Produk</th>
                    <th class="text-right px-3 py-2.5 font-bold">Terjual</th>
                    <th class="text-right px-3 py-2.5 font-bold">Omzet</th>
                    <th class="text-right px-3 py-2.5 font-bold">Dilihat</th>
                    <th class="text-right px-3 py-2.5 font-bold">Suka</th>
                    <th class="text-right px-3 py-2.5 font-bold">Komentar</th>
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
        <h2 class="text-sm font-extrabold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span>Rincian transaksi ({{ $orders->count() }})</span>
        </h2>
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
                        <td class="px-5 py-2.5 whitespace-nowrap text-amber-600 font-bold">{{ $o->buyer_rating ? $o->buyer_rating . ' / 5' : '—' }}</td>
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

