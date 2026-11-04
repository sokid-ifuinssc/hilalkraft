{{--
============================================================================
VIEW: DASHBOARD PEMILIK TOKO & PRODUK SAYA (seller/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman kelola produk & profil pemilik toko sesuai referensi
  desain Figma `Kelola produk · Desktop.png` (Sidebar Ruang Karya Siswa,
  4 Kartu Ringkasan, Tabel Produk Saya dengan indikator 👁️ Lihat, ❤️ Suka,
  💬 Komentar, Daftar Pesanan, serta Form Edit Profil Lengkap Satu Toko Satu
  Akun bagi Siswa maupun Guru).
Algoritma:
  1. Menghitung jumlah produk dengan stok menipis (`stock <= 3`) untuk
     menampilkan banner peringatan stok secara otomatis.
  2. Fungsi JS `filterSellerTable(query)` menyaring baris produk secara
     real-time pada sisi klien berdasarkan nama atau kode produk.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Produk Saya — Dashboard Pemilik Toko hilalkraf SMK PAL')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $effectiveClasses = $classList->count() > 0 ? $classList : collect(['X', 'XI', 'XII']);
    $effectiveMajors  = $majorList->count() > 0 ? $majorList : collect([
        'Teknik Komputer dan Jaringan Telekomunikasi (TKJT)',
        'Teknik Otomotif (TO)',
        'Akuntansi dan Keuangan Lembaga (AKL)',
    ]);
    $lowStockProducts = $products->filter(fn($p) => $p->stock <= 3);
@endphp

<div class="min-h-screen flex bg-[#F8FAFC] text-[#0F291E]">

    <!-- Sidebar Kiri Sesuai Desain `Kelola produk · Desktop.png` -->
    <aside class="w-64 bg-white border-r border-slate-200/80 hidden lg:flex flex-col justify-between shrink-0 sticky top-0 h-screen p-6">
        <div class="space-y-6">
            <!-- Brand dengan Logo hilalkraf & Logo SMK Plus Al Hilal -->
            <a href="{{ route('home') }}" class="flex flex-col gap-2 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-2.5 py-1.5 shadow-sm">
                    <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                         alt="hilalkraf"
                         class="h-11 w-auto object-contain">
                    <span class="h-7 w-[1px] bg-slate-200"></span>
                    <img src="{{ asset('images/logo-smk-alhilal.png') }}"
                         alt="SMK Plus Al Hilal"
                         class="h-9 w-9 object-contain">
                </div>
                <div>
                    <span class="text-lg font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                    <span class="text-[10px] font-bold text-[#0B6E4F]">Inkubator Bisnis & Wirausaha Muda SMK PAL</span>
                </div>
            </a>

            <!-- Identitas Ruang Karya -->
            <div class="space-y-1">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">RUANG KARYA SISWA</p>
                <p class="text-xs font-extrabold text-[#0F291E] truncate">{{ $store->student_class ?: $store->store_name }}</p>
                <span class="inline-block rounded-full {{ $store->is_verified ? 'bg-[#EBF9F2] text-[#0B6E4F]' : 'bg-amber-50 text-amber-700' }} px-2.5 py-0.5 text-[10px] font-bold">
                    {{ $store->is_verified ? 'Toko terverifikasi' : 'Menunggu verifikasi' }}
                </span>
            </div>

            <!-- Menu Navigasi Sidebar -->
            <nav class="space-y-1 text-xs font-semibold">
                <a href="#section-ringkasan" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>⊞</span>
                    <span>Dashboard</span>
                </a>
                <a href="#section-produk" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 bg-[#EBF9F2] text-[#0B6E4F] font-bold">
                    <span>📦</span>
                    <span>Produk Saya</span>
                </a>
                <a href="#section-pesanan" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span class="flex items-center gap-3">
                        <span>🚚</span>
                        <span>Progres Pesanan</span>
                    </span>
                    <span class="rounded-full bg-[#EBF9F2] px-2 py-0.5 text-[10px] font-bold text-[#0B6E4F]">{{ $orders->count() }}</span>
                </a>
                <a href="#section-diskon" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span class="flex items-center gap-3">
                        <span>🏷️</span>
                        <span>Diskon & Bazar</span>
                    </span>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Promo</span>
                </a>
                <a href="{{ route('seller.reports') }}" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>📊</span>
                    <span>Laporan Penjualan</span>
                </a>
                <a href="#section-profil" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-slate-600 hover:bg-slate-50 transition">
                    <span>🏪</span>
                    <span>Profil Toko</span>
                </a>
            </nav>
        </div>

        <!-- Bagian Bawah Sidebar -->
        <div class="space-y-4">
            <div class="rounded-2xl bg-[#EBF9F2] p-4 space-y-1.5">
                <span class="w-6 h-6 rounded-full bg-white text-[#0B6E4F] flex items-center justify-center text-xs font-bold">🛟</span>
                <p class="text-xs font-bold text-[#0F291E] pt-1">Butuh bantuan?</p>
                <p class="text-[11px] text-slate-500">Tim hilalkraft siap membantu.</p>
                <a href="#section-profil" class="inline-block text-[11px] font-bold text-[#0B6E4F] hover:underline pt-0.5">
                    Hubungi pengelola →
                </a>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#0B6E4F] transition">↗ Kembali ke marketplace</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-rose-600 font-semibold hover:underline">Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Konten Utama -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar -->
        <header class="h-16 bg-white border-b border-slate-200/80 px-6 sm:px-10 flex items-center justify-between gap-4">
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <a href="{{ route('home') }}" class="lg:hidden font-bold text-[#0B6E4F]">← Marketplace</a>
                <span>Dashboard pemilik toko</span>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Produk Saya</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-[#0F291E] leading-none">{{ $user->name }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Pemilik Toko ({{ $store->seller_type_label }})</p>
                </div>
                <div class="w-8 h-8 rounded-full bg-[#D5F5E6] text-[#0B6E4F] font-extrabold text-xs flex items-center justify-center">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>
        </header>

        <!-- Isi Halaman -->
        <div class="p-6 sm:p-10 space-y-8 max-w-6xl w-full mx-auto">

            <!-- Judul & Tombol Tambah Produk Baru -->
            <div id="section-ringkasan" class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">Produk Saya</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Kelola karya, pantau stok, dan terus bertumbuh.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('seller.reports') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#CDEFE0] bg-[#EBF9F2] px-4 py-3 text-xs sm:text-sm font-semibold text-[#0B6E4F] hover:bg-[#D5F5E6] transition">
                        📊 Laporan Penjualan
                    </a>
                    <a href="{{ route('seller.products.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#0B6E4F] px-5 py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm">
                        <span>+ Tambah Produk Baru</span>
                    </a>
                </div>
            </div>

            <!-- 4 Kartu KPI -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Produk aktif</span>
                        <span class="text-[#0B6E4F]">📦</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ $store->products_count }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">Semua sudah tayang</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Total dilihat (👁️)</span>
                        <span class="text-[#0B6E4F]">👁️</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ number_format($store->views_count + $products->sum('views_count')) }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">Pengunjung & akun</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Suka & Komentar</span>
                        <span class="text-[#0B6E4F]">❤️</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">
                        {{ number_format($products->sum('likes_count')) }} <span class="text-sm font-semibold text-slate-400">/ {{ number_format($store->comments_count ?? 0) }} 💬</span>
                    </p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">Apresiasi karya</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200/80 p-5 space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Stok menipis</span>
                        <span class="text-amber-600">⚠️</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-extrabold text-[#0F291E]">{{ $lowStockProducts->count() }}</p>
                    <p class="text-[11px] font-semibold text-[#0B6E4F]">{{ $lowStockProducts->count() > 0 ? 'Perlu segera ditambah' : 'Stok aman terkendali' }}</p>
                </div>
            </div>

            <!-- Banner Info Stok Menipis -->
            @if($lowStockProducts->first())
                <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] px-4 py-3 text-xs text-[#0B6E4F] flex items-center gap-2.5 font-medium">
                    <span class="font-bold">ⓘ</span>
                    <span>{{ $lowStockProducts->first()->product_name }} tersisa {{ $lowStockProducts->first()->stock }} unit. Tambah stok agar pembeli tidak kehabisan.</span>
                </div>
            @endif

            <!-- Tabel Produk Saya -->
            <div id="section-produk" class="rounded-2xl bg-white border border-slate-200/80 overflow-hidden">
                <div class="p-5 space-y-4 border-b border-slate-100">
                    <div class="flex items-center gap-5 text-xs font-bold">
                        <span class="text-[#0B6E4F]">Semua produk ({{ $products->total() }})</span>
                        <span class="text-slate-400">Aktif ({{ $products->total() }})</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <input
                                type="search"
                                oninput="filterSellerTable(this.value)"
                                placeholder="Cari nama atau ID produk..."
                                class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] pl-9 pr-4 py-2 text-xs text-[#0F291E] focus:bg-white focus:border-[#0B6E4F] focus:outline-none"
                            >
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-5">NAMA PRODUK</th>
                                <th class="py-3 px-4">HARGA</th>
                                <th class="py-3 px-4">STOK</th>
                                <th class="py-3 px-4">STATUS</th>
                                <th class="py-3 px-4">INTERAKSI (👁️/❤️/💬)</th>
                                <th class="py-3 px-5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="seller-product-rows">
                            @forelse($products as $product)
                                <tr class="hover:bg-slate-50/70 transition seller-product-row" data-name="{{ strtolower($product->product_name . ' hk-00' . $product->id) }}">
                                    <td class="py-3.5 px-5">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ Str::startsWith($product->image, 'http') ? $product->image : (Str::startsWith($product->image, 'images/') ? asset($product->image) : asset('storage/' . $product->image)) }}"
                                                 alt="{{ $product->product_name }}"
                                                 class="w-11 h-11 rounded-xl object-cover bg-slate-100 shrink-0"
                                                 onerror="this.src='{{ asset('images/logo-hilalkraf.jpg') }}'">
                                            <div>
                                                <p class="font-bold text-[#0F291E] text-xs sm:text-sm">{{ $product->product_name }}</p>
                                                <p class="text-[11px] text-slate-400">HK-{{ str_pad((string) $product->id, 3, '0', STR_PAD_LEFT) }} • {{ $product->category }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-[#0F291E]">{{ $product->formatted_price }}</td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-bold text-[#0F291E]">{{ $product->stock }}</span>
                                        @if($product->stock <= 3)
                                            <span class="block text-[10px] font-semibold text-amber-600">Stok menipis</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="rounded-md bg-[#EBF9F2] px-2.5 py-1 text-[10px] font-bold text-[#0B6E4F]">Aktif</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-medium text-slate-600">
                                        👁️ {{ $product->views_count ?? 0 }} • ❤️ {{ $product->likes_count ?? 0 }} • 💬 {{ $product->comments_count ?? 0 }}
                                    </td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap space-x-3">
                                        <a href="{{ route('seller.products.edit', $product) }}" class="font-bold text-[#0B6E4F] hover:underline">Edit</a>
                                        <form action="{{ route('seller.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Hapus produk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-bold text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                        <a href="{{ route('products.show', $product->slug) }}" class="text-slate-400 hover:text-[#0F291E]">View ↗</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-slate-400">
                                        Belum ada produk di toko ini. Klik <strong>+ Tambah Produk Baru</strong> untuk mulai mengunggah karya.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Menampilkan {{ $products->count() }} dari {{ $products->total() }} produk</span>
                    <div>{{ $products->links() }}</div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SKEMA PROGRES PESANAN PEMILIK TOKO (PESAN -> KEMAS -> KIRIM -> SAMPAI)     -->
            <!-- ========================================================================= -->
            <div id="section-pesanan" class="rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EBF9F2] px-3 py-1 text-[11px] font-bold text-[#0B6E4F] mb-2">
                            🚚 Skema Progres Pesanan 4 Tahap
                        </span>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Kelola Progres Pesanan Pembeli</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Walaupun transaksi dan negosiasi terhubung langsung ke WhatsApp, perbarui status progres barang mulai dari <strong>1. Pesan</strong> → <strong>2. Kemas</strong> → <strong>3. Kirim (Dalam Perjalanan)</strong> → <strong>4. Sampai</strong> agar pembeli dapat memantau progres secara realtime dan memberikan rating bintang ★.
                        </p>
                    </div>

                    <!-- Filter Tahap Progres -->
                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                        <button type="button" onclick="filterSellerOrders('all')" class="seller-order-tab rounded-xl bg-[#0B6E4F] text-white px-3 py-1.5 transition" data-tab="all">
                            Semua ({{ $orders->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('pesan')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="pesan">
                            1. Pesan ({{ $orders->filter(fn($o) => $o->effective_progress === 'pesan')->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('kemas')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="kemas">
                            2. Kemas ({{ $orders->filter(fn($o) => $o->effective_progress === 'kemas')->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('kirim')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="kirim">
                            3. Kirim ({{ $orders->filter(fn($o) => $o->effective_progress === 'kirim')->count() }})
                        </button>
                        <button type="button" onclick="filterSellerOrders('sampai')" class="seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition" data-tab="sampai">
                            4. Sampai ({{ $orders->filter(fn($o) => $o->effective_progress === 'sampai')->count() }})
                        </button>
                    </div>
                </div>

                @php
                    $stagesMeta = [
                        1 => ['key' => 'pesan', 'label' => '1. Dipesan', 'desc' => 'Pesanan masuk / WA'],
                        2 => ['key' => 'kemas', 'label' => '2. Dikemas', 'desc' => 'Disiapkan penjual'],
                        3 => ['key' => 'kirim', 'label' => '3. Dikirim', 'desc' => 'Dalam perjalanan / COD'],
                        4 => ['key' => 'sampai', 'label' => '4. Sampai', 'desc' => 'Diterima & beri ★'],
                    ];
                @endphp

                <div class="space-y-4" id="seller-orders-container">
                    @forelse($orders as $order)
                        @php
                            $currentStep = $order->progress_step;
                            $effProgress = $order->effective_progress;
                            $buyerSubtypeLabel = $store->getBuyerSubtypeLabel($order->buyer?->user_subtype ?? 'umum');
                            $buyerPhoneClean = preg_replace('/[^0-9]/', '', $order->buyer_phone ?? $order->buyer?->phone ?? '');
                            if (str_starts_with($buyerPhoneClean, '0')) {
                                $buyerPhoneClean = '62' . substr($buyerPhoneClean, 1);
                            }
                            $waBuyerText = urlencode("Halo Kak {$order->buyer?->name}, kami dari *{$store->store_name}* (hilalkraf SMK Plus Al Hilal) mengabarkan progres pesanan *{$order->order_code}* saat ini: *{$order->progress_label}*.");
                        @endphp
                        <div class="seller-order-card rounded-2xl border border-slate-200/90 bg-[#F8FAFC]/60 p-5 space-y-4 transition hover:border-emerald-500/40" data-progress="{{ $effProgress }}">
                            <!-- Baris Atas: Kode Pesanan, Pembeli, Jenis Pembeli, & Status Badge -->
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/70 pb-3.5">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-extrabold text-sm text-[#0F291E]">#{{ $order->order_code }}</span>
                                        <span class="rounded-full bg-white border border-slate-200 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">
                                            👤 {{ $order->buyer?->name ?? 'Pembeli' }}
                                        </span>
                                        <span class="rounded-full bg-[#EBF9F2] px-2.5 py-0.5 text-[10px] font-bold text-[#0B6E4F]">
                                            🏷️ {{ $buyerSubtypeLabel }}
                                        </span>
                                        @if($order->is_bazaar_offline || $order->bazaar_voucher_code)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800">
                                                🎪 Acara Bazar SMK ({{ $order->bazaar_voucher_code ?? 'Hadir di Bazar' }})
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        📍 {{ $order->shipping_address }} • 📞 {{ $order->buyer_phone }} • 🕒 {{ $order->created_at?->format('d M Y, H:i') }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($buyerPhoneClean)
                                        <a href="https://wa.me/{{ $buyerPhoneClean }}?text={{ $waBuyerText }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-emerald-700 transition">
                                            <span>💬 Hubungi WA Pembeli</span>
                                        </a>
                                    @endif
                                    <span class="rounded-xl px-3 py-1.5 text-[11px] font-extrabold {{ $effProgress === 'sampai' ? 'bg-emerald-100 text-emerald-800' : ($effProgress === 'dibatalkan' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $order->progress_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Visual Stepper 4 Tahap: Pesan -> Kemas -> Kirim -> Sampai -->
                            @if($effProgress !== 'dibatalkan')
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                                    @foreach($stagesMeta as $stepNum => $meta)
                                        @php
                                            $isDone = $currentStep >= $stepNum;
                                            $isCurrent = $currentStep === $stepNum;
                                        @endphp
                                        <div class="rounded-xl p-3 border transition {{ $isDone ? 'bg-[#EBF9F2] border-[#0B6E4F]/30 text-[#0F291E]' : 'bg-white border-slate-200/80 text-slate-400' }}">
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span class="w-6 h-6 rounded-full text-[11px] font-extrabold flex items-center justify-center {{ $isDone ? 'bg-[#0B6E4F] text-white' : 'bg-slate-100 text-slate-400' }}">
                                                    {{ $isDone ? '✓' : $stepNum }}
                                                </span>
                                                @if($isCurrent)
                                                    <span class="text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#0B6E4F] text-white">Tahap Saat Ini</span>
                                                @endif
                                            </div>
                                            <p class="text-xs font-extrabold {{ $isDone ? 'text-[#0F291E]' : 'text-slate-400' }}">{{ $meta['label'] }}</p>
                                            <p class="text-[10px] {{ $isDone ? 'text-[#0B6E4F]' : 'text-slate-400' }}">{{ $meta['desc'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-2.5 text-xs font-bold text-rose-700">
                                    ✕ Pesanan ini telah dibatalkan.
                                </div>
                            @endif

                            <!-- Daftar Item & Rincian Diskon Pembeli / Bazar -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start bg-white rounded-xl p-4 border border-slate-200/70">
                                <div class="lg:col-span-7 space-y-2">
                                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Item Karya yang Dipesan</p>
                                    @foreach($order->items as $item)
                                        <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100 last:border-0">
                                            <div>
                                                <span class="font-bold text-[#0F291E]">{{ $item->product?->product_name ?? 'Produk Karya' }}</span>
                                                <span class="text-slate-400 ml-1">× {{ $item->quantity }}</span>
                                            </div>
                                            <span class="font-semibold text-slate-700">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach

                                    @if($order->notes)
                                        <p class="text-[11px] text-slate-500 pt-1">
                                            <strong>Catatan Pembeli:</strong> "{{ $order->notes }}"
                                        </p>
                                    @endif
                                    @if($order->progress_note)
                                        <p class="text-[11px] text-[#0B6E4F] font-semibold pt-0.5">
                                            <strong>Info Pengiriman Toko:</strong> "{{ $order->progress_note }}"
                                        </p>
                                    @endif
                                </div>

                                <div class="lg:col-span-5 bg-[#F8FAFC] rounded-xl p-3.5 border border-slate-200/60 space-y-1.5 text-xs">
                                    <div class="flex justify-between text-slate-500">
                                        <span>Subtotal Harga Karya</span>
                                        <span class="font-semibold">{{ $order->formatted_subtotal_price }}</span>
                                    </div>
                                    @if(($order->buyer_type_discount_amount ?? 0) > 0)
                                        <div class="flex justify-between text-emerald-700 font-semibold">
                                            <span>Diskon {{ $buyerSubtypeLabel }} ({{ $order->buyer_type_discount_percent }}%)</span>
                                            <span>- Rp {{ number_format($order->buyer_type_discount_amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if(($order->bazaar_discount_amount ?? 0) > 0)
                                        <div class="flex justify-between text-amber-700 font-semibold">
                                            <span>Diskon Bazar SMK ({{ $order->bazaar_discount_percent }}%)</span>
                                            <span>- Rp {{ number_format($order->bazaar_discount_amount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    <div class="flex justify-between text-sm font-extrabold text-[#0F291E] pt-1.5 border-t border-slate-200">
                                        <span>Total Transaksi</span>
                                        <span class="text-[#0B6E4F]">{{ $order->formatted_total_price }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tampilan Rating Bintang dari Pembeli (Jika Sudah Memberi Rating) -->
                            @if($order->buyer_rating)
                                <div class="rounded-xl bg-amber-50/90 border border-amber-200 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold text-amber-700">
                                            @for($i = 1; $i <= 5; $i++)
                                                {{ $i <= $order->buyer_rating ? '★' : '☆' }}
                                            @endfor
                                            ({{ $order->buyer_rating }}/5)
                                        </span>
                                        <span class="text-xs font-bold text-[#0F291E]">Rating dari {{ $order->buyer?->name }}:</span>
                                        <span class="text-xs text-slate-600 italic">"{{ $order->buyer_review ?: 'Terima kasih, karya sangat bagus!' }}"</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-amber-700">Dinilai {{ $order->rated_at?->diffForHumans() }}</span>
                                </div>
                            @endif

                            <!-- Form Kontrol Ubah Tahap Progres oleh Pemilik Toko -->
                            <form action="{{ route('seller.orders.status', $order) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
                                @csrf
                                @method('PATCH')
                                <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                                    <div class="sm:w-56">
                                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Tahap Progres Barang</label>
                                        <select name="progress_status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                            <option value="pesan" @selected($effProgress === 'pesan')>1. Pesan (Pesanan Diterima)</option>
                                            <option value="kemas" @selected($effProgress === 'kemas')>2. Kemas (Sedang Dikemas)</option>
                                            <option value="kirim" @selected($effProgress === 'kirim')>3. Kirim (Dalam Perjalanan / COD)</option>
                                            <option value="sampai" @selected($effProgress === 'sampai')>4. Sampai (Diterima Pembeli)</option>
                                            <option value="dibatalkan" @selected($effProgress === 'dibatalkan')>✕ Batalkan Pesanan</option>
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Catatan Progres / Pengiriman (Opsional)</label>
                                        <input type="text" name="progress_note" value="{{ $order->progress_note }}" placeholder="Contoh: Barang sedang dikemas rapi / Diantar ke Stand Bazar SMK..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs text-[#0F291E] focus:border-[#0B6E4F] focus:outline-none">
                                    </div>
                                </div>
                                <div class="flex items-end gap-2 pt-2 sm:pt-4">
                                    <button type="submit" class="w-full sm:w-auto rounded-xl bg-[#0B6E4F] px-4 py-2 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                                        ✓ Simpan Progres
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 p-10 text-center space-y-2">
                            <p class="text-sm font-bold text-[#0F291E]">Belum Ada Pesanan Masuk</p>
                            <p class="text-xs text-slate-400 max-w-md mx-auto">
                                Saat pembeli memesan karya melalui keranjang atau tombol <strong>Pesan & Hubungi Penjual</strong> di halaman produk, progres pesanan (Pesan → Kemas → Kirim → Sampai) akan otomatis tampil di sini.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- SKEMA DISKON JENIS PEMBELI & ACARA BAZAR SMK OLEH PEMILIK TOKO            -->
            <!-- ========================================================================= -->
            <div id="section-diskon" class="rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-6">
                <div class="border-b border-slate-100 pb-4 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200/80 px-3 py-1 text-[11px] font-bold text-amber-800 mb-2">
                            🏷️ Skema Diskon Pembeli & Bazar SMK Plus Al Hilal
                        </span>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Atur Diskon Berdasarkan Jenis Pembeli & Event Bazar</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Penjual dapat memberikan persentase diskon otomatis untuk jenis pembeli (<strong>Pelajar</strong>, <strong>Keluarga SMK Plus Al Hilal</strong>, <strong>Alumni</strong>, atau <strong>Umum</strong>) serta diskon tambahan bagi pembeli yang hadir langsung pada <strong>Acara Bazar / Event SMK</strong>.
                        </p>
                    </div>
                </div>

                <form action="{{ route('seller.discounts.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">🎓 Diskon Pelajar</span>
                                <span class="text-[10px] font-bold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">Siswa/Pelajar</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan khusus bagi pembeli berstatus Pelajar.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_pelajar" min="0" max="90" value="{{ old('discount_pelajar', $store->discount_pelajar ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">🏫 Keluarga SMK</span>
                                <span class="text-[10px] font-bold text-[#0B6E4F] bg-[#EBF9F2] px-2 py-0.5 rounded-full">Warga SMK PAL</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan khusus Guru, Staf & Keluarga Besar SMK Plus Al Hilal.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_keluarga" min="0" max="90" value="{{ old('discount_keluarga', $store->discount_keluarga ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">🤝 Diskon Alumni</span>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-200/70 px-2 py-0.5 rounded-full">Alumni</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan harga bagi pembeli dari Ikatan Alumni SMK PAL.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_alumni" min="0" max="90" value="{{ old('discount_alumni', $store->discount_alumni ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-[#F8FAFC] border border-slate-200/80 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-[#0F291E]">🌐 Pembeli Umum</span>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-200/70 px-2 py-0.5 rounded-full">Masyarakat</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Potongan promo reguler untuk pembeli masyarakat umum.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_umum" min="0" max="90" value="{{ old('discount_umum', $store->discount_umum ?? 0) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-slate-400">%</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-amber-900">🎪 Ekstra Bazar</span>
                                <span class="text-[10px] font-bold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-full">Hadir di Bazar</span>
                            </div>
                            <p class="text-[11px] text-amber-800/80">Tambahan diskon toko saat pembeli datang ke Stand Bazar SMK.</p>
                            <div class="relative pt-1">
                                <input type="number" name="discount_bazar_extra" min="0" max="90" value="{{ old('discount_bazar_extra', $store->discount_bazar_extra ?? 0) }}" required class="w-full rounded-xl border border-amber-300 bg-white px-3.5 py-2 pr-8 text-sm font-extrabold text-amber-800 focus:border-amber-600 focus:outline-none">
                                <span class="absolute right-3 top-3 text-xs font-extrabold text-amber-600">%</span>
                            </div>
                        </div>
                    </div>

                    @if(isset($activeBazaarEvents) && $activeBazaarEvents->count() > 0)
                        <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] p-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="space-y-0.5">
                                <p class="text-xs font-extrabold text-[#0F291E]">
                                    🎪 Acara Bazar & Event Sekolah yang Sedang Aktif:
                                </p>
                                @foreach($activeBazaarEvents as $ev)
                                    <p class="text-[11px] text-[#0B6E4F]">
                                        • <strong>{{ $ev->title }}</strong> ({{ $ev->location ?? 'SMK Plus Al Hilal' }}) — Kode Voucher: <span class="font-mono font-extrabold bg-white px-2 py-0.5 rounded border border-emerald-300">{{ $ev->voucher_code }}</span> (Diskon Event {{ $ev->discount_percent }}% + Diskon Ekstra Toko Anda {{ $store->discount_bazar_extra ?? 0 }}%)
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#08583F] transition shadow-sm">
                            ✓ Simpan Skema Diskon Toko
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bagian Profil Toko & Edit Profil Lengkap Pemilik Toko (Satu Toko Satu Akun) -->
            <div id="section-profil" class="rounded-2xl bg-white border border-slate-200/80 p-6 sm:p-8 space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base sm:text-lg font-extrabold text-[#0F291E]">Profil Toko & Identitas Lengkap Pemilik Toko (1 Akun 1 Toko)</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Lengkapi Nama, NIS/NISN (Siswa) atau NUPTK (Guru), Kelas & Jurusan dari database, Alamat Lengkap (Desa, Kecamatan, Kabupaten, Kode Pos), serta Nomor HP.
                    </p>
                </div>

                <form action="{{ route('seller.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Status Pemilik Toko *</label>
                            <select name="seller_type" onchange="toggleSellerProfileType(this.value)" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-[#0B6E4F] focus:border-[#0B6E4F] focus:outline-none">
                                <option value="siswa" @selected(($store->seller_type ?? 'siswa') === 'siswa')>Siswa SMK Plus Al Hilal</option>
                                <option value="guru" @selected(($store->seller_type ?? '') === 'guru')>Guru SMK Plus Al Hilal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Nama Lengkap *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#0F291E] mb-1">Nomor HP / WhatsApp *</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? $store->contact_number) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        </div>
                    </div>

                    <!-- Atribut Siswa -->
                    <div id="profile-siswa-row" class="{{ ($store->seller_type ?? 'siswa') === 'guru' ? 'hidden' : '' }} grid grid-cols-1 sm:grid-cols-3 gap-3.5 rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">NIS / NISN Siswa *</label>
                            <input type="text" name="nis_nisn" value="{{ old('nis_nisn', $user->nis_nisn) }}" placeholder="Nomor NIS/NISN" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kelas (Database) *</label>
                            <select name="class_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">Pilih Kelas</option>
                                @foreach($effectiveClasses as $cl)
                                    <option value="{{ $cl }}" @selected(old('class_name', $user->class_name) === $cl)>{{ $cl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan (Database) *</label>
                            <select name="major_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">Pilih Jurusan</option>
                                @foreach($effectiveMajors as $mj)
                                    <option value="{{ $mj }}" @selected(old('major_name', $user->major_name) === $mj)>{{ $mj }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Atribut Guru -->
                    <div id="profile-guru-row" class="{{ ($store->seller_type ?? 'siswa') === 'guru' ? '' : 'hidden' }} grid grid-cols-1 sm:grid-cols-2 gap-3.5 rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">NUPTK Guru *</label>
                            <input type="text" name="nuptk" value="{{ old('nuptk', $user->nuptk) }}" placeholder="Nomor NUPTK" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jurusan / Unit (Database)</label>
                            <select name="major_name" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                                <option value="">Pilih Jurusan / Unit</option>
                                @foreach($effectiveMajors as $mj)
                                    <option value="{{ $mj }}" @selected(old('major_name', $user->major_name) === $mj)>{{ $mj }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Desa / Kelurahan *</label>
                            <input type="text" name="desa" value="{{ old('desa', $user->desa) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kecamatan *</label>
                            <input type="text" name="kecamatan" value="{{ old('kecamatan', $user->kecamatan) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kabupaten / Kota *</label>
                            <input type="text" name="kabupaten" value="{{ old('kabupaten', $user->kabupaten) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Kode Pos *</label>
                            <input type="text" name="kode_pos" value="{{ old('kode_pos', $user->kode_pos) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Detail Alamat</label>
                            <input type="text" name="address_detail" value="{{ old('address_detail', $user->address_detail) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Toko *</label>
                            <input type="text" name="store_name" value="{{ old('store_name', $store->store_name) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Deskripsi Toko *</label>
                            <input type="text" name="description" value="{{ old('description', $store->description) }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="rounded-xl bg-[#0B6E4F] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#08583F] transition">
                            ✓ Simpan Profil & Toko
                        </button>
                    </div>
                </form>
            </div>

            <footer class="text-[11px] text-slate-400 pt-2">
                &copy; {{ date('Y') }} hilalkraft • Karya hebat dimulai dari langkah kecil.
            </footer>
        </div>
    </div>
</div>

<script>
    function toggleSellerProfileType(val) {
        document.getElementById('profile-siswa-row').classList.toggle('hidden', val !== 'siswa');
        document.getElementById('profile-guru-row').classList.toggle('hidden', val !== 'guru');
    }

    function filterSellerTable(q) {
        const term = (q || '').toLowerCase();
        document.querySelectorAll('.seller-product-row').forEach(row => {
            const name = row.getAttribute('data-name') || '';
            row.style.display = name.includes(term) ? '' : 'none';
        });
    }

    function filterSellerOrders(stage) {
        document.querySelectorAll('.seller-order-card').forEach(card => {
            const p = card.getAttribute('data-progress');
            card.style.display = (stage === 'all' || p === stage) ? '' : 'none';
        });
        document.querySelectorAll('.seller-order-tab').forEach(btn => {
            const active = btn.getAttribute('data-tab') === stage;
            btn.className = active
                ? 'seller-order-tab rounded-xl bg-[#0B6E4F] text-white px-3 py-1.5 transition'
                : 'seller-order-tab rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 px-3 py-1.5 transition';
        });
    }
</script>
@endsection
