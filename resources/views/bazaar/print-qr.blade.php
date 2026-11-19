{{--
============================================================================
VIEW: HALAMAN CETAK BARCODE / QR CODE BAZAR & STAND TOKO (bazaar/print-qr.blade.php)
============================================================================
Fungsi:
  Menampilkan kartu QR Code siap cetak (`@media print`) untuk:
  - QR Code Kehadiran Acara Bazar (dicetak Admin / Super Admin untuk pintu masuk)
  - QR Code Kunjungan Stand Toko Peserta (dicetak Admin atau Pemilik Toko)
  - Cetak massal seluruh kartu QR Stand Peserta dalam 1 halaman rapi
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Cetak QR Code Bazar — ' . $bazaarEvent->title)
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #ffffff !important; }
        .print-page { padding: 0 !important; max-width: 100% !important; }
        .qr-card { break-inside: avoid; page-break-inside: avoid; box-shadow: none !important; border: 2px solid #0F291E !important; }
    }
</style>

<div class="min-h-screen bg-slate-100 py-6 px-4 print-page">
    <!-- Bar Kontrol Non-Print -->
    <div class="no-print max-w-5xl mx-auto mb-6 bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Kembali</span>
            </a>
            <div>
                <h1 class="text-sm font-extrabold text-[#0F291E]">Pratinjau Cetak QR Code Bazar</h1>
                <p class="text-[11px] text-slate-500">{{ $bazaarEvent->title }} ({{ count($cards) }} kartu siap cetak)</p>
            </div>
        </div>

        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-4 py-2 text-xs font-extrabold shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Cetak / Simpan PDF Sekarang</span>
        </button>
    </div>

    <!-- Grid Kartu QR Code -->
    <div class="max-w-5xl mx-auto grid {{ count($cards) === 1 ? 'grid-cols-1 max-w-md' : 'grid-cols-1 sm:grid-cols-2' }} gap-6">
        @foreach($cards as $card)
            <div class="qr-card rounded-3xl bg-white border-2 border-[#0B6E4F]/30 p-6 sm:p-8 text-center space-y-4 shadow-md flex flex-col justify-between">
                <div class="space-y-2">
                    <div class="flex items-center justify-center gap-2 pb-2 border-b border-slate-100">
                        <img src="{{ asset('images/logo-hilalkraf.jpg') }}" alt="hilalkraf" class="h-8 w-auto object-contain">
                        <span class="h-5 w-[1px] bg-slate-200"></span>
                        <img src="{{ asset('images/logo-smk-alhilal.png') }}" alt="SMK Plus Al Hilal" class="h-7 w-7 object-contain">
                        <span class="text-xs font-extrabold text-[#0F291E]">hilalkraf SMK PAL</span>
                    </div>

                    <span class="inline-block rounded-full bg-[#EBF9F2] border border-[#CDEFE0] px-3.5 py-1 text-[11px] font-black uppercase tracking-wider text-[#0B6E4F]">
                        {{ $card['badge'] }}
                    </span>

                    <h2 class="text-lg sm:text-xl font-black text-[#0F291E] leading-tight">
                        {{ $card['title'] }}
                    </h2>
                    <p class="text-xs font-medium text-slate-500">
                        {{ $card['subtitle'] }}
                    </p>
                </div>

                <!-- QR SVG Vector Tajam -->
                <div class="mx-auto inline-flex flex-col items-center justify-center rounded-2xl border-2 border-slate-900/10 bg-white p-4 shadow-2xs">
                    <div class="flex justify-center">
                        {!! $card['qrSvg'] !!}
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 w-full flex items-center justify-between gap-4 text-xs">
                        <span class="text-slate-400 font-semibold">Kode Manual:</span>
                        <span class="font-mono font-black text-[#0F291E] tracking-wider bg-slate-100 px-2.5 py-0.5 rounded-md">
                            {{ $card['shortCode'] }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-1.5 text-xs font-black text-amber-800">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        <span>Hadiah +{{ $card['points'] }} Poin Pengunjung (Doorprize)</span>
                    </div>
                    @if(!empty($card['prizeTiers']))
                        <div class="rounded-xl bg-[#F8FAFC] border border-slate-200 p-2.5 text-left space-y-1">
                            <p class="text-[10px] font-black uppercase tracking-wider text-[#0B6E4F] text-center">Informasi Hadiah Poin Pengunjung:</p>
                            <div class="flex flex-wrap items-center justify-center gap-1.5">
                                @foreach($card['prizeTiers'] as $pt)
                                    <span class="rounded-lg bg-white border border-amber-300 px-2 py-0.5 text-[10px] font-extrabold text-[#0F291E]">
                                        {{ number_format($pt['min_points']) }} Poin = {{ $pt['prize_name'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        {{ $card['footer'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
