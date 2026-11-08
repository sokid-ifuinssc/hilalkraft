{{--
============================================================================
LAYOUT: PANEL (layouts/panel.blade.php)
============================================================================
Fungsi:
  Kerangka halaman panel dengan sidebar sesuai konteks role (Penjual, Pembeli,
  Admin/Super Admin). Dipakai halaman laporan agar tampil fokus: satu menu =
  satu halaman. Responsif: sidebar tetap di desktop, laci (drawer) di mobile.
Penggunaan:
  @extends('layouts.panel')
  @section('panel_title', 'Judul Halaman')
  @section('panel') ...konten... @endsection
============================================================================
--}}
@extends('layouts.app')

@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $authUser = auth()->user();
    $panel = request()->routeIs('seller.*') ? 'seller' : (request()->routeIs('admin.*') ? 'admin' : 'buyer');

    $menus = match ($panel) {
        'seller' => [
            ['⊞',  'Dashboard',         route('seller.dashboard'),                    false],
            ['📦', 'Produk Saya',       route('seller.dashboard') . '#section-produk',  false],
            ['🚚', 'Progres Pesanan',   route('seller.dashboard') . '#section-pesanan', false],
            ['🏷️', 'Diskon & Bazar',    route('seller.dashboard') . '#section-diskon',  false],
            ['📊', 'Laporan Penjualan', route('seller.reports'),                      request()->routeIs('seller.reports')],
            ['🏪', 'Profil Toko',       route('seller.dashboard') . '#section-profil',  false],
        ],
        'admin' => [
            ['⊞',  'Ringkasan',          route('admin.dashboard'),                              false],
            ['📊', 'Laporan & Rekap',    route('admin.reports'),                                request()->routeIs('admin.reports')],
            ['📋', 'Log Akses & Audit',   route('admin.logs'),                                   request()->routeIs('admin.logs')],
            ['✨', 'CMS Konten Website', route('admin.dashboard') . '#sec-cms',                  false],
            ['👥', 'Manajemen User',     route('admin.dashboard') . '#sec-manajemen-user',       false],
            ['🏪', 'Kelola Toko',        route('admin.dashboard') . '#sec-kelola-toko',          false],
            ['🎪', 'Bazar & Diskon',     route('admin.dashboard') . '#sec-bazar-event',          false],
            ['📦', 'Kategori & Jurusan', route('admin.dashboard') . '#sec-master-data',          false],
            ['🧭', 'Pengunjung',         route('admin.dashboard') . '#sec-laporan-pengunjung',   false],
        ],
        default => [
            ['🚚', 'Pesanan Saya',    route('buyer.dashboard'),  request()->routeIs('buyer.dashboard')],
            ['🛒', 'Keranjang',       route('buyer.cart.index'), request()->routeIs('buyer.cart.*')],
            ['📊', 'Laporan Belanja', route('buyer.reports'),    request()->routeIs('buyer.reports')],
            ['🛍️', 'Belanja Lagi',    route('home'),             false],
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
                @foreach($menus as [$icon, $label, $url, $active])
                    <a href="{{ $url }}"
                       class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 transition {{ $active ? 'bg-[#EBF9F2] text-[#0B6E4F] font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
                        <span class="w-5 text-center">{{ $icon }}</span>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center justify-between pt-4 mt-6 border-t border-slate-100 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-[#0B6E4F]">↗ Marketplace</a>
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
            <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center shrink-0">
                {{ strtoupper(substr($authUser->name, 0, 2)) }}
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

    // Format angka rupiah singkat untuk grafik
    window.hkRupiah = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
    window.hkPalette = ['#0B6E4F', '#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6'];
    if (window.Chart) {
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.color = '#64748B';
        Chart.defaults.plugins.legend.labels.boxWidth = 10;
        Chart.defaults.maintainAspectRatio = false;
    }
</script>
@stack('panel_scripts')
@endsection

