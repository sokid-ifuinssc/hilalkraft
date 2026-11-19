{{--
============================================================================
VIEW: DASHBOARD PEMILIK TOKO & PRODUK SAYA (seller/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman kelola produk & profil pemilik toko sesuai referensi
  desain Figma `Kelola produk · Desktop.png` (Sidebar Ruang Karya Siswa,
  4 Kartu Ringkasan, Tabel Produk Saya dengan indikator Lihat, Suka,
  Komentar, Daftar Pesanan, serta Form Edit Profil Lengkap Satu Toko Satu
  Akun bagi Siswa maupun Guru).
Algoritma:
  1. Menghitung jumlah produk dengan stok menipis (`stock <= 3`) untuk
     menampilkan banner peringatan stok secara otomatis.
  2. Fungsi JS `filterSellerTable(query)` menyaring baris produk secara
     real-time pada sisi klien berdasarkan nama atau kode produk.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Produk Saya — Dashboard Pemilik Toko hilalkraf SMK PAL')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $effectiveClasses = $classList->count() > 0 ? $classList : collect(['X', 'XI', 'XII']);
    $effectiveMajors  = $majorList->count() > 0 ? $majorList : collect([
        'Teknik Komputer dan Jaringan Telekomunikasi (TKJT)',
        'Teknik Otomotif (TO)',
        'Akuntansi dan Keuangan Lembaga (AKL)',
    ]);
    $lowStockProducts = $products->filter(fn($p) => $p->stock <= 3);
@endphp

<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E] relative">

    <!-- Backdrop Overlay Drawer Mobile -->
    <div id="sellerOverlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 hidden lg:hidden transition-opacity" onclick="toggleSellerSidebar(false)"></div>

    <!-- Sidebar Kiri Sesuai Desain `Kelola produk · Desktop.png` (Responsive Drawer di Mobile & Sticky di Desktop) -->
    <aside id="sellerSidebar" class="w-64 bg-white border-r border-slate-200/80 fixed lg:sticky top-0 left-0 z-50 h-screen p-6 flex flex-col justify-between shrink-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out overflow-y-auto">
        <div class="space-y-6">
            <!-- Header Sidebar & Tombol Close Mobile -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 lg:border-none lg:pb-0">
                <a href="{{ route('home') }}" class="flex flex-col gap-2">
                    <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-2.5 py-1.5 shadow-sm">
                        <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                             alt="hilalkraf"
                             class="h-10 w-auto object-contain">
                        <span class="h-6 w-[1px] bg-slate-200"></span>
                        <img src="{{ asset('images/logo-smk-alhilal.png') }}"
                             alt="SMK Plus Al Hilal"
                             class="h-8 w-8 object-contain">
                    </div>
                    <div>
                        <span class="text-base font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                        <span class="text-[9px] font-bold text-[#0B6E4F]">Inkubator Bisnis Muda SMK PAL</span>
                    </div>
                </a>
                <button type="button" onclick="toggleSellerSidebar(false)" class="lg:hidden p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 text-lg leading-none font-bold" title="Tutup Menu">
                    &times;
                </button>
            </div>

            <!-- Identitas Ruang Karya -->
            <div class="space-y-1">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    {{ in_array($user->role, ['admin', 'super_admin']) || $user->is_teacher || ($store->seller_type ?? '') === 'guru' ? 'RUANG KARYA GURU' : 'RUANG KARYA SISWA' }}
                </p>
                <p class="text-xs font-extrabold text-[#0F291E] truncate">{{ $store->student_class ?: $store->store_name }}</p>
                <span class="inline-block rounded-full {{ $store->is_verified ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-700' }} px-2.5 py-0.5 text-[10px] font-bold">
                    {{ $store->is_verified ? 'Toko terverifikasi' : 'Menunggu verifikasi' }}
                </span>
            </div>

            <!-- Menu Navigasi Sidebar -->
            <nav class="space-y-1 text-xs font-semibold" id="seller-sidebar-nav">
                @if(in_array($user->role, ['admin', 'super_admin']))
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-xl px-3.5 py-2 bg-emerald-50 text-[#0B6E4F] font-bold hover:bg-emerald-100 transition border border-emerald-200/60 mb-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span>Panel Admin</span>
                    </a>
                @endif
                <button type="button" onclick="switchSellerTab('all')" class="seller-nav-item w-full flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition text-left" data-target="all">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <span>Semua Menu</span>
                </button>
                <button type="button" onclick="switchSellerTab('produk')" class="seller-nav-item w-full flex items-center justify-between rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold text-left transition" data-target="produk">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        <span>Produk Saya</span>
                    </span>
                    <span class="rounded-full bg-white/80 px-2 py-0.5 text-[10px]">{{ $products->total() }}</span>
                </button>
                <button type="button" onclick="switchSellerTab('pesanan')" class="seller-nav-item w-full flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition text-left" data-target="pesanan">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                        <span>Progres Pesanan</span>
                    </span>
                    <span class="rounded-full bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">{{ $orders->count() }}</span>
                </button>
                @php $unreadChatsSeller = \App\Models\StoreConversation::totalUnreadFor($user); @endphp
                <a href="{{ route('chat.index') }}" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        <span>Chat Pembeli</span>
                    </span>
                    @if($unreadChatsSeller > 0)
                        <span class="rounded-full bg-rose-600 text-white px-2 py-0.5 text-[10px] font-bold">{{ $unreadChatsSeller }}</span>
                    @endif
                </a>
                <button type="button" onclick="switchSellerTab('diskon')" class="seller-nav-item w-full flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition text-left" data-target="diskon">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        <span>Diskon & Bazar</span>
                    </span>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Promo</span>
                </button>
                <button type="button" onclick="switchSellerTab('profil')" class="seller-nav-item w-full flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition text-left" data-target="profil">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M9 20V14h6v6" /></svg>
                    <span>Profil Toko</span>
                </button>
                <a href="{{ route('seller.reports') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span>Laporan Penjualan</span>
                </a>
            </nav>
        </div>

        <!-- Bagian Bawah Sidebar -->
        <div class="space-y-4">
            <div class="rounded-2xl bg-[#EBF9F2] p-4 space-y-1.5">
                <span class="w-6 h-6 rounded-full bg-white text-[#0B6E4F] flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </span>
                <p class="text-xs font-bold text-[#0F291E] pt-1">Butuh bantuan?</p>
                <p class="text-[11px] text-slate-500">Tim hilalkraft siap membantu.</p>
                <button type="button" onclick="switchSellerTab('profil')" class="inline-block text-[11px] font-bold text-[#0B6E4F] hover:underline pt-0.5 text-left">
                    Hubungi pengelola →
                </button>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#0B6E4F] transition">↗ Beranda</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-rose-600 font-semibold hover:underline">Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Konten Utama -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar dengan Tombol Hamburger Mobile -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-4 sm:px-10 flex items-center justify-between gap-3 sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSellerSidebar(true)" class="lg:hidden p-2 rounded-xl text-slate-700 hover:bg-slate-100 border border-slate-200 flex items-center gap-1.5 font-bold text-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <span>Menu</span>
                </button>
                <div class="text-xs text-slate-400 hidden sm:flex items-center gap-2">
                    <a href="{{ route('home') }}" class="font-bold text-[#0B6E4F]">← Beranda</a>
                    <span>/</span>
                    <span>Panel Toko</span>
                    <span>/</span>
                    <span class="text-slate-700 font-semibold truncate max-w-[160px]">{{ $store->store_name }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if(in_array($user->role, ['admin', 'super_admin']))
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 text-[#0B6E4F] border border-emerald-200/60 px-3 py-1.5 text-xs font-bold hover:bg-emerald-100 transition">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span>Panel Admin</span>
                    </a>
                @endif
                <a href="{{ route('home') }}" class="text-xs font-bold text-[#0B6E4F] hover:underline hidden xs:inline">
                    ↗ Lihat Web
                </a>
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ $user->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $store->seller_type_label }}</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Floating Toast Notifikasi Sukses / Gagal Realtime -->
        @if(session('success') || session('error') || $errors->any())
            <div id="seller-floating-toast" class="fixed top-5 right-5 z-[9999] max-w-md w-[92vw] sm:w-[420px] transition-all duration-300">
                @if(session('success'))
                    <div class="rounded-2xl bg-white border-2 border-[#10B981] p-4 shadow-2xl flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-[#0B6E4F] uppercase tracking-wider">Perubahan Berhasil Disimpan</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5 leading-snug">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('seller-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-2xl bg-white border-2 border-rose-500 p-4 shadow-2xl flex items-start gap-3 mt-2">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-rose-600 uppercase tracking-wider">Perhatian / Gagal</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5 leading-snug">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('seller-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="rounded-2xl bg-white border-2 border-rose-500 p-4 shadow-2xl flex items-start gap-3 mt-2">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base shrink-0">
                            !
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-rose-600 uppercase tracking-wider">Terdapat Kesalahan Input</p>
                            <ul class="text-xs text-slate-700 font-medium mt-0.5 list-disc list-inside space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" onclick="document.getElementById('seller-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Isi Halaman -->
        <div class="p-4 sm:p-8 lg:p-10 space-y-6 max-w-6xl w-full mx-auto">

            <!-- Bilah Navigasi Tab Cepat (Quick Switcher Pills Anti-Scroll Panjang di Mobile & Desktop) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs font-bold -mx-1 px-1">
                <button type="button" onclick="switchSellerTab('all')" class="seller-pill inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-[#0B6E4F] text-white" data-pill="all">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <span>Semua Menu</span>
                </button>
                <button type="button" onclick="switchSellerTab('produk')" class="seller-pill inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-white text-slate-700 hover:bg-slate-100 border border-slate-200" data-pill="produk">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    <span>Produk Saya ({{ $products->total() }})</span>
                </button>
                <button type="button" onclick="switchSellerTab('pesanan')" class="seller-pill inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-white text-slate-700 hover:bg-slate-100 border border-slate-200" data-pill="pesanan">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                    <span>Progres Pesanan ({{ $orders->count() }})</span>
                </button>
                <button type="button" onclick="switchSellerTab('diskon')" class="seller-pill inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-white text-slate-700 hover:bg-slate-100 border border-slate-200" data-pill="diskon">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                    <span>Diskon & Bazar</span>
                </button>
                <button type="button" onclick="switchSellerTab('profil')" class="seller-pill inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-white text-slate-700 hover:bg-slate-100 border border-slate-200" data-pill="profil">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1-5h16l1 5M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M9 20V14h6v6" /></svg>
                    <span>Profil Toko</span>
                </button>
                <a href="{{ route('seller.reports') }}" class="rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <span>Laporan Penjualan</span>
                </a>
            </div>

            <!-- Ringkasan & 4 Kartu KPI -->
            <div id="section-ringkasan-wrap" class="seller-main-section space-y-6">
                <!-- Judul & Tombol Tambah Produk Baru -->
                <div id="section-ringkasan" class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">Dashboard & Produk Toko</h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Kelola karya, pantau stok, diskon, dan pesanan pembeli.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('seller.reports') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#CDEFE0] bg-[#EBF9F2] px-4 py-2.5 text-xs sm:text-sm font-semibold text-[#0B6E4F] hover:bg-[#D5F5E6] transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            <span>Laporan</span>
                        </a>
                        <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] px-4.5 py-2.5 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm">
                            <span>+ Tambah Produk Baru</span>
                        </a>
                    </div>
                </div>

                <!-- 4 Kartu KPI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Produk aktif</span>
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ $store->products_count }}</p>
                        <p class="text-[11px] font-semibold text-[#0B6E4F]">Semua sudah tayang</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Total dilihat</span>
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($store->views_count + $products->sum('views_count')) }}</p>
                        <p class="text-[11px] font-semibold text-[#0B6E4F]">Pengunjung & akun</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Suka & Komentar</span>
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">
                            {{ number_format($products->sum('likes_count')) }} <span class="text-sm font-semibold text-slate-400">/ {{ number_format($store->comments_count ?? 0) }} ulasan</span>
                        </p>
                        <p class="text-[11px] font-semibold text-[#0B6E4F]">Apresiasi karya</p>
                    </div>

                    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span>Stok menipis</span>
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ $lowStockProducts->count() }}</p>
                        <p class="text-[11px] font-semibold text-[#0B6E4F]">{{ $lowStockProducts->count() > 0 ? 'Perlu segera ditambah' : 'Stok aman terkendali' }}</p>
                    </div>
                </div>

                <!-- Banner Info Stok Menipis -->
                @if($lowStockProducts->first())
                    <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] px-4 py-3 text-xs text-[#0B6E4F] flex items-center gap-2.5 font-medium">
                        <span class="font-bold">ⓘ</span>
                        <span>{{ $lowStockProducts->first()->product_name }} tersisa {{ $lowStockProducts->first()->stock }} unit. Tambah stok agar pembeli tidak kehabisan.</span>
                    </div>
                @endif
            </div>

            <!-- Tabel Produk Saya -->
            <div id="section-produk" class="seller-main-section rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
                <div class="p-5 space-y-4 border-b border-slate-100">
                    <div class="flex items-center gap-5 text-xs font-bold">
                        <span class="text-[#0B6E4F]">Semua produk ({{ $products->total() }})</span>
                        <span class="text-slate-400">Aktif ({{ $products->total() }})</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <input
                                type="search"
                                oninput="filterSellerTable(this.value)"
                                placeholder="Cari nama atau ID produk..."
                                class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] pl-9 pr-4 py-2 text-xs text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none"
                            >
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-5">NAMA PRODUK</th>
                                <th class="py-3 px-4">HARGA</th>
                                <th class="py-3 px-4">STOK</th>
                                <th class="py-3 px-4">STATUS</th>
                                <th class="py-3 px-4">INTERAKSI</th>
                                <th class="py-3 px-5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="seller-product-rows">
                            @forelse($products as $product)
                                <tr class="hover:bg-slate-50/70 transition seller-product-row" data-name="{{ strtolower($product->product_name . ' hk-00' . $product->id) }}">
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ Str::startsWith($product->image, 'http') ? $product->image : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image)) }}"
                                                 alt="{{ $product->product_name }}"
                                                 class="w-11 h-11 rounded-xl object-cover bg-slate-100 shrink-0"
                                                 onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                                            <div>
                                                <p class="font-bold text-[#0F291E] text-xs sm:text-sm">{{ $product->product_name }}</p>
                                                <p class="text-[11px] text-slate-400">HK-{{ str_pad((string) $product->id, 3, '0', STR_PAD_LEFT) }} • {{ $product->category }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-[#0F291E]">{{ $product->formatted_price }}</td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-[#0F291E]">{{ $product->stock }}</span>
                                        @if($product->stock <= 3)
                                            <span class="block text-[10px] font-semibold text-amber-600">Stok menipis</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="rounded-md bg-[#EBF9F2] px-2.5 py-1 text-[10px] font-bold text-[#0B6E4F]">Aktif</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-medium text-slate-600">
                                        <span class="inline-flex items-center gap-2.5 text-[11px]">
                                            <span class="inline-flex items-center gap-1" title="Dilihat">
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                <span>{{ $product->views_count ?? 0 }}</span>
                                            </span>
                                            <span class="inline-flex items-center gap-1" title="Disukai">
                                                <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                                <span>{{ $product->likes_count ?? 0 }}</span>
                                            </span>
                                            <span class="inline-flex items-center gap-1" title="Komentar">
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                                <span>{{ $product->comments_count ?? 0 }}</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-3">
                                        <a href="{{ route('seller.products.edit', $product) }}" class="font-bold text-[#0B6E4F] hover:underline">Edit</a>
                                        <form action="{{ route('seller.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Hapus produk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                        <a href="{{ route('products.show', $product->slug) }}" class="text-slate-400 hover:text-[#0F291E]">View ↗</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-slate-400">
                                        Belum ada produk di toko ini. Klik <strong>+ Tambah Produk Baru</strong> untuk mulai mengunggah karya.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Menampilkan {{ $products->count() }} dari {{ $products->total() }} produk</span>
                    <div>{{ $products->links() }}</div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SKEMA PROGRES PESANAN PEMILIK TOKO (PESAN -> KEMAS -> KIRIM -> SAMPAI)     -->
            <!-- ========================================================================= -->
            <div id="section-pesanan" class="seller-main-section rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-bold text-[#0B6E4F] mb-2">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                            <span>Skema Progres Transaksi & Pengiriman</span>
                        </span>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Kelola Progres Pesanan Pembeli</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Mendukung 3 metode: <strong>Datang ke Sekolah</strong> (3 Tahap: Pesan → Kemas → Diterima), <strong>Delivery Lokal</strong> (4 Tahap, Khusus Arjawinangun, Susukan, Kaliwedi), dan <strong>Transfer Antar</strong> (+ Ongkir Ekspedisi & No. Resi).
                        </p>
                    </div>

                    <!-- Filter Tahap Progres -->
                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                        <button type="button" onclick="filterSellerOrders('all')" class="seller-order-tab rounded-xl bg-[#0B6E4F] text-white px-3 py-1.5 transition" data-tab="all">
                            Semua ({{ $orders->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('pesan')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="pesan">
                            1. Pesan ({{ $orders->filter(fn($o) => $o->effective_progress === 'pesan')->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('kemas')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="kemas">
                            2. Kemas ({{ $orders->filter(fn($o) => $o->effective_progress === 'kemas')->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('kirim')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="kirim">
                            3. Kirim ({{ $orders->filter(fn($o) => $o->effective_progress === 'kirim')->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('sampai')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="sampai">
                            4. Sampai ({{ $orders->filter(fn($o) => $o->effective_progress === 'sampai')->count() }})
                        </button>
                    </div>
                </div>

                @php
                    $stagesMeta = [
                        1 => ['key' => 'pesan', 'label' => '1. Dipesan', 'desc' => 'Pesanan masuk / WA'],
                        2 => ['key' => 'kemas', 'label' => '2. Dikemas', 'desc' => 'Disiapkan penjual'],
                        3 => ['key' => 'kirim', 'label' => '3. Dikirim', 'desc' => 'Dalam perjalanan / COD'],
                        4 => ['key' => 'sampai', 'label' => '4. Sampai', 'desc' => 'Diterima & beri ulasan'],
                    ];
                @endphp

                <div class="space-y-4" id="seller-orders-container">
                    @forelse($orders as $order)
                        @php
                            $currentStep = $order->progress_step;
                            $effProgress = $order->effective_progress;
                            $buyerSubtypeLabel = $store->getBuyerSubtypeLabel($order->buyer?->user_subtype ?? 'umum');
                            $buyerPhoneClean = preg_replace('/[^0-9]/', '', $order->buyer_phone ?? $order->buyer?->phone ?? '');
                            if (str_starts_with($buyerPhoneClean, '0')) {
                                $buyerPhoneClean = '62' . substr($buyerPhoneClean, 1);
                            }
                            $waBuyerText = urlencode("Halo Kak {$order->buyer?->name}, kami dari *{$store->store_name}* (hilalkraf SMK Plus Al Hilal) mengabarkan progres pesanan *{$order->order_code}* saat ini: *{$order->progress_label}*.");
                        @endphp
                        <div class="seller-order-card rounded-2xl border border-slate-200/90 bg-[#F8FAFC]/60 p-5 space-y-4 transition hover:border-emerald-500/40" data-progress="{{ $effProgress }}">
                            <!-- Baris Atas: Kode Pesanan, Pembeli, Jenis Pembeli, & Status Badge -->
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/70 pb-3.5">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-extrabold text-sm text-[#0F291E]">#{{ $order->order_code }}</span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-white border border-slate-200 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                                            <svg class="w-3 h-3 text-slate-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            <span>{{ $order->buyer?->name ?? 'Pembeli' }}</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                            <span>{{ $buyerSubtypeLabel }}</span>
                                        </span>
                                        @if($order->is_bazaar_offline || $order->bazaar_voucher_code)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800">
                                                Acara Bazar SMK ({{ $order->bazaar_voucher_code ?? 'Hadir di Bazar' }})
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                        <span>{{ $order->shipping_address }}</span>
                                        <span>&bull; {{ $order->buyer_phone }}</span>
                                        <span>&bull; {{ $order->created_at?->format('d M Y, H:i') }}</span>
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if($order->buyer)
                                        <a href="{{ route('chat.start.buyer', $order->buyer) }}"
                                           class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-3 py-1.5 text-[11px] font-bold text-white hover:bg-[#08583F] transition">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                            <span>Chat Pembeli</span>
                                        </a>
                                    @endif
                                    @if($buyerPhoneClean)
                                        <a href="https://wa.me/{{ $buyerPhoneClean }}?text={{ $waBuyerText }}"
                                           target="_blank"
                                           rel="noopener"
                                           class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-600 bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 hover:bg-emerald-600 hover:text-white transition">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                            <span>Hubungi WA Pembeli</span>
                                        </a>
                                    @endif
                                    <!-- Badge Metode Transaksi & Pengiriman -->
                                    <span class="rounded-xl px-2.5 py-1 text-[10px] font-extrabold border {{ $order->delivery_badge_class }}">
                                        {{ $order->delivery_method_short_label }}
                                    </span>
                                    <span class="rounded-xl px-3 py-1.5 text-[11px] font-extrabold {{ $effProgress === 'sampai' ? 'bg-emerald-100 text-emerald-800' : ($effProgress === 'dibatalkan' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $order->progress_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Visual Stepper Progres Dinamis (3 Tahap / 4 Tahap Sesuai Metode) -->
                            @if($effProgress !== 'dibatalkan')
                                @php
                                    $orderStages = $order->progress_stages;
                                    $stageCount = count($orderStages);
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-{{ $stageCount }} gap-2.5 pt-1">
                                    @foreach($orderStages as $stepNum => $meta)
                                        @php
                                            $isDone = $currentStep >= $stepNum;
                                            $isCurrent = $currentStep === $stepNum;
                                        @endphp
                                        <div class="rounded-xl p-3 border transition {{ $isDone ? 'bg-[#EBF9F2] border-[#0B6E4F]/30 text-[#0F291E]' : 'bg-white border-slate-200/80 text-slate-400' }}">
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span class="w-6 h-6 rounded-full text-[11px] font-extrabold flex items-center justify-center {{ $isDone ? 'bg-[#0B6E4F] text-white' : 'bg-slate-100 text-slate-400' }}">
                                                    @if($isDone)
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                    @else
                                                        {{ $stepNum }}
                                                    @endif
                                                </span>
                                                @if($isCurrent)
                                                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#0B6E4F] text-white">Tahap Saat Ini</span>
                                                @endif
                                            </div>
                                            <p class="text-xs font-extrabold {{ $isDone ? 'text-[#0F291E]' : 'text-slate-400' }}">{{ $meta['label'] }}</p>
                                            <p class="text-[10px] {{ $isDone ? 'text-[#0B6E4F]' : 'text-slate-400' }}">{{ $meta['desc'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    <span>Pesanan ini telah dibatalkan.</span>
                                </div>
                            @endif

                            <!-- Daftar Item & Rincian Diskon Pembeli / Bazar -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start bg-white rounded-xl p-4 border border-slate-200/70">
                                <div class="lg:col-span-7 space-y-2">
                                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Item Karya yang Dipesan</p>
                                    @foreach($order->items as $item)
                                        <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100 last:border-0">
                                            <div>
                                                <span class="font-bold text-[#0F291E]">{{ $item->product?->product_name ?? 'Produk Karya' }}</span>
                                                <span class="text-slate-400 ml-1">× {{ $item->quantity }}</span>
                                            </div>
                                            <span class="font-semibold text-slate-700">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach

                                    @if($order->notes)
                                        <p class="text-[11px] text-slate-500 pt-1">
                                            <strong>Catatan Pembeli:</strong> "{{ $order->notes }}"
                                        </p>
                                    @endif
                                    @if($order->progress_note)
                                        <p class="text-[11px] text-[#0B6E4F] font-semibold pt-0.5">
                                            <strong>Info Pengiriman Toko:</strong> "{{ $order->progress_note }}"
                                        </p>
                                    @endif
                                </div>

                                <div class="lg:col-span-5 bg-[#F8FAFC] rounded-xl p-3.5 border border-slate-200/60 space-y-1.5 text-xs">
                                    <div class="flex justify-between text-slate-500">
                                        <span>Subtotal Harga Karya</span>
                                        <span class="font-semibold">{{ $order->formatted_subtotal_price }}</span>
                                    </div>
                                    @if(($order->buyer_type_discount_amount ?? 0) > 0)
                                        <div class="flex justify-between text-emerald-700 font-semibold">
                                            <span>Diskon {{ $buyerSubtypeLabel }} ({{ $order->buyer_type_discount_percent }}%)</span>
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
                                        <span>Ongkos Kirim ({{ $order->delivery_method_short_label }})</span>
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
                                    <div class="flex justify-between text-sm font-extrabold text-[#0F291E] pt-1.5 border-t border-slate-200">
                                        <span>Total Transaksi</span>
                                        <span class="text-[#0B6E4F]">{{ $order->formatted_total_price }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tampilan Rating Bintang dari Pembeli (Jika Sudah Memberi Rating) -->
                            @if($order->buyer_rating)
                                <div class="rounded-xl bg-amber-50/90 border border-amber-200 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 text-xs font-extrabold text-amber-700">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-3.5 h-3.5 {{ $i <= $order->buyer_rating ? 'text-amber-500' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                            @endfor
                                            <span class="ml-0.5">({{ $order->buyer_rating }}/5)</span>
                                        </span>
                                        <span class="text-xs font-bold text-[#0F291E]">Rating dari {{ $order->buyer?->name }}:</span>
                                        <span class="text-xs text-slate-600 italic">"{{ $order->buyer_review ?: 'Terima kasih, karya sangat bagus!' }}"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-amber-700">Dinilai {{ $order->rated_at?->diffForHumans() }}</span>
                                </div>
                            @endif

                            <!-- Form Kontrol Ubah Tahap Progres oleh Pemilik Toko -->
                            @if($effProgress === 'sampai')
                                <div class="rounded-xl bg-[#EBF9F2] border border-[#B8E8D2] px-4 py-2.5 text-xs text-[#0B6E4F] font-bold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span>Pesanan ini telah selesai ({{ $order->delivery_method === 'pickup' ? 'Telah Diambil di Sekolah' : 'Sampai ke Pembeli' }}) dan tidak dapat dibatalkan.</span>
                                </div>
                            @elseif($effProgress === 'dibatalkan')
                                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs text-rose-700 font-bold flex items-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    <span>Pesanan ini telah dibatalkan.</span>
                                </div>
                            @else
                                <form action="{{ route('seller.orders.status', $order) }}" method="POST" class="space-y-3 pt-1">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                                        <div class="sm:w-64">
                                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">
                                                Tahap Progres ({{ $order->delivery_method_short_label }})
                                            </label>
                                            <select name="progress_status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                                @if($order->delivery_method === 'pickup')
                                                    <option value="pesan" @selected($effProgress === 'pesan')>1. Pesan (Pesanan Masuk)</option>
                                                    <option value="kemas" @selected($effProgress === 'kemas')>2. Kemas (Disiapkan di Sekolah/Stand)</option>
                                                    <option value="sampai" @selected($effProgress === 'sampai')>3. Selesai (Telah Diambil di Sekolah)</option>
                                                @elseif($order->delivery_method === 'delivery_lokal')
                                                    <option value="pesan" @selected($effProgress === 'pesan')>1. Pesan (Pesanan Diterima)</option>
                                                    <option value="kemas" @selected($effProgress === 'kemas')>2. Kemas (Sedang Dikemas)</option>
                                                    <option value="kirim" @selected($effProgress === 'kirim')>3. Kirim (Kurir Sekolah Mengantar)</option>
                                                    <option value="sampai" @selected($effProgress === 'sampai')>4. Sampai (Diterima Pembeli)</option>
                                                @else
                                                    <option value="pesan" @selected($effProgress === 'pesan')>1. Pesan (Menunggu Transfer/Konfirmasi)</option>
                                                    <option value="kemas" @selected($effProgress === 'kemas')>2. Kemas (Sedang Dipacking)</option>
                                                    <option value="kirim" @selected($effProgress === 'kirim')>3. Kirim (Diserahkan ke Ekspedisi)</option>
                                                    <option value="sampai" @selected($effProgress === 'sampai')>4. Sampai (Diterima Pembeli)</option>
                                                @endif
                                                <option value="dibatalkan">Batalkan Pesanan</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Catatan Progres / Info Pengiriman</label>
                                            <input type="text" name="progress_note" value="{{ $order->progress_note }}" placeholder="Contoh: Barang siap diambil di Stand TKJ / Diantar kurir..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                        </div>
                                    </div>

                                    @if($order->delivery_method === 'shipping_transfer')
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                            <div>
                                                <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-0.5">Nama Ekspedisi (JNE / J&T / SiCepat / dll)</label>
                                                <input type="text" name="courier_name" value="{{ $order->courier_name }}" placeholder="JNE / J&T / SiCepat" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-0.5">Nomor Resi Pengiriman</label>
                                                <input type="text" name="tracking_number" value="{{ $order->tracking_number }}" placeholder="Nomor Resi Ekspedisi" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-mono font-bold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex justify-end pt-1">
                                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#0B6E4F] px-5 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            <span>Simpan Perubahan Progres</span>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 p-10 text-center space-y-2">
                            <p class="text-sm font-bold text-[#0F291E]">Belum Ada Pesanan Masuk</p>
                            <p class="text-xs text-slate-400 max-w-md mx-auto">
                                Saat pembeli memesan karya melalui keranjang atau tombol <strong>Pesan & Hubungi Penjual</strong> di halaman produk, progres pesanan (Pesan → Kemas → Kirim → Sampai) akan otomatis tampil di sini.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SKEMA DISKON JENIS PEMBELI & ACARA BAZAR SMK OLEH PEMILIK TOKO            -->
            <!-- ========================================================================= -->
            <div id="section-diskon" class="seller-main-section rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-6">
                <div class="border-b border-slate-100 pb-4 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200/80 px-3 py-1 text-[11px] font-bold text-amber-800 mb-2">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            <span>Skema Diskon Pembeli & Bazar SMK Plus Al Hilal</span>
                        </span>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Atur Diskon Berdasarkan Jenis Pembeli & Event Bazar</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Penjual dapat memberikan persentase diskon otomatis untuk jenis pembeli (<strong>Pelajar</strong>, <strong>Keluarga SMK Plus Al Hilal</strong>, <strong>Alumni</strong>, atau <strong>Umum</strong>) serta diskon tambahan bagi pembeli yang hadir langsung pada <strong>Acara Bazar / Event SMK</strong>.
                        </p>
                    </div>
                </div>

                <form action="{{ route('seller.discounts.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">Diskon Pelajar</span>
                                <span class="text-[10px] font-bold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">Siswa/Pelajar</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan khusus bagi pembeli berstatus Pelajar.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_pelajar" min="0" max="90" value="{{ old('discount_pelajar', $store->discount_pelajar ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">Keluarga SMK</span>
                                <span class="text-[10px] font-bold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">Warga SMK PAL</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan khusus Guru, Staf & Keluarga Besar SMK Plus Al Hilal.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_keluarga" min="0" max="90" value="{{ old('discount_keluarga', $store->discount_keluarga ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">Diskon Alumni</span>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-200/70 px-2 py-0.5 rounded-full">Alumni</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan harga bagi pembeli dari Ikatan Alumni SMK PAL.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_alumni" min="0" max="90" value="{{ old('discount_alumni', $store->discount_alumni ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">Pembeli Umum</span>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-200/70 px-2 py-0.5 rounded-full">Masyarakat</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan promo reguler untuk pembeli masyarakat umum.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_umum" min="0" max="90" value="{{ old('discount_umum', $store->discount_umum ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-amber-900">Ekstra Bazar</span>
                                <span class="text-[10px] font-bold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-full">Hadir di Bazar</span>
                            </div>
                            <p class="text-[11px] text-amber-800/80">Tambahan diskon toko saat pembeli datang ke Stand Bazar SMK.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_bazar_extra" min="0" max="90" value="{{ old('discount_bazar_extra', $store->discount_bazar_extra ?? 0) }}" required class="w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-amber-800 focus:border-amber-600 focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-amber-600">%</span>
                            </div>
                        </div>
                    </div>

                    @if(isset($activeBazaarEvents) && $activeBazaarEvents->count() > 0)
                        <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] p-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <p class="text-xs font-extrabold text-[#0F291E]">
                                    Acara Bazar & Event Sekolah yang Sedang Aktif:
                                </p>
                                @foreach($activeBazaarEvents as $ev)
                                    <p class="text-[11px] text-[#0B6E4F]">
                                        &bull; <strong>{{ $ev->title }}</strong> ({{ $ev->location ?? 'SMK Plus Al Hilal' }}) &mdash; Diskon Event {{ $ev->discount_percent }}% + Diskon Ekstra Toko Anda {{ $store->discount_bazar_extra ?? 0 }}% (otomatis diterapkan oleh sistem jika toko Anda terdaftar &amp; disetujui sebagai peserta acara).
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <span>Simpan Skema Diskon Toko</span>
                        </button>
                    </div>
                </form>

                <!-- Kepesertaan Event Bazar & Pengajuan Toko + Cetak QR Stand -->
                @if(isset($manageableBazaars) && $manageableBazaars->count() > 0)
                    <div class="pt-6 border-t border-slate-100 space-y-4">
                        <div>
                            <h3 class="text-sm sm:text-base font-extrabold text-[#0F291E]">Kepesertaan Event Bazar & Cetak Barcode Stand Toko</h3>
                            <p class="text-xs text-slate-500">Ajukan toko Anda mengikuti event bazar sekolah agar mendapat persetujuan Admin/Super Admin serta QR Code kunjungan stand.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($manageableBazaars as $bzEv)
                                @php
                                    $part = $bazaarParticipations->get($bzEv->id);
                                @endphp
                                <div class="rounded-2xl border border-slate-200 bg-[#F8FAFC] p-4 space-y-3 flex flex-col justify-between">
                                    <div class="space-y-1.5">
                                        <div class="flex items-start justify-between gap-2">
                                            <h4 class="text-xs sm:text-sm font-black text-[#0F291E]">{{ $bzEv->title }}</h4>
                                            @if($part)
                                                @php
                                                    $badgeCss = match($part->status) {
                                                        'approved' => 'bg-emerald-100 text-[#0B6E4F]',
                                                        'rejected' => 'bg-rose-100 text-rose-700',
                                                        default    => 'bg-amber-100 text-amber-800',
                                                    };
                                                @endphp
                                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold shrink-0 {{ $badgeCss }}">
                                                    {{ $part->status === 'approved' ? 'Peserta Resmi' : ($part->status === 'rejected' ? 'Ditolak' : 'Menunggu ACC') }}
                                                </span>
                                            @else
                                                <span class="rounded-full bg-slate-200/70 text-slate-600 px-2.5 py-0.5 text-[10px] font-bold shrink-0">
                                                    Belum Terdaftar
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Lokasi: {{ $bzEv->location ?: 'SMK Plus Al Hilal' }} &bull;
                                            {{ $bzEv->start_date?->format('d M Y') ?? 'Aktif' }} – {{ $bzEv->end_date?->format('d M Y') ?? 'Selesai' }}
                                        </p>
                                    </div>

                                    @if($part && $part->status === 'approved')
                                        <div class="rounded-xl bg-white border border-emerald-200 p-3 flex flex-wrap items-center justify-between gap-2">
                                            <div class="text-xs">
                                                <span class="text-slate-500">Nomor Stand:</span>
                                                <strong class="font-mono text-[#0B6E4F]">{{ $part->stand_number ?: 'Peserta Resmi' }}</strong>
                                                <span class="block text-[10px] text-slate-400 font-mono">Kode Stand: {{ $part->stand_code }}</span>
                                            </div>
                                            <a href="{{ route('seller.bazaar.print-qr', $bzEv) }}" target="_blank"
                                               class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-3 py-1.5 text-xs font-bold shadow-2xs transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                <span>Cetak QR Stand</span>
                                            </a>
                                        </div>
                                    @elseif($part && $part->status === 'pending')
                                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-[11px] font-semibold text-amber-800">
                                            Pengajuan Anda sedang ditinjau Admin / Super Admin. QR Stand tersedia setelah disetujui.
                                        </div>
                                    @elseif($bzEv->allow_self_register)
                                        <form action="{{ route('seller.bazaar.request', $bzEv) }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                                            @csrf
                                            <input type="text" name="notes" placeholder="Catatan / produk bazar (opsional)..."
                                                   class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                            <button type="submit" class="rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-3.5 py-1.5 text-xs font-bold transition shrink-0">
                                                Ajukan Ikut Bazar
                                            </button>
                                        </form>
                                    @else
                                        <p class="text-[11px] text-slate-400 italic">Pendaftaran mandiri ditutup (hanya berdasarkan pilihan panitia).</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Bagian Profil Toko & Edit Profil Lengkap Pemilik Toko (Satu Toko Satu Akun) -->
            <div id="section-profil" class="seller-main-section rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Profil Toko & Identitas Lengkap Pemilik Toko (1 Akun 1 Toko)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Lengkapi Nama, NIS/NISN (Siswa) atau NUPTK (Guru), Kelas & Jurusan dari database, Alamat Lengkap (Desa, Kecamatan, Kabupaten, Kode Pos), serta Nomor HP.
                    </p>
                </div>

                <form action="{{ route('seller.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Status Pemilik Toko *</label>
                            <select name="seller_type" onchange="toggleSellerProfileType(this.value)" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <option value="siswa" @selected(($store->seller_type ?? 'siswa') === 'siswa')>Siswa SMK Plus Al Hilal</option>
                                <option value="guru" @selected(($store->seller_type ?? '') === 'guru')>Guru SMK Plus Al Hilal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Nomor HP / WhatsApp *</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? $store->contact_number) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                    </div>

                    <!-- Atribut Siswa -->
                    <div id="profile-siswa-row" class="{{ ($store->seller_type ?? 'siswa') === 'guru' ? 'hidden' : '' }} grid grid-cols-1 sm:grid-cols-3 gap-3.5 rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">NIS / NISN Siswa *</label>
                            <input type="text" name="nis_nisn" value="{{ old('nis_nisn', $user->nis_nisn) }}" placeholder="Nomor NIS/NISN" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kelas (Database) *</label>
                            <select name="class_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">Pilih Kelas</option>
                                @foreach($effectiveClasses as $cl)
                                    <option value="{{ $cl }}" @selected(old('class_name', $user->class_name) === $cl || \App\Models\ClassMajor::findMatchingMasterClass(old('class_name', $user->class_name)) === $cl)>{{ $cl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan (Database) *</label>
                            <select name="major_name" id="siswa_major_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">Pilih Jurusan</option>
                                @foreach($effectiveMajors as $mj)
                                    <option value="{{ $mj }}" @selected(old('major_name', $user->major_name) === $mj || \App\Models\ClassMajor::findMatchingMasterMajor(old('major_name', $user->major_name)) === $mj)>{{ $mj }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Atribut Guru -->
                    <div id="profile-guru-row" class="{{ ($store->seller_type ?? 'siswa') === 'guru' ? '' : 'hidden' }} grid grid-cols-1 sm:grid-cols-2 gap-3.5 rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">NUPTK Guru</label>
                            <input type="text" name="nuptk" id="guru_nuptk" value="{{ old('nuptk', $user->nuptk) }}" placeholder="Nomor NUPTK" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan / Unit (Database)</label>
                            <select name="teacher_major_name" id="guru_major_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">Pilih Jurusan / Unit</option>
                                @foreach($effectiveMajors as $mj)
                                    <option value="{{ $mj }}" @selected(old('teacher_major_name', old('major_name', $user->major_name)) === $mj || \App\Models\ClassMajor::findMatchingMasterMajor(old('teacher_major_name', old('major_name', $user->major_name))) === $mj)>{{ $mj }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Desa / Kelurahan *</label>
                            <input type="text" name="desa" value="{{ old('desa', $user->desa) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kecamatan *</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan', $user->kecamatan) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
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
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode Pos *</label>
                            <input type="text" name="kode_pos" value="{{ old('kode_pos', $user->kode_pos) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Detail Alamat</label>
                            <input type="text" name="address_detail" value="{{ old('address_detail', $user->address_detail) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Toko *</label>
                            <input type="text" name="store_name" value="{{ old('store_name', $store->store_name) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Deskripsi Toko *</label>
                            <input type="text" name="description" value="{{ old('description', $store->description) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                    </div>

                    <!-- Informasi Rekening Bank & E-Wallet Toko untuk Pembayaran Transfer -->
                    <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs sm:text-sm font-extrabold text-[#0F291E] flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    <span>Informasi Rekening Bank & E-Wallet Toko (Untuk Transaksi Transfer)</span>
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Informasi ini akan ditampilkan kepada pembeli saat memilih metode <strong>Transfer Antar / Ekspedisi</strong> agar pembeli dapat melakukan pembayaran.
                                </p>
                            </div>
                            @if($store->has_payment_account)
                                <span class="inline-flex items-center gap-1 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span>Rekening Terpasang</span>
                                </span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800">
                                    Belum Diisi
                                </span>
                            @endif
                        </div>

                        <!-- Rekening Bank -->
                        <div class="rounded-xl bg-white border border-slate-200 p-4 space-y-3">
                            <span class="text-xs font-extrabold text-[#0F291E] flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span>1. Rekening Bank Toko (BCA / BRI / BNI / Mandiri / BSI / BJB)</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Bank</label>
                                    <input type="text" name="bank_name" value="{{ old('bank_name', $store->bank_name) }}" placeholder="Contoh: BCA / BRI / BJB / BSI" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nomor Rekening</label>
                                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $store->bank_account_number) }}" placeholder="Nomor rekening bank" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-mono font-bold focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Atas Nama (Pemilik Rekening)</label>
                                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $store->bank_account_name) }}" placeholder="Nama pemilik rekening" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- E-Wallet -->
                        <div class="rounded-xl bg-white border border-slate-200 p-4 space-y-3">
                            <span class="text-xs font-extrabold text-[#0F291E] flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#0B6E4F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <span>2. Akun E-Wallet (DANA / GoPay / OVO / ShopeePay)</span>
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Jenis E-Wallet</label>
                                    <input type="text" name="ewallet_name" value="{{ old('ewallet_name', $store->ewallet_name) }}" placeholder="Contoh: DANA / GoPay / OVO" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nomor HP E-Wallet</label>
                                    <input type="text" name="ewallet_account_number" value="{{ old('ewallet_account_number', $store->ewallet_account_number) }}" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-mono font-bold focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Atas Nama E-Wallet</label>
                                    <input type="text" name="ewallet_account_name" value="{{ old('ewallet_account_name', $store->ewallet_account_name) }}" placeholder="Nama pemilik e-wallet" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Petunjuk Transfer Tambahan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Petunjuk Pembayaran Transfer untuk Pembeli (Opsional)</label>
                            <input type="text" name="payment_instructions" value="{{ old('payment_instructions', $store->payment_instructions) }}" placeholder="Contoh: Silakan kirim bukti transfer ke WhatsApp toko setelah order dibuat..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <span>Simpan Profil & Toko</span>
                        </button>
                    </div>
                </form>
            </div>

            <footer class="text-[11px] text-slate-400 pt-2">
                &copy; {{ date('Y') }} hilalkraft • Karya hebat dimulai dari langkah kecil.
            </footer>
        </div>
    </div>
</div>

<script>
    function toggleSellerSidebar(open) {
        const sidebar = document.getElementById('sellerSidebar');
        const overlay = document.getElementById('sellerOverlay');
        if (!sidebar || !overlay) return;

        if (open) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    function switchSellerTab(target) {
        // 1. Tutup drawer sidebar mobile jika terbuka
        toggleSellerSidebar(false);

        // 2. Tampilkan section terpilih atau semua section
        const sections = {
            'all': ['section-ringkasan-wrap', 'section-produk', 'section-pesanan', 'section-diskon', 'section-profil'],
            'produk': ['section-ringkasan-wrap', 'section-produk'],
            'pesanan': ['section-pesanan'],
            'diskon': ['section-diskon'],
            'profil': ['section-profil']
        };

        const activeIds = sections[target] || sections['all'];

        document.querySelectorAll('.seller-main-section').forEach(el => {
            const shouldShow = activeIds.includes(el.id);
            el.style.display = shouldShow ? '' : 'none';
        });

        // 3. Update active pill highlight
        document.querySelectorAll('.seller-pill').forEach(btn => {
            const isMatch = btn.getAttribute('data-pill') === target;
            btn.className = isMatch
                ? 'seller-pill rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-[#0B6E4F] text-white shadow-xs font-bold'
                : 'seller-pill rounded-full px-3.5 py-1.5 transition whitespace-nowrap bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 font-bold';
        });

        // 4. Update sidebar nav items
        document.querySelectorAll('.seller-nav-item').forEach(item => {
            const isMatch = item.getAttribute('data-target') === target;
            if (isMatch) {
                item.classList.add('bg-[#EBF9F2]', 'text-[#0B6E4F]', 'font-bold');
                item.classList.remove('text-slate-600', 'hover:bg-slate-50');
            } else {
                item.classList.remove('bg-[#EBF9F2]', 'text-[#0B6E4F]', 'font-bold');
                item.classList.add('text-slate-600', 'hover:bg-slate-50');
            }
        });

        // 5. Update URL hash
        if (target !== 'all') {
            history.replaceState(null, null, '#section-' + target);
        } else {
            history.replaceState(null, null, ' ');
        }

        // 6. Scroll ke posisi atas konten utama
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleSellerProfileType(val) {
        const siswaRow = document.getElementById('profile-siswa-row');
        const guruRow = document.getElementById('profile-guru-row');
        if (!siswaRow || !guruRow) return;

        const isSiswa = (val === 'siswa');
        siswaRow.classList.toggle('hidden', !isSiswa);
        guruRow.classList.toggle('hidden', isSiswa);

        // Nonaktifkan input pada baris yang disembunyikan agar tidak terkirim atau bentrok
        siswaRow.querySelectorAll('input, select').forEach(el => el.disabled = !isSiswa);
        guruRow.querySelectorAll('input, select').forEach(el => el.disabled = isSiswa);
    }

    function filterSellerTable(q) {
        const term = (q || '').toLowerCase();
        document.querySelectorAll('.seller-product-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            row.style.display = name.includes(term) ? '' : 'none';
        });
    }

    function filterSellerOrders(stage) {
        document.querySelectorAll('.seller-order-card').forEach(card => {
            const p = card.getAttribute('data-progress');
            card.style.display = (stage === 'all' || p === stage) ? '' : 'none';
        });
        document.querySelectorAll('.seller-order-tab').forEach(btn => {
            const active = btn.getAttribute('data-tab') === stage;
            btn.className = active
                ? 'seller-order-tab rounded-xl bg-[#0B6E4F] text-white px-3 py-1.5 transition'
                : 'seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition';
        });
    }

    // Auto-scroll ke section setelah submit & auto-dismiss toast
    window.addEventListener('DOMContentLoaded', () => {
        // Sinkronisasi status tipe pemilik toko (siswa/guru)
        const sellerTypeSelect = document.querySelector('select[name="seller_type"]');
        if (sellerTypeSelect) {
            toggleSellerProfileType(sellerTypeSelect.value);
        }

        const hash = window.location.hash;
        if (hash) {
            if (hash.includes('profil')) {
                switchSellerTab('profil');
            } else if (hash.includes('diskon')) {
                switchSellerTab('diskon');
            } else if (hash.includes('pesanan')) {
                switchSellerTab('pesanan');
            } else if (hash.includes('produk')) {
                switchSellerTab('produk');
            }
        }

        @if($errors->any())
            switchSellerTab('profil');
        @endif

        const toast = document.getElementById('seller-floating-toast');
        if (toast) {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 400);
            }, 6000);
        }
    });
</script>
@endsection
