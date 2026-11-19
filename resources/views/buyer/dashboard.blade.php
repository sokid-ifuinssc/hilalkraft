{{--
============================================================================
VIEW: DASHBOARD PEMBELI & PROGRES PESANAN (buyer/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan panel akun Pembeli dengan Sidebar responsif di semua perangkat
  (Desktop: Sidebar tetap, Mobile: Drawer navigasi samping).
  Menyediakan menu fokus:
  1. Tab Pesanan Saya:
     - Progres Barang 4 Tahap (Pesan → Kemas → Kirim → Sampai)
     - Rincian Diskon Jenis Pembeli & Bazar SMK
     - Fitur Batal Pesanan (sebelum dikemas)
     - Rating Bintang 1-5 & Ulasan
     - Hubungi Penjual via WhatsApp
  2. Tab Profil Akun Pembeli:
     - Form Edit Profil (Nama, No. WA, Jenis Pembeli, Alamat Pengiriman Lengkap)
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Akun Pembeli & Pesanan Saya — hilalkraf SMK PAL')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $user = auth()->user();
@endphp

<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Mobile Drawer Overlay -->
    <div id="buyerOverlay" class="fixed inset-0 bg-slate-900/40 z-40 hidden lg:hidden" onclick="toggleBuyerSidebar(false)"></div>

    <!-- Sidebar Kiri Akun Pembeli (Fixed di Desktop, Drawer di Mobile) -->
    <aside id="buyerSidebar"
           class="fixed lg:sticky top-0 left-0 z-50 h-screen w-64 bg-white border-r border-slate-200/80 p-5 flex flex-col justify-between overflow-y-auto
                  -translate-x-full lg:translate-x-0 transition-transform duration-200 shrink-0">
        <div class="space-y-6">
            <!-- Brand Logo hilalkraf & SMK Plus Al Hilal -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 pb-4 border-b border-slate-100">
                <img src="{{ asset('images/logo-hilalkraf.jpg') }}" alt="hilalkraf" class="h-10 w-auto object-contain">
                <img src="{{ asset('images/logo-smk-alhilal.png') }}" alt="SMK Plus Al Hilal" class="h-8 w-8 object-contain">
                <div class="leading-tight">
                    <span class="block text-base font-extrabold text-[#0F291E]">hilalkraf</span>
                    <span class="block text-[10px] font-bold text-[#0B6E4F]">SMK PAL</span>
                </div>
            </a>

            <!-- Info Profil Akun Pembeli -->
            <div class="rounded-xl bg-[#F8FAFC] p-3 border border-slate-100 space-y-1">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">AKUN PEMBELI</p>
                <p class="text-xs font-bold text-[#0F291E] truncate">{{ $user->name }}</p>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-1 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        <span>{{ $user->subtype_label }}</span>
                    </span>
                </div>
            </div>

            <!-- Menu Navigasi Sidebar -->
            <nav class="space-y-1 text-xs font-semibold">
                <button type="button" onclick="switchBuyerTab('pesanan')" id="side-tab-pesanan"
                        class="buyer-side-btn w-full flex items-center justify-between rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                        <span>Pesanan Saya</span>
                    </span>
                    <span class="rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">{{ $orders->total() }}</span>
                </button>

                <button type="button" onclick="switchBuyerTab('profil')" id="side-tab-profil"
                        class="buyer-side-btn w-full flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>Profil Akun Pembeli</span>
                </button>

                <a href="{{ route('buyer.cart.index') }}" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        <span>Keranjang Belanja</span>
                    </span>
                    @php $cartQty = count(session('hilalkraft_cart', [])); @endphp
                    @if($cartQty > 0)
                        <span class="rounded-full bg-emerald-600 text-white px-2 py-0.5 text-[10px] font-bold">{{ $cartQty }}</span>
                    @endif
                </a>

                <a href="{{ route('bazaar.scan.page') }}" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-[#0B6E4F] bg-amber-50/80 border border-amber-200 hover:bg-amber-100 transition font-bold">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>Scan QR & Poin Bazar</span>
                    </span>
                    <span class="rounded-full bg-amber-500 text-white px-2 py-0.5 text-[10px] font-black">Poin</span>
                </a>

                <a href="{{ route('buyer.reports') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span>Laporan Belanja</span>
                </a>

                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    <span>Katalog / Belanja Lagi</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#0B6E4F] font-semibold transition">↗ Ke Beranda</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-rose-600 font-bold hover:underline">Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Konten Utama Panel Pembeli -->
    <div class="flex-1 min-w-0 flex flex-col">

        <!-- Top Bar Header Responsif -->
        <header class="h-14 sm:h-16 bg-white border-b border-slate-200/80 px-4 sm:px-8 flex items-center justify-between gap-3 sticky top-0 z-30 shadow-2xs">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="toggleBuyerSidebar(true)" class="lg:hidden p-2 -ml-2 rounded-xl text-slate-600 hover:bg-slate-100 transition" aria-label="Buka Menu Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="text-xs text-slate-400 truncate">
                    Akun Pembeli / <span id="topbar-section-title" class="text-slate-800 font-bold">Pesanan Saya</span>
                </div>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-tight">{{ $user->name }}</p>
                    <p class="text-[10px] text-[#0B6E4F] font-semibold">{{ $user->subtype_label }}</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Floating Toast Notifikasi Sukses / Gagal -->
        @if(session('success') || session('error') || $errors->any())
            <div id="buyer-floating-toast" class="fixed top-5 right-5 z-[9999] max-w-md w-[92vw] sm:w-[420px] transition-all duration-300">
                @if(session('success'))
                    <div class="rounded-2xl bg-white border-2 border-[#10B981] p-4 shadow-2xl flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-[#0B6E4F] uppercase tracking-wider">Berhasil</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5 leading-snug">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('buyer-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-2xl bg-white border-2 border-rose-500 p-4 shadow-2xl flex items-start gap-3 mt-2">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-rose-600 uppercase tracking-wider">Perhatian</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5 leading-snug">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('buyer-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="rounded-2xl bg-white border-2 border-rose-500 p-4 shadow-2xl flex items-start gap-3 mt-2">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base shrink-0">!</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-rose-600 uppercase tracking-wider">Kesalahan Input</p>
                            <ul class="text-xs text-slate-700 font-medium mt-0.5 list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" onclick="document.getElementById('buyer-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
            </div>
        @endif

        <div class="p-4 sm:p-8 space-y-6 max-w-6xl w-full mx-auto">

            <!-- Navigasi Tab Cepat (Biar Tidak Perlu Scroll Panjang di Mobile) -->
            <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 overflow-x-auto text-xs font-bold scrollbar-none">
                <button type="button" onclick="switchBuyerTab('pesanan')" id="quick-tab-pesanan"
                        class="buyer-quick-pill px-4 py-2 rounded-xl transition bg-[#0B6E4F] text-white shadow-xs flex items-center gap-1.5 shrink-0">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                    <span>Pesanan Saya</span>
                    <span class="rounded-full bg-white/25 px-2 py-0.2 text-[10px] font-extrabold">{{ $orders->total() }}</span>
                </button>
                <button type="button" onclick="switchBuyerTab('profil')" id="quick-tab-profil"
                        class="buyer-quick-pill px-4 py-2 rounded-xl transition bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center gap-1.5 shrink-0">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>Profil Akun Pembeli</span>
                </button>
                <a href="{{ route('buyer.cart.index') }}" class="px-4 py-2 rounded-xl transition bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center gap-1.5 shrink-0">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    <span>Keranjang Belanja</span>
                </a>
                <a href="{{ route('buyer.reports') }}" class="px-4 py-2 rounded-xl transition bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center gap-1.5 shrink-0">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span>Laporan Belanja</span>
                </a>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 1: PESANAN SAYA & PROGRES BARANG 4 TAHAP                          -->
            <!-- ========================================================================= -->
            <div id="section-pesanan" class="buyer-content-section space-y-6">

                <!-- Header Pesanan -->
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 flex flex-wrap items-center justify-between gap-4 shadow-sm">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-extrabold text-[#0B6E4F]">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                <span>Status: {{ $user->subtype_label }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-[11px] font-bold text-amber-800">
                                <svg class="w-3 h-3 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                <span>Diskon Pembeli & Promo Bazar SMK</span>
                            </span>
                        </div>
                        <h1 class="text-lg sm:text-2xl font-extrabold text-[#0F291E]">Daftar Pesanan & Progres Barang</h1>
                        <p class="text-xs text-slate-500">
                            Pantau pesanan Anda dari <strong>Pesan &rarr; Kemas &rarr; Kirim &rarr; Sampai</strong> dan berikan Rating Bintang setelah barang diterima.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('bazaar.scan.page') }}" class="rounded-xl bg-amber-500 hover:bg-amber-600 px-4 py-2.5 text-xs font-extrabold text-white transition shadow-xs flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            <span>Scan QR & Poin Bazar</span>
                        </a>
                        <a href="{{ route('home') }}" class="rounded-xl bg-[#0B6E4F] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-xs">
                            + Belanja Karya Lainnya
                        </a>
                    </div>
                </div>

                <!-- Notifikasi Pemenang Doorprize (Jika Terpilih) -->
                @if(isset($buyerDoorprizeWins) && $buyerDoorprizeWins->count() > 0)
                    @foreach($buyerDoorprizeWins as $win)
                        <div class="rounded-2xl bg-amber-50 border-2 border-amber-300 p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-black uppercase text-amber-800">SELAMAT! PEMENANG DOORPRIZE BAZAR</span>
                                <p class="text-sm font-black text-[#0F291E]">{{ $win->prize_name }} — {{ $win->bazaarEvent?->title }}</p>
                                <p class="text-xs text-slate-600">Total Poin: <strong>{{ number_format($win->total_points_at_award) }} pts</strong> (Peringkat #{{ $win->rank_at_award ?? '-' }}). Silakan tunjukkan akun Anda kepada panitia bazar.</p>
                            </div>
                            <a href="{{ route('bazaar.scan.page') }}" class="rounded-xl bg-amber-600 text-white px-3.5 py-2 text-xs font-bold hover:bg-amber-700 transition">
                                Lihat Detail Poin &rarr;
                            </a>
                        </div>
                    @endforeach
                @endif

                <!-- Info Acara Bazar SMK & Poin Doorprize Pembeli -->
                @if(isset($activeBazaarEvents) && $activeBazaarEvents->count() > 0)
                    <div class="rounded-2xl bg-gradient-to-r from-[#0F291E] via-[#0B6E4F] to-emerald-700 text-white p-5 space-y-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="space-y-1">
                                <span class="inline-block rounded-full bg-amber-400 text-slate-950 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider">
                                    Acara Bazar & Poin Doorprize Aktif
                                </span>
                                @foreach($activeBazaarEvents as $ev)
                                    <p class="text-sm font-extrabold">
                                        {{ $ev->title }} — Diskon {{ $ev->discount_percent }}% ({{ $ev->location ?? 'SMK Plus Al Hilal' }})
                                    </p>
                                    <p class="text-xs text-emerald-100">
                                        Kode Voucher <span class="font-mono font-extrabold bg-white/20 px-2 py-0.5 rounded text-white">{{ $ev->voucher_code }}</span>
                                        &bull; Hadir +{{ $ev->points_checkin ?? 50 }} pts &bull; Stand +{{ $ev->points_stand_visit ?? 20 }} pts &bull; Belanja +{{ $ev->points_transaction ?? 30 }} pts
                                    </p>
                                @endforeach
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('bazaar.scan.page') }}" class="rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 px-4 py-2 text-xs font-black transition shadow-xs">
                                    Scan QR / Klaim Poin &rarr;
                                </a>
                                <a href="{{ route('buyer.cart.index') }}" class="rounded-xl bg-white text-[#0F291E] px-4 py-2 text-xs font-extrabold hover:bg-emerald-50 transition">
                                    Lihat Keranjang
                                </a>
                            </div>
                        </div>

                        @if(isset($buyerBazaarSummaries) && $buyerBazaarSummaries->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-2 border-t border-white/15">
                                @foreach($buyerBazaarSummaries as $bzSum)
                                    <div class="rounded-xl bg-white/10 backdrop-blur-xs border border-white/15 px-3.5 py-2.5 flex items-center justify-between gap-2 text-xs">
                                        <div>
                                            <p class="font-bold text-white line-clamp-1">{{ $bzSum->event->title }}</p>
                                            <p class="text-[11px] text-emerald-100">
                                                {{ $bzSum->stands_count }} stand &bull; {{ $bzSum->trx_count }} transaksi &bull; Peringkat: <strong>{{ $bzSum->rank ? '#' . $bzSum->rank : 'Belum' }}</strong>
                                            </p>
                                        </div>
                                        <span class="rounded-lg bg-amber-400 text-slate-950 px-2.5 py-1 font-black shrink-0">
                                            {{ number_format($bzSum->total_points) }} pts
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <!-- List Pesanan Pembeli -->
                <div class="space-y-5">
                    @forelse($orders as $order)
                        @php
                            $currentStep = $order->progress_step;
                            $effProgress = $order->effective_progress;
                            $waInvoice = urlencode("Assalamu'alaikum {$order->store->store_name}, saya {$user->name} ingin konfirmasi pesanan *#{$order->order_code}* (Progres: {$order->progress_label}) senilai *{$order->formatted_total_price}* di hilalkraf SMK Plus Al Hilal.");
                        @endphp

                        <div class="rounded-2xl bg-white border border-slate-200/90 p-5 sm:p-6 space-y-5 shadow-sm">
                            <!-- Baris Atas: Kode Pesanan, Nama Toko, Badge Metode Pengiriman & Tombol WA Penjual -->
                            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                                <div class="space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-extrabold text-[#0F291E]">#{{ $order->order_code }}</span>
                                        <a href="{{ route('stores.show', $order->store->slug) }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-[#EBF9F2] px-3 py-0.5 text-xs font-extrabold text-[#0B6E4F] hover:underline">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M9 20V14h6v6" /></svg>
                                            <span>{{ $order->store->store_name }}</span>
                                        </a>
                                        <!-- Badge Metode Transaksi & Pengiriman -->
                                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold border {{ $order->delivery_badge_class }}">
                                            {{ $order->delivery_method_short_label }}
                                        </span>
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-extrabold {{ $effProgress === 'sampai' ? 'bg-emerald-100 text-emerald-800' : ($effProgress === 'dibatalkan' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800') }}">
                                            {{ $order->progress_label }}
                                        </span>
                                        @if($order->is_bazaar_offline || $order->bazaar_voucher_code)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-900">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                                <span>Diskon Bazar ({{ $order->bazaar_voucher_code ?? 'Hadir di Stand' }})</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500 space-y-0.5">
                                        <p class="flex items-start gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            <span><strong>Alamat / Stand:</strong> {{ $order->shipping_address }}</span>
                                        </p>
                                        <div class="flex flex-wrap items-center gap-3 text-[11px]">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                <span>Dipesan {{ $order->created_at?->format('d M Y, H:i') }}</span>
                                            </span>
                                            @if($order->tracking_number)
                                                <span class="inline-flex items-center gap-1 font-bold text-blue-700">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                                                    <span>No. Resi: <span class="font-mono bg-blue-50 px-1.5 py-0.5 rounded">{{ $order->tracking_number }}</span> ({{ $order->courier_name ?? 'Kurir' }})</span>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if($effProgress === 'pesan')
                                        <form action="{{ route('buyer.orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan #{{ $order->order_code }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-rose-300 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 transition shadow-2xs">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                <span>Batalkan Pesanan</span>
                                            </button>
                                        </form>
                                    @elseif($effProgress === 'kemas' || $effProgress === 'kirim')
                                        <span class="inline-flex items-center gap-1 rounded-xl bg-slate-100 border border-slate-200 px-3 py-1.5 text-[11px] text-slate-500 font-semibold" title="Pesanan sudah dalam tahap pengemasan/pengiriman">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            <span>Tidak bisa dibatalkan (sudah diproses)</span>
                                        </span>
                                    @endif

                                    @if($effProgress !== 'sampai' && $effProgress !== 'dibatalkan')
                                        <form action="{{ route('buyer.orders.delivered', $order) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-600 bg-[#EBF9F2] px-3.5 py-2 text-xs font-extrabold text-[#0B6E4F] hover:bg-[#0B6E4F] hover:text-white transition">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                <span>{{ $order->delivery_method === 'pickup' ? 'Konfirmasi Telah Diambil' : 'Konfirmasi Barang Sampai' }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ $order->store->whatsapp_url }}?text={{ $waInvoice }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                        <span>Hubungi WA Penjual</span>
                                    </a>
                                </div>
                            </div>

                            <!-- SKEMA PROGRES BARANG (DINAMIS 3 TAHAP / 4 TAHAP) -->
                            @if($effProgress !== 'dibatalkan')
                                @php
                                    $orderStages = $order->progress_stages;
                                    $stageCount = count($orderStages);
                                @endphp
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-[#0F291E] uppercase tracking-wider text-[11px]">Progres Pesanan</span>
                                            <span class="text-[10px] text-slate-500">({{ $stageCount }} Tahap: {{ $order->delivery_method_label }})</span>
                                        </div>
                                        @if($order->progress_note)
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-[#0B6E4F]">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                                <span>Catatan Toko: "{{ $order->progress_note }}"</span>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-{{ $stageCount }} gap-3">
                                        @foreach($orderStages as $stepNum => $meta)
                                            @php
                                                $isDone = $currentStep >= $stepNum;
                                                $isCurrent = $currentStep === $stepNum;
                                            @endphp
                                            <div class="rounded-2xl p-3.5 border transition {{ $isDone ? 'bg-[#EBF9F2] border-[#0B6E4F]/40' : 'bg-slate-50 border-slate-200/70 opacity-70' }}">
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <span class="w-7 h-7 rounded-full text-xs font-extrabold flex items-center justify-center {{ $isDone ? 'bg-[#0B6E4F] text-white' : 'bg-slate-200 text-slate-500' }}">
                                                        @if($isDone)
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                        @else
                                                            {{ $stepNum }}
                                                        @endif
                                                    </span>
                                                    @if($isCurrent)
                                                        <span class="rounded-full bg-[#0B6E4F] text-white px-2 py-0.5 text-[9px] font-extrabold uppercase">Aktif</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs font-extrabold text-[#0F291E]">{{ $meta['label'] }}</p>
                                                <p class="text-[11px] text-slate-500 leading-snug">{{ $meta['desc'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-700 space-y-1">
                                    <p class="font-bold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        <span>Pesanan ini telah dibatalkan.</span>
                                    </p>
                                    @if($order->progress_note)
                                        <p class="text-[11px] text-rose-600">Alasan: {{ $order->progress_note }}</p>
                                    @endif
                                </div>
                            @endif

                            <!-- Rincian Produk & Biaya -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 bg-[#F8FAFC] rounded-2xl p-4 border border-slate-200/70">
                                <div class="lg:col-span-7 space-y-2">
                                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Karya yang Dipesan</p>
                                    @foreach($order->items as $item)
                                        <div class="flex items-center justify-between text-xs py-1 border-b border-slate-200/60 last:border-0">
                                            <div class="flex items-center gap-2.5">
                                                <span class="font-bold text-[#0F291E]">{{ $item->product?->product_name ?? 'Karya Siswa' }}</span>
                                                <span class="text-slate-400">× {{ $item->quantity }}</span>
                                            </div>
                                            <span class="font-bold text-[#0F291E]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="lg:col-span-5 bg-white rounded-xl p-3.5 border border-slate-200/70 space-y-1.5 text-xs">
                                    <div class="flex justify-between text-slate-500">
                                        <span>Subtotal Produk</span>
                                        <span class="font-semibold">{{ $order->formatted_subtotal_price }}</span>
                                    </div>
                                    @if(($order->buyer_type_discount_amount ?? 0) > 0)
                                        <div class="flex justify-between text-emerald-700 font-semibold">
                                            <span>Diskon {{ $user->subtype_label }} ({{ $order->buyer_type_discount_percent }}%)</span>
                                            <span>- Rp {{ number_format($order->buyer_type_discount_amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if(($order->bazaar_discount_amount ?? 0) > 0)
                                        <div class="flex justify-between text-amber-700 font-semibold">
                                            <span>Diskon Bazar SMK ({{ $order->bazaar_discount_percent }}%)</span>
                                            <span>- Rp {{ number_format($order->bazaar_discount_amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-slate-600">
                                        <span>Ongkir ({{ $order->delivery_method_short_label }})</span>
                                        @if($order->delivery_method === 'shipping_transfer')
                                            <span class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md">
                                                Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir.
                                            </span>
                                        @else
                                            <span class="font-semibold {{ $order->shipping_cost > 0 ? 'text-[#0F291E]' : 'text-[#0B6E4F]' }}">
                                                {{ $order->shipping_cost > 0 ? 'Rp ' . number_format($order->shipping_cost, 0, ',', '.') : 'Rp 0 (Gratis)' }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex justify-between text-sm font-extrabold text-[#0F291E] pt-1.5 border-t border-slate-100">
                                        <span>Total Pembayaran</span>
                                        <span class="text-[#0B6E4F]">{{ $order->formatted_total_price }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($order->delivery_method === 'shipping_transfer' && $order->store->has_payment_account)
                                <div class="rounded-xl bg-amber-50/80 border border-amber-200 p-3 text-xs text-amber-950 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                        <span><strong>Rekening / E-Wallet Toko:</strong> {{ $order->store->payment_summary }}</span>
                                    </div>
                                    <span class="text-[10px] text-amber-800 font-bold bg-amber-200/60 px-2 py-0.5 rounded-full">Transfer Pembayaran</span>
                                </div>
                            @endif

                            <!-- Rating Bintang Interaktif -->
                            <div class="rounded-2xl {{ $order->buyer_rating ? 'bg-amber-50/70 border border-amber-200' : 'bg-[#EBF9F2]/60 border border-[#CDEFE0]' }} p-4 sm:p-5">
                                <form action="{{ route('buyer.orders.rate', $order) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-extrabold text-[#0F291E] flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                <span>{{ $order->buyer_rating ? 'Penilaian Bintang Anda untuk Transaksi Ini' : 'Berikan Rating Bintang untuk Toko' }}</span>
                                            </h3>
                                            <p class="text-[11px] text-slate-500">
                                                Apresiasi karya & pelayanan toko <strong>{{ $order->store->store_name }}</strong>.
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-1" data-rating-group="{{ $order->id }}">
                                            <input type="hidden" name="rating" id="rating-input-{{ $order->id }}" value="{{ $order->buyer_rating ?? 5 }}">
                                            @for($star = 1; $star <= 5; $star++)
                                                @php $activeStar = ($order->buyer_rating ?? 5) >= $star; @endphp
                                                <button type="button"
                                                        onclick="selectOrderStar({{ $order->id }}, {{ $star }})"
                                                        class="star-btn-{{ $order->id }} p-0.5 transition transform hover:scale-110 focus:outline-none {{ $activeStar ? 'text-amber-400' : 'text-slate-300' }}"
                                                        data-star="{{ $star }}"
                                                        title="Beri {{ $star }} Bintang">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                                </button>
                                            @endfor
                                            <span id="rating-label-{{ $order->id }}" class="ml-2 text-xs font-extrabold text-amber-700 bg-white px-2.5 py-1 rounded-lg border border-amber-200">
                                                {{ $order->buyer_rating ?? 5 }} / 5
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-2.5">
                                        <input type="text"
                                               name="review"
                                               value="{{ $order->buyer_review }}"
                                               placeholder="Tulis ulasan singkat Anda..."
                                               class="flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0B6E4F] px-5 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shrink-0 shadow-sm">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            <span>{{ $order->buyer_rating ? 'Perbarui Rating' : 'Kirim Rating' }}</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-white border border-slate-200 p-10 text-center space-y-3">
                            <p class="text-base font-extrabold text-[#0F291E]">Belum Ada Riwayat Pesanan</p>
                            <p class="text-xs text-slate-500 max-w-md mx-auto">
                                Belanja karya siswa sekarang untuk mendapatkan diskon khusus dan memantau status pesanan.
                            </p>
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition">
                                Jelajahi Katalog Karya →
                            </a>
                        </div>
                    @endforelse

                    @if($orders->hasPages())
                        <div class="pt-4">
                            {{ $orders->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SECTION 2: PROFIL AKUN PEMBELI & ALAMAT PENGIRIMAN                        -->
            <!-- ========================================================================= -->
            <div id="section-profil" class="buyer-content-section space-y-6 hidden">
                <div class="rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-6 shadow-sm">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F] mb-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            <span>KELOLA PROFIL AKUN</span>
                        </span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-[#0F291E]">Data Profil Akun & Alamat Pengiriman</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Perbarui identitas Anda, nomor WhatsApp aktif untuk konfirmasi pesanan, kategori pembeli (diskon khusus), dan alamat pengiriman.
                        </p>
                    </div>

                    <form action="{{ route('buyer.profile.update') }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                       class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs focus:border-[#0B6E4F] focus:outline-none font-semibold">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP Aktif *</label>
                                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required placeholder="Contoh: 08123456789"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs focus:border-[#0B6E4F] focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Email Akun (Login)</label>
                                <input type="email" value="{{ $user->email }}" disabled
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-500 font-mono">
                                <span class="text-[10px] text-slate-400">Email digunakan sebagai identitas akun tetap.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Status / Kategori Pembeli *</label>
                                <select name="user_subtype" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                    <option value="pelajar" {{ old('user_subtype', $user->user_subtype) === 'pelajar' ? 'selected' : '' }}>Pelajar SMK Plus Al Hilal (Diskon Pelajar)</option>
                                    <option value="keluarga" {{ old('user_subtype', $user->user_subtype) === 'keluarga' ? 'selected' : '' }}>Keluarga SMK Plus Al Hilal (Diskon Spesial)</option>
                                    <option value="alumni" {{ old('user_subtype', $user->user_subtype) === 'alumni' ? 'selected' : '' }}>Alumni SMK Plus Al Hilal</option>
                                    <option value="umum" {{ old('user_subtype', $user->user_subtype) === 'umum' ? 'selected' : '' }}>Pengunjung Umum</option>
                                </select>
                            </div>
                        </div>

                        <!-- Data Alamat Pengiriman -->
                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 sm:p-5 space-y-4">
                            <p class="text-xs font-extrabold text-[#0F291E] flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <span>Detail Wilayah & Alamat Pengiriman</span>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Desa / Kelurahan *</label>
                                    <input type="text" name="desa" value="{{ old('desa', $user->desa) }}" required placeholder="Nama Desa"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Kecamatan *</label>
                                    <input type="text" name="kecamatan" value="{{ old('kecamatan', $user->kecamatan) }}" required placeholder="Nama Kecamatan"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Kabupaten / Kota *</label>
                                    <select name="kabupaten" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                                        <option value="">Pilih Kabupaten / Kota</option>
                                        @foreach(\App\Models\Regency::getGroupedByProvince() as $prov => $rList)
                                            <optgroup label="Provinsi {{ $prov }}">
                                                @foreach($rList as $rItem)
                                                    <option value="{{ $rItem->name }}" @selected(old('kabupaten', $user->kabupaten) === $rItem->name || old('kabupaten', $user->kabupaten) === str_replace(['Kabupaten ', 'Kota '], '', $rItem->name))>{{ $rItem->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div class="sm:col-span-1">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode Pos</label>
                                    <input type="text" name="kode_pos" value="{{ old('kode_pos', $user->kode_pos) }}" placeholder="45161"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Alamat Lengkap / Patokan Rumah</label>
                                    <input type="text" name="address_detail" value="{{ old('address_detail', $user->address_detail) }}" placeholder="Contoh: Jl. Pesantren No. 12, Samping Mushola Al-Barokah"
                                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Ganti Password Opsional -->
                        <div class="rounded-xl border border-slate-200 p-4 bg-white">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Ganti Password (Opsional)</label>
                            <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password..."
                                   class="w-full sm:w-1/2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <span>Simpan Perubahan Profil</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Tab Switcher Akun Pembeli (Pesanan vs Profil)
    function switchBuyerTab(tabName) {
        const secPesanan = document.getElementById('section-pesanan');
        const secProfil  = document.getElementById('section-profil');
        const sidePesanan = document.getElementById('side-tab-pesanan');
        const sideProfil  = document.getElementById('side-tab-profil');
        const quickPesanan = document.getElementById('quick-tab-pesanan');
        const quickProfil  = document.getElementById('quick-tab-profil');
        const topbarTitle  = document.getElementById('topbar-section-title');

        if (tabName === 'profil') {
            if (secPesanan) secPesanan.classList.add('hidden');
            if (secProfil) secProfil.classList.remove('hidden');

            if (sidePesanan) {
                sidePesanan.classList.remove('bg-[#EBF9F2]', 'text-[#0B6E4F]', 'font-bold');
                sidePesanan.classList.add('text-slate-600');
            }
            if (sideProfil) {
                sideProfil.classList.add('bg-[#EBF9F2]', 'text-[#0B6E4F]', 'font-bold');
                sideProfil.classList.remove('text-slate-600');
            }

            if (quickPesanan) {
                quickPesanan.classList.remove('bg-[#0B6E4F]', 'text-white', 'shadow-xs');
                quickPesanan.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-600');
            }
            if (quickProfil) {
                quickProfil.classList.add('bg-[#0B6E4F]', 'text-white', 'shadow-xs');
                quickProfil.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-600');
            }

            if (topbarTitle) topbarTitle.textContent = 'Profil Akun Pembeli';
            history.replaceState(null, null, '#menu-profil');
        } else {
            if (secPesanan) secPesanan.classList.remove('hidden');
            if (secProfil) secProfil.classList.add('hidden');

            if (sidePesanan) {
                sidePesanan.classList.add('bg-[#EBF9F2]', 'text-[#0B6E4F]', 'font-bold');
                sidePesanan.classList.remove('text-slate-600');
            }
            if (sideProfil) {
                sideProfil.classList.remove('bg-[#EBF9F2]', 'text-[#0B6E4F]', 'font-bold');
                sideProfil.classList.add('text-slate-600');
            }

            if (quickPesanan) {
                quickPesanan.classList.add('bg-[#0B6E4F]', 'text-white', 'shadow-xs');
                quickPesanan.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-600');
            }
            if (quickProfil) {
                quickProfil.classList.remove('bg-[#0B6E4F]', 'text-white', 'shadow-xs');
                quickProfil.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-600');
            }

            if (topbarTitle) topbarTitle.textContent = 'Pesanan Saya';
            history.replaceState(null, null, '#menu-pesanan');
        }

        // Otomatis tutup drawer di mobile
        toggleBuyerSidebar(false);
    }

    // Toggle Drawer Sidebar Mobile Pembeli
    function toggleBuyerSidebar(open) {
        const sidebar = document.getElementById('buyerSidebar');
        const overlay = document.getElementById('buyerOverlay');
        if (sidebar) sidebar.classList.toggle('-translate-x-full', !open);
        if (overlay) overlay.classList.toggle('hidden', !open);
    }

    // Rating Bintang Interaktif
    function selectOrderStar(orderId, starValue) {
        const input = document.getElementById('rating-input-' + orderId);
        const label = document.getElementById('rating-label-' + orderId);
        if (input) input.value = starValue;
        if (label) label.textContent = starValue + ' / 5';

        document.querySelectorAll('.star-btn-' + orderId).forEach(btn => {
            const s = parseInt(btn.getAttribute('data-star') || '1', 10);
            if (s <= starValue) {
                btn.classList.remove('text-slate-300');
                btn.classList.add('text-amber-400');
            } else {
                btn.classList.remove('text-amber-400');
                btn.classList.add('text-slate-300');
            }
        });
    }

    // Auto-buka tab sesuai Hash URL saat load atau saat terjadi error validasi
    document.addEventListener('DOMContentLoaded', function () {
        if (window.location.hash === '#menu-profil' || {{ $errors->any() ? 'true' : 'false' }}) {
            switchBuyerTab('profil');
        } else {
            switchBuyerTab('pesanan');
        }
    });
</script>
@endsection
