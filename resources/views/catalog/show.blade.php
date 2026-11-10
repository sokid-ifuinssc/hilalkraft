{{--
============================================================================
VIEW: DETAIL PRODUK, IKON LIHAT (👁️), SUKA (❤️) & KOMENTAR (💬) (catalog/show.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman detail produk sesuai referensi desain Figma
  `Detail produk · Desktop.png` (Galeri + 4 Thumbnail, Detail Harga/Stok/Toko,
  Ikon Lihat 👁️, Suka ❤️, Komentar 💬, Tab Deskripsi & Ulasan, Kartu "Kenali
  pembuatnya" dengan tombol WhatsApp, serta bagian "Karya lain untukmu").
Algoritma:
  1. Setiap halaman dibuka, `CatalogController@show` otomatis meng-increment
     `views_count` dan mencatat `VisitorLog`.
  2. Fungsi JS `switchProductTab(tab)` mengganti tampilan antara tab
     "Deskripsi Produk" dan "Ulasan / Komentar".
============================================================================
--}}
@extends('layouts.app')

@section('title', $product->product_name . ' — hilalkraf')
@section('og_type', 'product')
@section('og_title', $product->product_name . ' — ' . $product->formatted_price . ' | hilalkraf')
@section('og_description', 'Beli ' . $product->product_name . ' (' . $product->formatted_price . ') dari toko ' . ($product->store->store_name ?? 'SMK Plus Al Hilal') . '. ' . \Illuminate\Support\Str::limit(strip_tags($product->description ?? 'Karya inovatif siswa SMK Plus Al Hilal'), 120))
@section('og_image', Str::startsWith($product->image, 'http') ? $product->image : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image)))
@section('og_image_banner', Str::startsWith($product->image, 'http') ? $product->image : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image)))

