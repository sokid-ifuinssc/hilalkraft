{{--
============================================================================
LAYOUT: PANEL (layouts/panel.blade.php)
============================================================================
Fungsi:
  Kerangka halaman panel dengan sidebar sesuai konteks role (Penjual, Pembeli,
  Admin/Super Admin). Dipakai halaman laporan agar tampil fokus: satu menu =
  satu halaman. Responsif: sidebar tetap di desktop, laci (drawer) di mobile.
  Seluruh ikon menggunakan Flat SVG Icon bersih tanpa emoji.
============================================================================
--}}
@extends('layouts.app')

@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $authUser = auth()->user();
    $panel = request()->routeIs('seller.*') ? 'seller' : (request()->routeIs('admin.*') ? 'admin' : 'buyer');

    // Flat SVG Icon definitions
    $svgGrid = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>';
    $svgBox = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
    $svgTruck = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $svgTag = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>';
    $svgChart = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
    $svgStore = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>';
    $svgUsers = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
    $svgShield = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
    $svgClipboard = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>';
    $svgSparkles = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>';
    $svgCart = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
    $svgUser = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    $svgBag = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>';

    $menus = match ($panel) {
        'seller' => [
            [$svgGrid,      'Dashboard',         route('seller.dashboard'),                    false],
            [$svgBox,       'Produk Saya',       route('seller.dashboard') . '#section-produk',  false],
            [$svgTruck,     'Progres Pesanan',   route('seller.dashboard') . '#section-pesanan', false],
            [$svgTag,       'Diskon & Bazar',    route('seller.dashboard') . '#section-diskon',  false],
            [$svgChart,     'Laporan Penjualan', route('seller.reports'),                      request()->routeIs('seller.reports')],
            [$svgStore,     'Profil Toko',       route('seller.dashboard') . '#section-profil',  false],
        ],
        'admin' => [
            [$svgGrid,      'Ringkasan',          route('admin.dashboard'),                              false],
            [$svgShield,    'Skema Role & Akses', route('admin.dashboard') . '#sec-role-matrix',        false],
            [$svgChart,     'Laporan & Rekap',    route('admin.reports'),                                request()->routeIs('admin.reports')],
            [$svgClipboard, 'Log Akses & Audit',   route('admin.logs'),                                   request()->routeIs('admin.logs')],
            [$svgSparkles,  'CMS Konten Website', route('admin.dashboard') . '#sec-cms',                  false],
            [$svgUsers,     'Manajemen User',     route('admin.dashboard') . '#sec-manajemen-user',       false],
            [$svgStore,     'Kelola Toko',        route('admin.dashboard') . '#sec-kelola-toko',          false],
            [$svgTag,       'Bazar & Diskon',     route('admin.dashboard') . '#sec-bazar-event',          false],
            [$svgBox,       'Kategori & Jurusan', route('admin.dashboard') . '#sec-master-data',          false],
            [$svgUser,      'Laporan Pengunjung', route('admin.dashboard') . '#sec-laporan-pengunjung',   false],
        ],
        default => [
            [$svgTruck,     'Pesanan Saya',    route('buyer.dashboard'),                      request()->routeIs('buyer.dashboard')],
            [$svgUser,      'Profil Akun',     route('buyer.dashboard') . '#menu-profil',     false],
            [$svgCart,      'Keranjang',       route('buyer.cart.index'),                     request()->routeIs('buyer.cart.*')],
            [$svgChart,     'Laporan Belanja', route('buyer.reports'),                        request()->routeIs('buyer.reports')],
            [$svgBag,       'Belanja Lagi',    route('home'),                                 false],
        ],
    };

    $panelName = ['seller' => 'Ruang Karya Siswa', 'admin' => 'Panel Administrasi', 'buyer' => 'Akun Pembeli'][$panel];
    $roleLabel = match ($authUser->role) {
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin (Guru)',
        'seller'      => 'Pemilik Toko',
        default       => 'Pembeli',
    };
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<div class="min-h-screen lg:flex bg-[#F8FAFC] text-[#0F291E]">

    {{-- Overlay mobile --}}
    <div id="panelOverlay" class="fixed inset-0 bg-slate-900/40 z-40 hidden lg:hidden" onclick="togglePanelMenu(false)"></div>

    {{-- Sidebar --}}
    <aside id="panelSidebar"
           class="fixed lg:sticky top-0 left-0 z-50 h-screen w-64 bg-white border-r border-slate-200/80 p-5 flex flex-col justify-between overflow-y-auto
                  -translate-x-full lg:translate-x-0 transition-transform duration-200 shrink-0">
        <div class="space-y-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 pb-4 border-b border-slate-100">
                <img src="{{ asset('images/logo-hilalkraf.jpg') }}" alt="hilalkraf" class="h-10 w-auto object-contain">
                <img src="{{ asset('images/logo-smk-alhilal.png') }}" alt="SMK Plus Al Hilal" class="h-8 w-8 object-contain">
                <div class="leading-tight">
                    <span class="block text-base font-extrabold">hilalkraf</span>
                    <span class="block text-[10px] font-bold text-[#0B6E4F]">SMK PAL</span>
                </div>
            </a>

            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ $panelName }}</p>
                <p class="text-xs font-bold truncate mt-0.5">{{ $authUser->name }}</p>
                <span class="inline-block mt-1 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F]">{{ $roleLabel }}</span>
            </div>

            <nav class="space-y-1 text-xs font-semibold">
                @foreach($menus as [$iconSvg, $label, $url, $active])
                    <a href="{{ $url }}"
                       class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 transition {{ $active ? 'bg-[#EBF9F2] text-[#0B6E4F] font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
                        <span class="w-5 flex items-center justify-center shrink-0 {!! $active ? 'text-[#0B6E4F]' : 'text-slate-400' !!}">{!! $iconSvg !!}</span>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center justify-between pt-4 mt-6 border-t border-slate-100 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-[#0B6E4F] flex items-center gap-1 font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Marketplace</span>
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-rose-600 font-semibold hover:underline">Keluar</button>
            </form>
        </div>
    </aside>

    {{-- Konten --}}
    <div class="flex-1 min-w-0 flex flex-col">
        <header class="h-14 sm:h-16 bg-white border-b border-slate-200/80 px-4 sm:px-8 flex items-center justify-between gap-3 sticky top-0 z-30">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="togglePanelMenu(true)" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100" aria-label="Buka menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="text-xs text-slate-400 truncate">
                    {{ $panelName }} / <span class="text-slate-700 font-semibold">@yield('panel_title', 'Laporan')</span>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                @if($authUser->role === 'super_admin' || session()->has('impersonator_id'))
                    <div class="relative inline-block text-left" id="role-switcher-dropdown">
                        <button type="button" onclick="document.getElementById('role-switcher-menu').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/30 bg-[#EBF9F2] px-3 py-1.5 text-xs font-bold text-[#0B6E4F] hover:bg-[#D5F5E6] transition shadow-2xs">
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span class="hidden sm:inline">Lihat Sebagai Peran</span>
                            <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="role-switcher-menu" class="hidden absolute right-0 mt-2 w-64 rounded-2xl bg-white shadow-xl border border-slate-200 p-2.5 z-50 text-xs space-y-1">
                            <div class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Pilih Tampilan Role:</div>
                            
                            <!-- 1. Super Admin -->
                            <form action="{{ route('admin.impersonate.role', 'super_admin') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center justify-between px-2.5 py-2 rounded-xl hover:bg-slate-50 {{ session()->get('view_as_role') ? 'text-slate-700' : 'bg-emerald-50 text-[#0B6E4F] font-bold' }}">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        <div>
                                            <div class="font-bold">Super Admin</div>
                                            <div class="text-[10px] text-slate-400">Kendali penuh sistem & master DB</div>
                                        </div>
                                    </div>
                                </button>
                            </form>

                            <!-- 2. Admin (Guru) -->
                            <form action="{{ route('admin.impersonate.role', 'admin') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center justify-between px-2.5 py-2 rounded-xl hover:bg-blue-50 {{ session()->get('view_as_role') === 'admin' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-700' }}">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                        <div>
                                            <div class="font-bold">Admin (Guru)</div>
                                            <div class="text-[10px] text-slate-400">Pendamping & moderasi toko</div>
                                        </div>
                                    </div>
                                </button>
                            </form>

                            <!-- 3. Pemilik Toko (Seller) -->
                            <a href="{{ route('seller.dashboard') }}" class="w-full text-left flex items-center justify-between px-2.5 py-2 rounded-xl hover:bg-emerald-50 text-slate-700">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    <div>
                                        <div class="font-bold">Pemilik Toko (Seller)</div>
                                        <div class="text-[10px] text-slate-400">Ruang kelola produk & pesanan siswa</div>
                                    </div>
                                </div>
                            </a>

                            <!-- 4. Pembeli (Buyer) -->
                            <a href="{{ route('buyer.dashboard') }}" class="w-full text-left flex items-center justify-between px-2.5 py-2 rounded-xl hover:bg-amber-50 text-slate-700">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    <div>
                                        <div class="font-bold">Pembeli (Buyer)</div>
                                        <div class="text-[10px] text-slate-400">Ruang transaksi belanja & keranjang</div>
                                    </div>
                                </div>
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>
                            <a href="{{ route('home') }}" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 text-slate-500 font-medium text-[11px]">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                <span>Katalog Publik Marketplace</span>
                            </a>
                        </div>
                    </div>
                @endif

                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($authUser->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">
            @yield('panel')
        </div>
    </div>
</div>

<script>
    function togglePanelMenu(open) {
        document.getElementById('panelSidebar').classList.toggle('-translate-x-full', !open);
        document.getElementById('panelOverlay').classList.toggle('hidden', !open);
    }
</script>
@endsection
