{{--
============================================================================
VIEW: LAPORAN BELANJA PEMBELI (reports/buyer.blade.php)
============================================================================
Fungsi:
  Rincian transaksi pribadi pembeli: total belanja, total hemat (diskon),
  jumlah barang, status pesanan, toko favorit, grafik pengeluaran, serta
  tabel detail tiap transaksi. Dapat difilter harian/bulanan/tahunan/rentang.
============================================================================
--}}
@extends('layouts.panel')

@section('title', 'Laporan Belanja Saya — hilalkraf')
@section('panel_title', 'Laporan Belanja')

@section('panel')
@php $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.'); @endphp

<div>
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Laporan Belanja Saya</h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Rincian seluruh transaksi Anda di hilalkraf beserta hemat dari diskon.</p>
</div>

<x-report-filter :action="route('buyer.reports')" :range="$range" />

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    @include('reports.partials.kpi', ['icon' => 'wallet', 'label' => 'Total belanja', 'value' => $rp($kpi['spent']), 'growth' => $kpi['spent_growth'], 'hint' => 'vs periode lalu'])
    @include('reports.partials.kpi', ['icon' => 'receipt', 'label' => 'Transaksi', 'value' => number_format($kpi['orders']), 'hint' => number_format($kpi['items']) . ' barang dibeli'])
    @include('reports.partials.kpi', ['icon' => 'gift', 'label' => 'Total hemat', 'value' => $rp($kpi['saved']), 'hint' => 'dari diskon pembeli & bazar'])
    @include('reports.partials.kpi', ['icon' => 'truck', 'label' => 'Sedang diproses', 'value' => number_format($kpi['in_progress']), 'hint' => $kpi['completed'] . ' selesai · ' . $kpi['unrated'] . ' belum dinilai'])
</div>

@if($kpi['unrated'] > 0)
    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 flex flex-wrap items-center justify-between gap-2">
        <span class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
            <span>Ada {{ $kpi['unrated'] }} pesanan selesai yang belum Anda beri rating. Penilaian Anda membantu siswa memperbaiki karyanya.</span>
        </span>
        <a href="{{ route('buyer.dashboard') }}" class="font-bold underline">Beri rating &rarr;</a>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
            <span>Pengeluaran belanja</span>
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
</div>

@if($favoriteStores->isNotEmpty())
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5">
        <h2 class="text-sm font-extrabold mb-3 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M9 20V14h6v6" /></svg>
            <span>Toko langganan</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($favoriteStores->take(6) as $name => $s)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-bold text-[#0F291E] truncate">{{ $name }}</p>
                    <p class="text-[11px] text-slate-500">{{ $s['orders'] }} transaksi · {{ $rp($s['total']) }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
    <div class="p-5 pb-3">
        <h2 class="text-sm font-extrabold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span>Detail transaksi ({{ $orders->count() }})</span>
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                <tr>
                    <th class="text-left px-5 py-2.5 font-bold">Tanggal</th>
                    <th class="text-left px-3 py-2.5 font-bold">Invoice</th>
                    <th class="text-left px-3 py-2.5 font-bold">Toko</th>
                    <th class="text-left px-3 py-2.5 font-bold">Produk</th>
                    <th class="text-right px-3 py-2.5 font-bold">Subtotal</th>
                    <th class="text-right px-3 py-2.5 font-bold">Hemat</th>
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
                        <td class="px-3 py-2.5 font-semibold min-w-[8rem]">{{ $o->store?->store_name ?? '-' }}</td>
                        <td class="px-3 py-2.5 min-w-[10rem]">
                            @foreach($o->items as $item)
                                <span class="block">{{ $item->product?->product_name ?? 'Produk' }} <span class="text-slate-400">×{{ $item->quantity }}</span></span>
                            @endforeach
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">{{ $o->formatted_subtotal }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap text-[#0B6E4F]">{{ $o->total_discount_amount > 0 ? $rp($o->total_discount_amount) : '—' }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap font-bold">{{ $rp($o->total_price) }}</td>
                        <td class="px-3 py-2.5">@include('reports.partials.status-badge', ['order' => $o])</td>
                        <td class="px-5 py-2.5 whitespace-nowrap text-amber-600 font-bold">
                            @if($o->buyer_rating)
                                <span class="inline-flex items-center gap-0.5">{{ $o->buyer_rating }} / 5</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-slate-400">Tidak ada transaksi pada periode ini.</td></tr>
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

    new Chart(document.getElementById('chartTrend'), {
        data: {
            labels: c.trend.labels,
            datasets: [
                { type: 'bar', label: 'Belanja', data: c.trend.revenue, backgroundColor: 'rgba(11,110,79,.75)', borderRadius: 6, yAxisID: 'y' },
                { type: 'line', label: 'Transaksi', data: c.trend.orders, borderColor: '#F59E0B', backgroundColor: '#F59E0B', tension: .35, yAxisID: 'y1' },
            ],
        },
        options: {
            interaction: { mode: 'index', intersect: false },
            plugins: { tooltip: { callbacks: { label: (ctx) => ctx.dataset.yAxisID === 'y' ? 'Belanja: ' + hkRupiah(ctx.raw) : 'Transaksi: ' + ctx.raw } } },
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
})();
</script>
@endpush

