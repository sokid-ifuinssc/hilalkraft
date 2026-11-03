{{--
============================================================================
VIEW: FORM UPLOAD & EDIT PRODUK (seller/product-form.blade.php)
============================================================================
Fungsi:
  Menyediakan satu form terpadu (Reusable Form) bagi Pemilik Toko (Siswa) untuk
  mengunggah produk baru maupun mengedit produk yang sudah ada.
Algoritma (Polymorphic Form State Binding):
  1. Memeriksa variabel `$product`: jika bernilai objek (mode Edit), arahkan
     action form ke `route('seller.products.update', $product)` dengan spoofing
     method `@method('PUT')` dan jadikan input foto opsional.
  2. Jika `$product` bernilai `null` (mode Tambah Baru), arahkan action ke
     `route('seller.products.store')` (POST) dan wajibkan unggah foto (`required`).
============================================================================
--}}
@extends('layouts.app')

@section('title', ($product ? 'Edit Produk' : 'Upload Karya Produk Baru') . ' — hilalkraft')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#059669]">Studio Kreator Siswa</span>
            <h1 class="text-2xl font-extrabold text-slate-900">
                {{ $product ? 'Edit Karya Produk: ' . $product->product_name : 'Upload Karya Produk Baru' }}
            </h1>
            <p class="text-sm text-slate-500">Toko: <strong>{{ $store->store_name }}</strong></p>
        </div>
        <a href="{{ route('seller.dashboard') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">
            ← Kembali ke Dashboard
        </a>
    </div>

    <form action="{{ $product ? route('seller.products.update', $product) : route('seller.products.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-5">
        @csrf
        @if($product)
            @method('PUT')
        @endif

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Produk Karya Siswa *</label>
            <input type="text" name="product_name" value="{{ old('product_name', $product?->product_name) }}" required
                   placeholder="Contoh: Kaligrafi Kayu Jati Ukir Laser Ayat Kursi"
                   class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Karya *</label>
                <select name="category" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
                    @php
                        $categoryOptions = isset($categories) && count($categories) > 0
                            ? $categories
                            : ['Kerajinan Tangan', 'Desain Grafis & Cetak', 'Kuliner & Olahan Pangan', 'Busana & Tekstil', 'Teknologi & IoT Siswa', 'Seni Kaligrafi & Islami'];
                    @endphp
                    @foreach($categoryOptions as $cat)
                        <option value="{{ $cat }}" @selected(old('category', $product?->category) === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Harga (Rp) *</label>
                <input type="number" name="price" min="1000" step="500" value="{{ old('price', $product?->price) }}" required
                       placeholder="75000"
                       class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Stok Tersedia *</label>
                <input type="number" name="stock" min="0" value="{{ old('stock', $product?->stock ?? 5) }}" required
                       class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Lengkap Produk *</label>
            <textarea name="description" rows="5" required
                      placeholder="Jelaskan bahan, ukuran, keunikan karya, dan proses pembuatannya oleh siswa SMK Plus Al Hilal..."
                      class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">{{ old('description', $product?->description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                Foto Produk Resolusi Tinggi {{ $product ? '(Kosongkan jika tidak diganti)' : '*' }}
            </label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" {{ $product ? '' : 'required' }}
                   class="w-full rounded-xl border border-dashed border-emerald-500/40 bg-emerald-50/30 px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-[#10B981] file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-[#059669]">
            <p class="text-[11px] text-slate-400 mt-1">Format JPG, PNG, atau WEBP (Maks. 3 MB).</p>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="{{ route('seller.dashboard') }}" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-emerald-500/25 hover:opacity-95 transition">
                {{ $product ? 'Simpan Perubahan' : 'Publikasikan Produk' }}
            </button>
        </div>
    </form>
</div>
@endsection
