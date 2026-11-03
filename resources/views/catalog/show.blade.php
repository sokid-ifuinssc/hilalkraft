{{--
============================================================================
VIEW: DETAIL PRODUK & INTEGRASI WHATSAPP (catalog/show.blade.php)
============================================================================
Fungsi:
  Menampilkan foto produk resolusi tinggi, status stok, deskripsi lengkap,
  identitas toko siswa, tombol tambah ke keranjang, dan tombol kontak WhatsApp.
Algoritma:
  1. URL-Encoded WhatsApp Message Builder: Menyusun teks sapaan otomatis berisi
     nama pemilik toko, nama toko, nama produk, dan harga menggunakan `urlencode()`,
     lalu menggabungkannya dengan accessor `$product->store->whatsapp_url`.
  2. Conditional Stock & Role Guard: Hanya menampilkan tombol "+ Tambah ke Keranjang"
     apabila user login sebagai `buyer` dan `$product->stock > 0`.
============================================================================
--}}
@extends('layouts.app')

@section('title', $product->product_name . ' — hilalkraft')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Breadcrumb -->
    <nav class="text-xs text-slate-500 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-[#059669]">Beranda</a>
        <span>/</span>
        <a href="{{ route('home', ['category' => $product->category]) }}" class="hover:text-[#059669]">{{ $product->category }}</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold truncate">{{ $product->product_name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
        <!-- Foto Resolusi Tinggi -->
        <div class="lg:col-span-6">
            <div class="aspect-square rounded-2xl overflow-hidden bg-[#F8FAFC] border border-slate-100">
                <img
                    src="{{ Str::startsWith($product->image, 'http') ? $product->image : asset('storage/' . $product->image) }}"
                    alt="{{ $product->product_name }}"
                    class="w-full h-full object-cover"
                    onerror="this.src='https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=1200&q=85'"
                >
            </div>
        </div>

        <!-- Informasi Detail Produk, Stok, Keranjang & WhatsApp Pemilik Toko -->
        <div class="lg:col-span-6 flex flex-col justify-between space-y-6">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-bold text-[#059669]">
                        {{ $product->category }}
                    </span>
                    <span class="rounded-lg {{ $product->stock > 0 ? 'bg-emerald-100/70 text-emerald-800' : 'bg-rose-100 text-rose-700' }} px-3 py-1 text-xs font-bold">
                        {{ $product->stock > 0 ? 'Stok Tersedia: ' . $product->stock . ' unit' : 'Stok Habis' }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">
                    {{ $product->product_name }}
                </h1>

                <div class="text-3xl font-extrabold bg-gradient-to-r from-[#10B981] to-[#059669] bg-clip-text text-transparent">
                    {{ $product->formatted_price }}
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900 mb-2">Deskripsi Karya Produk</h2>
                    <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </div>
                </div>
            </div>

            <!-- Profil Toko (Siswa, Guru, atau Jurusan) & Tombol Aksi -->
            <div class="space-y-4 pt-6 border-t border-slate-100">
                <div class="rounded-2xl bg-[#F8FAFC] p-4 border border-slate-200/70 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-[#059669]">Diproduksi oleh {{ $product->store->seller_type_label }} Terverifikasi:</p>
                        <h3 class="text-base font-extrabold text-slate-900">{{ $product->store->store_name }}</h3>
                        <p class="text-xs text-slate-500">
                            Penanggung Jawab: {{ $product->store->owner->name }}
                            @if($product->store->student_class)
                                • {{ $product->store->student_class }}
                            @endif
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-[#059669] text-[11px] font-bold">
                        ✓ Terverifikasi
                    </span>
                </div>

                <!-- Tombol Aksi Pembelian & Kontak WhatsApp Penjual -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @php
                        $waMessage = urlencode("Assalamu'alaikum Kak {$product->store->owner->name} ({$product->store->store_name}), saya tertarik membeli produk *{$product->product_name}* seharga {$product->formatted_price} di hilalkraft SMK Plus Al Hilal.");
                    @endphp
                    <a href="{{ $product->store->whatsapp_url }}?text={{ $waMessage }}"
                       target="_blank"
                       rel="noopener"
                       class="flex items-center justify-center gap-2 rounded-xl border-2 border-[#10B981] bg-white px-5 py-3 text-sm font-bold text-[#059669] hover:bg-emerald-50 transition">
                        <span>💬 Hubungi WhatsApp Toko</span>
                    </a>

                    @auth
                        @if(auth()->user()->role === 'buyer' && $product->stock > 0)
                            <form action="{{ route('buyer.cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 hover:opacity-95 transition">
                                    <span>🛒 + Tambah ke Keranjang</span>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 hover:opacity-95 transition">
                            <span>Masuk untuk Membeli</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
