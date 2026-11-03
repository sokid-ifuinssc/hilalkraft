{{--
============================================================================
VIEW: DASHBOARD PEMILIK TOKO SISWA (seller/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan status verifikasi toko siswa, daftar produk milik sendiri
  (dengan tombol Tambah, Edit, Hapus), serta daftar pesanan masuk.
Algoritma:
  Melakukan iterasi `@forelse` pada koleksi `$products` dan `$orders` yang
  telah dipaginasi dari `SellerProductController@index`.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Dashboard Toko Siswa — hilalkraft')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <div class="rounded-3xl bg-gradient-to-r from-[#10B981] to-[#059669] p-6 sm:p-8 text-white flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold">
                {{ $store->seller_type_label }} • {{ $store->is_verified ? '✓ Terverifikasi Admin' : '⏳ Menunggu Verifikasi Admin' }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-2">{{ $store->store_name }}</h1>
            <p class="text-xs text-emerald-50">{{ $store->student_class }} • WA: {{ $store->contact_number }}</p>
        </div>
        <a href="{{ route('seller.products.create') }}" class="rounded-xl bg-white px-5 py-3 text-xs font-extrabold text-[#059669] shadow-md hover:bg-emerald-50 transition">
            + Upload Karya Produk Baru
        </a>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <h2 class="text-lg font-extrabold text-slate-900 mb-4">Katalog Karya Toko Saya ({{ $store->products_count }})</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase text-slate-500 bg-[#F8FAFC]">
                        <th class="py-3 px-4">Produk</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Harga</th>
                        <th class="py-3 px-4">Stok</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr>
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $product->product_name }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">{{ $product->category }}</td>
                            <td class="py-3.5 px-4 font-bold text-[#059669]">{{ $product->formatted_price }}</td>
                            <td class="py-3.5 px-4">{{ $product->stock }}</td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <a href="{{ route('seller.products.edit', $product) }}" class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-[#059669]">Edit</a>
                                <form action="{{ route('seller.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-600">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">Belum ada produk. Klik "Upload Karya Produk Baru" untuk memulai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

