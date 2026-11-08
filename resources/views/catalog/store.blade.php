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
            <span class="inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold">
                Etalase Resmi Siswa SMK Plus Al Hilal
            </span>
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
        <div class="flex items-center gap-2">
            <button type="button" onclick="copyStoreLink()" class="rounded-xl border border-white/40 bg-white/15 px-4 py-3 text-xs font-bold text-white hover:bg-white/25 transition">
                ↗ Bagikan Toko
            </button>
            <a href="{{ $store->whatsapp_url }}" target="_blank" class="rounded-xl bg-white px-5 py-3 text-xs font-extrabold text-[#059669] shadow-md hover:bg-emerald-50 transition">
                💬 Hubungi WhatsApp Toko
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 sm:gap-6">
        @forelse($store->products as $product)
            <article class="rounded-xl sm:rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-xs hover:shadow-lg transition flex flex-col">
                <a href="{{ route('products.show', $product->slug) }}" class="aspect-[4/3] bg-slate-100 block overflow-hidden">
                    <img src="{{ Str::startsWith($product->image, 'http') ? $product->image : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image)) }}"
                         alt="{{ $product->product_name }}"
                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
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
                    <div class="pt-2 sm:pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-extrabold text-[#059669]">{{ $product->formatted_price }}</span>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    onclick="copyProductUrl('{{ route('products.show', $product->slug) }}', '{{ addslashes($product->product_name) }}')"
                                    class="text-[10px] sm:text-xs font-semibold text-slate-500 hover:text-[#0B6E4F] transition"
                                    title="Bagikan produk">
                                ↗ Bagikan
                            </button>
                            <a href="{{ route('products.show', $product->slug) }}" class="text-[10px] sm:text-xs font-bold text-[#059669] hover:underline">Detail →</a>
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

