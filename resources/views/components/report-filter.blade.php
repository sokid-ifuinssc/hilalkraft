{{--
============================================================================
KOMPONEN: FILTER LAPORAN (components/report-filter.blade.php)
============================================================================
Fungsi:
  Filter tanggal laporan: pintasan cepat (Hari ini, 7 hari, 30 hari, Bulan ini,
  Bulan lalu, Tahun ini), filter Harian / Bulanan / Tahunan, serta rentang
  tanggal bebas (dari ... sampai ...), plus tombol unduh CSV.
Props:
  action : URL halaman laporan
  range  : array hasil ReportController::resolveRange()
============================================================================
--}}
@props(['action', 'range'])

@php
    $preset = $range['preset'];
    $chips = [
        'today'      => 'Hari ini',
        '7d'         => '7 hari',
        '30d'        => '30 hari',
        'this_month' => 'Bulan ini',
        'last_month' => 'Bulan lalu',
        'this_year'  => 'Tahun ini',
    ];
    $inputCls = 'rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs focus:outline-none focus:border-[#0B6E4F] focus:ring-2 focus:ring-emerald-500/10';
    $btnCls   = 'rounded-lg bg-[#0B6E4F] px-3 py-1.5 text-xs font-bold text-white hover:bg-[#08583F] transition';
    $activeTab = in_array($preset, ['day', 'month', 'year', 'custom']) ? $preset : 'custom';
    $exportUrl = $action . '?' . http_build_query(array_merge(request()->except('export'), ['export' => 'csv']));
@endphp

<div class="rounded-2xl bg-white border border-slate-200/80 p-4 sm:p-5 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Periode laporan</p>
            <p class="text-sm font-extrabold text-[#0F291E]">📅 {{ $range['label'] }} <span class="text-xs font-semibold text-slate-400">· grafik {{ $range['group_label'] }}</span></p>
        </div>
        <a href="{{ $exportUrl }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
            ⬇️ Unduh CSV (Excel)
        </a>
    </div>

    {{-- Pintasan cepat --}}
    <div class="flex flex-wrap gap-2">
        @foreach($chips as $key => $label)
            <a href="{{ $action }}?preset={{ $key }}"
               class="rounded-full px-3 py-1 text-xs font-semibold border transition {{ $preset === $key ? 'bg-[#0B6E4F] border-[#0B6E4F] text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-[#0B6E4F] hover:text-[#0B6E4F]' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Filter detail --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 pt-3 border-t border-slate-100">
        <form method="GET" action="{{ $action }}" class="rounded-xl p-3 space-y-2 {{ $activeTab === 'day' ? 'bg-[#EBF9F2]' : 'bg-slate-50' }}">
            <input type="hidden" name="preset" value="day">
            <p class="text-[11px] font-bold text-slate-600">Harian</p>
            <div class="flex gap-2">
                <input type="date" name="date" value="{{ $preset === 'day' ? $range['from']->format('Y-m-d') : now()->format('Y-m-d') }}" class="{{ $inputCls }} flex-1 min-w-0">
                <button class="{{ $btnCls }}">Lihat</button>
            </div>
        </form>

        <form method="GET" action="{{ $action }}" class="rounded-xl p-3 space-y-2 {{ $activeTab === 'month' ? 'bg-[#EBF9F2]' : 'bg-slate-50' }}">
            <input type="hidden" name="preset" value="month">
            <p class="text-[11px] font-bold text-slate-600">Bulanan</p>
            <div class="flex gap-2">
                <input type="month" name="month" value="{{ $preset === 'month' ? $range['from']->format('Y-m') : now()->format('Y-m') }}" class="{{ $inputCls }} flex-1 min-w-0">
                <button class="{{ $btnCls }}">Lihat</button>
            </div>
        </form>

        <form method="GET" action="{{ $action }}" class="rounded-xl p-3 space-y-2 {{ $activeTab === 'year' ? 'bg-[#EBF9F2]' : 'bg-slate-50' }}">
            <input type="hidden" name="preset" value="year">
            <p class="text-[11px] font-bold text-slate-600">Tahunan</p>
            <div class="flex gap-2">
                <select name="year" class="{{ $inputCls }} flex-1 min-w-0">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" @selected($preset === 'year' && (int) $range['from']->year === $y)>{{ $y }}</option>
                    @endfor
                </select>
                <button class="{{ $btnCls }}">Lihat</button>
            </div>
        </form>

        <form method="GET" action="{{ $action }}" class="rounded-xl p-3 space-y-2 {{ $preset === 'custom' ? 'bg-[#EBF9F2]' : 'bg-slate-50' }}">
            <input type="hidden" name="preset" value="custom">
            <p class="text-[11px] font-bold text-slate-600">Rentang tanggal</p>
            <div class="flex flex-wrap items-center gap-1.5">
                <input type="date" name="from" value="{{ $range['from']->format('Y-m-d') }}" class="{{ $inputCls }} flex-1 min-w-[7.5rem]" aria-label="Dari tanggal">
                <span class="text-[11px] text-slate-400">s/d</span>
                <input type="date" name="to" value="{{ $range['to']->format('Y-m-d') }}" class="{{ $inputCls }} flex-1 min-w-[7.5rem]" aria-label="Sampai tanggal">
            </div>
            <div class="flex gap-2">
                <select name="group" class="{{ $inputCls }} flex-1 min-w-0" aria-label="Kelompokkan grafik">
                    <option value="auto">Grafik otomatis</option>
                    <option value="day" @selected(request('group') === 'day')>Per hari</option>
                    <option value="month" @selected(request('group') === 'month')>Per bulan</option>
                    <option value="year" @selected(request('group') === 'year')>Per tahun</option>
                </select>
                <button class="{{ $btnCls }}">Terapkan</button>
            </div>
        </form>
    </div>
</div>
