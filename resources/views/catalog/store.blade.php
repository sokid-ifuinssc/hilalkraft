{{--
============================================================================
VIEW: PROFIL TOKO SISWA (catalog/store.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman etalase khusus untuk satu Toko Siswa SMK Plus Al Hilal
  beserta informasi pemilik, kelas/jurusan, tombol WhatsApp, dan daftar produknya.
Algoritma:
  Melakukan iterasi `@forelse` pada koleksi `$store->products` yang telah
  dimuat secara Eager Loading oleh `CatalogController@storeProfile`.
============================================================================
--}}
@extends('layouts.app')

@section('title', $store->store_name . ' — Toko Siswa hilalkraf')
@section('og_type', 'profile')
@section('og_title', 'Toko ' . $store->store_name . ' — hilalkraf')
@section('og_description', 'Kunjungi etalase resmi ' . $store->store_name . ' (' . $store->seller_type_label . ') di hilalkraf. Dukung aneka karya inovatif siswa SMK Plus Al Hilal.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="rounded-3xl bg-gradient-to-r from-[#10B981] to-[#059669] p-6 sm:p-8 text-white flex flex-wrap items-center justify-between gap-4 shadow-lg">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold">
                    Etalase Resmi Siswa SMK Plus Al Hilal
                </span>
                @if(isset($storeActiveBazaar) && $storeActiveBazaar)
                    <span class="inline-block rounded-full bg-amber-400 text-amber-950 px-3 py-1 text-xs font-extrabold shadow-xs">
                        Peserta {{ $storeActiveBazaar->title }} &bull; Diskon Bazar {{ $storeActiveBazaar->discount_percent + ($store->discount_bazar_extra ?? 0) }}%
                    </span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-2">{{ $store->store_name }}</h1>
            <p class="text-sm text-emerald-50 mt-1">
                Kreator: <strong>{{ $store->owner->name }}</strong>
                @if($store->student_class)
                    • {{ $store->student_class }}
                @endif
            </p>
            @if($store->description)
                <p class="text-xs text-emerald-100 mt-2 max-w-2xl">{{ $store->description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="copyStoreLink()" class="inline-flex items-center gap-1.5 rounded-xl border border-white/40 bg-white/15 px-4 py-3 text-xs font-bold text-white hover:bg-white/25 transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                <span>Bagikan Toko</span>
            </button>
            @php
                $storeChatUrl = route('chat.start.store', $store);
                $isOwnProfile = auth()->check() && (int) auth()->id() === (int) $store->user_id;
            @endphp
            <a href="{{ auth()->check() ? ($isOwnProfile ? route('chat.index') : $storeChatUrl) : route('login', ['redirect' => $storeChatUrl]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-3 text-xs font-extrabold text-[#059669] shadow-md hover:bg-emerald-50 transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>{{ $isOwnProfile ? 'Kotak Masuk Chat' : 'Chat Penjual' }}</span>
            </a>
            <a href="{{ $store->whatsapp_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-xl border border-white/50 bg-[#0F291E]/25 px-4 py-3 text-xs font-extrabold text-white hover:bg-[#0F291E]/40 transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                <span>Tanya via WA</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-6">
        @forelse($store->products as $product)
            <article class="rounded-xl sm:rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-xs hover:shadow-lg transition flex flex-col">
                <a href="{{ route('products.show', $product->slug) }}" class="relative aspect-square w-full bg-slate-100 block overflow-hidden" style="aspect-ratio: 1 / 1;">
                    <img src="{{ Str::startsWith($product->image, 'http') ? $product->image : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image)) }}"
                         alt="{{ $product->product_name }}"
                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                         style="aspect-ratio: 1 / 1; object-fit: cover;"
                         onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                </a>
                <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between space-y-2">
                    <div class="space-y-1">
                        <span class="inline-block rounded bg-[#EBF9F2] px-1.5 py-0.5 text-[9px] sm:text-[10px] font-bold text-[#0B6E4F]">
                            {{ $product->category ?: 'Karya Siswa' }}
                        </span>
                        <h3 class="font-bold text-slate-900 text-xs sm:text-sm line-clamp-1 hover:text-[#0B6E4F] transition">
                            <a href="{{ route('products.show', $product->slug) }}">{{ $product->product_name }}</a>
                        </h3>
                        <p class="text-[10px] sm:text-xs text-slate-500 line-clamp-2 leading-relaxed">
                            {{ $product->description ?: 'Karya terbaik dari toko siswa SMK Plus Al Hilal.' }}
                        </p>
                    </div>
                    <div class="pt-2 sm:pt-2.5 border-t border-slate-100/90 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs sm:text-sm font-black text-[#0B6E4F]">{{ $product->formatted_price }}</span>
                            <button type="button"
                                    onclick="copyProductUrl('{{ route('products.show', $product->slug) }}', '{{ addslashes($product->product_name) }}')"
                                    class="text-[10px] sm:text-xs font-semibold text-slate-400 hover:text-[#0B6E4F] transition"
                                    title="Bagikan tautan karya">
                                ↗ Bagikan
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                            <a href="{{ route('products.show', $product->slug) }}"
                               class="inline-flex items-center justify-center rounded-lg sm:rounded-xl bg-[#F8FAFC] hover:bg-slate-100 text-slate-700 border border-slate-200/80 py-1.5 px-2 text-[10px] sm:text-xs font-bold transition text-center hover:text-[#0B6E4F]">
                                <span>Detail</span>
                            </a>
                            <a href="{{ route('products.show', $product->slug) }}"
                               class="inline-flex items-center justify-center gap-1 rounded-lg sm:rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white py-1.5 px-2 text-[10px] sm:text-xs font-extrabold transition text-center active:scale-95 shadow-2xs">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                <span>Pesan</span>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
                Toko ini belum memiliki produk aktif.
            </div>
        @endforelse
    </div>
</div>

<script>
    function copyStoreLink() {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(window.location.href);
        }
        alert('Tautan toko berhasil disalin!');
    }

    function copyProductUrl(url, name) {
        if (navigator.share) {
            navigator.share({ title: name, url: url }).catch(() => {});
        } else {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url);
            }
            alert('Tautan produk "' + name + '" berhasil disalin!');
        }
    }
</script>
@endsection

