{{-- Kartu KPI laporan. Variabel: $icon, $label, $value, $hint (opsional), $growth (opsional, persen) --}}
<div class="rounded-2xl bg-white border border-slate-200/80 p-4 sm:p-5 space-y-1.5">
    <div class="flex items-center justify-between text-[11px] sm:text-xs text-slate-400">
        <span class="font-semibold">{{ $label }}</span>
        <span>{{ $icon }}</span>
    </div>
    <p class="text-lg sm:text-2xl font-extrabold text-[#0F291E] break-words">{{ $value }}</p>
    <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
        @isset($growth)
            @if($growth !== null)
                <span class="rounded-full px-1.5 py-0.5 font-bold {{ $growth >= 0 ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-rose-50 text-rose-600' }}">
                    {{ $growth >= 0 ? '▲' : '▼' }} {{ abs($growth) }}%
                </span>
            @endif
        @endisset
        @if(! empty($hint))
            <span class="text-slate-500">{{ $hint }}</span>
        @endif
    </div>
</div>

