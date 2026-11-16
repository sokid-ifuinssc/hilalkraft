{{--
============================================================================
VIEW: FORM TAMBAH & EDIT PRODUK (seller/product-form.blade.php)
============================================================================
Fungsi:
  Menampilkan form Tambah / Edit Produk dengan dukungan multi-upload hingga 5 foto asli:
  - Foto Utama (Slot 1) + 4 Slot Galeri Tambahan (Slot 2-5).
  - Pratinjau langsung di sisi klien (client-side JS preview).
  - Pengelolaan foto lama (opsi hapus foto tertentu saat edit).
  - Informasi produk, metode pengiriman, dan panduan upload.
  - Semua ikon menggunakan Flat SVG Icon bersih tanpa emoji.
============================================================================
--}}
@extends('layouts.app')

@section('title', ($product ? 'Edit Produk' : 'Tambah Produk Baru') . ' — hilalkraf SMK PAL')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $categoryOptions = isset($categories) && count($categories) > 0
        ? $categories
        : ['Kerajinan', 'Teknologi', 'Tata Boga', 'Seni', 'Fesyen'];

    $existingImages = $product ? $product->all_image_urls : [];
    $rawExistingImages = $product ? $product->all_images : [];
    $mainImg = count($existingImages) > 0 ? $existingImages[0] : asset('images/logo-hilalkraf.jpg');
@endphp

