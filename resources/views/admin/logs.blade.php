{{--
============================================================================
VIEW: LOG AKSES & AKTIVITAS SISTEM (admin/logs.blade.php)
============================================================================
Fungsi:
  Menampilkan catatan log audit lengkap (seperti pada platform HilalEdu)
  untuk mendeteksi siapa saja yang masuk, login/logout, mendaftar akun,
  mengubah toko, produk, CMS, maupun transaksi, lengkap dengan IP address,
  perangkat/browser, waktu, dan rincian aktivitas.
Akses:
  Super Admin & Admin (Guru SMK Plus Al Hilal).
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Log Akses & Aktivitas Sistem — hilalkraf SMK Plus Al Hilal')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Sidebar Kiri Administrasi -->
    <aside class="w-64 bg-white border-r border-slate-200/80 hidden lg:flex flex-col justify-between shrink-0 sticky top-0 h-screen p-6 overflow-y-auto">
        <div class="space-y-6">
            <a href="{{ route('home') }}" class="flex flex-col gap-2 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-2.5 py-1.5 shadow-xs">
                    <img src="{{ asset('images/logo-hilalkraf.jpg') }}" alt="hilalkraf" class="h-10 w-auto object-contain">
                    <span class="h-6 w-[1px] bg-slate-200"></span>
                    <img src="{{ asset('images/logo-smk-alhilal.png') }}" alt="SMK Plus Al Hilal" class="h-8 w-8 object-contain">
                </div>
                <div>
                    <span class="text-base font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                    <span class="text-[10px] font-bold text-[#0B6E4F]">Inkubator Bisnis SMK PAL</span>
                </div>
            </a>

            <div class="space-y-1">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">PANEL AUDIT SISTEM</p>
                <p class="text-xs font-extrabold text-[#0F291E]">SMK Plus Al Hilal</p>
                <span class="inline-block rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F] mt-1">
                    {{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin (Guru)' }}
                </span>
            </div>

            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>⊞</span>
                    <span>Dashboard Utama</span>
                </a>
                <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>📊</span>
                    <span>Laporan & Rekap</span>
                </a>
                <a href="{{ route('admin.logs') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold shadow-2xs">
                    <span>📋</span>
                    <span>Log Akses & Aktivitas</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-cms" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>✨</span>
                    <span>CMS Konten Website</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-manajemen-user" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>👥</span>
                    <span>Manajemen User</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-kelola-toko" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🏪</span>
                    <span>Kelola & Peringkat Toko</span>
                </a>
                <a href="{{ route('admin.dashboard') }}#sec-bazar-event" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🎪</span>
                    <span>Bazar & Diskon SMK</span>
                </a>
            </nav>
        </div>

        <div class="space-y-3 pt-4 border-t border-slate-100 text-xs">
            <a href="{{ route('home') }}" class="block text-slate-500 hover:text-[#0B6E4F] transition">↗ Lihat Beranda Publik</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-rose-600 font-semibold hover:underline">Keluar dari Akun</button>
            </form>
        </div>
    </aside>

    <!-- Konten Utama Halaman Log -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar Header -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-10 flex items-center justify-between gap-4 sticky top-0 z-20">
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="font-bold text-[#0B6E4F]">← Kembali ke Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Log Audit Sistem</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin (Guru)' }}</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Area Konten Utama -->
        <main class="p-6 sm:p-10 space-y-6 max-w-7xl w-full mx-auto">

            <!-- Judul & Keterangan -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-[11px] font-bold text-[#0B6E4F] mb-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Audit Log Sistem Realtime (Ala HilalEdu)</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">Log Akses & Aktivitas Sistem</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Mendeteksi siapa saja yang masuk, login/logout, registrasi akun, dan melakukan perubahan data di hilalkraft.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.logs') }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                        🔄 Refresh Data
                    </a>
                </div>
            </div>

            <!-- 4 Kartu KPI Ringkasan Log -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-1.5 shadow-2xs">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Total Seluruh Log</span>
                        <span class="text-[#0B6E4F] text-base">📋</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($stats['total_logs']) }}</p>
                    <p class="text-[11px] font-semibold text-slate-500">Rekam jejak aktivitas tersimpan</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-1.5 shadow-2xs">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Aktivitas Hari Ini</span>
                        <span class="text-emerald-600 text-base">⚡</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($stats['today_logs']) }}</p>
                    <p class="text-[11px] font-semibold text-emerald-700">{{ date('d M Y') }}</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-1.5 shadow-2xs">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Login Masuk Hari Ini</span>
                        <span class="text-blue-600 text-base">🔑</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-blue-600">{{ number_format($stats['logins_today']) }}</p>
                    <p class="text-[11px] font-semibold text-blue-700">Sesi pengguna aktif</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-1.5 shadow-2xs">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Alamat IP Unik Terpantau</span>
                        <span class="text-purple-600 text-base">🌐</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-purple-600">{{ number_format($stats['unique_ips']) }}</p>
                    <p class="text-[11px] font-semibold text-purple-700">{{ number_format($stats['unique_users']) }} Pengguna Unik</p>
                </div>
            </div>

            <!-- Filter Interaktif Bar -->
            <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-4 shadow-2xs">
                <form action="{{ route('admin.logs') }}" method="GET" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                        <!-- Pencarian -->
                        <div class="lg:col-span-4">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Aktor, Judul, IP, atau Deskripsi</label>
                            <input type="search"
                                   name="q"
                                   value="{{ request('q') }}"
                                   placeholder="Contoh: Silvi, Ahmad, 192.168, Login..."
                                   class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                        </div>

                        <!-- Filter Jenis Aktivitas -->
                        <div class="lg:col-span-3">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jenis Aktivitas</label>
                            <select name="type" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none cursor-pointer">
                                <option value="all" @selected(!request('type') || request('type') === 'all')>Semua Aktivitas</option>
                                <option value="login" @selected(request('type') === 'login')>🔑 Login Masuk</option>
                                <option value="logout" @selected(request('type') === 'logout')>🚪 Logout Keluar</option>
                                <option value="register" @selected(request('type') === 'register')>👤 Registrasi Akun</option>
                                <option value="store_create" @selected(request('type') === 'store_create')>🏪 Pembuatan Toko</option>
                                <option value="store_update" @selected(request('type') === 'store_update')>✏️ Edit / Status Toko</option>
                                <option value="store_delete" @selected(request('type') === 'store_delete')>🗑️ Penghapusan Toko</option>
                                <option value="store_restore" @selected(request('type') === 'store_restore')>♻️ Pemulihan Toko</option>
                                <option value="product_create" @selected(request('type') === 'product_create')>🎨 Tambah Produk</option>
                                <option value="product_update" @selected(request('type') === 'product_update')>✏️ Edit Produk</option>
                                <option value="product_delete" @selected(request('type') === 'product_delete')>🗑️ Hapus Produk</option>
                                <option value="order_update" @selected(request('type') === 'order_update')>🚚 Progres Pesanan</option>
                                <option value="cms_update" @selected(request('type') === 'cms_update')>✨ Kelola CMS</option>
                            </select>
                        </div>

                        <!-- Filter Peran Pengguna -->
                        <div class="lg:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Peran Akun</label>
                            <select name="role" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none cursor-pointer">
                                <option value="all" @selected(!request('role') || request('role') === 'all')>Semua Peran</option>
                                <option value="super_admin" @selected(request('role') === 'super_admin')>Super Admin</option>
                                <option value="admin" @selected(request('role') === 'admin')>Admin Guru</option>
                                <option value="seller" @selected(request('role') === 'seller')>Toko Siswa</option>
                                <option value="buyer" @selected(request('role') === 'buyer')>Pembeli</option>
                                <option value="guest" @selected(request('role') === 'guest')>Tamu (Guest)</option>
                            </select>
                        </div>

                        <!-- Rentang Tanggal -->
                        <div class="lg:col-span-3 flex items-center gap-2">
                            <div class="flex-1">
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 py-1.5 text-xs">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-2.5 py-1.5 text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <span class="text-xs text-slate-400">
                            Menampilkan <strong>{{ $logs->total() }}</strong> baris rekam jejak sistem
                        </span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.logs') }}" class="rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                                Reset Filter
                            </a>
                            <button type="submit" class="rounded-xl bg-[#0B6E4F] px-5 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-xs">
                                🔍 Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabel Log Audit Akses & Aktivitas -->
            <div class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-2xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-4">WAKTU & TANGGAL</th>
                                <th class="py-3 px-4">PENGGUNA / AKTOR</th>
                                <th class="py-3 px-4">AKTIVITAS</th>
                                <th class="py-3 px-4">RINCIAN DETAIL</th>
                                <th class="py-3 px-4">PERANGKAT & ALAMAT IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($logs as $log)
                                @php
                                    $badge = $log->type_badge;
                                    $roleBadge = match($log->user_role) {
                                        'super_admin' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'admin'       => 'bg-emerald-100 text-[#0B6E4F] border-emerald-200',
                                        'seller'      => 'bg-teal-100 text-teal-800 border-teal-200',
                                        'buyer'       => 'bg-blue-100 text-blue-800 border-blue-200',
                                        default       => 'bg-slate-100 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <tr class="hover:bg-[#F8FAFC]/80 transition">
                                    <!-- Waktu -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <p class="font-extrabold text-[#0F291E]">{{ $log->created_at->format('d/m/Y') }}</p>
                                        <p class="text-[11px] text-slate-400 font-mono">{{ $log->created_at->format('H:i:s') }} WIB</p>
                                        <p class="text-[10px] text-emerald-600 font-medium">{{ $log->created_at->diffForHumans() }}</p>
                                    </td>

                                    <!-- Pengguna / Aktor -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-[#EBF9F2] text-[#0B6E4F] font-bold text-xs flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($log->user_name ?? 'G', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-[#0F291E] truncate">{{ $log->user_name }}</p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="inline-block rounded-md border px-1.5 py-0.2 text-[9px] font-bold {{ $roleBadge }}">
                                                        {{ strtoupper($log->user_role ?? 'GUEST') }}
                                                    </span>
                                                    @if($log->user?->email)
                                                        <span class="text-[10px] text-slate-400 truncate font-mono">{{ $log->user->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Aktivitas -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[10px] font-extrabold {{ $badge['color'] }}">
                                            <span>{{ $badge['label'] }}</span>
                                        </span>
                                    </td>

                                    <!-- Rincian Detail -->
                                    <td class="py-3.5 px-4">
                                        <p class="font-bold text-[#0F291E]">{{ $log->title }}</p>
                                        @if($log->description)
                                            <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ $log->description }}</p>
                                        @endif
                                        @if($log->url)
                                            <p class="text-[10px] text-slate-400 font-mono truncate mt-0.5">URL: {{ parse_url($log->url, PHP_URL_PATH) }}</p>
                                        @endif
                                    </td>

                                    <!-- Perangkat & IP -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <p class="font-mono text-xs font-semibold text-slate-700">{{ $log->ip_address ?: '127.0.0.1' }}</p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $log->device ?: 'Perangkat Web' }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400">
                                        <span class="text-2xl block mb-1">📋</span>
                                        <p class="font-bold text-slate-600">Belum ada riwayat aktivitas yang tercatat</p>
                                        <p class="text-xs mt-0.5">Semua aktivitas masuk dan perubahan data akan otomatis tercatat di sini secara realtime.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="p-4 border-t border-slate-100 bg-[#F8FAFC]">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </main>
    </div>
</div>
@endsection

