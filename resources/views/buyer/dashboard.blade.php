{{--
============================================================================
VIEW: DASHBOARD TRANSAKSI PEMBELI (buyer/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan daftar invoice transaksi yang telah dilakukan oleh Pembeli
  beserta tombol integrasi langsung ke WhatsApp Toko Siswa terkait.
Algoritma:
  Melakukan iterasi pada setiap `$order`, memformat pesan konfirmasi invoice
  dengan `urlencode()`, dan menautkannya ke accessor `$order->store->whatsapp_url`.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Riwayat Pembelian — hilalkraft')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
    <h1 class="text-2xl font-extrabold text-slate-900">Riwayat Pesanan Saya</h1>

    <div class="space-y-4">
        @forelse($orders as $order)
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 flex flex-wrap items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-bold text-[#059669]">{{ $order->invoice_number }} • {{ $order->store->store_name }}</span>
                    <p class="text-sm font-bold text-slate-900">Total: {{ $order->formatted_total }} (Status: {{ strtoupper($order->status) }})</p>
                    <p class="text-xs text-slate-500">Alamat: {{ $order->shipping_address }}</p>
                </div>
                @php
                    $waInvoice = urlencode("Assalamu'alaikum {$order->store->store_name}, saya ingin konfirmasi pesanan *{$order->invoice_number}* senilai {$order->formatted_total} di hilalkraft.");
                @endphp
                <a href="{{ $order->store->whatsapp_url }}?text={{ $waInvoice }}" target="_blank"
                   class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-4 py-2.5 text-xs font-bold text-white">
                    💬 Konfirmasi via WhatsApp Toko
                </a>
            </div>
        @empty
            <div class="bg-white rounded-3xl border border-slate-200 p-8 text-center text-sm text-slate-500">
                Belum ada riwayat transaksi.
            </div>
        @endforelse
    </div>
</div>
@endsection

