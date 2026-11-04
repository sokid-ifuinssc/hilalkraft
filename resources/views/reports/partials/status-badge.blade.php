{{-- Badge status progres pesanan. Variabel: $order --}}
@php
    $cls = match ($order->effective_progress) {
        'sampai'     => 'bg-[#EBF9F2] text-[#0B6E4F]',
        'kirim'      => 'bg-blue-50 text-blue-700',
        'kemas'      => 'bg-amber-50 text-amber-700',
        'dibatalkan' => 'bg-rose-50 text-rose-600',
        default      => 'bg-slate-100 text-slate-600',
    };
@endphp
<span class="inline-block whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold {{ $cls }}">{{ $order->progress_label }}</span>

