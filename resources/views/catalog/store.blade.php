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

@section('title', $store->store_name . ' — Toko Siswa hilalkraft')

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
        <a href="{{ $store->whatsapp_url }}" target="_blank" class="rounded-xl bg-white px-5 py-3 text-xs font-extrabold text-[#059669] shadow-md hover:bg-emerald-50 transition">
            💬 Hubungi WhatsApp Toko
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($store->products as $product)
            <article class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden shadow-sm hover:shadow-xl transition flex flex-col">
                <a href="{{ route('products.show', $product->slug) }}" class="aspect-[4/3] bg-slate-100 block overflow-hidden">
                    <img src="{{ Str::startsWith($product->image, 'http') ? $product->image : asset('storage/' . $product->image) }}"
                         alt="{{ $product->product_name }}"
                         class="w-full h-full object-cover"
                         onerror="this.src='https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=800&q=80'">
                </a>
                <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm line-clamp-2">
                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->product_name }}</a>
                    </h3>
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="font-extrabold text-[#059669]">{{ $product->formatted_price }}</span>
                        <a href="{{ route('products.show', $product->slug) }}" class="text-xs font-bold text-[#059669] hover:underline">Detail →</a>
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
@endsection