<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Sidebar Kiri -->
    <aside class="w-64 bg-white border-r border-slate-200/80 hidden lg:flex flex-col justify-between shrink-0 sticky top-0 h-screen p-6">
        <div class="space-y-6">
            <a href="{{ route('home') }}" class="flex flex-col gap-2 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-2.5 py-1.5 shadow-xs">
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
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">RUANG KARYA SISWA</p>
                <p class="text-xs font-extrabold text-[#0F291E] truncate">{{ $store->student_class ?: $store->store_name }}</p>
                <span class="inline-block rounded-full {{ $store->is_verified ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-700' }} px-2.5 py-0.5 text-[10px] font-bold">
                    {{ $store->is_verified ? 'Toko terverifikasi' : 'Menunggu verifikasi' }}
                </span>
            </div>

            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ route('seller.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('seller.dashboard') }}#section-produk" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold">
                    <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span>Produk Saya</span>
                </a>
                <a href="{{ route('seller.dashboard') }}#section-pesanan" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Pesanan</span>
                </a>
                <a href="{{ route('seller.dashboard') }}#section-profil" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Profil Toko</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl bg-[#EBF9F2] p-4 space-y-1.5">
                <span class="w-7 h-7 rounded-full bg-white text-[#0B6E4F] flex items-center justify-center shadow-xs">
                    <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
                <p class="text-xs font-bold text-[#0F291E] pt-1">Butuh bantuan?</p>
                <p class="text-[11px] text-slate-500">Tim pengelola siap mendampingi karya Anda.</p>
                <a href="{{ route('seller.dashboard') }}#section-profil" class="inline-block text-[11px] font-bold text-[#0B6E4F] hover:underline pt-0.5">
                    Hubungi pengelola &rarr;
                </a>
            </div>

            <a href="{{ route('home') }}" class="block pt-2 border-t border-slate-100 text-xs text-slate-500 hover:text-[#0B6E4F] transition">
                &nearr; Kembali ke marketplace
            </a>
        </div>
    </aside>

    <!-- Konten Utama -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-10 flex items-center justify-between gap-4">
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <span>Dashboard pemilik toko</span>
                <span>/</span>
                <a href="{{ route('seller.dashboard') }}" class="hover:text-[#0B6E4F]">Produk Saya</a>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Pemilik Toko</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Area Form -->
        <div class="p-6 sm:p-10 max-w-6xl w-full mx-auto space-y-6">

            <!-- Breadcrumb & Judul -->
            <div class="space-y-1.5">
                <div class="text-xs text-slate-400 flex items-center gap-2">
                    <a href="{{ route('seller.dashboard') }}" class="hover:text-[#0B6E4F] flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Produk Saya</span>
                    </a>
                    <span>/</span>
                    <span>{{ $product ? 'Edit Produk' : 'Tambah Produk' }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">
                    {{ $product ? 'Edit Produk' : 'Tambah Produk Baru' }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500">
                    {{ $product ? 'Perbarui detail karya dan tambahkan foto terbaik agar pembeli yakin bertransaksi.' : 'Tampilkan karya terbaikmu dengan foto jelas, deskripsi lengkap, dan harga transparan.' }}
                </p>
            </div>

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                    <p class="font-bold">Mohon perbaiki kesalahan input berikut:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Grid 2 Kolom (Form di Kiri, Kartu Panduan / Pratinjau di Kanan) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- Kolom Kiri: Form Utama -->
                <form action="{{ $product ? route('seller.products.update', $product) : route('seller.products.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="lg:col-span-8 space-y-6">
                    @csrf
                    @if($product)
                        @method('PUT')
                    @endif

                    <!-- ============================================================ -->
                    <!-- KARTU 1: FOTO PRODUK MULTI-UPLOAD (HINGGA 5 FOTO)             -->
                    <!-- ============================================================ -->
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-extrabold text-[#0F291E] flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Galeri Foto Produk</span>
                                </h2>
                                <p class="text-xs text-slate-400 mt-0.5">Upload hingga 5 foto asli. Foto dummy produk lain tidak akan ditampilkan.</p>
                            </div>
                            <span id="photo-count-badge" class="rounded-md bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-[#0B6E4F]">
                                {{ count($existingImages) }} / 5 foto aktif
                            </span>
                        </div>

                        <!-- Info Banner Foto Bersih -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-start gap-2.5 text-xs text-slate-600">
                            <svg class="w-4 h-4 text-[#0B6E4F] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="leading-relaxed">
                                <strong>Privasi & Kerapihan Foto:</strong> Sistem hanya menampilkan foto-foto yang Anda upload di sini. Halaman produk pembeli tidak akan lagi menampilkan foto milik produk lain.
                            </p>
                        </div>

                        @if($product && count($existingImages) > 0)
                            <div class="space-y-2">
                                <p class="text-xs font-bold text-slate-700">Foto yang Sedang Digunakan (Centang untuk menghapus):</p>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    @foreach($existingImages as $idx => $imgUrl)
                                        @php
                                            $rawPath = $rawExistingImages[$idx] ?? '';
                                            $isMain = ($idx === 0);
                                        @endphp
                                        <div class="relative group rounded-xl border border-slate-200 overflow-hidden bg-slate-50 p-1.5 flex flex-col justify-between">
                                            <div class="aspect-square rounded-lg overflow-hidden bg-slate-100">
                                                <img src="{{ $imgUrl }}" alt="Foto {{ $idx + 1 }}" class="w-full h-full object-cover">
                                            </div>
                                            <div class="mt-1.5 text-center">
                                                @if($isMain)
                                                    <span class="inline-block text-[9px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded">Foto Utama</span>
                                                @else
                                                    <label class="flex items-center justify-center gap-1 cursor-pointer text-[10px] font-semibold text-rose-600 hover:text-rose-800">
                                                        <input type="checkbox" name="remove_images[]" value="{{ $rawPath }}" class="rounded text-rose-600 focus:ring-rose-500">
                                                        <span>Hapus</span>
                                                    </label>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Slot Upload 5 Foto Mandiri -->
                        <div class="space-y-3 pt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800">Upload Foto Baru (Slot 1 s/d Slot 5):</label>
                                <span class="text-[11px] text-slate-400">JPG, PNG, WebP (Maks 2MB)</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                <!-- Slot 1: Foto Utama -->
                                <div class="rounded-xl border-2 border-dashed border-emerald-400/80 bg-emerald-50/20 p-3 text-center flex flex-col items-center justify-center relative hover:bg-emerald-50/40 transition">
                                    <div id="slot-preview-0" class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden mb-2 hidden border border-emerald-500">
                                        <img src="" class="w-full h-full object-cover">
                                    </div>
                                    <svg class="w-6 h-6 text-emerald-600 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-[11px] font-bold text-emerald-800">Foto 1 (Utama)*</span>
                                    <span class="text-[9px] text-slate-400">{{ $product ? 'Ganti utama' : 'Wajib diisi' }}</span>
                                    <input type="file" name="photo_slots[0]" accept="image/jpeg,image/png,image/webp" {{ $product ? '' : 'required' }} onchange="previewSlotImage(this, 0)" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>

                                <!-- Slot 2 -->
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-center flex flex-col items-center justify-center relative hover:border-[#0B6E4F] transition">
                                    <div id="slot-preview-1" class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden mb-2 hidden border border-[#0B6E4F]">
                                        <img src="" class="w-full h-full object-cover">
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-[11px] font-semibold text-slate-700">Foto 2 (Detail)</span>
                                    <span class="text-[9px] text-slate-400">Opsional</span>
                                    <input type="file" name="photo_slots[1]" accept="image/jpeg,image/png,image/webp" onchange="previewSlotImage(this, 1)" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>

                                <!-- Slot 3 -->
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-center flex flex-col items-center justify-center relative hover:border-[#0B6E4F] transition">
                                    <div id="slot-preview-2" class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden mb-2 hidden border border-[#0B6E4F]">
                                        <img src="" class="w-full h-full object-cover">
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-[11px] font-semibold text-slate-700">Foto 3 (Sudut)</span>
                                    <span class="text-[9px] text-slate-400">Opsional</span>
                                    <input type="file" name="photo_slots[2]" accept="image/jpeg,image/png,image/webp" onchange="previewSlotImage(this, 2)" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>

                                <!-- Slot 4 -->
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-center flex flex-col items-center justify-center relative hover:border-[#0B6E4F] transition">
                                    <div id="slot-preview-3" class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden mb-2 hidden border border-[#0B6E4F]">
                                        <img src="" class="w-full h-full object-cover">
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-[11px] font-semibold text-slate-700">Foto 4 (Kemasan)</span>
                                    <span class="text-[9px] text-slate-400">Opsional</span>
                                    <input type="file" name="photo_slots[3]" accept="image/jpeg,image/png,image/webp" onchange="previewSlotImage(this, 3)" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>

                                <!-- Slot 5 -->
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-center flex flex-col items-center justify-center relative hover:border-[#0B6E4F] transition">
                                    <div id="slot-preview-4" class="w-16 h-16 rounded-lg bg-slate-100 overflow-hidden mb-2 hidden border border-[#0B6E4F]">
                                        <img src="" class="w-full h-full object-cover">
                                    </div>
                                    <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span class="text-[11px] font-semibold text-slate-700">Foto 5 (Variasi)</span>
                                    <span class="text-[9px] text-slate-400">Opsional</span>
                                    <input type="file" name="photo_slots[4]" accept="image/jpeg,image/png,image/webp" onchange="previewSlotImage(this, 4)" class="absolute inset-0 opacity-0 cursor-pointer">
                                </div>
                            </div>

                            <!-- Opsi Bulk Upload Sekaligus -->
                            <div class="mt-2 pt-2 border-t border-slate-100">
                                <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer transition">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <span class="text-xs font-bold text-slate-700">Atau pilih sekaligus banyak file foto (Multi-select)</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-[#0B6E4F] bg-white border border-slate-200 px-2 py-1 rounded-md">Pilih Banyak File</span>
                                    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" onchange="previewMultiFiles(this)" class="hidden">
                                </label>
                                <div id="multi-preview-container" class="grid grid-cols-5 gap-2 mt-2 hidden"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 2: INFORMASI PRODUK                                    -->
                    <!-- ============================================================ -->
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                        <h2 class="text-sm font-extrabold text-[#0F291E] flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Informasi Produk</span>
                        </h2>

                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Nama Produk *</label>
                            <input type="text" name="product_name" id="input-product-name" value="{{ old('product_name', $product?->product_name) }}" required
                                   placeholder="Contoh: Kopi Gula Aren (Kopdes) / Gantungan Kunci Rajut"
                                   oninput="updatePreviewCardTitle(this.value)"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                            <p class="text-[10px] text-slate-400 mt-1">Nama yang spesifik dan jelas membantu pembeli menemukan karya Anda.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Kategori *</label>
                            <select name="category" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                                <option value="">Pilih kategori produk</option>
                                @foreach($categoryOptions as $cat)
                                    <option value="{{ $cat }}" @selected(old('category', $product?->category) === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#0F291E] mb-1">Harga Satuan (Rp) *</label>
                                <input type="number" name="price" id="input-product-price" min="500" value="{{ old('price', $product ? (int) $product->price : '') }}" required
                                       placeholder="10000"
                                       oninput="updatePreviewCardPrice(this.value)"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#0F291E] mb-1">Jumlah Stok Siap Jual *</label>
                                <input type="number" name="stock" min="0" value="{{ old('stock', $product?->stock ?? 1) }}" required
                                       placeholder="10"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Deskripsi & Cerita Karya *</label>
                            <textarea name="description" rows="5" required oninput="document.getElementById('desc-char-count').textContent = this.value.length"
                                      placeholder="Ceritakan bahan yang digunakan, dimensi/ukuran, rasa, keunikan, serta petunjuk perawatan produk..."
                                      class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">{{ old('description', $product?->description) }}</textarea>
                            <p class="text-[10px] text-slate-400 mt-1"><span id="desc-char-count">{{ strlen(old('description', $product?->description ?? '')) }}</span> / 2.000 karakter</p>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 3: METODE TRANSAKSI & PENGIRIMAN YANG DIIZINKAN         -->
                    <!-- ============================================================ -->
                    @php
                        $allowed = old('allowed_delivery_methods', $product ? $product->effective_allowed_delivery_methods : ['pickup', 'delivery_lokal', 'shipping_transfer']);
                        if (!is_array($allowed)) $allowed = ['pickup', 'delivery_lokal', 'shipping_transfer'];
                    @endphp
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                        <div>
                            <h2 class="text-sm font-extrabold text-[#0F291E] flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>Metode Pengambilan & Pengiriman yang Diizinkan *</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">Tentukan bagaimana cara pembeli menerima produk ini.</p>
                        </div>

                        <div class="space-y-3">
                            <!-- Opsi 1: Datang ke Sekolah -->
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/20 cursor-pointer transition">
                                <input type="checkbox" name="allowed_delivery_methods[]" value="pickup" @checked(in_array('pickup', $allowed, true)) class="mt-1 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                                <div class="space-y-0.5 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-[#0F291E] flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <span>1. Ambil di Sekolah / Stan Bazar (Rp 0)</span>
                                        </span>
                                        <span class="text-[10px] font-extrabold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">3 Tahap</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Pembeli mengambil pesanan langsung di lingkungan sekolah / stan bazar. Sangat disarankan untuk makanan basah & produk cepat saji.
                                    </p>
                                </div>
                            </label>

                            <!-- Opsi 2: Delivery Lokal -->
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/20 cursor-pointer transition">
                                <input type="checkbox" name="allowed_delivery_methods[]" value="delivery_lokal" @checked(in_array('delivery_lokal', $allowed, true)) class="mt-1 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                                <div class="space-y-0.5 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-[#0F291E] flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span>2. Antar Langsung Kurir Sekolah (Rp 5.000)</span>
                                        </span>
                                        <span class="text-[10px] font-extrabold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">4 Tahap</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Diantar langsung oleh kurir siswa/sekolah untuk wilayah <strong class="text-slate-800">Kec. Arjawinangun, Susukan, & Kaliwedi</strong> (Kab. Cirebon).
                                    </p>
                                </div>
                            </label>

                            <!-- Opsi 3: Transfer Antar / Ekspedisi -->
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/20 cursor-pointer transition">
                                <input type="checkbox" name="allowed_delivery_methods[]" value="shipping_transfer" @checked(in_array('shipping_transfer', $allowed, true)) class="mt-1 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                                <div class="space-y-0.5 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-[#0F291E] flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            <span>3. Transfer Antar / Ekspedisi (Ongkir Langsung Kurir)</span>
                                        </span>
                                        <span class="text-[10px] font-extrabold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full">4 Tahap</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Pengiriman luar wilayah. Pembeli mentransfer dana ke rekening/e-wallet toko Anda, dan ongkir dibayar langsung ke kurir ekspedisi.
                                    </p>
                                    @if(!$store->has_payment_account)
                                        <p class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-1">
                                            <strong>Perhatian:</strong> Toko Anda belum mencantumkan info rekening/e-wallet di profil toko. Lengkapi di tab <em>Profil Toko</em> agar pembeli dapat mentransfer pembayaran.
                                        </p>
                                    @endif
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Tombol Aksi Simpan / Terbitkan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <a href="{{ route('seller.dashboard') }}" class="rounded-xl border border-slate-200 bg-white py-3 text-center text-xs sm:text-sm font-bold text-[#0F291E] hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-bold text-white hover:bg-[#08583F] transition shadow-xs flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ $product ? 'Simpan Perubahan' : 'Terbitkan Produk Sekarang' }}</span>
                        </button>
                    </div>
                </form>

                <!-- Kolom Kanan: Pratinjau Produk & Tips -->
                <div class="lg:col-span-4 space-y-5">

                    <!-- Kartu Pratinjau Produk -->
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-3 sticky top-20 shadow-xs">
                        <h3 class="text-xs font-bold text-[#0F291E] flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>Pratinjau Kartu Produk</span>
                        </h3>
                        <div class="aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-100">
                            <img id="preview-card-img" src="{{ $mainImg }}"
                                 alt="Pratinjau"
                                 class="w-full h-full object-cover">
                        </div>
                        <span class="inline-block rounded-md bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                            {{ $store->student_class ?: 'Karya Siswa' }}
                        </span>
                        <h4 id="preview-card-title" class="text-sm font-bold text-[#0F291E] line-clamp-2">
                            {{ $product?->product_name ?: 'Nama Produk Anda' }}
                        </h4>
                        <p id="preview-card-price" class="text-base font-extrabold text-[#0B6E4F]">
                            {{ $product ? $product->formatted_price : 'Rp 0' }}
                        </p>
                        @if($product)
                            <p class="text-[11px] text-slate-400">
                                HK-{{ str_pad((string) $product->id, 3, '0', STR_PAD_LEFT) }} &bull; Aktif &bull; {{ $product->views_count ?? 0 }} dilihat
                            </p>
                            <a href="{{ route('products.show', $product->slug) }}" class="inline-block text-xs font-bold text-[#0B6E4F] hover:underline">
                                Lihat di katalog pembeli &rarr;
                            </a>
                        @endif

                        <!-- Kartu Panduan Bersih -->
                        <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] p-4 space-y-2 mt-4 text-xs text-slate-700">
                            <p class="font-extrabold text-[#0B6E4F] flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#0B6E4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Kunci Karya Laris Manis:</span>
                            </p>
                            <ul class="space-y-1.5 text-[11px] text-slate-600">
                                <li class="flex items-start gap-1.5">
                                    <span class="text-[#0B6E4F] font-bold">&bull;</span>
                                    <span>Gunakan pencahayaan terang alami tanpa teks watermark.</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <span class="text-[#0B6E4F] font-bold">&bull;</span>
                                    <span>Unggah beberapa foto dari sudut depan, detail, dan kemasan.</span>
                                </li>
                                <li class="flex items-start gap-1.5">
                                    <span class="text-[#0B6E4F] font-bold">&bull;</span>
                                    <span>Pastikan stok dan harga selalu diperbarui.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function previewSlotImage(input, slotIdx) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const previewBox = document.getElementById('slot-preview-' + slotIdx);
            if (previewBox) {
                const img = previewBox.querySelector('img');
                if (img) img.src = e.target.result;
                previewBox.classList.remove('hidden');
            }
            if (slotIdx === 0) {
                const cardImg = document.getElementById('preview-card-img');
                if (cardImg) cardImg.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }

    function previewMultiFiles(input) {
        const container = document.getElementById('multi-preview-container');
        if (!container || !input.files) return;
        container.innerHTML = '';
        container.classList.remove('hidden');

        Array.from(input.files).slice(0, 5).forEach((file, i) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'aspect-square rounded-lg overflow-hidden bg-slate-100 border border-slate-200 relative';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                                 <span class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[8px] text-center py-0.5">File ${i + 1}</span>`;
                container.appendChild(div);
                if (i === 0) {
                    const cardImg = document.getElementById('preview-card-img');
                    if (cardImg) cardImg.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        });
    }

    function updatePreviewCardTitle(val) {
        const el = document.getElementById('preview-card-title');
        if (el) el.textContent = val.trim() || 'Nama Produk Anda';
    }

    function updatePreviewCardPrice(val) {
        const el = document.getElementById('preview-card-price');
        if (el) {
            const num = parseInt(val, 10);
            if (!isNaN(num) && num > 0) {
                el.textContent = 'Rp ' + num.toLocaleString('id-ID');
            } else {
                el.textContent = 'Rp 0';
            }
        }
    }
</script>
@endsection