@section('content')
@php
    $mainImg = Str::startsWith($product->image, 'http')
        ? $product->image
        : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image));
    $fallbackImg = asset('images/products/gantungan-kunci-bunga.jpg');
    $thumbImages = [
        $mainImg,
        asset('images/products/gantungan-kunci-bunga.jpg'),
        asset('images/products/tokoganci-iyung-iyung.jpg'),
        $mainImg,
    ];
    $waMessage = urlencode("Assalamu'alaikum {$product->store->owner->name} ({$product->store->store_name}), saya tertarik membeli produk *{$product->product_name}* seharga {$product->formatted_price} di hilalkraf SMK Plus Al Hilal. Saya ingin konsultasi transaksi (Datang ke Sekolah / Delivery Lokal Arjawinangun, Susukan, Kaliwedi / Transfer Antar).");
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">

    <!-- Breadcrumb -->
    <nav class="text-xs text-slate-400 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-[#0B6E4F]">Home</a>
        <span>›</span>
        <a href="{{ route('home', ['category' => $product->category]) }}" class="hover:text-[#0B6E4F]">{{ $product->category }}</a>
        <span>›</span>
        <span class="text-slate-600 font-medium truncate">{{ $product->product_name }}</span>
    </nav>

    <!-- Bagian Atas: 2 Kolom (Foto & Informasi Utama) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

        <!-- Kolom Kiri: Foto Utama & 4 Thumbnail -->
        <div class="lg:col-span-6 space-y-3">
            <div class="relative aspect-[4/3] rounded-2xl overflow-hidden bg-slate-100 border border-slate-100">
                <img
                    id="main-product-image"
                    src="{{ $mainImg }}"
                    alt="{{ $product->product_name }}"
                    class="w-full h-full object-cover"
                    onerror="this.src='{{ $fallbackImg }}'"
                >
            </div>

            <div class="grid grid-cols-4 gap-3">
                @foreach($thumbImages as $i => $tImg)
                    <button type="button"
                            onclick="document.getElementById('main-product-image').src = this.querySelector('img').src"
                            class="aspect-[4/3] rounded-xl overflow-hidden border-2 {{ $i === 0 ? 'border-[#0B6E4F]' : 'border-transparent hover:border-slate-300' }} bg-slate-100 transition">
                        <img src="{{ $tImg }}" alt="Preview {{ $i + 1 }}" class="w-full h-full object-cover" onerror="this.src='{{ $fallbackImg }}'">
                    </button>
                @endforeach
            </div>

            <p class="text-[11px] text-slate-400 flex items-center gap-1.5 pt-1">
                <span>✥</span>
                <span>Detail kecil yang membuat setiap karya istimewa.</span>
            </p>
        </div>

        <!-- Kolom Kanan: Detail Produk -->
        <div class="lg:col-span-6 space-y-5">

            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex items-center rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-bold text-[#0B6E4F]">
                    {{ $product->category }} • Buatan tangan
                </span>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" title="Jumlah dilihat">
                        👁️ {{ number_format($product->views_count ?? 0) }}
                    </span>
                    <button type="button" onclick="switchProductTab('ulasan'); document.getElementById('section-tabs').scrollIntoView({behavior:'smooth'});" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition" title="Lihat komentar">
                        💬 {{ $product->comments->count() }}
                    </button>
                    <form action="{{ route('products.like', $product->slug) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-rose-500 hover:bg-rose-50 transition" title="Sukai karya ini">
                            ❤️ {{ number_format($product->likes_count ?? 0) }}
                        </button>
                    </form>
                    <!-- Tombol Menu Bagikan Produk -->
                    <button type="button"
                            onclick="openShareModal()"
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-[#EBF9F2] px-3 py-1 text-xs font-bold text-[#0B6E4F] hover:bg-[#D5F5E6] transition shadow-2xs"
                            title="Bagikan link produk">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span>Bagikan</span>
                    </button>
                </div>
            </div>

            <div class="space-y-1.5">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">
                    {{ $product->product_name }}
                </h1>
                <p class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                    <span class="text-amber-600 font-bold">★ {{ number_format($product->average_rating, 1, ',', '.') }} ({{ $product->comments->count() }} ulasan)</span>
                    <span>•</span>
                    <span>👁️ {{ number_format($product->views_count ?? 0) }} dilihat</span>
                    <span>•</span>
                    <span>❤️ {{ number_format($product->likes_count ?? 0) }} disukai</span>
                    <span>•</span>
                    <span>Dibuat oleh {{ $product->store->seller_type === 'guru' ? 'guru' : 'siswa' }}</span>
                </p>
            </div>

            <div class="space-y-2">
                <div class="text-2xl sm:text-3xl font-extrabold text-[#0B6E4F]">
                    {{ $product->formatted_price }}
                </div>
                <p class="text-xs font-medium text-[#0B6E4F]">
                    {{ $product->stock > 0 ? 'Stok tersedia: ' . $product->stock . ' produk' : 'Stok sedang habis' }}
                </p>

                <!-- Info Skema Diskon Jenis Pembeli & Bazar SMK -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @if(($product->store->discount_pelajar ?? 0) > 0)
                        <span class="rounded-full bg-[#EBF9F2] border border-[#CDEFE0] px-2.5 py-0.5 text-[11px] font-bold text-[#0B6E4F]">
                            🎓 Diskon Pelajar {{ $product->store->discount_pelajar }}%
                        </span>
                    @endif
                    @if(($product->store->discount_keluarga ?? 0) > 0)
                        <span class="rounded-full bg-[#EBF9F2] border border-[#CDEFE0] px-2.5 py-0.5 text-[11px] font-bold text-[#0B6E4F]">
                            🏫 Diskon Keluarga SMK {{ $product->store->discount_keluarga }}%
                        </span>
                    @endif
                    @if(($product->store->discount_alumni ?? 0) > 0)
                        <span class="rounded-full bg-[#EBF9F2] border border-[#CDEFE0] px-2.5 py-0.5 text-[11px] font-bold text-[#0B6E4F]">
                            🤝 Diskon Alumni {{ $product->store->discount_alumni }}%
                        </span>
                    @endif
                    @if(isset($activeBazaarEvents) && $activeBazaarEvents->first())
                        <span class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-[11px] font-extrabold text-amber-800">
                            🎪 Diskon Bazar SMK {{ $activeBazaarEvents->first()->discount_percent + ($product->store->discount_bazar_extra ?? 0) }}% (Kode: {{ $activeBazaarEvents->first()->voucher_code }})
                        </span>
                    @endif
                </div>

                <!-- Opsi Metode Transaksi yang Didukung Produk -->
                <div class="pt-1">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/20 bg-[#EBF9F2] px-3 py-1.5 text-xs font-semibold text-[#0B6E4F]">
                        <span>🚚 Opsi Transaksi:</span>
                        <strong class="font-extrabold text-[#0F291E]">{{ $product->allowed_delivery_label }}</strong>
                    </span>
                </div>
            </div>

            <!-- Kotak Toko Soft Mint -->
            <a href="{{ route('stores.show', $product->store->slug) }}" class="flex items-center gap-2.5 rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] px-4 py-3 text-xs font-semibold text-[#0B6E4F] hover:bg-[#D5F5E6] transition">
                <span class="w-4 h-4 rounded-full bg-[#0B6E4F] text-white text-[10px] flex items-center justify-center shrink-0">✓</span>
                <span class="truncate">
                    Toko: {{ $product->store->store_name }}
                    @if($product->store->student_class)
                        – {{ $product->store->student_class }}
                    @else
                        – {{ $product->store->owner->name }}
                    @endif
                </span>
            </a>

            <!-- Ringkasan Deskripsi -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <h2 class="text-xs sm:text-sm font-bold text-[#0F291E]">Hangatkan sudut favoritmu.</h2>
                <div class="text-xs sm:text-sm text-slate-500 leading-relaxed line-clamp-3">
                    {{ $product->description }}
                </div>
            </div>

            <!-- Jumlah & Tombol Aksi -->
            <div class="pt-2 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-[#0F291E]">Jumlah</span>
                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white">
                        <button type="button" onclick="changeQty(-1)" class="px-3 py-1.5 text-sm font-bold text-slate-500 hover:text-[#0B6E4F]">−</button>
                        <input type="number" id="qty-display" value="1" min="1" max="{{ max(1, $product->stock) }}" readonly class="w-10 text-center text-xs font-bold text-[#0F291E] focus:outline-none">
                        <button type="button" onclick="changeQty(1)" class="px-3 py-1.5 text-sm font-bold text-[#0B6E4F] hover:opacity-80">+</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @auth
                        <form action="{{ route('buyer.cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" id="cart-qty-input" value="1">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs sm:text-sm font-semibold text-[#0F291E] hover:border-[#0B6E4F] hover:text-[#0B6E4F] transition">
                                <span>🛍️</span>
                                <span>Tambah ke Keranjang</span>
                            </button>
                        </form>

                        <button type="button" onclick="openDirectOrderModal()" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm">
                            <span>🚚</span>
                            <span>Pesan & Pantau Progres</span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs sm:text-sm font-semibold text-[#0F291E] hover:border-[#0B6E4F] hover:text-[#0B6E4F] transition">
                            <span>🛍️</span>
                            <span>Tambah ke Keranjang</span>
                        </a>

                        <a href="{{ $product->store->whatsapp_url }}?text={{ $waMessage }}"
                           target="_blank"
                           rel="noopener"
                           class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm">
                            <span>Beli Sekarang (WA)</span>
                        </a>
                    @endauth
                </div>

                @auth
                    <!-- Pilihan Klaim Diskon Bazar SMK & Tombol WA Langsung -->
                    <div class="rounded-xl bg-amber-50/70 border border-amber-200 p-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-amber-950 font-semibold">
                            <input type="checkbox" onchange="document.getElementById('direct-bazaar-input').value = this.checked ? '1' : '0'" class="rounded border-amber-400 text-[#0B6E4F] focus:ring-[#0B6E4F]">
                            <span>🎪 Beli di Acara Bazar SMK (Klaim Diskon Bazar + Diskon {{ auth()->user()->subtype_label }})</span>
                        </label>
                        <a href="{{ $product->store->whatsapp_url }}?text={{ $waMessage }}"
                           target="_blank"
                           rel="noopener"
                           class="font-extrabold text-[#0B6E4F] hover:underline">
                            💬 Chat WA Langsung ↗
                        </a>
                    </div>
                @endauth

                <!-- Tombol Menu Bagikan Produk Langsung -->
                <button type="button"
                        onclick="openShareModal()"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-500/30 bg-[#EBF9F2]/60 hover:bg-[#EBF9F2] px-4 py-2.5 text-xs font-bold text-[#0B6E4F] transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    <span>Bagikan Link Produk ke Teman / Media Sosial</span>
                </button>

                <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                    <span class="text-[#0B6E4F] font-bold">✓</span>
                    <span>Karya asli siswa • Kualitas diperiksa sebelum dikirim</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Bagian Tengah: 2 Kolom (Tab Deskripsi/Ulasan di Kiri & Kartu "Kenali pembuatnya" di Kanan) -->
    <div id="section-tabs" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start pt-4">

        <!-- Kiri: Kartu Tab Deskripsi Produk & Ulasan/Komentar -->
        <div class="lg:col-span-7 rounded-2xl bg-[#F8FAFC] border border-slate-100 p-6 sm:p-8 space-y-6">
            <div class="flex items-center gap-6 border-b border-slate-200/80 text-xs sm:text-sm font-bold">
                <button type="button" id="tab-btn-deskripsi" onclick="switchProductTab('deskripsi')" class="pb-3 border-b-2 border-[#0B6E4F] text-[#0B6E4F] transition">
                    Deskripsi Produk
                </button>
                <button type="button" id="tab-btn-ulasan" onclick="switchProductTab('ulasan')" class="pb-3 border-b-2 border-transparent text-slate-400 hover:text-slate-700 transition">
                    Ulasan & Komentar ({{ $product->comments->count() }})
                </button>
            </div>

            <!-- Konten Tab 1: Deskripsi & Spesifikasi -->
            <div id="tab-panel-deskripsi" class="space-y-5">
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                    {{ $product->description }}
                </p>

                <div class="space-y-2.5 pt-2 border-t border-slate-200/60 text-xs">
                    <div class="grid grid-cols-3 py-1">
                        <span class="text-slate-400">Kategori</span>
                        <span class="col-span-2 font-semibold text-[#0F291E]">{{ $product->category }}</span>
                    </div>
                    <div class="grid grid-cols-3 py-1">
                        <span class="text-slate-400">Unit / Kelas</span>
                        <span class="col-span-2 font-semibold text-[#0F291E]">{{ $product->store->student_class ?: 'SMK Plus Al Hilal' }}</span>
                    </div>
                    <div class="grid grid-cols-3 py-1">
                        <span class="text-slate-400">Stok Tersedia</span>
                        <span class="col-span-2 font-semibold text-[#0F291E]">{{ $product->stock }} unit</span>
                    </div>
                    <div class="grid grid-cols-3 py-1">
                        <span class="text-slate-400">Interaksi</span>
                        <span class="col-span-2 font-semibold text-[#0F291E]">
                            👁️ {{ number_format($product->views_count ?? 0) }} dilihat • ❤️ {{ number_format($product->likes_count ?? 0) }} suka • 💬 {{ $product->comments->count() }} komentar
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400 pt-1">
                    Catatan: Setiap karya dibuat dengan ketelitian tinggi oleh sivitas SMK Plus Al Hilal.
                </p>
            </div>

            <!-- Konten Tab 2: Komentar & Ulasan Pengunjung Berakun -->
            <div id="tab-panel-ulasan" class="hidden space-y-5">
                @auth
                    <form action="{{ route('products.comment', $product->slug) }}" method="POST" class="rounded-xl bg-white p-4 border border-slate-200/80 space-y-3">
                        @csrf
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-[#0F291E]">{{ auth()->user()->name }} ({{ auth()->user()->subtype_label }})</span>
                            <select name="rating" class="rounded-lg border border-slate-200 bg-[#F8FAFC] px-2.5 py-1 text-xs font-semibold text-slate-700">
                                <option value="5">★ 5 - Sangat Bagus</option>
                                <option value="4">★ 4 - Bagus</option>
                                <option value="3">★ 3 - Cukup</option>
                                <option value="2">★ 2 - Kurang</option>
                                <option value="1">★ 1 - Perlu Perbaikan</option>
                            </select>
                        </div>
                        <textarea name="comment" rows="3" required placeholder="Tulis komentar atau apresiasi Anda..." class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none"></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                                Kirim Komentar
                            </button>
                        </div>
                    </form>
                @else
                    <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] p-4 flex items-center justify-between gap-3">
                        <p class="text-xs text-[#0F291E]">Silakan masuk untuk memberikan komentar pada karya ini.</p>
                        <a href="{{ route('login') }}" class="rounded-lg bg-[#0B6E4F] px-3 py-1.5 text-xs font-semibold text-white shrink-0">Masuk</a>
                    </div>
                @endauth

                <div class="space-y-3">
                    @forelse($product->comments as $c)
                        <div class="rounded-xl bg-white border border-slate-200/60 p-4 space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-[#0F291E]">{{ $c->user->name ?? 'Pengunjung' }}</span>
                                <span class="text-[11px] text-slate-400">{{ $c->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ $c->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3">Belum ada komentar. Jadilah yang pertama memberikan apresiasi!</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Kanan: Kartu "Kenali pembuatnya" -->
        <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-7 space-y-5 shadow-sm">
            <h3 class="text-sm font-bold text-[#0F291E]">Kenali pembuatnya</h3>

            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-sm flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($product->store->owner->name, 0, 2)) }}
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-[#0F291E]">{{ $product->store->owner->name }}</h4>
                    <p class="text-xs text-slate-500">{{ $product->store->student_class ?: $product->store->store_name }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F] mt-0.5">✓ Identitas sivitas terverifikasi</p>
                </div>
            </div>

            <p class="text-xs text-slate-500 italic leading-relaxed border-b border-slate-100 pb-4">
                "{{ $product->store->description ?: 'Saya percaya teknologi dan kerajinan bisa bertemu untuk membuat hal sederhana jadi lebih berarti.' }}"
            </p>

            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <p class="font-extrabold text-[#0F291E]">± 15 menit</p>
                    <p class="text-[11px] text-slate-400">Rata-rata balasan</p>
                </div>
                <div>
                    <p class="font-extrabold text-[#0F291E]">4,9 / 5</p>
                    <p class="text-[11px] text-slate-400">Penilaian pembeli</p>
                </div>
            </div>

            <a href="{{ $product->store->whatsapp_url }}?text={{ $waMessage }}"
               target="_blank"
               rel="noopener"
               class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition">
                <span>💬</span>
                <span>Hubungi Penjual via WhatsApp</span>
            </a>

            <p class="text-[11px] text-center text-slate-400">
                Tanyakan detail, kustomisasi, atau pengiriman.
            </p>
        </div>
    </div>

    <!-- Bagian Bawah: Karya lain untukmu -->
    @if($relatedProducts->count() > 0)
        <div class="pt-6 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-lg sm:text-xl font-extrabold text-[#0F291E]">Karya lain untukmu</h2>
                <a href="{{ route('home') }}" class="text-xs font-bold text-[#0B6E4F] hover:underline">Jelajahi semua →</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-5">
                @foreach($relatedProducts as $rel)
                    <article class="group rounded-xl sm:rounded-2xl bg-white border border-slate-200/80 overflow-hidden hover:shadow-md transition flex flex-col">
                        <a href="{{ route('products.show', $rel->slug) }}" class="relative aspect-square bg-slate-100 overflow-hidden block">
                            <img src="{{ Str::startsWith($rel->image, 'http') ? $rel->image : asset('storage/' . $rel->image) }}"
                                 alt="{{ $rel->product_name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 onerror="this.src='{{ $fallbackImg }}'">
                        </a>
                        <div class="p-2.5 sm:p-4 space-y-1 sm:space-y-1.5 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="inline-block rounded bg-[#EBF9F2] px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold text-[#0B6E4F] truncate max-w-full">
                                    {{ $rel->store->student_class ?: $rel->category }}
                                </span>
                                <h3 class="font-bold text-[#0F291E] text-xs sm:text-sm line-clamp-1 hover:text-[#0B6E4F] transition mt-1">
                                    <a href="{{ route('products.show', $rel->slug) }}">{{ $rel->product_name }}</a>
                                </h3>
                                <p class="text-[10px] sm:text-xs text-slate-500 line-clamp-2 leading-relaxed mt-0.5">
                                    {{ $rel->description ?: 'Karya siswa SMK Plus Al Hilal.' }}
                                </p>
                                <p class="text-xs sm:text-sm font-extrabold text-[#0B6E4F] mt-1">{{ $rel->formatted_price }}</p>
                            </div>
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] sm:text-[11px] text-slate-400">
                                <span class="text-[#0B6E4F] font-semibold">💬 Chat</span>
                                <span>👁️ {{ $rel->views_count ?? 0 }} • ❤️ {{ $rel->likes_count ?? 0 }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ========================================================================
         MODAL / DRAWER MENU BAGIKAN PRODUK (SEBAGAIMANA MARKETPLACE LAIN)
         ======================================================================== -->
    <div id="share-product-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4 transition-all">
        <div class="relative w-full max-w-md rounded-2xl bg-white p-5 sm:p-6 shadow-2xl border border-slate-200/80 space-y-4 animate-scale-up">
            <!-- Header Modal -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-full bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center font-bold text-sm">
                        ↗
                    </span>
                    <div>
                        <h3 class="text-sm font-extrabold text-[#0F291E]">Bagikan Produk</h3>
                        <p class="text-[11px] text-slate-400">Bagikan karya ini ke media sosial atau teman Anda</p>
                    </div>
                </div>
                <button type="button" onclick="closeShareModal()" class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold text-base flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <!-- Kartu Cuplikan Produk -->
            <div class="flex items-center gap-3 rounded-xl bg-[#F8FAFC] border border-slate-200/80 p-3">
                <img src="{{ $mainImg }}" alt="{{ $product->product_name }}" class="w-14 h-14 rounded-lg object-cover bg-white shrink-0 border border-slate-200" onerror="this.src='{{ $fallbackImg }}'">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-[#0F291E] line-clamp-1">{{ $product->product_name }}</p>
                    <p class="text-xs font-extrabold text-[#0B6E4F] mt-0.5">{{ $product->formatted_price }}</p>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">{{ $product->store->store_name }} • SMK Plus Al Hilal</p>
                </div>
            </div>

            <!-- Input Salin Link Langsung -->
            <div class="space-y-1.5">
                <label class="block text-[11px] font-bold text-slate-600">Salin Tautan / Link Produk</label>
                <div class="flex items-center gap-2">
                    <input type="text"
                           id="share-product-url-input"
                           value="{{ url()->current() }}"
                           readonly
                           class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs text-slate-700 font-mono focus:outline-none">
                    <button type="button"
                            id="btn-copy-share-link"
                            onclick="copyShareLink()"
                            class="rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shrink-0 shadow-xs">
                        Salin
                    </button>
                </div>
                <p id="share-copy-feedback" class="text-[11px] font-semibold text-[#0B6E4F] hidden">✓ Link berhasil disalin ke clipboard!</p>
            </div>

            <!-- Pilihan Cepat Bagikan ke Platform Lain -->
            <div class="space-y-2 pt-2 border-t border-slate-100">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Bagikan Ke</p>
                <div class="grid grid-cols-4 gap-2 text-center text-xs">
                    <!-- WhatsApp -->
                    @php
                        $waShareText = urlencode("Yuk lihat karya siswa SMK Plus Al Hilal:\n*{$product->product_name}*\nHarga: {$product->formatted_price}\n\nKunjungi produk di hilalkraft:\n" . url()->current());
                    @endphp
                    <a href="https://api.whatsapp.com/send?text={{ $waShareText }}"
                       target="_blank"
                       rel="noopener"
                       class="flex flex-col items-center gap-1 p-2 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition group">
                        <span class="w-10 h-10 rounded-full bg-[#25D366]/15 text-[#25D366] group-hover:scale-110 flex items-center justify-center font-bold text-lg transition">
                            💬
                        </span>
                        <span class="text-[10px] font-bold text-slate-700">WhatsApp</span>
                    </a>

                    <!-- Telegram -->
                    <a href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&text={{ urlencode("Karya Siswa SMK Plus Al Hilal: {$product->product_name} ({$product->formatted_price})") }}"
                       target="_blank"
                       rel="noopener"
                       class="flex flex-col items-center gap-1 p-2 rounded-xl border border-slate-200 hover:border-sky-500 hover:bg-sky-50/50 transition group">
                        <span class="w-10 h-10 rounded-full bg-[#0088cc]/15 text-[#0088cc] group-hover:scale-110 flex items-center justify-center font-bold text-lg transition">
                            ✈️
                        </span>
                        <span class="text-[10px] font-bold text-slate-700">Telegram</span>
                    </a>

                    <!-- Facebook -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                       target="_blank"
                       rel="noopener"
                       class="flex flex-col items-center gap-1 p-2 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 transition group">
                        <span class="w-10 h-10 rounded-full bg-[#1877F2]/15 text-[#1877F2] group-hover:scale-110 flex items-center justify-center font-bold text-lg transition">
                            f
                        </span>
                        <span class="text-[10px] font-bold text-slate-700">Facebook</span>
                    </a>

                    <!-- Twitter / X -->
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode("Karya Siswa SMK Plus Al Hilal: {$product->product_name}") }}&url={{ urlencode(url()->current()) }}"
                       target="_blank"
                       rel="noopener"
                       class="flex flex-col items-center gap-1 p-2 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition group">
                        <span class="w-10 h-10 rounded-full bg-slate-900/10 text-slate-900 group-hover:scale-110 flex items-center justify-center font-bold text-base transition">
                            𝕏
                        </span>
                        <span class="text-[10px] font-bold text-slate-700">Twitter / X</span>
                    </a>
                </div>

                <!-- Tombol Web Share Native untuk Smartphone -->
                <button type="button"
                        id="btn-native-share"
                        onclick="triggerNativeShare()"
                        class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition">
                    <span>📱</span>
                    <span>Bagikan ke Aplikasi Lain di Ponsel</span>
                </button>
            </div>
        </div>
    </div>

    @auth
    <!-- Modal Pemesanan Langsung & Pilihan Metode Pengiriman (3 Skema Transaksi) -->
    <div id="direct-order-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden my-8">
            <div class="bg-gradient-to-r from-[#0B6E4F] to-[#0F291E] p-5 text-white flex items-center justify-between">
                <div>
                    <h3 class="text-base font-extrabold flex items-center gap-2">
                        <span>🚚</span>
                        <span>Pesan Langsung: {{ $product->product_name }}</span>
                    </h3>
                    <p class="text-xs text-emerald-200 mt-0.5">Toko: {{ $product->store->store_name }} • Pilih skema penyerahan barang</p>
                </div>
                <button type="button" onclick="closeDirectOrderModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm font-bold transition">
                    ✕
                </button>
            </div>

            <form action="{{ route('buyer.direct-order', $product) }}" method="POST" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                @csrf
                <input type="hidden" name="quantity" id="modal-direct-qty" value="1">

                <!-- Info Jumlah Item -->
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <div>
                        <div class="text-xs font-bold text-[#0F291E]">Jumlah Pesanan</div>
                        <div class="text-[11px] text-slate-500">Harga Satuan: {{ $product->formatted_price }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="changeModalQty(-1)" class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-xs font-bold hover:bg-slate-100">-</button>
                        <span id="modal-qty-display" class="w-8 text-center text-xs font-extrabold">1</span>
                        <button type="button" onclick="changeModalQty(1)" class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-xs font-bold hover:bg-slate-100">+</button>
                    </div>
                </div>

                <!-- 3 Opsi Skema Transaksi Sesuai Izin Produk Toko -->
                @php
                    $canPickup = $product->canPickup();
                    $canDelivery = $product->canDeliveryLokal();
                    $canTransfer = $product->canShippingTransfer();
                    $defaultMethod = $canPickup ? 'pickup' : ($canDelivery ? 'delivery_lokal' : 'shipping_transfer');
                @endphp
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold text-[#0F291E]">Pilih Metode Transaksi & Pengiriman *</span>
                        <span class="text-[10px] text-slate-500">Disesuaikan izin toko</span>
                    </div>

                    <!-- 1. Datang ke Sekolah -->
                    <label class="modal-delivery-card block rounded-2xl border-2 p-3 transition {{ $canPickup ? 'cursor-pointer border-[#0B6E4F] bg-[#EBF9F2]/30' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="pickup">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="pickup" @checked($defaultMethod === 'pickup') @disabled(!$canPickup) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateModalDeliveryUI('pickup')">
                            <div class="flex-1 space-y-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">🏫 1. Datang ke Sekolah (Transaksi Langsung)</span>
                                    <span class="text-[10px] font-extrabold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">Rp 0 (Gratis)</span>
                                </div>
                                <p class="text-[11px] text-slate-600">Ambil/serah terima langsung di SMK Plus Al Hilal / Stand Bazar.</p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="font-bold text-slate-500">Alur: Dipesan → Dikemas → Diterima (3 Tahap)</span>
                                    @if(!$canPickup)
                                        <span class="font-extrabold text-rose-600">(Produk ini tidak melayani Datang Langsung)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- 2. Delivery / Antar Langsung (Arjawinangun, Susukan, Kaliwedi) -->
                    <label class="modal-delivery-card block rounded-2xl border-2 p-3 transition {{ $canDelivery ? 'cursor-pointer border-slate-200 hover:border-slate-300 bg-white' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="delivery_lokal">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="delivery_lokal" @checked($defaultMethod === 'delivery_lokal') @disabled(!$canDelivery) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateModalDeliveryUI('delivery_lokal')">
                            <div class="flex-1 space-y-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">🛵 2. Delivery / Antar Langsung</span>
                                    <span class="text-[10px] font-extrabold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">Rp 5.000</span>
                                </div>
                                <p class="text-[11px] text-slate-600">Kurir sekolah khusus: <strong class="text-slate-900">Kec. Arjawinangun, Susukan, & Kaliwedi</strong>.</p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="font-bold text-slate-500">Alur: Dipesan → Dikemas → Dikirim → Diterima (4 Tahap)</span>
                                    @if(!$canDelivery)
                                        <span class="font-extrabold text-rose-600">(Produk ini tidak melayani Delivery Lokal)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>

                    <!-- 3. Transfer Antar -->
                    <label class="modal-delivery-card block rounded-2xl border-2 p-3 transition {{ $canTransfer ? 'cursor-pointer border-slate-200 hover:border-slate-300 bg-white' : 'cursor-not-allowed opacity-50 border-slate-200 bg-slate-50' }}" data-method="shipping_transfer">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="delivery_method" value="shipping_transfer" @checked($defaultMethod === 'shipping_transfer') @disabled(!$canTransfer) class="mt-1 text-[#0B6E4F] focus:ring-[#0B6E4F]" onchange="updateModalDeliveryUI('shipping_transfer')">
                            <div class="flex-1 space-y-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold text-[#0F291E]">📦 3. Transfer Antar (Luar Wilayah / Ekspedisi)</span>
                                    <span id="modal-transfer-badge" class="text-[10px] font-extrabold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full">Ongkir Kurir Langsung</span>
                                </div>
                                <p class="text-[11px] text-amber-950 font-medium leading-snug">Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir. Pembeli hanya mentransfer biaya karya ke rekening/e-wallet toko.</p>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="font-bold text-slate-500">Alur: Dipesan → Dikemas → Dikirim → Diterima (4 Tahap)</span>
                                    @if(!$canTransfer)
                                        <span class="font-extrabold text-rose-600">(Produk ini tidak melayani Transfer / Ekspedisi)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Kotak Informasi Rekening Bank & E-Wallet Toko (Ditampilkan Saat Transfer Antar Dipilih) -->
                <div id="modal-transfer-bank-box" class="hidden rounded-2xl bg-amber-50/80 border border-amber-200 p-4 space-y-2.5 text-xs text-amber-950">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-amber-900 flex items-center gap-1.5">
                            <span>💳</span>
                            <span>Rekening & E-Wallet Toko {{ $product->store->store_name }}</span>
                        </span>
                        <span class="text-[10px] text-amber-800 font-bold bg-amber-200/70 px-2 py-0.5 rounded-full">Transfer Pembayaran</span>
                    </div>

                    @if($product->store->has_bank_account)
                        <div class="flex items-center justify-between bg-white rounded-xl p-2.5 border border-amber-200 shadow-2xs">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Bank {{ $product->store->bank_name }}</span>
                                <span class="font-mono font-extrabold text-xs sm:text-sm text-[#0F291E]" id="copy-bank-acc">{{ $product->store->bank_account_number }}</span>
                                <span class="text-[11px] text-slate-500 block">a.n. {{ $product->store->bank_account_name }}</span>
                            </div>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $product->store->bank_account_number }}'); alert('Nomor Rekening {{ $product->store->bank_name }} berhasil disalin!');" class="rounded-lg bg-amber-100 hover:bg-amber-200 px-3 py-1.5 text-[10px] font-extrabold text-amber-900 transition">
                                Salin Rekening
                            </button>
                        </div>
                    @endif

                    @if($product->store->has_ewallet_account)
                        <div class="flex items-center justify-between bg-white rounded-xl p-2.5 border border-amber-200 shadow-2xs">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">E-Wallet {{ $product->store->ewallet_name }}</span>
                                <span class="font-mono font-extrabold text-xs sm:text-sm text-[#0F291E]" id="copy-ewallet-acc">{{ $product->store->ewallet_account_number }}</span>
                                <span class="text-[11px] text-slate-500 block">a.n. {{ $product->store->ewallet_account_name }}</span>
                            </div>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $product->store->ewallet_account_number }}'); alert('Nomor HP E-Wallet {{ $product->store->ewallet_name }} berhasil disalin!');" class="rounded-lg bg-amber-100 hover:bg-amber-200 px-3 py-1.5 text-[10px] font-extrabold text-amber-900 transition">
                                Salin E-Wallet
                            </button>
                        </div>
                    @endif

                    @if(!$product->store->has_payment_account)
                        <p class="text-[11px] text-amber-800 bg-white/70 p-2.5 rounded-xl border border-amber-200">
                            💬 Penjual belum memasukkan nomor rekening di profil toko. Anda dapat meminta rekening langsung via chat WhatsApp saat pesanan dibuat.
                        </p>
                    @endif

                    @if($product->store->payment_instructions)
                        <p class="text-[11px] text-amber-900 bg-amber-100/60 p-2 rounded-xl italic">
                            <strong>Petunjuk Pembayaran Toko:</strong> "{{ $product->store->payment_instructions }}"
                        </p>
                    @endif
                </div>

                <!-- Input Alamat & Lokasi -->
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3.5 space-y-2.5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 mb-0.5">Kabupaten / Kota</label>
                            <input type="text" name="destination_kabupaten" id="modal-dest-kabupaten" value="{{ old('destination_kabupaten', auth()->user()->kabupaten ?: 'Cirebon') }}" placeholder="Cirebon" oninput="calculateModalOngkir()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 mb-0.5">Kecamatan</label>
                            <input type="text" name="destination_kecamatan" id="modal-dest-kecamatan" value="{{ old('destination_kecamatan', auth()->user()->kecamatan ?: 'Arjawinangun') }}" placeholder="Arjawinangun / Susukan / Kaliwedi" oninput="calculateModalOngkir()" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                    </div>

                    <!-- Shortcut 3 kecamatan untuk delivery lokal -->
                    <div id="modal-local-shortcuts" class="hidden text-[10px] text-slate-600 space-y-1">
                        <span class="font-bold text-blue-900">Pilih cepat:</span>
                        <div class="flex gap-1.5">
                            <button type="button" onclick="setModalKecamatan('Arjawinangun')" class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold hover:bg-blue-200">Arjawinangun</button>
                            <button type="button" onclick="setModalKecamatan('Susukan')" class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold hover:bg-blue-200">Susukan</button>
                            <button type="button" onclick="setModalKecamatan('Kaliwedi')" class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-bold hover:bg-blue-200">Kaliwedi</button>
                        </div>
                    </div>

                    <div id="modal-local-warning" class="hidden rounded-xl bg-rose-50 border border-rose-200 p-2 text-[10px] text-rose-800">
                        ⚠️ Delivery Antar Langsung hanya untuk Kec. Arjawinangun, Susukan, dan Kaliwedi (Kab. Cirebon).
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-700 mb-0.5" id="modal-lbl-address">Alamat Pengiriman / Stand Penyerahan *</label>
                        <input type="text" name="shipping_address" id="modal-shipping-address" value="{{ old('shipping_address', auth()->user()->full_address !== 'Wilayah belum dilengkapi' ? auth()->user()->full_address : 'Stand Bazar / Kampus SMK Plus Al Hilal') }}" required placeholder="Contoh: Stand Bazar SMK Plus Al Hilal / Jl. Nyi Mas Gandasari No..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                </div>

                <!-- Diskon Bazar & Catatan -->
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-amber-950 font-semibold bg-amber-50/70 p-2.5 rounded-xl border border-amber-200">
                        <input type="checkbox" name="is_bazaar_offline" value="1" class="rounded border-amber-400 text-[#0B6E4F] focus:ring-[#0B6E4F]">
                        <span>🎪 Beli langsung di Acara Bazar SMK (Klaim Promo Stand)</span>
                    </label>
                    <textarea name="notes" rows="1" placeholder="Catatan untuk penjual (opsional)..." class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-1.5 text-xs focus:bg-white focus:border-[#0B6E4F] focus:outline-none"></textarea>
                </div>

                <!-- Total Biaya -->
                <div class="pt-3 border-t border-slate-100 space-y-1 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Ongkos Kirim:</span>
                        <span id="modal-ongkir-text" class="font-bold text-[#0B6E4F]">Rp 0 (Gratis)</span>
                    </div>
                    <div class="flex justify-between text-sm font-extrabold text-[#0F291E]">
                        <span>Estimasi Total:</span>
                        <span id="modal-total-text" class="text-base text-[#0B6E4F]">{{ $product->formatted_price }}</span>
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="button" onclick="closeDirectOrderModal()" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit" id="modal-submit-btn" class="flex-2 rounded-xl bg-[#0B6E4F] py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                        ✓ Buat Pesanan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endauth

</div>

<script>
    function changeQty(delta) {
        const display = document.getElementById('qty-display');
        const hiddenInput = document.getElementById('cart-qty-input');
        const directInput = document.getElementById('direct-qty-input');
        if (!display) return;
        const max = parseInt(display.getAttribute('max') || '99', 10);
        let next = parseInt(display.value || '1', 10) + delta;
        if (next < 1) next = 1;
        if (next > max) next = max;
        display.value = next;
        if (hiddenInput) hiddenInput.value = next;
        if (directInput) directInput.value = next;
    }

    function switchProductTab(tab) {
        const isDesc = tab === 'deskripsi';
        document.getElementById('tab-panel-deskripsi').classList.toggle('hidden', !isDesc);
        document.getElementById('tab-panel-ulasan').classList.toggle('hidden', isDesc);

        const btnDesc = document.getElementById('tab-btn-deskripsi');
        const btnUlasan = document.getElementById('tab-btn-ulasan');
        btnDesc.className = isDesc
            ? 'pb-3 border-b-2 border-[#0B6E4F] text-[#0B6E4F] transition'
            : 'pb-3 border-b-2 border-transparent text-slate-400 hover:text-slate-700 transition';
        btnUlasan.className = !isDesc
            ? 'pb-3 border-b-2 border-[#0B6E4F] text-[#0B6E4F] transition'
            : 'pb-3 border-b-2 border-transparent text-slate-400 hover:text-slate-700 transition';
    }

    // Fungsi Menu Bagikan Produk
    function openShareModal() {
        const modal = document.getElementById('share-product-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            // Cek apakah browser mendukung Web Share API
            const nativeBtn = document.getElementById('btn-native-share');
            if (nativeBtn && !navigator.share) {
                nativeBtn.style.display = 'none';
            }
        }
    }

    function closeShareModal() {
        const modal = document.getElementById('share-product-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Salin link produk ke clipboard
    function copyShareLink() {
        const input = document.getElementById('share-product-url-input');
        const btn = document.getElementById('btn-copy-share-link');
        const feedback = document.getElementById('share-copy-feedback');

        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999); // Untuk perangkat mobile

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(onCopySuccess).catch(() => {
                document.execCommand('copy');
                onCopySuccess();
            });
        } else {
            document.execCommand('copy');
            onCopySuccess();
        }

        function onCopySuccess() {
            if (btn) {
                btn.textContent = '✓ Tersalin!';
                btn.classList.remove('bg-[#0B6E4F]');
                btn.classList.add('bg-emerald-700');
                setTimeout(() => {
                    btn.textContent = 'Salin';
                    btn.classList.remove('bg-emerald-700');
                    btn.classList.add('bg-[#0B6E4F]');
                }, 2500);
            }
            if (feedback) {
                feedback.classList.remove('hidden');
                setTimeout(() => feedback.classList.add('hidden'), 3500);
            }
        }
    }

    // Native Web Share API untuk HP/browser pendukung
    function triggerNativeShare() {
        if (navigator.share) {
            navigator.share({
                title: '{{ addslashes($product->product_name) }}',
                text: 'Yuk lihat karya siswa SMK Plus Al Hilal: {{ addslashes($product->product_name) }} ({{ $product->formatted_price }}) di hilalkraft!',
                url: window.location.href,
            }).catch(() => {});
        } else {
            copyShareLink();
        }
    }

    // Tutup modal jika klik di luar box
    document.addEventListener('click', (e) => {
        const shareModal = document.getElementById('share-product-modal');
        if (shareModal && e.target === shareModal) {
            closeShareModal();
        }
        const directModal = document.getElementById('direct-order-modal');
        if (directModal && e.target === directModal) {
            closeDirectOrderModal();
        }
    });

    // Modal Direct Order
    const productBasePrice = {{ (float) $product->price }};
    const localDistricts = ['arjawinangun', 'susukan', 'kaliwedi'];

    function openDirectOrderModal() {
        const modal = document.getElementById('direct-order-modal');
        if (!modal) return;
        // Ambil quantity dari input utama
        const mainQty = document.getElementById('qty-display');
        const q = mainQty ? parseInt(mainQty.value || '1', 10) : 1;
        setModalQtyVal(q);
        updateModalDeliveryUI('{{ $defaultMethod }}');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDirectOrderModal() {
        const modal = document.getElementById('direct-order-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function setModalQtyVal(val) {
        val = Math.max(1, val);
        const qtyDisplay = document.getElementById('modal-qty-display');
        const hiddenQty = document.getElementById('modal-direct-qty');
        if (qtyDisplay) qtyDisplay.textContent = val;
        if (hiddenQty) hiddenQty.value = val;
        calculateModalOngkir();
    }

    function changeModalQty(delta) {
        const hiddenQty = document.getElementById('modal-direct-qty');
        let current = hiddenQty ? parseInt(hiddenQty.value || '1', 10) : 1;
        setModalQtyVal(current + delta);
    }

    function setModalKecamatan(name) {
        const kecInput = document.getElementById('modal-dest-kecamatan');
        const kabInput = document.getElementById('modal-dest-kabupaten');
        if (kecInput) kecInput.value = name;
        if (kabInput && !kabInput.value) kabInput.value = 'Cirebon';
        calculateModalOngkir();
    }

    function getModalSelectedMethod() {
        const radios = document.getElementsByName('delivery_method');
        for (let r of radios) {
            if (r.checked) return r.value;
        }
        return 'pickup';
    }

    function updateModalDeliveryUI(method) {
        document.querySelectorAll('.modal-delivery-card').forEach(el => {
            if (el.getAttribute('data-method') === method) {
                el.classList.add('border-[#0B6E4F]', 'bg-[#EBF9F2]/30');
                el.classList.remove('border-slate-200', 'bg-white');
            } else {
                el.classList.remove('border-[#0B6E4F]', 'bg-[#EBF9F2]/30');
                el.classList.add('border-slate-200', 'bg-white');
            }
        });

        const addressLabel = document.getElementById('modal-lbl-address');
        const addressInput = document.getElementById('modal-shipping-address');
        const shortcuts = document.getElementById('modal-local-shortcuts');
        const warning = document.getElementById('modal-local-warning');
        const submitBtn = document.getElementById('modal-submit-btn');
        const bankBox = document.getElementById('modal-transfer-bank-box');

        if (method === 'pickup') {
            addressLabel.textContent = 'Stand / Ruang Penyerahan di Sekolah *';
            if (addressInput && (!addressInput.value || addressInput.value.includes('Jl.'))) {
                addressInput.value = 'Stand Bazar / Kampus SMK Plus Al Hilal';
            }
            if (shortcuts) shortcuts.classList.add('hidden');
            if (warning) warning.classList.add('hidden');
            if (bankBox) bankBox.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        } else if (method === 'delivery_lokal') {
            addressLabel.textContent = 'Alamat Pengantaran (Arjawinangun/Susukan/Kaliwedi) *';
            if (shortcuts) shortcuts.classList.remove('hidden');
            if (bankBox) bankBox.classList.add('hidden');
        } else {
            addressLabel.textContent = 'Alamat Lengkap Pengiriman Ekspedisi *';
            if (shortcuts) shortcuts.classList.add('hidden');
            if (warning) warning.classList.add('hidden');
            if (bankBox) bankBox.classList.remove('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }

        calculateModalOngkir();
    }

    function calculateModalOngkir() {
        const method = getModalSelectedMethod();
        const kabInput = document.getElementById('modal-dest-kabupaten');
        const kecInput = document.getElementById('modal-dest-kecamatan');
        const hiddenQty = document.getElementById('modal-direct-qty');
        const warning = document.getElementById('modal-local-warning');
        const submitBtn = document.getElementById('modal-submit-btn');
        const ongkirText = document.getElementById('modal-ongkir-text');
        const totalText = document.getElementById('modal-total-text');

        const qty = hiddenQty ? parseInt(hiddenQty.value || '1', 10) : 1;
        const subtotal = productBasePrice * qty;
        let ongkir = 0;

        if (method === 'pickup') {
            ongkir = 0;
            if (ongkirText) {
                ongkirText.textContent = 'Rp 0 (Gratis)';
                ongkirText.className = 'font-bold text-[#0B6E4F]';
            }
        } else if (method === 'delivery_lokal') {
            ongkir = 5000;
            if (ongkirText) {
                ongkirText.textContent = 'Rp 5.000';
                ongkirText.className = 'font-bold text-blue-700';
            }
            const cleanKec = (kecInput ? kecInput.value : '').toLowerCase().trim();
            const isMatch = localDistricts.some(d => cleanKec.includes(d));
            if (!isMatch && cleanKec.length > 0) {
                if (warning) warning.classList.remove('hidden');
                if (submitBtn) submitBtn.disabled = true;
            } else {
                if (warning) warning.classList.add('hidden');
                if (submitBtn) submitBtn.disabled = false;
            }
        } else {
            // shipping_transfer (Luar Wilayah)
            ongkir = 0; // Biaya ongkir tidak ditambahkan ke tagihan toko
            if (ongkirText) {
                ongkirText.innerHTML = '<span class="text-[10px] font-semibold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md inline-block text-right">Biaya ongkir ditanggung pembeli, pembayaran langsung dibayarkan kepada kurir.</span>';
                ongkirText.className = '';
            }
            const badge = document.getElementById('modal-transfer-badge');
            if (badge) badge.textContent = 'Ongkir Langsung ke Kurir';
            if (warning) warning.classList.add('hidden');
            if (submitBtn) submitBtn.disabled = false;
        }

        const grand = subtotal + ongkir;
        if (totalText) {
            totalText.textContent = 'Rp ' + Number(grand).toLocaleString('id-ID');
        }
    }
</script>
@endsection
