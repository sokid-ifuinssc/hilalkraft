{{--
============================================================================
VIEW: LOGIN MULTI-ROLE SPLIT SCREEN (auth/login.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman masuk dua kolom (Split-Screen) yang kalem dan bersih
  sesuai referensi desain Figma untuk Super Admin, Admin, Pemilik Toko, dan
  Pengunjung/Pembeli.
Algoritma:
  1. Mengirimkan payload POST ke `route('login.attempt')` dengan proteksi CSRF.
  2. Fungsi JS `toggleLoginPassword()` mengubah atribut tipe input password
     (`password` <-> `text`) ketika ikon mata diklik.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Masuk — hilalkraf (Inkubator Bisnis & Wirausaha Muda SMK PAL)')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
<div class="min-h-screen grid grid-cols-1 lg:grid-cols-12 bg-white">

    <!-- Panel Kiri: Editorial Mint Visual -->
    <div class="hidden lg:flex lg:col-span-5 bg-gradient-to-b from-[#D5F5E6] via-[#EBF9F2] to-white p-10 xl:p-14 flex-col justify-between border-r border-slate-100">
        <div class="space-y-8">
            <!-- Brand Header + Logo hilalkraf & Logo SMK Plus Al Hilal -->
            <div class="pb-5 border-b-2 border-[#0B6E4F]">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-3 py-1.5 shadow-sm">
                        <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                             alt="hilalkraf"
                             class="h-12 w-auto object-contain">
                        <span class="h-7 w-[1px] bg-slate-200"></span>
                        <img src="{{ asset('images/logo-smk-alhilal.png') }}"
                             alt="SMK Plus Al Hilal"
                             class="h-10 w-10 object-contain">
                    </div>
                    <div>
                        <span class="text-xl font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                        <span class="text-[10px] font-bold text-[#0B6E4F]">Inkubator Bisnis & Wirausaha Muda SMK PAL</span>
                    </div>
                </a>
            </div>

            <!-- Copywriting Utama -->
            <div class="space-y-3 pt-2">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#0B6E4F]">
                    RUANG UNTUK BERTUMBUH
                </p>
                <h1 class="text-3xl xl:text-4xl font-extrabold text-[#0F291E] leading-[1.15] tracking-tight">
                    Karya sederhana.<br>
                    Kemungkinan<br>
                    tak terbatas.
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-sm">
                    Bersama <strong>hilalkraf</strong> — unit resmi milik <strong>SMK Plus Al Hilal</strong> di bawah <strong>Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</strong>.
                </p>
            </div>

            <!-- Kartu Foto & Kutipan Siswa -->
            <div class="rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden max-w-md">
                <div class="aspect-[16/10] bg-slate-100 overflow-hidden">
                    <img
                        src="{{ asset('images/products/gantungan-kunci-bunga.jpg') }}"
                        alt="Karya Siswa SMK Plus Al Hilal"
                        class="w-full h-full object-cover"
                    >
                </div>
                <div class="p-5 space-y-2">
                    <p class="text-xs sm:text-sm font-medium text-[#0F291E] leading-relaxed">
                        "Gantungan Kunci Bunga — Manis, simple, bikin semangat! Teman kecil yang selalu bikin hari lebih berwarna."
                    </p>
                    <p class="text-[11px] font-bold text-[#0B6E4F]">
                        Khoiriyah Muntazah & Yulianti • Kelas XI TKJT
                    </p>
                </div>
            </div>
        </div>

        <div class="pt-6 space-y-0.5">
            <p class="text-[11px] font-bold text-[#0B6E4F]">Milik Resmi SMK Plus Al Hilal Arjawinangun</p>
            <p class="text-[10px] text-slate-500">Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</p>
        </div>
    </div>

    <!-- Panel Kanan: Formulir Login Bersih -->
    <div class="lg:col-span-7 flex flex-col justify-between p-6 sm:p-12 xl:p-16">
        <div class="max-w-md w-full mx-auto space-y-8 my-auto">

            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-[#0B6E4F] transition">
                <span>←</span>
                <span>Kembali ke marketplace</span>
            </a>

            <div class="space-y-1.5">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">
                    Selamat datang kembali.
                </h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Masuk untuk menemukan karya favorit dan mendukung kreator muda.
                </p>
            </div>

            <form action="{{ route('login.attempt') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1.5">Email</label>
                    <div class="relative flex items-center">
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="nama@email.com"
                            class="w-full rounded-xl border border-slate-200 bg-white pl-4 pr-10 py-2.5 text-sm text-[#0F291E] placeholder-slate-400 focus:border-[#0B6E4F] focus:outline-none focus:ring-2 focus:ring-emerald-500/10"
                        >
                        <svg class="w-4 h-4 text-slate-400 absolute right-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1.5">Password</label>
                    <div class="relative flex items-center">
                        <input
                            type="password"
                            name="password"
                            id="login-password"
                            required
                            placeholder="Masukkan password"
                            class="w-full rounded-xl border border-slate-200 bg-white pl-4 pr-10 py-2.5 text-sm text-[#0F291E] placeholder-slate-400 focus:border-[#0B6E4F] focus:outline-none focus:ring-2 focus:ring-emerald-500/10"
                        >
                        <button type="button" onclick="toggleLoginPassword()" class="absolute right-3.5 text-slate-400 hover:text-slate-600" title="Tampilkan/Sembunyikan Password">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="inline-flex items-center gap-2 text-slate-500 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F]">
                        <span>Ingat saya</span>
                    </label>
                    <span class="font-semibold text-[#0B6E4F]">Portal Resmi Al Hilal</span>
                </div>

                <button type="submit" class="w-full rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm flex items-center justify-center gap-2">
                    <span>→</span>
                    <span>Masuk</span>
                </button>
            </form>

            <div class="space-y-4 text-center pt-2">
                <p class="text-xs text-slate-500">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="font-bold text-[#0B6E4F] hover:underline">Daftar sekarang</a>
                </p>

                <p class="text-[11px] text-slate-400 flex items-center justify-center gap-1.5">
                    <span>🔒</span>
                    <span>Data privasimu aman bersama hilalkraf SMK PAL.</span>
                </p>
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px] text-slate-400">
                <span>&copy; {{ date('Y') }} hilalkraf • SMK Plus Al Hilal</span>
                <span class="font-semibold text-[#0B6E4F]">Inkubator Bisnis SMK PAL</span>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleLoginPassword() {
        const input = document.getElementById('login-password');
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>
@endsection
