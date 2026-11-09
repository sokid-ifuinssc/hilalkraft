{{--
============================================================================
VIEW: FORM TAMBAH & EDIT PRODUK (seller/product-form.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman Tambah Produk Baru & Edit Produk sesuai desain Figma
  `Tambah produk · Desktop.png` dan `Edit produk · Desktop.png` (Sidebar
  Ruang Karya Siswa, Kartu Upload Foto Produk dengan pratinjau langsung,
  Kartu Informasi Produk, Kartu Publikasi, Kartu Pratinjau Produk, serta
  Kartu Panduan "Buat karyamu bersinar").
Algoritma:
  1. Polymorphic Form State Binding: Menyesuaikan rute action (`store` vs
     `update`) dan method HTTP (`POST` vs `PUT`) berdasarkan variabel `$product`.
  2. Client-Side Image Preview & Character Counter: Menampilkan pratinjau
     gambar seketika saat file dipilih dan menghitung jumlah karakter deskripsi.
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
@endphp

<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Sidebar Kiri -->
    <aside class="w-64 bg-white border-r border-slate-200/80 hidden lg:flex flex-col justify-between shrink-0 sticky top-0 h-screen p-6">
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
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">RUANG KARYA SISWA</p>
                <p class="text-xs font-extrabold text-[#0F291E] truncate">{{ $store->student_class ?: $store->store_name }}</p>
                <span class="inline-block rounded-full {{ $store->is_verified ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-700' }} px-2.5 py-0.5 text-[10px] font-bold">
                    {{ $store->is_verified ? 'Toko terverifikasi' : 'Menunggu verifikasi' }}
                </span>
            </div>

            <nav class="space-y-1 text-xs font-semibold">
                <a href="{{ route('seller.dashboard') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>⊞</span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('seller.dashboard') }}#section-produk" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold">
                    <span>📦</span>
                    <span>Produk Saya</span>
                </a>
                <a href="{{ route('seller.dashboard') }}#section-pesanan" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🛍️</span>
                    <span>Pesanan</span>
                </a>
                <a href="{{ route('seller.dashboard') }}#section-profil" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🏪</span>
                    <span>Profil Toko</span>
                </a>
            </nav>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl bg-[#EBF9F2] p-4 space-y-1.5">
                <span class="w-6 h-6 rounded-full bg-white text-[#0B6E4F] flex items-center justify-center text-xs font-bold">🛟</span>
                <p class="text-xs font-bold text-[#0F291E] pt-1">Butuh bantuan?</p>
                <p class="text-[11px] text-slate-500">Tim hilalkraft siap membantu.</p>
                <a href="{{ route('seller.dashboard') }}#section-profil" class="inline-block text-[11px] font-bold text-[#0B6E4F] hover:underline pt-0.5">
                    Hubungi pengelola →
                </a>
            </div>

            <a href="{{ route('home') }}" class="block pt-2 border-t border-slate-100 text-xs text-slate-500 hover:text-[#0B6E4F] transition">
                ↗ Kembali ke marketplace
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
                    <a href="{{ route('seller.dashboard') }}" class="hover:text-[#0B6E4F]">← Produk Saya</a>
                    <span>/</span>
                    <span>{{ $product ? 'Edit Produk' : 'Tambah Produk' }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">
                    {{ $product ? 'Edit Produk' : 'Tambah Produk Baru' }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500">
                    {{ $product ? 'Perbarui detail karya agar pembeli selalu mendapat informasi terbaru.' : 'Setiap karya punya cerita. Yuk, tampilkan karya terbaikmu.' }}
                </p>
            </div>

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

                    <!-- Kartu 1: Foto Produk -->
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-extrabold text-[#0F291E]">Foto Produk</h2>
                                <p class="text-xs text-slate-400 mt-0.5">Gunakan foto yang jelas, terang, dan menampilkan detail karya.</p>
                            </div>
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-500">
                                {{ $product ? '1 / 5 foto' : '0 / 5 foto' }}
                            </span>
                        </div>

                        @if($product)
                            @php
                                $currentProductImg = Str::startsWith($product->image, 'http')
                                    ? $product->image
                                    : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image));
                            @endphp
                            <div class="grid grid-cols-4 gap-3">
                                <div class="space-y-1">
                                    <div class="aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                                        <img id="preview-img-box" src="{{ $currentProductImg }}"
                                             alt="{{ $product->product_name }}"
                                             class="w-full h-full object-cover"
                                             onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                                    </div>
                                    <span class="block text-[10px] font-semibold text-[#0B6E4F]">✓ Foto utama</span>
                                </div>
                            </div>
                        @else
                            <div id="new-preview-wrapper" class="hidden">
                                <img id="preview-img-box" src="" alt="Preview" class="w-28 h-28 rounded-xl object-cover border border-[#0B6E4F]">
                            </div>
                        @endif

                        <label class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-[#F8FAFC] py-8 px-4 text-center cursor-pointer hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/40 transition">
                            <span class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-[#0B6E4F] flex items-center justify-center text-lg shadow-sm">🖼️</span>
                            <span class="text-xs font-bold text-[#0B6E4F] mt-2.5">
                                {{ $product ? '+ Ganti / Tambah foto produk' : 'Klik untuk upload foto produk' }}
                            </span>
                            <span class="text-[11px] text-slate-400 mt-0.5">atau tarik dan letakkan di sini • JPG / PNG • Maks. 3 MB</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">Rekomendasi ukuran 1:1, minimal 800 × 800 px.</span>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" {{ $product ? '' : 'required' }} onchange="previewUploadedImage(this)" class="hidden">
                        </label>
                    </div>

                    <!-- Kartu 2: Informasi Produk -->
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                        <h2 class="text-sm font-extrabold text-[#0F291E]">Informasi Produk</h2>

                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Nama Produk *</label>
                            <input type="text" name="product_name" value="{{ old('product_name', $product?->product_name) }}" required
                                   placeholder="Contoh: Gantungan Kunci Karakter"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                            <p class="text-[10px] text-slate-400 mt-1">Nama yang spesifik membantu pembeli menemukan karyamu.</p>
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
                                <label class="block text-xs font-bold text-[#0F291E] mb-1">Harga (Rp) *</label>
                                <input type="number" name="price" min="1000" value="{{ old('price', $product ? (int) $product->price : '') }}" required
                                       placeholder="0"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#0F291E] mb-1">Stok *</label>
                                <input type="number" name="stock" min="0" value="{{ old('stock', $product?->stock ?? 0) }}" required
                                       placeholder="0"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Deskripsi Produk *</label>
                            <textarea name="description" rows="4" required oninput="document.getElementById('desc-char-count').textContent = this.value.length"
                                      placeholder="Ceritakan bahan, ukuran, keunikan, dan cara merawat produkmu..."
                                      class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">{{ old('description', $product?->description) }}</textarea>
                            <p class="text-[10px] text-slate-400 mt-1"><span id="desc-char-count">{{ strlen(old('description', $product?->description ?? '')) }}</span> / 2.000 karakter</p>
                        </div>
                    </div>

                    <!-- Kartu 3: Pilihan Metode Transaksi yang Diizinkan -->
                    @php
                        $allowed = old('allowed_delivery_methods', $product ? $product->effective_allowed_delivery_methods : ['pickup', 'delivery_lokal', 'shipping_transfer']);
                        if (!is_array($allowed)) $allowed = ['pickup', 'delivery_lokal', 'shipping_transfer'];
                    @endphp
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 space-y-4">
                        <div>
                            <h2 class="text-sm font-extrabold text-[#0F291E]">Metode Transaksi yang Diizinkan *</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Pilih opsi transaksi dan pengiriman apa saja yang dapat dipilih oleh pembeli untuk karya ini.</p>
                        </div>

                        <div class="space-y-3">
                            <!-- Opsi 1: Datang ke Sekolah -->
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/20 cursor-pointer transition">
                                <input type="checkbox" name="allowed_delivery_methods[]" value="pickup" @checked(in_array('pickup', $allowed, true)) class="mt-1 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#0F291E]">🏫 1. Datang ke Sekolah / Transaksi Langsung (Rp 0)</span>
                                        <span class="text-[10px] font-extrabold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">3 Tahap</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Pembeli bertransaksi dan mengambil barang langsung di lingkungan SMK Plus Al Hilal / Stand Bazar SMK. Wajib untuk makanan basah / barang rapuh / pesanan langsung.
                                    </p>
                                </div>
                            </label>

                            <!-- Opsi 2: Delivery / Antar Langsung -->
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/20 cursor-pointer transition">
                                <input type="checkbox" name="allowed_delivery_methods[]" value="delivery_lokal" @checked(in_array('delivery_lokal', $allowed, true)) class="mt-1 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#0F291E]">🛵 2. Delivery / Antar Langsung (Rp 5.000)</span>
                                        <span class="text-[10px] font-extrabold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">4 Tahap</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Diantar kurir sekolah khusus wilayah <strong class="text-slate-800">Kec. Arjawinangun, Susukan, & Kaliwedi</strong> (Kab. Cirebon).
                                    </p>
                                </div>
                            </label>

                            <!-- Opsi 3: Transfer Antar -->
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-[#0B6E4F] hover:bg-[#EBF9F2]/20 cursor-pointer transition">
                                <input type="checkbox" name="allowed_delivery_methods[]" value="shipping_transfer" @checked(in_array('shipping_transfer', $allowed, true)) class="mt-1 rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#0F291E]">📦 3. Transfer Antar / Ekspedisi (+ Ongkir Wilayah)</span>
                                        <span class="text-[10px] font-extrabold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full">4 Tahap</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        Pengiriman luar wilayah melalui ekspedisi kurir. Pembeli mentransfer ke nomor rekening atau e-wallet toko Anda.
                                    </p>
                                    @if(!$store->has_payment_account)
                                        <p class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-1">
                                            💡 <strong>Tips:</strong> Toko Anda belum melengkapi nomor rekening / e-wallet di profil toko. Lengkapi di menu <em>Profil Toko</em> agar pembeli tahu nomor rekening tujuan transfer Anda.
                                        </p>
                                    @endif
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Kartu 4: Publikasi -->
                    <div class="rounded-2xl bg-white border border-slate-200/80 p-6 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-extrabold text-[#0F291E]">Publikasi</h2>
                            <p class="text-xs font-bold text-[#0F291E] mt-2">Tampilkan di marketplace</p>
                            <p class="text-[11px] text-slate-400">Produk dapat ditemukan dan dibeli semua pengunjung.</p>
                        </div>
                        <span class="w-11 h-6 rounded-full bg-[#0B6E4F] p-0.5 flex items-center justify-end">
                            <span class="w-5 h-5 rounded-full bg-white shadow"></span>
                        </span>
                    </div>

                    <!-- Baris Tombol Bawah -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="{{ route('seller.dashboard') }}" class="rounded-xl border border-slate-200 bg-white py-3 text-center text-xs sm:text-sm font-bold text-[#0F291E] hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                            ✓ {{ $product ? 'Simpan Perubahan' : 'Terbitkan Produk' }}
                        </button>
                    </div>
                </form>

                <!-- Kolom Kanan: Pratinjau Produk & Kartu Panduan "Buat karyamu bersinar" -->
                <div class="lg:col-span-4 space-y-5">

                    @if($product)
                        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-3">
                            <h3 class="text-xs font-bold text-[#0F291E]">Pratinjau produk</h3>
                            <div class="aspect-[4/3] rounded-xl overflow-hidden bg-slate-100">
                                <img id="preview-card-img" src="{{ $currentProductImg }}"
                                     alt="{{ $product->product_name }}"
                                     class="w-full h-full object-cover"
                                     onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                            </div>
                            <span class="inline-block rounded-md bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                                {{ $store->student_class ?: $product->category }}
                            </span>
                            <h4 class="text-sm font-bold text-[#0F291E]">{{ $product->product_name }}</h4>
                            <p class="text-base font-extrabold text-[#0B6E4F]">{{ $product->formatted_price }}</p>
                            <p class="text-[11px] text-slate-400">
                                HK-{{ str_pad((string) $product->id, 3, '0', STR_PAD_LEFT) }} • Aktif • 👁️ {{ $product->views_count ?? 0 }} dilihat
                            </p>
                            <a href="{{ route('products.show', $product->slug) }}" class="inline-block text-xs font-bold text-[#0B6E4F] hover:underline">
                                Lihat produk di marketplace ↗
                            </a>
                        </div>
                    @endif

                    <!-- Kartu Panduan Soft Mint -->
                    <div class="rounded-2xl bg-[#EBF9F2] border border-[#CDEFE0] p-6 space-y-3">
                        <span class="text-lg text-[#0B6E4F]">✨</span>
                        <h3 class="text-sm font-extrabold text-[#0F291E]">Buat karyamu bersinar</h3>
                        <ul class="space-y-2 text-xs text-slate-600">
                            <li class="flex items-start gap-2">
                                <span class="text-[#0B6E4F] font-bold">✓</span>
                                <span>Foto utama tanpa teks atau watermark</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#0B6E4F] font-bold">✓</span>
                                <span>Nama produk jelas dan mudah dicari</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#0B6E4F] font-bold">✓</span>
                                <span>Deskripsi bahan dan ukuran lengkap</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-[#0B6E4F] font-bold">✓</span>
                                <span>Harga serta stok selalu terbaru</span>
                            </li>
                        </ul>
                        <p class="text-[11px] text-slate-400 pt-2 border-t border-[#CDEFE0]">
                            Produk yang lengkap lebih mudah dipercaya calon pembeli.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function previewUploadedImage(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.getElementById('preview-img-box');
            const cardImg = document.getElementById('preview-card-img');
            const wrap = document.getElementById('new-preview-wrapper');
            if (img) img.src = e.target.result;
            if (cardImg) cardImg.src = e.target.result;
            if (wrap) wrap.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
</script>
@endsection
