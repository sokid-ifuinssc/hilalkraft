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

@section('title', $product->product_name . ' — hilalkraft')

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
    $waMessage = urlencode("Assalamu'alaikum {$product->store->owner->name} ({$product->store->store_name}), saya tertarik membeli produk *{$product->product_name}* seharga {$product->formatted_price} di hilalkraf SMK Plus Al Hilal.");
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

                <div class="flex items-center gap-2">
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

                        <form action="{{ route('buyer.direct-order', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" id="direct-qty-input" value="1">
                            <input type="hidden" name="is_bazaar_offline" id="direct-bazaar-input" value="0">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm">
                                <span>🚚</span>
                                <span>Pesan & Pantau Progres</span>
                            </button>
                        </form>
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

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
                @foreach($relatedProducts as $rel)
                    <article class="group rounded-2xl bg-white border border-slate-200/80 overflow-hidden hover:shadow-md transition flex flex-col">
                        <a href="{{ route('products.show', $rel->slug) }}" class="relative aspect-square bg-slate-100 overflow-hidden block">
                            <img src="{{ Str::startsWith($rel->image, 'http') ? $rel->image : asset('storage/' . $rel->image) }}"
                                 alt="{{ $rel->product_name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 onerror="this.src='{{ $fallbackImg }}'">
                        </a>
                        <div class="p-4 space-y-1.5">
                            <span class="inline-block rounded-md bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                                {{ $rel->store->student_class ?: $rel->category }}
                            </span>
                            <h3 class="font-bold text-[#0F291E] text-sm line-clamp-1">
                                <a href="{{ route('products.show', $rel->slug) }}">{{ $rel->product_name }}</a>
                            </h3>
                            <p class="text-sm font-extrabold text-[#0B6E4F]">{{ $rel->formatted_price }}</p>
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                <span class="text-[#0B6E4F] font-semibold">💬 Chat penjual</span>
                                <span>👁️ {{ $rel->views_count ?? 0 }} • ❤️ {{ $rel->likes_count ?? 0 }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

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
</script>
@endsection
