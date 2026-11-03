{{--
============================================================================
VIEW: LANDING PAGE & GALERI KATALOG PRODUK (catalog/index.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman beranda interaktif bergaya marketplace dengan Hero Banner
  gradasi hijau-putih, filter kategori horizontal, serta grid kartu produk
  (foto produk, nama produk, nama toko/siswa, dan harga).
Algoritma:
  1. Active Category State Matching: Membandingkan `request('category')` dengan
     setiap item kategori untuk menerapkan class gradasi hijau aktif sambil
     mempertahankan query pencarian `request('q')`.
  2. Iterative Card Rendering (`@forelse`): Melakukan perulangan koleksi `$products`
     (12 item per halaman) dan menampilkan empty-state otomatis jika hasil kosong.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'hilalkraft — Galeri & Katalog Karya Siswa SMK Plus Al Hilal')

@section('content')
<!-- Hero Banner Gradasi Hijau Emerald & Putih -->
<section class="relative overflow-hidden bg-gradient-to-br from-[#ECFDF5] via-white to-[#D1FAE5]/60 border-b border-emerald-500/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-5">
                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100/80 border border-emerald-500/20 px-3.5 py-1.5 text-xs font-bold text-[#059669]">
                    <span>✨ Marketplace Resmi SMK Plus Al Hilal</span>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    Dukung & Miliki Karya Terbaik Siswa <span class="bg-gradient-to-r from-[#10B981] to-[#059669] bg-clip-text text-transparent">hilalkraft</span>
                </h1>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl leading-relaxed">
                    Temukan produk kriya, kaligrafi, desain kreatif, busana, hingga kuliner halal hasil inovasi wirausaha muda <strong>SMK Plus Al Hilal</strong>. Transaksi mudah dan terhubung langsung dengan kreator siswa.
                </p>
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <a href="#katalog-produk" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 hover:opacity-95 transition">
                        Jelajahi Karya Siswa
                    </a>
                    @guest
                        <a href="{{ route('register') }}" class="rounded-xl border border-emerald-500/30 bg-white px-6 py-3 text-sm font-bold text-[#059669] hover:bg-emerald-50 transition">
                            Buka Toko Kreator
                        </a>
                    @endguest
                </div>
            </div>

            <!-- Kartu Highlight Logo & Statistik -->
            <div class="lg:col-span-5">
                <div class="rounded-3xl bg-white p-6 sm:p-8 shadow-xl shadow-emerald-900/5 border border-emerald-500/15 relative">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/logo-hilalkraft.jpg') }}" alt="hilalkraft Logo" class="w-20 h-20 rounded-2xl object-contain border border-emerald-100 p-1">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#10B981]">Karya Siswa Berkah Bersama</span>
                            <h2 class="text-xl font-extrabold text-slate-900 mt-0.5">Inkubator Bisnis SMK Plus Al Hilal</h2>
                            <p class="text-xs text-slate-500 mt-1">Seluruh toko siswa telah melalui proses kurasi & verifikasi Admin Sekolah.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 mt-6 pt-6 border-t border-slate-100 text-center">
                        <div class="rounded-2xl bg-[#F8FAFC] p-3">
                            <p class="text-lg font-extrabold text-[#059669]">100%</p>
                            <p class="text-[11px] text-slate-500 font-medium">Karya Asli Siswa</p>
                        </div>
                        <div class="rounded-2xl bg-[#F8FAFC] p-3">
                            <p class="text-lg font-extrabold text-[#059669]">Terverifikasi</p>
                            <p class="text-[11px] text-slate-500 font-medium">Guru & Admin</p>
                        </div>
                        <div class="rounded-2xl bg-[#F8FAFC] p-3">
                            <p class="text-lg font-extrabold text-amber-500">Langsung</p>
                            <p class="text-[11px] text-slate-500 font-medium">Integrasi WA</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filter Kategori & Katalog Produk -->
<section id="katalog-produk" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Baris Filter Kategori -->
    <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900">Galeri & Katalog Produk Siswa</h2>
            <p class="text-sm text-slate-500">Menampilkan karya terbaik dari toko siswa terverifikasi</p>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-2">
            <a href="{{ route('home') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ !request('category') ? 'bg-gradient-to-r from-[#10B981] to-[#059669] text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:border-emerald-400' }}">
                Semua Kategori
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('home', ['category' => $cat, 'q' => request('q')]) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition {{ request('category') === $cat ? 'bg-gradient-to-r from-[#10B981] to-[#059669] text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:border-emerald-400' }}">
                    {{ $cat }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Grid Card Produk -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($products as $product)
            <article class="group rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-sm hover:shadow-xl hover:border-emerald-500/40 transition-all duration-300 flex flex-col">
                <!-- Foto Produk -->
                <a href="{{ route('products.show', $product->slug) }}" class="relative aspect-[4/3] bg-slate-100 overflow-hidden block">
                    <img
                        src="{{ Str::startsWith($product->image, 'http') ? $product->image : asset('storage/' . $product->image) }}"
                        alt="{{ $product->product_name }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        onerror="this.src='https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=800&q=80'"
                    >
                    <span class="absolute top-3 left-3 rounded-lg bg-white/90 backdrop-blur px-2.5 py-1 text-[11px] font-bold text-[#059669] shadow-sm">
                        {{ $product->category }}
                    </span>
                </a>

                <!-- Konten Card -->
                <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                    <div>
                        <!-- Nama Toko / Siswa -->
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-1">
                            <span class="inline-block w-2 h-2 rounded-full bg-[#10B981]"></span>
                            <span class="font-semibold text-slate-700 truncate">{{ $product->store->store_name }}</span>
                            <span>•</span>
                            <span class="truncate">{{ $product->store->owner->name }}</span>
                        </div>

                        <!-- Nama Produk -->
                        <h3 class="font-bold text-slate-900 text-base leading-snug line-clamp-2 group-hover:text-[#059669] transition">
                            <a href="{{ route('products.show', $product->slug) }}">
                                {{ $product->product_name }}
                            </a>
                        </h3>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Harga Karya</span>
                            <span class="text-lg font-extrabold text-[#059669]">
                                {{ $product->formatted_price }}
                            </span>
                        </div>
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="rounded-xl bg-emerald-50 px-3.5 py-2 text-xs font-bold text-[#059669] group-hover:bg-gradient-to-r group-hover:from-[#10B981] group-hover:to-[#059669] group-hover:text-white transition">
                            Detail
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-3xl bg-white border border-slate-200 p-12 text-center space-y-3">
                <p class="text-lg font-bold text-slate-800">Belum ada produk yang ditemukan</p>
                <p class="text-sm text-slate-500">Coba gunakan kata kunci pencarian lain atau pilih kategori berbeda.</p>
                <a href="{{ route('home') }}" class="inline-block rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-2.5 text-xs font-bold text-white">
                    Reset Pencarian
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</section>
@endsection
