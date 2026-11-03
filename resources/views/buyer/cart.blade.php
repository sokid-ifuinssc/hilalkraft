{{--
============================================================================
VIEW: KERANJANG & CHECKOUT PEMBELI (buyer/cart.blade.php & dashboard)
============================================================================
Fungsi:
  Menampilkan daftar item di keranjang session, menghitung total harga,
  dan memproses form checkout.
Algoritma:
  Mengakumulasi subtotal tiap item (`price * quantity`) secara linear O(N)
  untuk menampilkan Grand Total sebelum dikirim ke `BuyerOrderController@checkout`.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Keranjang Belanja — hilalkraft')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-extrabold text-slate-900 mb-6">Keranjang Belanja Anda</h1>

    @php $grandTotal = collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-7 space-y-4">
            @forelse($cart as $id => $item)
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 flex items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-bold text-[#059669]">{{ $item['store_name'] }}</span>
                        <h3 class="font-bold text-slate-900 text-sm">{{ $item['product_name'] }}</h3>
                        <p class="text-xs text-slate-500">{{ $item['quantity'] }} x Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                    </div>
                    <form action="{{ route('buyer.cart.remove', $id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Hapus</button>
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
                    Keranjang Anda masih kosong.
                </div>
            @endforelse
        </div>

        <div class="lg:col-span-5">
            <form action="{{ route('buyer.checkout') }}" method="POST" class="bg-white rounded-3xl border border-slate-200/80 p-6 space-y-4">
                @csrf
                <h2 class="text-base font-extrabold text-slate-900">Ringkasan Checkout</h2>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Pengiriman / Kelas Pengambilan *</label>
                    <input type="text" name="shipping_address" required placeholder="Contoh: Ruang Tamu SMK Plus Al Hilal / Alamat Rumah" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm"></textarea>
                </div>
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">Total Pembayaran</span>
                    <span class="text-lg font-extrabold text-[#059669]">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] py-3 text-sm font-bold text-white shadow-lg">
                    Buat Pesanan Sekarang
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

