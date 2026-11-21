@extends('layouts.app')

@section('title', 'Sesi Berakhir Demi Keamanan (419) — hilalkraf')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#EBF9F2] via-white to-slate-50 flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200/90 shadow-xl p-6 sm:p-8 text-center space-y-6">
        <!-- Logo Duet -->
        <div class="flex items-center justify-center gap-2">
            <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/20 px-3 py-1.5 shadow-xs">
                <img src="{{ asset('images/logo-hilalkraf.jpg') }}" alt="hilalkraf" class="h-9 w-auto object-contain">
                <span class="h-5 w-[1px] bg-slate-200"></span>
                <img src="{{ asset('images/logo-smk-alhilal.png') }}" alt="SMK Plus Al Hilal" class="h-8 w-8 object-contain">
            </div>
        </div>

        <!-- Ikon Keamanan & Status Sesi -->
        <div class="mx-auto w-20 h-20 rounded-3xl bg-[#EBF9F2] border-2 border-[#CDEFE0] flex items-center justify-center text-[#0B6E4F]">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <!-- Headline & Penjelasan Ramah & Informatif -->
        <div class="space-y-2">
            <span class="inline-block px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                Keamanan Akun & Poin Terjaga (Error 419)
            </span>
            <h1 class="text-xl sm:text-2xl font-black text-[#0F291E]">Sesi Anda Telah Berakhir</h1>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                Demi keamanan akun dan poin bazar Anda, sesi peramban otomatis dikunci setelah tidak aktif beberapa saat atau saat berpindah peramban scanner.
            </p>
        </div>

        <!-- Panduan Singkat -->
        <div class="rounded-2xl bg-slate-50 border border-slate-200/80 p-3.5 text-left text-xs text-slate-700 space-y-1.5">
            <div class="flex items-center gap-2 font-bold text-[#0B6E4F]">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Apa yang harus dilakukan?</span>
            </div>
            <p class="text-slate-500 pl-6">
                Klik tombol <strong>Masuk Kembali</strong> di bawah. Anda akan diarahkan untuk login, dan sistem akan langsung melanjutkan proses scan / halaman poin bazar Anda.
            </p>
        </div>

        <!-- Aksi Tombol -->
        <div class="space-y-2 pt-2">
            <a href="{{ route('login') }}"
               class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white font-extrabold text-sm py-3 px-4 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                <span>Masuk Kembali ke Akun</span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.location.reload()"
                        class="flex-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 px-3 transition">
                    Muat Ulang Halaman
                </button>
                <a href="{{ route('home') }}"
                   class="flex-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 px-3 transition">
                    Ke Beranda
                </a>
            </div>
        </div>

        <p class="text-[10px] text-slate-400">
            hilalkraf &bull; Inkubator Bisnis & Wirausaha Muda SMK Plus Al Hilal
        </p>
    </div>
</div>
@endsection
