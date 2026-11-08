{{--
============================================================================
VIEW: DASHBOARD ADMIN & SUPER ADMIN + CMS LENGKAP (admin/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman Panel Administrasi & CMS Super Admin sesuai desain
  Figma `Admin overview · Desktop.png`:
  1. Sidebar Administrasi SMK Plus Al Hilal (termasuk menu CMS Website &
     CMS Karya Siswa).
  2. 4 Kartu KPI, Grafik Performa Toko (Views 👁️, Komentar 💬, Likes ❤️,
     Transaksi 🛒), dan Kartu Komunitas hilalkraft.
  3. CMS Konten Website & Banner Beranda (Bisa Diedit & Ditambahkan).
  4. CMS Karya Siswa / Produk Marketplace (Bisa Diedit, Ditambahkan, & Dihapus).
  5. Manajemen User, Kredensial Password Realtime Super Admin, & Penetapan
     Guru (Ibu Silvi Dwi Manitik & Ibu Melinda) sebagai Admin.
  6. Kelola & Validasi Toko, Master Kategori & Kelas/Jurusan, serta Laporan
     Pengunjung (Siapa Saja & Dari Mana Saja).
Algoritma:
  1. Visualisasi Chart.js: Memetakan metrik toko ke dalam grafik batang.
  2. Dynamic Modal Binding: Mengisi form modal Edit CMS, Edit Karya Siswa,
     Edit User, Edit Kategori, dan Edit Kelas/Jurusan menggunakan atribut
     `data-*` pada tombol Edit tanpa reload halaman.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Panel Administrasi & CMS — hilalkraf SMK Plus Al Hilal (SMK PAL)')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
    $totalUsersCount = max(1, $stats['total_users']);
    $buyerCount      = \App\Models\User::where('role', 'buyer')->count();
    $sellerCount     = \App\Models\User::where('role', 'seller')->count();
    $adminCount      = \App\Models\User::where('role', 'admin')->count();
    $superAdminCount = \App\Models\User::where('role', 'super_admin')->count();
@endphp

<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Sidebar Kiri Sesuai `Admin overview · Desktop.png` dengan Logo hilalkraf & Logo SMK Plus Al Hilal -->
    <aside class="w-64 bg-white border-r border-slate-200/80 hidden lg:flex flex-col justify-between shrink-0 sticky top-0 h-screen p-6 overflow-y-auto">
        <div class="space-y-6">
            <a href="{{ route('home') }}" class="flex flex-col gap-2 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-2.5 py-1.5 shadow-sm">
                    <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                         alt="hilalkraf"
                         class="h-11 w-auto object-contain">
                    <span class="h-7 w-[1px] bg-slate-200"></span>
                    <img src="{{ asset('images/logo-smk-alhilal.png') }}"
                         alt="SMK Plus Al Hilal"
                         class="h-9 w-9 object-contain">
                </div>
                <div>
                    <span class="text-lg font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                    <span class="text-[10px] font-bold text-[#0B6E4F]">Inkubator Bisnis & Wirausaha Muda SMK PAL</span>
                </div>
            </a>

            <div class="space-y-1">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">ADMINISTRASI & CMS</p>
                <p class="text-xs font-extrabold text-[#0F291E]">SMK Plus Al Hilal (SMK PAL)</p>
                <p class="text-[10px] text-slate-500 leading-snug">Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</p>
                <span class="inline-block rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F] mt-1">
                    {{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin (Guru)' }}
                </span>
            </div>

            <nav class="space-y-1 text-xs font-semibold">
                <a href="#sec-ringkasan" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold">
                    <span>⊞</span>
                    <span>Ringkasan</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>📊</span>
                    <span>Laporan & Rekap</span>
                </a>
                <a href="#sec-cms" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>✨</span>
                    <span>CMS Konten Website</span>
                </a>
                <a href="#sec-cms-produk" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🎨</span>
                    <span>CMS Karya Siswa</span>
                </a>
                <a href="#sec-manajemen-user" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>👥</span>
                    <span>Manajemen User & Guru</span>
                </a>
                <a href="#sec-kelola-toko" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🏪</span>
                    <span>Peringkat & Kelola Toko</span>
                </a>
                <a href="#sec-bazar-event" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span class="flex items-center gap-3">
                        <span>🎪</span>
                        <span>Bazar & Skema Diskon</span>
                    </span>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Event</span>
                </a>
                <a href="#sec-master-data" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>📦</span>
                    <span>Kategori & Kelas/Jurusan</span>
                </a>
                <a href="#sec-laporan-pengunjung" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🧭</span>
                    <span>Laporan Pengunjung</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4 pt-4">
            <div class="rounded-2xl bg-[#EBF9F2] p-4 space-y-1.5">
                <span class="w-6 h-6 rounded-full bg-white text-[#0B6E4F] flex items-center justify-center text-xs font-bold">✨</span>
                <p class="text-xs font-bold text-[#0F291E] pt-1">CMS Aktif</p>
                <p class="text-[11px] text-slate-500">Konten beranda & karya siswa dapat diedit dan ditambah kapan saja.</p>
                <a href="#sec-cms" class="inline-block text-[11px] font-bold text-[#0B6E4F] hover:underline pt-0.5">
                    Buka CMS Website →
                </a>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#0B6E4F] transition">↗ Lihat Beranda</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-rose-600 font-semibold hover:underline">Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Konten Utama -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-10 flex items-center justify-between gap-4">
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <a href="{{ route('home') }}" class="lg:hidden font-bold text-[#0B6E4F]">← Marketplace</a>
                <span>Panel administrasi & CMS</span>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Dashboard Utama</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin (Guru SMK Plus Al Hilal)' }}</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Floating Toast Notifikasi Sukses / Gagal (Tetap Terlihat Di Mana Pun Posisi Scroll) -->
        @if(session('success') || session('error') || $errors->any())
            <div id="admin-floating-toast" class="fixed top-5 right-5 z-[9999] max-w-md w-[92vw] sm:w-[420px] transition-all duration-300">
                @if(session('success'))
                    <div class="rounded-2xl bg-white border-2 border-[#10B981] p-4 shadow-2xl flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center font-bold text-base shrink-0">
                            ✓
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-[#0B6E4F] uppercase tracking-wider">Perubahan Berhasil Disimpan</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5 leading-snug">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('admin-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-2xl bg-white border-2 border-rose-500 p-4 shadow-2xl flex items-start gap-3 mt-2">
                        <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base shrink-0">
                            ✕
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-extrabold text-rose-600 uppercase tracking-wider">Perhatian / Gagal</p>
                            <p class="text-xs text-slate-700 font-medium mt-0.5 leading-snug">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="document.getElementById('admin-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
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
                        <button type="button" onclick="document.getElementById('admin-floating-toast').remove()" class="text-slate-400 hover:text-slate-700 font-bold text-lg leading-none shrink-0">&times;</button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Area Konten -->
        <div class="p-6 sm:p-10 space-y-8 max-w-6xl w-full mx-auto">

            <!-- Header Ringkasan Marketplace -->
            <div id="sec-ringkasan" class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">Ringkasan Marketplace & CMS</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Selamat datang, {{ auth()->user()->name }}. Kelola konten website, karya siswa, guru admin, dan seluruh data hilalkraft.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.reports') }}" class="rounded-xl bg-white border border-slate-200 px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        📊 Laporan & Rekap
                    </a>
                    <a href="#sec-cms" class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] px-3.5 py-2 text-xs font-bold text-[#0B6E4F] hover:bg-[#D5F5E6] transition">
                        ✨ CMS Website
                    </a>
                    <a href="#sec-cms-produk" class="rounded-xl bg-[#0B6E4F] px-3.5 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition">
                        + Karya Siswa
                    </a>
                </div>
            </div>

            <!-- 4 Kartu KPI Utama -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Total Users</span>
                        <span class="text-[#0B6E4F]">👥</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($stats['total_users']) }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">Termasuk 2 Guru Admin, Siswa & Pembeli</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Total Stores</span>
                        <span class="text-[#0B6E4F]">🏪</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($stats['total_stores']) }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">{{ $stats['pending_stores'] }} menunggu verifikasi</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Karya Siswa Aktif</span>
                        <span class="text-[#0B6E4F]">🎨</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($stats['total_products']) }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">👁️ {{ number_format($stats['total_views']) }} dilihat • ❤️ {{ number_format($stats['total_likes']) }} suka</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Blok Konten CMS</span>
                        <span class="text-[#0B6E4F]">✨</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($cmsContents->count()) }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">Bisa diedit & ditambah langsung</p>
                </div>
            </div>

            <!-- Baris Grafik Toko & Komunitas hilalkraft -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Kiri: Grafik Performa & Filter Toko Teratas -->
                <div class="lg:col-span-8 rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F] mb-1">
                                📊 FILTER PERINGKAT TOKO TERATAS
                            </div>
                            <h2 class="text-sm sm:text-base font-extrabold text-[#0F291E]">Grafik Toko Teratas (Pelihat, Like, Komentar, Rating ★ & Pesanan)</h2>
                            <p class="text-xs text-slate-400">
                                Jika jumlah toko banyak, gunakan filter di bawah untuk melihat toko teratas berdasarkan pengunjung terbanyak, like terbanyak, komentar terbanyak, atau rating bintang tertinggi.
                            </p>
                        </div>
                        <span id="top-store-highlight-badge" class="rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-[11px] font-extrabold text-amber-800">
                            🏆 Memuat Peringkat Toko...
                        </span>
                    </div>

                    <!-- Baris Filter Interaktif Grafik Toko -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 rounded-xl bg-[#F8FAFC] p-3 border border-slate-200/80 text-xs">
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Urutkan Toko Teratas</label>
                            <select id="chart-sort-metric" onchange="updateStoreAnalyticsChart()" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                <option value="views">👁️ Pengunjung / Pelihat Terbanyak</option>
                                <option value="likes">❤️ Like / Disukai Terbanyak</option>
                                <option value="comments">💬 Komentar Terbanyak</option>
                                <option value="ratings">★ Rating Bintang Teratas</option>
                                <option value="orders">🛒 Pesanan / Transaksi Terbanyak</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Jumlah Toko Ditampilkan</label>
                            <select id="chart-limit" onchange="updateStoreAnalyticsChart()" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                <option value="5">Top 5 Toko Teratas</option>
                                <option value="10" selected>Top 10 Toko Teratas</option>
                                <option value="20">Top 20 Toko Teratas</option>
                                <option value="999">Semua Toko ({{ $stores->count() }})</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Filter Jenis Toko</label>
                            <select id="chart-seller-type" onchange="updateStoreAnalyticsChart()" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                <option value="all">Semua Toko (Siswa & Guru)</option>
                                <option value="siswa">Hanya Toko Siswa</option>
                                <option value="guru">Hanya Toko Guru</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Mode Tampilan Grafik</label>
                            <select id="chart-dataset-mode" onchange="updateStoreAnalyticsChart()" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <option value="all">Semua Metrik (👁️ ❤️ 💬 ★ 🛒)</option>
                                <option value="selected">Fokus Metrik Terpilih Saja</option>
                            </select>
                        </div>
                    </div>

                    <div class="h-64 w-full">
                        <canvas id="storeAnalyticsChart"></canvas>
                    </div>
                </div>

                <!-- Kanan: Komunitas hilalkraft -->
                <div class="lg:col-span-4 rounded-2xl bg-white border border-slate-200/80 p-6 flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        <h2 class="text-sm sm:text-base font-extrabold text-[#0F291E]">Komunitas hilalkraft</h2>

                        <div class="space-y-3 text-xs">
                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Pembeli / Pengunjung</span>
                                    <span class="font-bold text-[#0F291E]">{{ $buyerCount }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-[#0B6E4F] rounded-full" style="width: {{ min(100, round(($buyerCount / $totalUsersCount) * 100)) }}%"></div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Pemilik Toko (Siswa/Guru)</span>
                                    <span class="font-bold text-[#0F291E]">{{ $sellerCount }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-[#0B6E4F] rounded-full" style="width: {{ min(100, round(($sellerCount / $totalUsersCount) * 100)) }}%"></div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Admin (Guru SMK Plus Al Hilal)</span>
                                    <span class="font-bold text-[#0F291E]">{{ $adminCount }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-[#0B6E4F] rounded-full" style="width: {{ min(100, round(($adminCount / $totalUsersCount) * 100)) }}%"></div>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Super Admin</span>
                                    <span class="font-bold text-[#0F291E]">{{ $superAdminCount }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-[#0B6E4F] rounded-full" style="width: {{ min(100, round(($superAdminCount / $totalUsersCount) * 100)) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="#sec-cms" class="flex items-center justify-between rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] px-4 py-3 text-xs font-bold text-[#0B6E4F] hover:bg-[#D5F5E6] transition">
                        <span>✨ Kelola CMS Beranda & Karya Siswa</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

            <!-- =================================================================
                 1. CMS KONTEN WEBSITE & BANNER BERANDA (EDIT & TAMBAH)
                 ================================================================= -->
            <div id="sec-cms" class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F] mb-1">
                            ✨ CMS WEBSITE & IKLAN MITRA
                        </div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">CMS Iklan Mitra (BSI, PT. Mega Mentari, Daihatsu) & Konten Website</h2>
                        <p class="text-xs text-slate-400">
                            Atur baris iklan mitra di atas beranda (durasi kedip/tayang per detik & masa kontrak), teks Hero, Topbar, dan Footer.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                onclick="openAddCmsModal('iklan')"
                                class="rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-2xs">
                            + Tambah Iklan Mitra
                        </button>
                        <button type="button"
                                onclick="openAddCmsModal('custom_banner')"
                                class="rounded-xl bg-[#0B6E4F] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                            + Tambah Konten CMS
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-5">BAGIAN (SECTION)</th>
                                <th class="py-3 px-4">JUDUL / MITRA</th>
                                <th class="py-3 px-4">ISI TEKS & KONTRAK IKLAN</th>
                                <th class="py-3 px-4">DURASI TAYANG</th>
                                <th class="py-3 px-4">STATUS</th>
                                <th class="py-3 px-5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($cmsContents as $cms)
                                @php
                                    $sectionBadge = match($cms->section) {
                                        'iklan' => '⚡ Iklan Mitra (Atas)',
                                        'topbar' => 'Topbar Atas',
                                        'hero' => 'Hero Beranda',
                                        'values' => '3 Keunggulan',
                                        'story' => 'Cerita Sekolah',
                                        'footer' => 'Footer Bawah',
                                        default => 'Banner / Custom',
                                    };
                                    $badgeStyle = $cms->section === 'iklan'
                                        ? 'bg-amber-100 text-amber-800 border border-amber-300/60'
                                        : 'bg-[#EBF9F2] text-[#0B6E4F]';
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-5 whitespace-nowrap">
                                        <span class="rounded-md px-2.5 py-1 text-[10px] font-bold {{ $badgeStyle }}">
                                            {{ $sectionBadge }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <p class="font-bold text-[#0F291E]">{{ $cms->title }}</p>
                                        @if($cms->partner_name)
                                            <span class="inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 mt-0.5">
                                                Mitra: {{ $cms->partner_name }}
                                            </span>
                                        @else
                                            <code class="text-[10px] text-slate-400">{{ $cms->key }}</code>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 max-w-md">
                                        <p class="text-slate-700 line-clamp-2">{{ $cms->content }}</p>
                                        @if($cms->section === 'iklan')
                                            <p class="text-[11px] font-semibold {{ $cms->isWithinContract() ? 'text-[#0B6E4F]' : 'text-rose-600' }} mt-0.5">
                                                📅 {{ $cms->contract_status_label }}
                                            </p>
                                        @elseif($cms->link_url)
                                            <p class="text-[11px] text-slate-400 mt-0.5">Tautan: {{ $cms->link_url }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-600 whitespace-nowrap">
                                        @if($cms->section === 'iklan')
                                            <span class="rounded-lg bg-amber-50 border border-amber-200 px-2 py-0.5 text-[11px] font-bold text-amber-800">
                                                ⏱ {{ $cms->duration_seconds ?: 5 }} detik
                                            </span>
                                        @else
                                            <span class="text-slate-400">#{{ $cms->sort_order }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold {{ $cms->is_active && $cms->isWithinContract() ? 'text-[#0B6E4F]' : 'text-rose-500' }}">
                                            ● {{ $cms->is_active ? ($cms->isWithinContract() ? 'Aktif' : 'Kontrak Habis') : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-2">
                                        <button type="button"
                                                data-url="{{ route('admin.cms.update', $cms) }}"
                                                data-section="{{ $cms->section }}"
                                                data-title="{{ $cms->title }}"
                                                data-partner="{{ $cms->partner_name }}"
                                                data-badge="{{ $cms->badge_text }}"
                                                data-content="{{ $cms->content }}"
                                                data-image="{{ $cms->image }}"
                                                data-link="{{ $cms->link_url }}"
                                                data-duration="{{ $cms->duration_seconds ?: 5 }}"
                                                data-start="{{ $cms->contract_start ? $cms->contract_start->format('Y-m-d') : '' }}"
                                                data-end="{{ $cms->contract_end ? $cms->contract_end->format('Y-m-d') : '' }}"
                                                data-theme="{{ $cms->theme_color ?: 'emerald' }}"
                                                data-active="{{ $cms->is_active ? 1 : 0 }}"
                                                onclick="openEditCmsModal(this)"
                                                class="font-bold text-[#0B6E4F] hover:underline">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.cms.destroy', $cms) }}" method="POST" class="inline" onsubmit="return confirm('Hapus item CMS ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">Belum ada item CMS. Klik "+ Tambah Iklan Mitra" atau "+ Tambah Konten CMS" di kanan atas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =================================================================
                 2. CMS KARYA SISWA / PRODUK MARKETPLACE (EDIT, TAMBAH, HAPUS)
                 ================================================================= -->
            <div id="sec-cms-produk" class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F] mb-1">
                            🎨 CMS KARYA SISWA
                        </div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Kelola Karya Siswa & Produk Marketplace</h2>
                        <p class="text-xs text-slate-400">
                            Super Admin & Admin dapat menambahkan karya siswa baru, mengedit karya yang sudah ada, atau menghapusnya.
                        </p>
                    </div>

                    <button type="button"
                            onclick="document.getElementById('add-product-modal').classList.remove('hidden'); document.getElementById('add-product-modal').classList.add('flex');"
                            class="rounded-xl bg-[#0B6E4F] px-4 py-2.5 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                        + Tambah Karya Siswa
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-5">KARYA SISWA</th>
                                <th class="py-3 px-4">TOKO & KREATOR</th>
                                <th class="py-3 px-4">KATEGORI</th>
                                <th class="py-3 px-4">HARGA</th>
                                <th class="py-3 px-4">STOK</th>
                                <th class="py-3 px-4 text-center">INTERAKSI (👁️/❤️/💬)</th>
                                <th class="py-3 px-5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($allProducts as $prod)
                                @php
                                    $prodImg = $prod->image
                                        ? (\Illuminate\Support\Str::startsWith($prod->image, ['http://', 'https://']) ? $prod->image : (\Illuminate\Support\Str::startsWith($prod->image, 'images/') ? asset($prod->image) : asset('storage/' . $prod->image)))
                                        : asset('images/logo-hilalkraf.jpg');
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200/60">
                                                <img src="{{ $prodImg }}" alt="{{ $prod->product_name }}" class="w-full h-full object-cover">
                                            </div>
                                            <div>
                                                <a href="{{ route('products.show', $prod->slug) }}" class="font-bold text-[#0F291E] hover:text-[#0B6E4F]">
                                                    {{ $prod->product_name }}
                                                </a>
                                                <p class="text-[11px] text-slate-400 line-clamp-1 max-w-xs">{{ $prod->description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <p class="font-bold text-[#0F291E]">{{ $prod->store->store_name ?? '-' }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $prod->store->owner->name ?? '-' }} ({{ $prod->store->student_class ?? '-' }})</p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="rounded-md bg-[#EBF9F2] px-2.5 py-1 text-[10px] font-bold text-[#0B6E4F]">
                                            {{ $prod->category ?? 'Kerajinan' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-extrabold text-[#0B6E4F]">
                                        Rp{{ number_format($prod->price, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold">
                                        {{ $prod->stock }} unit
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-2 rounded-lg bg-[#F8FAFC] border border-slate-200/80 px-2.5 py-1 text-[11px]">
                                            <span title="Dilihat">👁️ {{ number_format($prod->views_count) }}</span>
                                            <span title="Disukai">❤️ {{ number_format($prod->likes_count ?? 0) }}</span>
                                            <span title="Komentar">💬 {{ number_format($prod->comments_count ?? 0) }}</span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-2">
                                        <button type="button"
                                                data-url="{{ route('admin.products.update', $prod) }}"
                                                data-store="{{ $prod->store_id }}"
                                                data-category="{{ $prod->category }}"
                                                data-name="{{ $prod->product_name }}"
                                                data-price="{{ (int) $prod->price }}"
                                                data-stock="{{ $prod->stock }}"
                                                data-image="{{ $prod->image }}"
                                                data-description="{{ $prod->description }}"
                                                onclick="openEditProductModal(this)"
                                                class="font-bold text-[#0B6E4F] hover:underline">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.products.destroy', $prod) }}" method="POST" class="inline" onsubmit="return confirm('Hapus karya siswa ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400">Belum ada karya siswa. Klik "+ Tambah Karya Siswa" untuk membuat karya baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =================================================================
                 3. MANAJEMEN USER, GURU ADMIN, & KREDENSIAL REALTIME SUPER ADMIN
                 ================================================================= -->
            <div id="sec-manajemen-user" class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
                @if(session('success'))
                    <div class="m-6 mb-0 rounded-xl bg-[#EBF9F2] border border-[#B8E8D2] p-3.5 text-xs text-[#0B6E4F] font-bold flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-[#0B6E4F] text-white flex items-center justify-center text-[10px]">✓</span>
                            <span>{{ session('success') }}</span>
                        </div>
                        <span class="text-[10px] text-[#0B6E4F]/70 font-normal">Tersimpan</span>
                    </div>
                @endif
                <div class="p-6 space-y-4 border-b border-slate-100">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Manajemen User & Penetapan Guru sebagai Admin</h2>
                            <p class="text-xs text-slate-400">
                                Daftar seluruh akun (Super Admin, 2 Guru Admin: Ibu Silvi Dwi Manitik & Ibu Melinda, Siswa Pemilik Toko, dan Pembeli).
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(auth()->user()->role === 'super_admin')
                                <button type="button" onclick="toggleAllPasswords()" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                    👁️ Password Realtime
                                </button>
                            @endif
                            <button type="button" onclick="document.getElementById('add-user-modal').classList.remove('hidden'); document.getElementById('add-user-modal').classList.add('flex');" class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                                + Tambah User
                            </button>
                        </div>
                    </div>

                    <!-- Highlight Daftar Guru yang Ditetapkan sebagai Admin -->
                    @if($teachers->count() > 0)
                        <div class="rounded-2xl bg-[#EBF9F2]/70 border border-[#CDEFE0] p-4 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-extrabold text-[#0B6E4F]">👩‍🏫 Daftar Guru SMK Plus Al Hilal (Hak Akses Admin)</p>
                                <span class="text-[11px] font-semibold text-slate-500">{{ $teachers->count() }} Guru Terdaftar</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($teachers as $teacher)
                                    <div class="rounded-xl bg-white border border-emerald-500/20 p-3 flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-bold text-[#0F291E]">{{ $teacher->name }}</p>
                                            <p class="text-[11px] text-slate-500">{{ $teacher->email }} • NUPTK: {{ $teacher->nuptk ?? '-' }}</p>
                                            <span class="inline-block mt-1 rounded-md px-2 py-0.5 text-[10px] font-bold {{ $teacher->role === 'admin' ? 'bg-[#D5F5E6] text-[#0B6E4F]' : 'bg-slate-100 text-slate-600' }}">
                                                Peran: {{ $teacher->role === 'admin' ? 'Admin Aktif' : 'Guru / Pemilik Toko' }}
                                            </span>
                                        </div>
                                        @if(auth()->user()->role === 'super_admin')
                                            <form action="{{ route('admin.users.promote-teacher', $teacher) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg bg-[#0B6E4F] px-3 py-1.5 text-[10px] font-bold text-white hover:bg-[#08583F] transition">
                                                    {{ $teacher->role === 'admin' ? 'Jadikan Seller' : '👑 Jadikan Admin' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Filter Role Pengguna (Semua, Super Admin, Admin Guru, Toko Siswa, Pembeli) -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <span class="text-[11px] font-bold text-slate-400 mr-1">Filter Role:</span>
                        <button type="button" onclick="setAdminUserRoleFilter('all', this)"
                                class="admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-bold transition bg-[#0B6E4F] text-white shadow-xs">
                            Semua ({{ $stats['total_users'] }})
                        </button>
                        @if(auth()->user()->role === 'super_admin')
                            <button type="button" onclick="setAdminUserRoleFilter('super_admin', this)"
                                    class="admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-slate-100 text-slate-600 hover:bg-slate-200">
                                Super Admin ({{ $superAdminCount }})
                            </button>
                        @endif
                        <button type="button" onclick="setAdminUserRoleFilter('admin', this)"
                                class="admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-slate-100 text-slate-600 hover:bg-slate-200">
                            Admin / Guru ({{ $adminCount }})
                        </button>
                        <button type="button" onclick="setAdminUserRoleFilter('seller', this)"
                                class="admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-slate-100 text-slate-600 hover:bg-slate-200">
                            Toko Siswa ({{ $sellerCount }})
                        </button>
                        <button type="button" onclick="setAdminUserRoleFilter('buyer', this)"
                                class="admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-slate-100 text-slate-600 hover:bg-slate-200">
                            Pembeli ({{ $buyerCount }})
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="relative flex-1 min-w-[240px]">
                            <input
                                type="search"
                                oninput="filterAdminUserRows(this.value)"
                                placeholder="Cari nama, email, atau User ID..."
                                class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] pl-9 pr-4 py-2 text-xs text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none"
                            >
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-slate-400">{{ $stats['total_users'] }} pengguna terdaftar</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-5">USER ID</th>
                                <th class="py-3 px-4">NAMA & EMAIL</th>
                                @if(auth()->user()->role === 'super_admin')
                                    <th class="py-3 px-4">PASSWORD REALTIME</th>
                                @endif
                                <th class="py-3 px-4">PERAN</th>
                                <th class="py-3 px-4">STATUS</th>
                                <th class="py-3 px-4">BERGABUNG</th>
                                <th class="py-3 px-5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($users as $user)
                                @php
                                    $isEdited = $user->updated_at && $user->created_at && $user->updated_at->gt($user->created_at);
                                    $realtimePassword = $user->password_plain ?? 'password123';
                                    $roleBadgeClass = match($user->role) {
                                        'super_admin' => 'bg-[#0B6E4F] text-white',
                                        'admin' => 'bg-[#D5F5E6] text-[#0B6E4F]',
                                        'seller' => 'bg-[#EBF9F2] text-[#0B6E4F]',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                    $roleLabel = match($user->role) {
                                        'super_admin' => 'Super Admin',
                                        'admin' => $user->is_teacher ? 'Admin (Guru)' : 'Admin',
                                        'seller' => 'Pemilik Toko',
                                        default => 'Pembeli',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition admin-user-row" data-role="{{ $user->role }}" data-search="{{ strtolower($user->name . ' ' . $user->email . ' usr-00' . $user->id) }}">
                                    <td class="py-3.5 px-5 font-mono text-slate-400">
                                        USR-{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-[#EBF9F2] text-[#0B6E4F] font-bold text-[11px] flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#0F291E]">{{ $user->name }}</p>
                                                <p class="text-[11px] text-slate-400">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    @if(auth()->user()->role === 'super_admin')
                                        <td class="py-3.5 px-4">
                                            <div class="inline-flex items-center gap-1.5 rounded-lg bg-[#F8FAFC] border border-slate-200 px-2.5 py-1">
                                                <code data-pw="{{ $realtimePassword }}" class="pw-display font-mono font-bold text-[#0B6E4F]">{{ $realtimePassword }}</code>
                                                <button type="button" onclick="toggleSinglePassword(this)" class="text-slate-400 hover:text-slate-700" title="Lihat/Sembunyikan">👁️</button>
                                            </div>
                                            <span class="block text-[10px] mt-0.5 {{ $isEdited ? 'text-amber-600' : 'text-slate-400' }}">
                                                {{ $isEdited ? 'Sudah diedit' : 'Belum diedit' }}
                                            </span>
                                        </td>
                                    @endif

                                    <td class="py-3.5 px-4">
                                        <span class="rounded-md px-2.5 py-1 text-[10px] font-bold {{ $roleBadgeClass }}">
                                            {{ $roleLabel }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold {{ $user->is_active ? 'text-[#0B6E4F]' : 'text-rose-500' }}">
                                            ● {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <td class="py-3.5 px-4 text-slate-400">
                                        {{ $user->created_at?->format('d M Y') ?? '-' }}
                                    </td>

                                    <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-2">
                                        @if(auth()->user()->role === 'super_admin' && ($user->is_teacher || !empty($user->nuptk)))
                                            <form action="{{ route('admin.users.promote-teacher', $user) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg bg-amber-50 border border-amber-200 px-2.5 py-1 text-[10px] font-bold text-amber-800 hover:bg-amber-100">
                                                    {{ $user->role === 'admin' ? 'Set Seller' : '👑 Jadikan Admin' }}
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button"
                                                data-name="{{ $user->name }}"
                                                data-email="{{ $user->email }}"
                                                data-phone="{{ $user->phone }}"
                                                data-role="{{ $user->role }}"
                                                data-active="{{ $user->is_active ? 1 : 0 }}"
                                                data-password="{{ $realtimePassword }}"
                                                data-url="{{ route('admin.users.update', $user) }}"
                                                onclick="openEditUserModal(this)"
                                                class="font-bold text-[#0B6E4F] hover:underline">
                                            Edit
                                        </button>

                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                    <span>Menampilkan {{ $users->count() }} dari {{ $stats['total_users'] }} pengguna</span>
                    <div>{{ $users->links() }}</div>
                </div>
            </div>

            <!-- =================================================================
                 4. MASTER KATEGORI PRODUK & KELAS / JURUSAN
                 ================================================================= -->
            <div id="sec-master-data" class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Kelola Kategori Produk -->
                <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#0F291E]">Kelola Kategori Produk</h3>
                        <p class="text-xs text-slate-400">Tambah, ubah, dan hapus kategori karya di marketplace.</p>
                    </div>

                    <form action="{{ route('admin.categories.store') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="text" name="name" required placeholder="Nama kategori baru..." class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="description" placeholder="Keterangan..." class="w-36 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-semibold text-white hover:bg-[#08583F] shrink-0">
                            + Tambah
                        </button>
                    </form>

                    <div class="overflow-x-auto max-h-60 overflow-y-auto border border-slate-100 rounded-xl">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                                    <th class="py-2.5 px-3">Kategori</th>
                                    <th class="py-2.5 px-3">Keterangan</th>
                                    <th class="py-2.5 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($categories as $cat)
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold text-[#0F291E]">{{ $cat->name }}</td>
                                        <td class="py-2.5 px-3 text-slate-500">{{ $cat->description ?? '-' }}</td>
                                        <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">
                                            <button type="button"
                                                    data-name="{{ $cat->name }}"
                                                    data-description="{{ $cat->description }}"
                                                    data-url="{{ route('admin.categories.update', $cat) }}"
                                                    onclick="openEditCategoryModal(this)"
                                                    class="font-bold text-[#0B6E4F] hover:underline">Edit</button>
                                            <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-slate-400">Belum ada kategori. Silakan tambahkan di atas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kelola Kelas & Jurusan -->
                <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-[#0F291E]">Kelola Kelas & Jurusan</h3>
                        <p class="text-xs text-slate-400">Digunakan sebagai pilihan database saat registrasi Siswa & Keluarga.</p>
                    </div>

                    <form action="{{ route('admin.class-majors.store') }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="text" name="class_name" required placeholder="Kelas (X, XI, XII)" class="w-32 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="major_name" required placeholder="Nama Jurusan..." class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-semibold text-white hover:bg-[#08583F] shrink-0">
                            + Tambah
                        </button>
                    </form>

                    <div class="overflow-x-auto max-h-60 overflow-y-auto border border-slate-100 rounded-xl">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                                    <th class="py-2.5 px-3">Kelas</th>
                                    <th class="py-2.5 px-3">Jurusan</th>
                                    <th class="py-2.5 px-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($classMajors as $cm)
                                    <tr>
                                        <td class="py-2.5 px-3">
                                            <span class="rounded-md bg-[#EBF9F2] px-2 py-0.5 font-bold text-[#0B6E4F]">{{ $cm->class_name }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 font-semibold text-[#0F291E]">{{ $cm->major_name }}</td>
                                        <td class="py-2.5 px-3 text-right space-x-2 whitespace-nowrap">
                                            <button type="button"
                                                    data-class="{{ $cm->class_name }}"
                                                    data-major="{{ $cm->major_name }}"
                                                    data-url="{{ route('admin.class-majors.update', $cm) }}"
                                                    onclick="openEditClassMajorModal(this)"
                                                    class="font-bold text-[#0B6E4F] hover:underline">Edit</button>
                                            <form action="{{ route('admin.class-majors.destroy', $cm) }}" method="POST" class="inline" onsubmit="return confirm('Hapus Kelas & Jurusan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-6 text-center text-slate-400">Belum ada Kelas & Jurusan. Silakan tambahkan di atas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =================================================================
                 5. PERINGKAT TOKO TERATAS, FILTER & VALIDASI TOKO
                 ================================================================= -->
            <div id="sec-kelola-toko" class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F] mb-1">
                            🏆 LEADERBOARD & VALIDASI TOKO
                        </span>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Peringkat Toko Teratas & Validasi Toko</h2>
                        <p class="text-xs text-slate-400">
                            Filter dan urutkan toko berdasarkan Pelihat Terbanyak (👁️), Like Terbanyak (❤️), Komentar Terbanyak (💬), Rating Bintang Teratas (★), atau Pesanan Terbanyak (🛒).
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <select id="table-store-sort" onchange="sortAdminStoresTable()" class="rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 font-bold text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                            <option value="views">Urutkan: 👁️ Pelihat Terbanyak</option>
                            <option value="likes">Urutkan: ❤️ Like Terbanyak</option>
                            <option value="comments">Urutkan: 💬 Komentar Terbanyak</option>
                            <option value="ratings">Urutkan: ★ Rating Bintang Teratas</option>
                            <option value="orders">Urutkan: 🛒 Pesanan Terbanyak</option>
                        </select>
                        <input type="search" id="table-store-search" oninput="sortAdminStoresTable()" placeholder="Cari nama toko / pemilik..." class="rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">NAMA TOKO</th>
                                <th class="py-3 px-4">PEMILIK</th>
                                <th class="py-3 px-3 text-center">👁️ DILIHAT</th>
                                <th class="py-3 px-3 text-center">❤️ DISUKAI</th>
                                <th class="py-3 px-3 text-center">💬 KOMENTAR</th>
                                <th class="py-3 px-3 text-center">★ RATING</th>
                                <th class="py-3 px-3 text-center">🛒 PESANAN</th>
                                <th class="py-3 px-3">🏷️ SKEMA DISKON</th>
                                <th class="py-3 px-3">STATUS</th>
                                <th class="py-3 px-4 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="admin-store-tbody" class="divide-y divide-slate-100">
                            @forelse($stores as $idx => $store)
                                <tr class="admin-store-row hover:bg-slate-50/70 transition"
                                    data-search="{{ strtolower($store->store_name . ' ' . ($store->owner->name ?? '') . ' ' . ($store->student_class ?? '')) }}"
                                    data-views="{{ $store->metric_views }}"
                                    data-likes="{{ $store->metric_likes }}"
                                    data-comments="{{ $store->metric_comments }}"
                                    data-ratings="{{ $store->metric_rating }}"
                                    data-orders="{{ $store->metric_orders }}">
                                    <td class="py-3.5 px-4 font-extrabold text-slate-400 store-rank-cell">
                                        #{{ $idx + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-[#0F291E]">
                                        <a href="{{ route('stores.show', $store->slug) }}" class="hover:text-[#0B6E4F] hover:underline">{{ $store->store_name }}</a>
                                        <span class="block text-[10px] font-medium text-[#0B6E4F]">{{ $store->seller_type_label }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-600">
                                        {{ $store->owner->name }}
                                        <span class="block text-[10px] text-slate-400">{{ $store->student_class ?? '-' }}</span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-[#0B6E4F]">{{ number_format($store->metric_views) }}</td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-rose-500">{{ number_format($store->metric_likes) }}</td>
                                    <td class="py-3.5 px-3 text-center font-bold text-slate-700">{{ number_format($store->metric_comments) }}</td>
                                    <td class="py-3.5 px-3 text-center">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[11px] font-extrabold text-amber-700">
                                            ★ {{ number_format($store->metric_rating, 1, ',', '.') }}
                                        </span>
                                        <span class="block text-[10px] text-slate-400 mt-0.5">{{ $store->metric_rating_count }} nilai</span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center font-extrabold text-[#0F291E]">{{ number_format($store->metric_orders) }}</td>
                                    <td class="py-3.5 px-3 text-[10px] text-slate-600 space-y-0.5">
                                        <div>🎓 Pelajar: <strong>{{ $store->discount_pelajar ?? 0 }}%</strong></div>
                                        <div>🏫 Keluarga SMK: <strong>{{ $store->discount_keluarga ?? 0 }}%</strong></div>
                                        @if(($store->discount_bazar_extra ?? 0) > 0)
                                            <div class="text-amber-700 font-bold">🎪 Bazar: +{{ $store->discount_bazar_extra }}%</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="rounded-md px-2.5 py-1 text-[10px] font-bold {{ $store->is_verified ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $store->is_verified ? 'Terverifikasi' : 'Menunggu' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <form action="{{ route('admin.stores.verify', $store) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-xl px-3 py-1.5 text-xs font-semibold {{ $store->is_verified ? 'border border-slate-200 text-slate-600' : 'bg-[#0B6E4F] text-white' }}">
                                                {{ $store->is_verified ? 'Nonaktifkan' : '✓ Verifikasi' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-8 text-center text-slate-400">Belum ada toko yang terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =================================================================
                 5B. SKEMA ACARA BAZAR & EVENT DISKON SMK PLUS AL HILAL
                 ================================================================= -->
            <div id="sec-bazar-event" class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3 py-0.5 text-[10px] font-extrabold text-amber-800 mb-1">
                            🎪 SKEMA DISKON ACARA BAZAR & EVENT SMK PAL
                        </span>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Kelola Acara Bazar & Event Diskon SMK Plus Al Hilal</h2>
                        <p class="text-xs text-slate-400">
                            Buat skema promo bazar atau event sekolah agar pembeli yang datang ke stand bazar / berbelanja saat acara berlangsung mendapatkan potongan diskon khusus.
                        </p>
                    </div>
                </div>

                <!-- Form Tambah Acara Bazar / Event Diskon Baru -->
                <form action="{{ route('admin.bazaar.store') }}" method="POST" class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-5 space-y-4 text-xs">
                    @csrf
                    <p class="font-extrabold text-[#0F291E]">+ Tambah / Perbarui Acara Bazar & Event Diskon SMK</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Nama Acara Bazar / Event *</label>
                            <input type="text" name="title" required placeholder="Contoh: Bazar Karya & Inovasi SMK Plus Al Hilal" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Kode Voucher Bazar *</label>
                            <input type="text" name="voucher_code" required placeholder="Contoh: BAZARSMKPAL" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono font-bold uppercase text-[#0B6E4F]">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Diskon Event (%) *</label>
                            <input type="number" name="discount_percent" required min="1" max="90" value="10" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-extrabold text-[#0B6E4F]">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Lokasi Bazar / Stand</label>
                            <input type="text" name="location" placeholder="Halaman Utama SMK Plus Al Hilal" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Mulai Tanggal</label>
                            <input type="date" name="start_date" value="{{ date('Y-m-d') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Maks. Potongan Rp (Opsional)</label>
                            <input type="number" name="max_discount_amount" min="0" placeholder="Kosongkan jika tanpa batas" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2">
                        </div>
                        <div>
                            <button type="submit" class="w-full rounded-xl bg-[#0B6E4F] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                                + Simpan Acara Bazar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tabel Acara Bazar -->
                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                                <th class="py-3 px-4">NAMA ACARA BAZAR / EVENT</th>
                                <th class="py-3 px-4">KODE VOUCHER</th>
                                <th class="py-3 px-4">DISKON EVENT</th>
                                <th class="py-3 px-4">PERIODE ACARA</th>
                                <th class="py-3 px-4">STATUS</th>
                                <th class="py-3 px-4 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($bazaarEvents ?? [] as $bz)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-[#0F291E]">{{ $bz->title }}</p>
                                        <p class="text-[11px] text-slate-400">📍 {{ $bz->location ?: 'SMK Plus Al Hilal' }}</p>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-mono font-extrabold bg-amber-50 border border-amber-200 text-amber-900 px-2.5 py-1 rounded-lg">
                                            {{ $bz->voucher_code }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-extrabold text-[#0B6E4F]">
                                        {{ $bz->discount_percent }}%
                                        @if($bz->max_discount_amount)
                                            <span class="block text-[10px] font-normal text-slate-400">Maks. Rp {{ number_format($bz->max_discount_amount, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">
                                        {{ $bz->start_date?->format('d M Y') ?? 'Sekarang' }} – {{ $bz->end_date?->format('d M Y') ?? 'Seterusnya' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $bz->isActiveNow() ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $bz->isActiveNow() ? '● Aktif Berlangsung' : 'Nonaktif / Selesai' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <form action="{{ route('admin.bazaar.destroy', $bz) }}" method="POST" class="inline" onsubmit="return confirm('Hapus acara bazar ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-400">Belum ada acara bazar. Tambahkan di atas untuk mengaktifkan promo bazar SMK.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- =================================================================
                 6. LAPORAN PENGUNJUNG (SIAPA SAJA & DARI MANA SAJA)
                 ================================================================= -->
            <div id="sec-laporan-pengunjung" class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-5">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Laporan Pengunjung (Siapa Saja & Dari Mana Saja)</h2>
                    <p class="text-xs text-slate-400">Rekapitulasi kategori pengunjung (Pelajar, Keluarga, Alumni, Umum) dan asal wilayah.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <div class="lg:col-span-5 rounded-xl bg-[#F8FAFC] p-4 border border-slate-100 space-y-2">
                        <h3 class="text-xs font-bold text-[#0F291E]">Siapa Saja Pengunjung</h3>
                        @foreach($visitorByCategory as $label => $total)
                            <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2 border border-slate-100 text-xs">
                                <span class="text-slate-600">{{ $label }}</span>
                                <span class="font-bold text-[#0B6E4F]">{{ number_format($total) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="lg:col-span-7 rounded-xl bg-[#F8FAFC] p-4 border border-slate-100 space-y-2">
                        <h3 class="text-xs font-bold text-[#0F291E]">Dari Mana Saja (Kabupaten & Kecamatan)</h3>
                        <div class="overflow-x-auto max-h-48 overflow-y-auto bg-white rounded-lg border border-slate-100">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 text-[10px] uppercase text-slate-400">
                                        <th class="py-2 px-3">Kabupaten / Kota</th>
                                        <th class="py-2 px-3">Kecamatan</th>
                                        <th class="py-2 px-3 text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($visitorByRegion as $reg)
                                        <tr>
                                            <td class="py-2 px-3 font-bold text-[#0F291E]">{{ $reg->kabupaten }}</td>
                                            <td class="py-2 px-3 text-slate-500">{{ $reg->kecamatan ?? '-' }}</td>
                                            <td class="py-2 px-3 text-right font-bold text-[#0B6E4F]">{{ $reg->total }} orang</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-4 text-center text-slate-400">Belum ada data wilayah.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="text-[11px] text-slate-400 pt-2 flex flex-wrap items-center justify-between gap-2">
                <span>&copy; {{ date('Y') }} <strong>hilalkraf</strong> • Milik Resmi SMK Plus Al Hilal Arjawinangun</span>
                <span class="font-semibold text-[#0B6E4F]">Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</span>
            </footer>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL TAMBAH KONTEN / IKLAN MITRA CMS BARU
     ===================================================================== -->
<div id="add-cms-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Tambah Iklan Mitra / Konten CMS Baru</h3>
            <button type="button" onclick="document.getElementById('add-cms-modal').classList.add('hidden'); document.getElementById('add-cms-modal').classList.remove('flex');" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <form action="{{ route('admin.cms.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Bagian / Section *</label>
                    <select name="section" id="add-cms-section" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="iklan">⚡ Iklan Mitra Kerjasama (Atas Beranda)</option>
                        <option value="custom_banner">Banner / Pengumuman Beranda</option>
                        <option value="hero">Hero Beranda</option>
                        <option value="values">Poin Keunggulan</option>
                        <option value="topbar">Topbar Atas</option>
                        <option value="footer">Footer Bawah</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Tema Warna Banner</label>
                    <select name="theme_color" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="bsi">Hijau-Emas (Gaya BSI)</option>
                        <option value="megamentari">Biru Industri (PT. Mega Mentari)</option>
                        <option value="daihatsu">Merah Otomotif (Daihatsu)</option>
                        <option value="emerald">Hijau SMK Plus Al Hilal</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Nama Mitra / Instansi (Untuk Iklan)</label>
                    <input type="text" name="partner_name" placeholder="Misal: Bank Syariah Indonesia (BSI)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Label Badge Kerjasama</label>
                    <input type="text" name="badge_text" placeholder="Misal: MITRA PERBANKAN SYARIAH" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Judul Iklan / Konten *</label>
                <input type="text" name="title" required placeholder="Contoh: Program Tabungan & Literasi Keuangan Siswa bersama BSI" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Isi Teks / Deskripsi *</label>
                <textarea name="content" rows="2" required placeholder="Tuliskan deskripsi iklan mitra atau isi teks konten..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2"></textarea>
            </div>

            <div class="grid grid-cols-3 gap-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80 p-3">
                <div>
                    <label class="block font-bold text-amber-900 mb-1">Durasi Tampil (Detik)</label>
                    <input type="number" name="duration_seconds" min="1" max="120" value="5" class="w-full rounded-lg border border-amber-300 bg-white px-2.5 py-1.5 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-amber-900 mb-1">Mulai Kontrak</label>
                    <input type="date" name="contract_start" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-amber-300 bg-white px-2 py-1.5">
                </div>
                <div>
                    <label class="block font-bold text-amber-900 mb-1">Akhir Kontrak</label>
                    <input type="date" name="contract_end" value="{{ date('Y-m-d', strtotime('+6 months')) }}" class="w-full rounded-lg border border-amber-300 bg-white px-2 py-1.5">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Upload Gambar Banner (Opsional)</label>
                    <input type="file" name="image_file" accept="image/*" class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-[11px]">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Atau URL Gambar (Opsional)</label>
                    <input type="text" name="image_url" placeholder="https://..." class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Link Tujuan (Opsional)</label>
                <input type="text" name="link_url" placeholder="#katalog-produk atau https://..." class="w-full rounded-xl border border-slate-200 px-3 py-2">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('add-cms-modal').classList.add('hidden'); document.getElementById('add-cms-modal').classList.remove('flex');" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan ke CMS</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL EDIT KONTEN / IKLAN MITRA CMS
     ===================================================================== -->
<div id="edit-cms-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Edit Iklan Mitra / Konten CMS</h3>
            <button type="button" onclick="closeEditCmsModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <form id="edit-cms-form" method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-3 gap-2.5">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Bagian / Section *</label>
                    <select name="section" id="edit-cms-section" required class="w-full rounded-xl border border-slate-200 px-2.5 py-2">
                        <option value="iklan">⚡ Iklan Mitra (Atas)</option>
                        <option value="topbar">Topbar Atas</option>
                        <option value="hero">Hero Beranda</option>
                        <option value="values">Poin Keunggulan</option>
                        <option value="story">Cerita Sekolah</option>
                        <option value="footer">Footer Bawah</option>
                        <option value="custom_banner">Banner / Pengumuman</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Tema Warna</label>
                    <select name="theme_color" id="edit-cms-theme" class="w-full rounded-xl border border-slate-200 px-2.5 py-2">
                        <option value="bsi">Hijau-Emas (BSI)</option>
                        <option value="megamentari">Biru (Mega Mentari)</option>
                        <option value="daihatsu">Merah (Daihatsu)</option>
                        <option value="emerald">Hijau Al Hilal</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Status Tampil</label>
                    <select name="is_active" id="edit-cms-active" class="w-full rounded-xl border border-slate-200 px-2.5 py-2">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Nama Mitra / Instansi</label>
                    <input type="text" name="partner_name" id="edit-cms-partner" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Label Badge Kerjasama</label>
                    <input type="text" name="badge_text" id="edit-cms-badge" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Judul Iklan / Label Item CMS *</label>
                <input type="text" name="title" id="edit-cms-title" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Isi Teks yang Tampil di Website *</label>
                <textarea name="content" id="edit-cms-content" rows="2" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2"></textarea>
            </div>

            <div class="grid grid-cols-3 gap-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80 p-3">
                <div>
                    <label class="block font-bold text-amber-900 mb-1">Durasi Tampil (Detik)</label>
                    <input type="number" name="duration_seconds" id="edit-cms-duration" min="1" max="120" class="w-full rounded-lg border border-amber-300 bg-white px-2.5 py-1.5 font-bold">
                </div>
                <div>
                    <label class="block font-bold text-amber-900 mb-1">Mulai Kontrak</label>
                    <input type="date" name="contract_start" id="edit-cms-start" class="w-full rounded-lg border border-amber-300 bg-white px-2 py-1.5">
                </div>
                <div>
                    <label class="block font-bold text-amber-900 mb-1">Akhir Kontrak</label>
                    <input type="date" name="contract_end" id="edit-cms-end" class="w-full rounded-lg border border-amber-300 bg-white px-2 py-1.5">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Upload Gambar Baru (Opsional)</label>
                    <input type="file" name="image_file" accept="image/*" class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-[11px]">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Atau URL Gambar</label>
                    <input type="text" name="image_url" id="edit-cms-image" class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Link Tujuan (Opsional)</label>
                <input type="text" name="link_url" id="edit-cms-link" class="w-full rounded-xl border border-slate-200 px-3 py-2">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditCmsModal()" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL TAMBAH KARYA SISWA (PRODUK) DARI CMS ADMIN
     ===================================================================== -->
<div id="add-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Tambah Karya Siswa Baru</h3>
            <button type="button" onclick="document.getElementById('add-product-modal').classList.add('hidden'); document.getElementById('add-product-modal').classList.remove('flex');" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Toko Siswa / Guru *</label>
                    <select name="store_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->store_name }} ({{ $st->owner->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Kategori Karya *</label>
                    <select name="category" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        @forelse($categories as $cat)
                            <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @empty
                            <option value="Kerajinan">Kerajinan</option>
                        @endforelse
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Nama Karya Siswa *</label>
                <input type="text" name="product_name" required placeholder="Contoh: Gantungan Kunci Karakter" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Harga (Rp) *</label>
                    <input type="number" name="price" required min="1000" value="185000" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Stok Tersedia *</label>
                    <input type="number" name="stock" required min="0" value="12" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Upload Foto Karya (Opsional)</label>
                    <input type="file" name="image_file" accept="image/*" class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-[11px]">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Atau URL Gambar (Opsional)</label>
                    <input type="text" name="image_url" placeholder="https://..." class="w-full rounded-xl border border-slate-200 px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Deskripsi Karya Siswa *</label>
                <textarea name="description" rows="3" required placeholder="Ceritakan bahan, fungsi, dan keunikan karya siswa ini..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('add-product-modal').classList.add('hidden'); document.getElementById('add-product-modal').classList.remove('flex');" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan Karya Siswa</button>
            </div>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL EDIT KARYA SISWA (PRODUK) DARI CMS ADMIN
     ===================================================================== -->
<div id="edit-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-lg w-full p-6 shadow-xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Edit Karya Siswa (CMS)</h3>
            <button type="button" onclick="closeEditProductModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <form id="edit-product-form" method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Toko Siswa / Guru *</label>
                    <select name="store_id" id="edit-prod-store" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->store_name }} ({{ $st->owner->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Kategori Karya *</label>
                    <input type="text" name="category" id="edit-prod-category" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Nama Karya Siswa *</label>
                <input type="text" name="product_name" id="edit-prod-name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Harga (Rp) *</label>
                    <input type="number" name="price" id="edit-prod-price" required min="1000" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Stok *</label>
                    <input type="number" name="stock" id="edit-prod-stock" required min="0" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">URL Gambar Karya</label>
                <input type="text" name="image_url" id="edit-prod-image" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Deskripsi Karya Siswa *</label>
                <textarea name="description" id="edit-prod-description" rows="3" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditProductModal()" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah User Baru -->
<div id="add-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Tambah Pengguna / Akun Toko</h3>
            <button type="button" onclick="document.getElementById('add-user-modal').classList.add('hidden'); document.getElementById('add-user-modal').classList.remove('flex');" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Email Login *</label>
                <input type="email" name="email" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">No. WhatsApp</label>
                    <input type="text" name="phone" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Peran *</label>
                    <select name="role" onchange="toggleAdminSellerFields(this.value)" required class="w-full rounded-xl border border-slate-200 px-3 py-2">
                        <option value="seller">Pemilik Toko</option>
                        <option value="buyer">Pembeli</option>
                        @if(auth()->user()->role === 'super_admin')
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        @endif
                    </select>
                </div>
            </div>

            <div id="admin-seller-fields" class="space-y-2 rounded-xl bg-[#F8FAFC] p-3 border border-slate-200">
                <select name="seller_type" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-semibold">
                    <option value="siswa">Siswa (NIS/NISN)</option>
                    <option value="guru">Guru (NUPTK)</option>
                </select>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="nis_nisn" placeholder="NIS/NISN" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5">
                    <input type="text" name="nuptk" placeholder="NUPTK" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5">
                </div>
                <input type="text" name="store_name" placeholder="Nama Toko" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5">
            </div>

            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Password *</label>
                <input type="text" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 font-mono">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('add-user-modal').classList.add('hidden'); document.getElementById('add-user-modal').classList.remove('flex');" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User & Kredensial Realtime -->
<div id="edit-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Edit Akun & Kredensial Realtime</h3>
            <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>

        <form id="edit-user-form" method="POST" class="space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Nama Lengkap *</label>
                <input type="text" name="name" id="edit-name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Email Login *</label>
                <input type="email" name="email" id="edit-email" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 font-mono">
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">No. WhatsApp</label>
                    <input type="text" name="phone" id="edit-phone" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                </div>
                <div>
                    <label class="block font-bold text-[#0F291E] mb-1">Status</label>
                    <select name="is_active" id="edit-is-active" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Peran *</label>
                <select name="role" id="edit-role" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
                    @if(auth()->user()->role === 'super_admin')
                        <option value="super_admin">Super Admin</option>
                        <option value="admin">Admin</option>
                    @endif
                    <option value="seller">Pemilik Toko</option>
                    <option value="buyer">Pembeli</option>
                </select>
            </div>
            <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] p-3 space-y-1">
                <label class="block font-bold text-[#0B6E4F]">Password Realtime</label>
                <input type="text" name="password" id="edit-password" minlength="6" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-mono font-bold text-[#0B6E4F]">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditUserModal()" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="edit-category-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Ubah Kategori Produk</h3>
            <button type="button" onclick="closeEditCategoryModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>
        <form id="edit-category-form" method="POST" class="space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Nama Kategori *</label>
                <input type="text" name="name" id="edit-category-name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Keterangan</label>
                <input type="text" name="description" id="edit-category-desc" class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditCategoryModal()" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kelas & Jurusan -->
<div id="edit-class-major-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-extrabold text-[#0F291E]">Ubah Kelas & Jurusan</h3>
            <button type="button" onclick="closeEditClassMajorModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
        </div>
        <form id="edit-class-major-form" method="POST" class="space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Kelas *</label>
                <input type="text" name="class_name" id="edit-cm-class" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div>
                <label class="block font-bold text-[#0F291E] mb-1">Nama Jurusan *</label>
                <input type="text" name="major_name" id="edit-cm-major" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditClassMajorModal()" class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 font-semibold text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

@php
    $chartRawData = [];
    foreach ($stores as $s) {
        $chartRawData[] = [
            'name'        => $s->store_name,
            'seller_type' => $s->seller_type ?? 'siswa',
            'views'       => (int) $s->metric_views,
            'likes'       => (int) $s->metric_likes,
            'comments'    => (int) $s->metric_comments,
            'ratings'     => (float) $s->metric_rating,
            'orders'      => (int) $s->metric_orders,
        ];
    }
    $chartRawJson = json_encode($chartRawData);
@endphp

<script>
    const RAW_STORES_ANALYTICS = {!! $chartRawJson !!};
    let storeAnalyticsChartInstance = null;

    function updateStoreAnalyticsChart() {
        const ctx = document.getElementById('storeAnalyticsChart');
        if (!ctx) return;

        const sortMetric = document.getElementById('chart-sort-metric')?.value || 'views';
        const limitVal = parseInt(document.getElementById('chart-limit')?.value || '10', 10);
        const sellerType = document.getElementById('chart-seller-type')?.value || 'all';
        const datasetMode = document.getElementById('chart-dataset-mode')?.value || 'all';

        let filtered = RAW_STORES_ANALYTICS.filter(item => {
            if (sellerType === 'all') return true;
            return (item.seller_type || 'siswa') === sellerType;
        });

        filtered.sort((a, b) => (b[sortMetric] || 0) - (a[sortMetric] || 0));

        const topStore = filtered[0];
        const badgeEl = document.getElementById('top-store-highlight-badge');
        if (badgeEl) {
            if (topStore) {
                const metricText = {
                    views: `👁️ ${topStore.views} dilihat`,
                    likes: `❤️ ${topStore.likes} disukai`,
                    comments: `💬 ${topStore.comments} komentar`,
                    ratings: `★ ${topStore.ratings} / 5`,
                    orders: `🛒 ${topStore.orders} pesanan`,
                }[sortMetric] || '';
                badgeEl.textContent = `🏆 #1 Teratas: ${topStore.name} (${metricText})`;
            } else {
                badgeEl.textContent = 'Belum ada data toko pada filter ini';
            }
        }

        const sliced = filtered.slice(0, limitVal);
        const labels = sliced.map(s => s.name);

        const allDatasets = [
            {
                key: 'views',
                label: 'Dilihat (👁️)',
                data: sliced.map(s => s.views),
                backgroundColor: '#0B6E4F',
                borderRadius: 6,
            },
            {
                key: 'likes',
                label: 'Disukai (❤️)',
                data: sliced.map(s => s.likes),
                backgroundColor: '#F43F5E',
                borderRadius: 6,
            },
            {
                key: 'comments',
                label: 'Komentar (💬)',
                data: sliced.map(s => s.comments),
                backgroundColor: '#10B981',
                borderRadius: 6,
            },
            {
                key: 'ratings',
                label: 'Rating Bintang (★)',
                data: sliced.map(s => s.ratings),
                backgroundColor: '#F59E0B',
                borderRadius: 6,
            },
            {
                key: 'orders',
                label: 'Pesanan (🛒)',
                data: sliced.map(s => s.orders),
                backgroundColor: '#3B82F6',
                borderRadius: 6,
            },
        ];

        const activeDatasets = datasetMode === 'selected'
            ? allDatasets.filter(d => d.key === sortMetric)
            : allDatasets;

        if (storeAnalyticsChartInstance) {
            storeAnalyticsChartInstance.data.labels = labels;
            storeAnalyticsChartInstance.data.datasets = activeDatasets;
            storeAnalyticsChartInstance.update();
        } else {
            storeAnalyticsChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: activeDatasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Sinkronkan juga dropdown tabel peringkat toko
        const tableSortSelect = document.getElementById('table-store-sort');
        if (tableSortSelect && tableSortSelect.value !== sortMetric) {
            tableSortSelect.value = sortMetric;
            sortAdminStoresTable(false);
        }
    }

    function sortAdminStoresTable(syncChart = true) {
        const tbody = document.getElementById('admin-store-tbody');
        if (!tbody) return;

        const sortKey = document.getElementById('table-store-sort')?.value || 'views';
        const query = (document.getElementById('table-store-search')?.value || '').toLowerCase();

        const rows = Array.from(tbody.querySelectorAll('.admin-store-row'));
        rows.sort((a, b) => {
            const valA = parseFloat(a.getAttribute('data-' + sortKey) || '0');
            const valB = parseFloat(b.getAttribute('data-' + sortKey) || '0');
            return valB - valA;
        });

        let rank = 1;
        rows.forEach(row => {
            const text = row.getAttribute('data-search') || '';
            const visible = text.includes(query);
            row.style.display = visible ? '' : 'none';
            if (visible) {
                const rankCell = row.querySelector('.store-rank-cell');
                if (rankCell) rankCell.textContent = '#' + rank++;
            }
            tbody.appendChild(row);
        });

        if (syncChart) {
            const chartSort = document.getElementById('chart-sort-metric');
            if (chartSort && chartSort.value !== sortKey) {
                chartSort.value = sortKey;
                updateStoreAnalyticsChart();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateStoreAnalyticsChart();
    });

    let allMasked = false;

    function toggleSinglePassword(btn) {
        const codeEl = btn.previousElementSibling;
        const realPw = codeEl.getAttribute('data-pw');
        codeEl.textContent = (codeEl.textContent === '••••••••') ? realPw : '••••••••';
    }

    function toggleAllPasswords() {
        allMasked = !allMasked;
        document.querySelectorAll('.pw-display').forEach(el => {
            el.textContent = allMasked ? '••••••••' : el.getAttribute('data-pw');
        });
    }

    let currentAdminRoleFilter = 'all';
    let currentAdminUserSearchTerm = '';

    function setAdminUserRoleFilter(role, btn) {
        currentAdminRoleFilter = role;
        document.querySelectorAll('.admin-role-filter-btn').forEach(b => {
            b.className = 'admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-slate-100 text-slate-600 hover:bg-slate-200';
        });
        if (btn) {
            btn.className = 'admin-role-filter-btn rounded-lg px-3 py-1.5 text-xs font-bold transition bg-[#0B6E4F] text-white shadow-xs';
        }
        applyAdminUserFilters();
    }

    function filterAdminUserRows(q) {
        currentAdminUserSearchTerm = (q || '').toLowerCase();
        applyAdminUserFilters();
    }

    function applyAdminUserFilters() {
        document.querySelectorAll('.admin-user-row').forEach(row => {
            const rowRole = row.getAttribute('data-role') || '';
            const rowTxt = row.getAttribute('data-search') || '';
            const roleMatch = (currentAdminRoleFilter === 'all' || rowRole === currentAdminRoleFilter);
            const searchMatch = (!currentAdminUserSearchTerm || rowTxt.includes(currentAdminUserSearchTerm));
            row.style.display = (roleMatch && searchMatch) ? '' : 'none';
        });
    }

    function toggleAdminSellerFields(role) {
        const el = document.getElementById('admin-seller-fields');
        if (role === 'seller') {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    function openAddCmsModal(defaultSection) {
        const modal = document.getElementById('add-cms-modal');
        const sectionSelect = document.getElementById('add-cms-section');
        if (sectionSelect && defaultSection) {
            sectionSelect.value = defaultSection;
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function openEditCmsModal(btn) {
        const modal = document.getElementById('edit-cms-modal');
        document.getElementById('edit-cms-form').action = btn.getAttribute('data-url');
        document.getElementById('edit-cms-section').value = btn.getAttribute('data-section') || 'custom_banner';
        document.getElementById('edit-cms-title').value = btn.getAttribute('data-title') || '';
        document.getElementById('edit-cms-partner').value = btn.getAttribute('data-partner') || '';
        document.getElementById('edit-cms-badge').value = btn.getAttribute('data-badge') || '';
        document.getElementById('edit-cms-content').value = btn.getAttribute('data-content') || '';
        document.getElementById('edit-cms-image').value = btn.getAttribute('data-image') || '';
        document.getElementById('edit-cms-link').value = btn.getAttribute('data-link') || '';
        document.getElementById('edit-cms-duration').value = btn.getAttribute('data-duration') || '5';
        document.getElementById('edit-cms-start').value = btn.getAttribute('data-start') || '';
        document.getElementById('edit-cms-end').value = btn.getAttribute('data-end') || '';
        document.getElementById('edit-cms-theme').value = btn.getAttribute('data-theme') || 'emerald';
        document.getElementById('edit-cms-active').value = btn.getAttribute('data-active') || '1';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditCmsModal() {
        const modal = document.getElementById('edit-cms-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditProductModal(btn) {
        const modal = document.getElementById('edit-product-modal');
        document.getElementById('edit-product-form').action = btn.getAttribute('data-url');
        document.getElementById('edit-prod-store').value = btn.getAttribute('data-store') || '';
        document.getElementById('edit-prod-category').value = btn.getAttribute('data-category') || 'Kerajinan';
        document.getElementById('edit-prod-name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit-prod-price').value = btn.getAttribute('data-price') || '0';
        document.getElementById('edit-prod-stock').value = btn.getAttribute('data-stock') || '0';
        document.getElementById('edit-prod-image').value = btn.getAttribute('data-image') || '';
        document.getElementById('edit-prod-description').value = btn.getAttribute('data-description') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditProductModal() {
        const modal = document.getElementById('edit-product-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditUserModal(btn) {
        const modal = document.getElementById('edit-user-modal');
        const form = document.getElementById('edit-user-form');
        form.action = btn.getAttribute('data-url');
        document.getElementById('edit-name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit-email').value = btn.getAttribute('data-email') || '';
        document.getElementById('edit-phone').value = btn.getAttribute('data-phone') || '';
        document.getElementById('edit-role').value = btn.getAttribute('data-role') || 'buyer';
        document.getElementById('edit-is-active').value = btn.getAttribute('data-active') || '1';
        document.getElementById('edit-password').value = btn.getAttribute('data-password') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditUserModal() {
        const modal = document.getElementById('edit-user-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditCategoryModal(btn) {
        const modal = document.getElementById('edit-category-modal');
        document.getElementById('edit-category-form').action = btn.getAttribute('data-url');
        document.getElementById('edit-category-name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit-category-desc').value = btn.getAttribute('data-description') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditCategoryModal() {
        const modal = document.getElementById('edit-category-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditClassMajorModal(btn) {
        const modal = document.getElementById('edit-class-major-modal');
        document.getElementById('edit-class-major-form').action = btn.getAttribute('data-url');
        document.getElementById('edit-cm-class').value = btn.getAttribute('data-class') || '';
        document.getElementById('edit-cm-major').value = btn.getAttribute('data-major') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditClassMajorModal() {
        const modal = document.getElementById('edit-class-major-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Auto-scroll ke section hash setelah simpan tanpa reload melompat
    window.addEventListener('DOMContentLoaded', () => {
        // 1. Cek apakah ada hash di URL (misal: #sec-manajemen-user)
        if (window.location.hash) {
            const targetEl = document.querySelector(window.location.hash);
            if (targetEl) {
                setTimeout(() => {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 150);
            }
        }

        // 2. Auto-dismiss floating toast setelah 5 detik
        const toast = document.getElementById('admin-floating-toast');
        if (toast) {
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 400);
            }, 5000);
        }

        // 3. Simpan posisi scroll sebelum submit form apa pun di admin dashboard
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                sessionStorage.setItem('admin_last_scroll_y', window.scrollY);
            });
        });
    });
</script>
@endsection
