{{--
============================================================================
VIEW: KELOLA EVENT BAZAR, PESERTA TOKO, QR BARCODE, RANKING & DOORPRIZE
============================================================================
Halaman manajemen terpadu untuk satu acara bazar tertentu (`/panel-admin/bazar/{id}`):
  1. Konfigurasi poin bazar & cetak QR Kehadiran Acara (pintu masuk)
  2. Pemilihan peserta toko oleh Admin/Super Admin + persetujuan pengajuan toko
  3. Cetak QR Kunjungan Stand untuk masing-masing stand toko peserta
  4. Papan Ranking Toko KHUSUS event bazar (Kunjungan Stand, Transaksi, Omzet, View, Rating)
  5. Leaderboard Pengunjung & Penetapan Pemenang Doorprize Panitia
============================================================================
--}}
@extends('layouts.panel')

@section('title', 'Kelola Bazar: ' . $bazaarEvent->title . ' — hilalkraf')
@section('panel_title', 'Kelola Event Bazar')

@section('panel')
    <!-- Notifikasi -->
    @if(session('success'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs font-bold text-emerald-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs font-bold text-rose-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Header Acara & Aksi Cetak -->
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a href="{{ route('admin.dashboard') }}#sec-diskon-bazar" class="text-slate-400 hover:text-[#0B6E4F] font-semibold inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span>Daftar Acara Bazar</span>
                    </a>
                    <span class="text-slate-300">/</span>
                    <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $bazaarEvent->isActiveNow() ? 'bg-emerald-100 text-[#0B6E4F]' : 'bg-slate-100 text-slate-600' }}">
                        {{ $bazaarEvent->isActiveNow() ? 'Sedang Aktif' : 'Nonaktif / Di Luar Jadwal' }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-[#0F291E]">{{ $bazaarEvent->title }}</h1>
                <p class="text-xs text-slate-500">
                    Lokasi: <strong>{{ $bazaarEvent->location ?: 'SMK Plus Al Hilal' }}</strong>
                    &bull; Voucher: <span class="font-mono font-bold text-[#0B6E4F]">{{ $bazaarEvent->voucher_code }}</span> ({{ $bazaarEvent->discount_percent }}%)
                    &bull; Periode: {{ $bazaarEvent->start_date?->format('d M Y') ?? 'Mulai Sekarang' }} s/d {{ $bazaarEvent->end_date?->format('d M Y') ?? 'Seterusnya' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.bazaar.print-qr', $bazaarEvent) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-3.5 py-2 text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak QR Hadir Bazar</span>
                </a>
                <a href="{{ route('admin.bazaar.print-all-qr', $bazaarEvent) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white px-3.5 py-2 text-xs font-bold shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Cetak Semua QR Stand</span>
                </a>
                <a href="{{ route('admin.bazaar.export', ['bazaarEvent' => $bazaarEvent, 'type' => 'stores']) }}"
                   class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3 py-2 text-xs font-bold transition">
                    <span>CSV Toko</span>
                </a>
                <a href="{{ route('admin.bazaar.export', ['bazaarEvent' => $bazaarEvent, 'type' => 'visitors']) }}"
                   class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3 py-2 text-xs font-bold transition">
                    <span>CSV Doorprize</span>
                </a>
            </div>
        </div>

        <!-- Ringkasan KPI Event Bazar -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-2">
            <div class="rounded-xl bg-[#F8FAFC] border border-slate-200/70 p-3.5">
                <p class="text-[10px] font-bold uppercase text-slate-400">Toko Peserta</p>
                <p class="text-xl font-black text-[#0F291E] mt-0.5">{{ $summary['approved_stores'] }}</p>
                <p class="text-[10px] font-semibold text-amber-700">{{ $summary['pending_stores'] }} pengajuan baru</p>
            </div>
            <div class="rounded-xl bg-[#F8FAFC] border border-slate-200/70 p-3.5">
                <p class="text-[10px] font-bold uppercase text-slate-400">Scan Hadir Bazar</p>
                <p class="text-xl font-black text-[#0B6E4F] mt-0.5">{{ number_format($summary['total_checkins']) }}</p>
                <p class="text-[10px] text-slate-400">+{{ $bazaarEvent->points_checkin }} poin/hadir</p>
            </div>
            <div class="rounded-xl bg-[#F8FAFC] border border-slate-200/70 p-3.5">
                <p class="text-[10px] font-bold uppercase text-slate-400">Kunjungan Stand</p>
                <p class="text-xl font-black text-blue-700 mt-0.5">{{ number_format($summary['total_stands']) }}</p>
                <p class="text-[10px] text-slate-400">+{{ $bazaarEvent->points_stand_visit }} poin/stand</p>
            </div>
            <div class="rounded-xl bg-[#F8FAFC] border border-slate-200/70 p-3.5">
                <p class="text-[10px] font-bold uppercase text-slate-400">Transaksi Bazar</p>
                <p class="text-xl font-black text-[#0F291E] mt-0.5">{{ number_format($summary['total_orders']) }}</p>
                <p class="text-[10px] text-slate-400">+{{ $bazaarEvent->points_transaction }} poin/transaksi</p>
            </div>
            <div class="rounded-xl bg-[#F8FAFC] border border-slate-200/70 p-3.5">
                <p class="text-[10px] font-bold uppercase text-slate-400">Omzet Bazar</p>
                <p class="text-lg font-black text-[#0B6E4F] mt-0.5">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-slate-400">Khusus acara ini</p>
            </div>
            <div class="rounded-xl bg-amber-50/70 border border-amber-200 p-3.5">
                <p class="text-[10px] font-bold uppercase text-amber-700">Peserta Poin</p>
                <p class="text-xl font-black text-amber-900 mt-0.5">{{ number_format($summary['total_visitors']) }}</p>
                <p class="text-[10px] font-semibold text-amber-700">{{ $summary['winners_count'] }} pemenang doorprize</p>
            </div>
        </div>
    </div>

    <!-- Baris 2: Pengaturan Poin & Kartu Barcode Kehadiran Event -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kartu Barcode Kehadiran -->
        <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 flex flex-col sm:flex-row items-center gap-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-3 shrink-0 shadow-2xs">
                {!! $eventQrSvg !!}
            </div>
            <div class="space-y-2.5 text-center sm:text-left min-w-0 flex-1">
                <span class="inline-block rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold text-[#0B6E4F]">
                    QR KEHADIRAN PINTU MASUK
                </span>
                <h3 class="text-sm font-black text-[#0F291E]">Barcode Check-in Pengunjung</h3>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Cetak dan tempel di pintu masuk acara bazar. Pembeli yang memiliki akun scan barcode ini sekali saat tiba untuk mendapat <strong>+{{ $bazaarEvent->points_checkin }} Poin</strong>.
                </p>
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs">
                    <span class="font-mono font-black bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg text-[#0F291E]">
                        {{ $bazaarEvent->short_code }}
                    </span>
                    <a href="{{ route('admin.bazaar.print-qr', $bazaarEvent) }}" target="_blank"
                       class="rounded-lg bg-[#0B6E4F] hover:bg-[#08583F] text-white px-3 py-1 font-bold text-xs transition">
                        Cetak Poster QR
                    </a>
                </div>
            </div>
        </div>

        <!-- Pengaturan Poin & Kebijakan Pengajuan Toko -->
        <form action="{{ route('admin.bazaar.settings', $bazaarEvent) }}" method="POST"
              class="lg:col-span-7 rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-4">
            @csrf
            @method('PATCH')
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-[#0F291E]">Konfigurasi Poin Doorprize & Pengajuan Toko</h3>
                    <p class="text-[11px] text-slate-400">Sesuaikan poin yang diperoleh pembeli pada acara bazar ini.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl bg-[#F8FAFC] border border-slate-200 p-3">
                    <label class="block font-bold text-slate-700 mb-1">Poin Hadir Bazar</label>
                    <input type="number" name="points_checkin" min="0" max="10000" required
                           value="{{ old('points_checkin', $bazaarEvent->points_checkin ?? 50) }}"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-extrabold text-[#0B6E4F]">
                    <span class="block text-[10px] text-slate-400 mt-1">1x saat scan hadir acara</span>
                </div>
                <div class="rounded-xl bg-[#F8FAFC] border border-slate-200 p-3">
                    <label class="block font-bold text-slate-700 mb-1">Poin Kunjungan Stand</label>
                    <input type="number" name="points_stand_visit" min="0" max="10000" required
                           value="{{ old('points_stand_visit', $bazaarEvent->points_stand_visit ?? 20) }}"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-extrabold text-blue-700">
                    <span class="block text-[10px] text-slate-400 mt-1">Per stand toko yang discan</span>
                </div>
                <div class="rounded-xl bg-[#F8FAFC] border border-slate-200 p-3">
                    <label class="block font-bold text-slate-700 mb-1">Poin Transaksi Bazar</label>
                    <input type="number" name="points_transaction" min="0" max="10000" required
                           value="{{ old('points_transaction', $bazaarEvent->points_transaction ?? 30) }}"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-extrabold text-amber-700">
                    <span class="block text-[10px] text-slate-400 mt-1">Per pesanan bazar sukses</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="allow_self_register" value="1"
                           @checked(old('allow_self_register', $bazaarEvent->allow_self_register))
                           class="rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F]">
                    <span>Izinkan toko mengajukan diri untuk mengikuti acara bazar ini</span>
                </label>

                <button type="submit" class="rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-4 py-2 text-xs font-bold shadow-xs transition">
                    Simpan Pengaturan Poin
                </button>
            </div>
        </form>
    </div>

    <!-- ===================================================================== -->
    <!-- BAGIAN 1: PEMILIHAN TOKO OLEH ADMIN & PERSETUJUAN PENGAJUAN TOKO      -->
    <!-- ===================================================================== -->
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-6">
        <div class="border-b border-slate-100 pb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base sm:text-lg font-black text-[#0F291E]">Peserta Toko Acara Bazar & Cetak QR Stand</h2>
                <p class="text-xs text-slate-500">Pilih langsung toko yang berpartisipasi atau setujui/tolak pengajuan mandiri dari pemilik toko.</p>
            </div>
        </div>

        <!-- Form Pilih Langsung Toko oleh Super Admin / Admin -->
        @if($availableStores->count() > 0)
            <form action="{{ route('admin.bazaar.participants.add', $bazaarEvent) }}" method="POST"
                  class="rounded-2xl bg-[#F8FAFC] border border-slate-200 p-4 sm:p-5 space-y-3">
                @csrf
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="text-xs font-extrabold text-[#0F291E]">+ Pilih Toko untuk Diikutsertakan Langsung (Otomatis Disetujui):</label>
                    <input type="search" oninput="filterAddStoreCheckboxes(this.value)" placeholder="Cari nama toko / kelas..."
                           class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs focus:border-[#0B6E4F] focus:outline-none w-full sm:w-64">
                </div>

                <div id="add-store-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-52 overflow-y-auto pr-1">
                    @foreach($availableStores as $availStore)
                        <label class="add-store-opt flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2 cursor-pointer hover:border-[#0B6E4F] transition text-xs"
                               data-search="{{ strtolower($availStore->store_name . ' ' . ($availStore->student_class ?? '') . ' ' . ($availStore->owner?->name ?? '')) }}">
                            <input type="checkbox" name="store_ids[]" value="{{ $availStore->id }}" class="rounded text-[#0B6E4F] focus:ring-[#0B6E4F]">
                            <div class="min-w-0">
                                <p class="font-bold text-[#0F291E] truncate">{{ $availStore->store_name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">{{ $availStore->owner?->name ?? '-' }} &bull; {{ $availStore->student_class ?: 'Karya Siswa' }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-200/70">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-slate-500 font-semibold">Awalan Nomor Stand:</span>
                        <input type="text" name="stand_prefix" value="ST-" class="w-20 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-mono font-bold">
                    </div>
                    <button type="submit" class="rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-4 py-2 text-xs font-bold shadow-xs transition">
                        Tambahkan Toko Terpilih ke Bazar
                    </button>
                </div>
            </form>
        @endif

        <!-- Daftar Pengajuan Menunggu Persetujuan (`pending`) -->
        @php
            $pendingList = $participants->where('status', 'pending');
        @endphp
        @if($pendingList->count() > 0)
            <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 space-y-3">
                <p class="text-xs font-black text-amber-900 uppercase tracking-wider">
                    Pengajuan Toko Menunggu Persetujuan ({{ $pendingList->count() }})
                </p>
                <div class="space-y-2">
                    @foreach($pendingList as $pend)
                        <div class="rounded-xl bg-white border border-amber-200 p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div>
                                <p class="font-bold text-[#0F291E]">{{ $pend->store?->store_name ?? 'Toko' }}</p>
                                <p class="text-[11px] text-slate-500">
                                    Pemilik: {{ $pend->store?->owner?->name ?? '-' }} &bull; {{ $pend->store?->student_class ?: '-' }}
                                    @if($pend->notes)
                                        &bull; Catatan: <em>"{{ $pend->notes }}"</em>
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <form action="{{ route('admin.bazaar.participants.approve', [$bazaarEvent, $pend]) }}" method="POST" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="stand_number" placeholder="No. Stand (mis. A-01)"
                                           class="w-32 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-mono">
                                    <button type="submit" class="rounded-lg bg-[#0B6E4F] hover:bg-[#08583F] text-white px-3 py-1 font-bold transition">
                                        Setujui
                                    </button>
                                </form>
                                <form action="{{ route('admin.bazaar.participants.reject', [$bazaarEvent, $pend]) }}" method="POST"
                                      onsubmit="return confirm('Tolak pengajuan toko ini?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 px-3 py-1 font-bold transition">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Tabel Peserta Resmi Bazar -->
        <div class="overflow-x-auto border border-slate-100 rounded-xl">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                        <th class="py-3 px-4">STAND</th>
                        <th class="py-3 px-4">NAMA TOKO & PEMILIK</th>
                        <th class="py-3 px-4">STATUS</th>
                        <th class="py-3 px-4">QR STAND TOKO</th>
                        <th class="py-3 px-4 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($participants as $part)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3 px-4">
                                @if($part->status === 'approved')
                                    <form action="{{ route('admin.bazaar.participants.stand', [$bazaarEvent, $part]) }}" method="POST" class="flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="stand_number" value="{{ $part->stand_number }}"
                                               class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-xs font-mono font-bold text-[#0F291E]">
                                        <button type="submit" class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2 py-1 text-[10px] font-bold text-slate-700">Simpan</button>
                                    </form>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-[#0F291E]">{{ $part->store?->store_name ?? 'Toko Terhapus' }}</p>
                                <p class="text-[11px] text-slate-400">{{ $part->store?->owner?->name ?? '-' }} &bull; {{ $part->store?->student_class ?: '-' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $stClass = match($part->status) {
                                        'approved' => 'bg-emerald-100 text-[#0B6E4F]',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                        default    => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $stClass }}">
                                    {{ $part->status_label }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($part->status === 'approved')
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-12 h-12 rounded-lg border border-slate-200 bg-white p-1 shrink-0 overflow-hidden flex items-center justify-center">
                                            {!! str_replace(['width="120"', 'height="120"'], ['width="44"', 'height="44"'], $standQrSvgs[$part->id] ?? '') !!}
                                        </div>
                                        <div>
                                            <span class="block font-mono font-bold text-[#0F291E]">{{ $part->stand_code }}</span>
                                            <a href="{{ route('admin.bazaar.participants.print-qr', [$bazaarEvent, $part]) }}" target="_blank"
                                               class="text-[11px] font-bold text-[#0B6E4F] hover:underline">
                                                Cetak QR Stand &rarr;
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400">Tersedia setelah disetujui</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                @if($part->status !== 'approved')
                                    <form action="{{ route('admin.bazaar.participants.approve', [$bazaarEvent, $part]) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="font-bold text-[#0B6E4F] hover:underline">Setujui</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.bazaar.participants.remove', [$bazaarEvent, $part]) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Keluarkan toko ini dari daftar peserta bazar?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-bold text-rose-600 hover:underline">Keluarkan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">Belum ada toko peserta pada bazar ini. Silakan pilih toko di atas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- BAGIAN 2: RANKING TOKO KHUSUS ACARA BAZAR INI + GRAFIK                -->
    <!-- ===================================================================== -->
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-base sm:text-lg font-black text-[#0F291E]">Ranking Toko / Penjual Khusus Acara: {{ $bazaarEvent->title }}</h2>
                <p class="text-xs text-slate-500">Peringkat diukur dari kunjungan scan QR stand, jumlah transaksi bazar, omzet, view katalog selama bazar, dan rating pembeli.</p>
            </div>
            <form method="GET" action="{{ route('admin.bazaar.show', $bazaarEvent) }}">
                <select name="sort" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-[#F8FAFC] px-3 py-2 text-xs font-bold text-[#0F291E]">
                    <option value="score" @selected($sortBy === 'score')>Urutkan: Skor Komposit Terbaik</option>
                    <option value="visits" @selected($sortBy === 'visits')>Urutkan: Kunjungan Stand Terbanyak</option>
                    <option value="orders" @selected($sortBy === 'orders')>Urutkan: Transaksi Terbanyak</option>
                    <option value="revenue" @selected($sortBy === 'revenue')>Urutkan: Omzet Tertinggi</option>
                    <option value="views" @selected($sortBy === 'views')>Urutkan: View Katalog Periode Bazar</option>
                    <option value="rating" @selected($sortBy === 'rating')>Urutkan: Rating Bintang Tertinggi</option>
                </select>
            </form>
        </div>

        @if($storeRankings->isNotEmpty())
            <!-- Podium Top 3 Toko Terbaik -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($storeRankings->take(3) as $topStore)
                    @php
                        $podiumBg = match($topStore->rank) {
                            1 => 'bg-gradient-to-br from-amber-50 to-amber-100/50 border-amber-300',
                            2 => 'bg-gradient-to-br from-slate-50 to-slate-100/70 border-slate-300',
                            default => 'bg-gradient-to-br from-emerald-50/70 to-emerald-100/40 border-emerald-200',
                        };
                        $rankBadge = match($topStore->rank) {
                            1 => 'bg-amber-400 text-amber-950',
                            2 => 'bg-slate-300 text-slate-800',
                            default => 'bg-[#0B6E4F] text-white',
                        };
                    @endphp
                    <div class="rounded-2xl border p-4 space-y-2 {{ $podiumBg }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $rankBadge }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                <span>Peringkat #{{ $topStore->rank }}</span>
                            </span>
                            <span class="text-[11px] font-mono font-bold text-slate-500">Stand {{ $topStore->stand_number }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-[#0F291E] truncate">{{ $topStore->store->store_name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ $topStore->store->student_class ?: ($topStore->store->owner?->name ?? '-') }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-1.5 pt-2 border-t border-black/5 text-center text-[11px]">
                            <div class="rounded-lg bg-white/80 py-1.5 px-1">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Stand</span>
                                <span class="font-black text-blue-700">{{ $topStore->stand_visits_count }}</span>
                            </div>
                            <div class="rounded-lg bg-white/80 py-1.5 px-1">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Trx</span>
                                <span class="font-black text-[#0F291E]">{{ $topStore->orders_count }}</span>
                            </div>
                            <div class="rounded-lg bg-white/80 py-1.5 px-1">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">Skor</span>
                                <span class="font-black text-[#0B6E4F]">{{ number_format($topStore->composite_score, 1) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Grafik Visual Ranking Penjual / Toko -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-7 rounded-2xl border border-slate-200/80 bg-[#F8FAFC]/60 p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-[#0F291E]">Grafik Performa Toko (Top 10)</h3>
                            <p class="text-[11px] text-slate-400">Pilih metrik tampilan untuk mempresentasikan capaian toko.</p>
                        </div>
                        <div class="flex flex-wrap gap-1 text-[10px] font-bold">
                            <button type="button" onclick="switchStoreChartMode('composite', this)" class="store-chart-tab rounded-lg px-2.5 py-1 bg-[#0B6E4F] text-white transition">Skor</button>
                            <button type="button" onclick="switchStoreChartMode('activity', this)" class="store-chart-tab rounded-lg px-2.5 py-1 bg-white border border-slate-200 text-slate-600 hover:border-[#0B6E4F] transition">Stand &amp; Trx</button>
                            <button type="button" onclick="switchStoreChartMode('views', this)" class="store-chart-tab rounded-lg px-2.5 py-1 bg-white border border-slate-200 text-slate-600 hover:border-[#0B6E4F] transition">View</button>
                        </div>
                    </div>
                    <div class="relative h-64 sm:h-72 bg-white rounded-xl border border-slate-100 p-3">
                        <canvas id="bazaarStoreRankChart"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-[#F8FAFC]/60 p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-[#0F291E]">Grafik Omzet Penjualan Bazar (Rp)</h3>
                        <p class="text-[11px] text-slate-400">Perbandingan total omzet pesanan bazar per toko peserta.</p>
                    </div>
                    <div class="relative h-64 sm:h-72 bg-white rounded-xl border border-slate-100 p-3">
                        <canvas id="bazaarStoreRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto border border-slate-100 rounded-xl">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                        <th class="py-3 px-4">PERINGKAT</th>
                        <th class="py-3 px-4">STAND &amp; TOKO</th>
                        <th class="py-3 px-4 text-center">KUNJUNGAN STAND</th>
                        <th class="py-3 px-4 text-center">VIEW PERIODE BAZAR</th>
                        <th class="py-3 px-4 text-center">TRANSAKSI (ITEM)</th>
                        <th class="py-3 px-4 text-right">OMZET BAZAR</th>
                        <th class="py-3 px-4 text-center">RATING</th>
                        <th class="py-3 px-4 text-right">SKOR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($storeRankings as $row)
                        <tr class="hover:bg-slate-50/70">
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-black text-xs {{ $row->rank === 1 ? 'bg-amber-400 text-amber-950' : ($row->rank === 2 ? 'bg-slate-200 text-slate-800' : ($row->rank === 3 ? 'bg-amber-700/20 text-amber-900' : 'bg-slate-100 text-slate-500')) }}">
                                    #{{ $row->rank }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-[#0F291E]">{{ $row->store->store_name }}</p>
                                <p class="text-[11px] text-slate-400">Stand {{ $row->stand_number }} &bull; {{ $row->store->student_class ?: '-' }}</p>
                            </td>
                            <td class="py-3 px-4 text-center font-extrabold text-blue-700">{{ number_format($row->stand_visits_count) }}</td>
                            <td class="py-3 px-4 text-center font-semibold text-slate-600">{{ number_format($row->catalog_views_count) }}</td>
                            <td class="py-3 px-4 text-center font-bold text-[#0F291E]">
                                {{ number_format($row->orders_count) }} <span class="text-slate-400 font-normal">({{ $row->items_sold }} item)</span>
                            </td>
                            <td class="py-3 px-4 text-right font-extrabold text-[#0B6E4F]">Rp {{ number_format($row->revenue, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-center font-bold text-amber-600">
                                {{ $row->avg_rating > 0 ? number_format($row->avg_rating, 1) . ' / 5' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right font-black text-[#0F291E]">{{ number_format($row->composite_score, 1) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">Belum ada toko peserta resmi untuk dirangking pada bazar ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- BAGIAN 3: INFORMASI HADIAH POIN, GRAFIK & LEADERBOARD PENGUNJUNG      -->
    <!-- ===================================================================== -->
    @php
        $configuredTiers = $bazaarEvent->normalized_prize_tiers;
    @endphp
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-base sm:text-lg font-black text-[#0F291E]">Ranking Poin Pengunjung, Info Hadiah &amp; Penerima Doorprize</h2>
                <p class="text-xs text-slate-500">Akumulasi poin dari hadir di bazar (+{{ $bazaarEvent->points_checkin }}), kunjungan stand (+{{ $bazaarEvent->points_stand_visit }}/stand), transaksi bazar (+{{ $bazaarEvent->points_transaction }}/transaksi), serta bonus panitia.</p>
            </div>
        </div>

        <!-- Form Pengaturan Informasi Hadiah Poin Doorprize (Tampil untuk Pengunjung) -->
        <form action="{{ route('admin.bazaar.prize-tiers.update', $bazaarEvent) }}" method="POST"
              class="rounded-2xl bg-amber-50/60 border border-amber-200/90 p-4 sm:p-5 space-y-3 text-xs">
            @csrf
            @method('PUT')
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-xs sm:text-sm font-black text-amber-950 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        <span>Informasi Hadiah Poin Pengunjung (Target Poin &rarr; Hadiah)</span>
                    </h3>
                    <p class="text-[11px] text-amber-900/80 mt-0.5">
                        Informasikan ketentuan hadiah bagi pengunjung (misal: <strong>300 Poin mendapatkan Payung</strong>, <strong>100 Poin mendapatkan Tumbler</strong>, dll). Informasi ini tampil otomatis pada halaman Scan Poin Pembeli dan Poster QR Acara.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="fillSamplePrizeTiers()" class="rounded-lg border border-amber-300 bg-white hover:bg-amber-100/70 px-2.5 py-1.5 text-[11px] font-bold text-amber-900 transition">
                        + Isi Contoh
                    </button>
                    <button type="button" onclick="addPrizeTierRow()" class="rounded-lg bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 text-[11px] font-bold transition shadow-2xs">
                        + Tambah Hadiah
                    </button>
                </div>
            </div>

            <div id="prize-tiers-rows" class="space-y-2">
                @forelse($configuredTiers as $idx => $tier)
                    <div class="prize-tier-row grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-white/90 rounded-xl border border-amber-200/80 p-2.5">
                        <div class="sm:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Minimal Poin</label>
                            <input type="number" name="tiers[{{ $idx }}][min_points]" min="1" max="1000000" value="{{ $tier['min_points'] }}" placeholder="300"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-black text-[#0B6E4F]">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Hadiah yang Didapat</label>
                            <input type="text" name="tiers[{{ $idx }}][prize_name]" maxlength="120" value="{{ $tier['prize_name'] }}" placeholder="Contoh: Payung Lipat Eksklusif"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-[#0F291E]">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Keterangan / Kuota (Opsional)</label>
                            <input type="text" name="tiers[{{ $idx }}][notes]" maxlength="200" value="{{ $tier['notes'] ?? '' }}" placeholder="Contoh: Tukar di meja panitia / stok 20 pcs"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs">
                        </div>
                        <div class="sm:col-span-1 flex justify-end sm:pt-4">
                            <button type="button" onclick="this.closest('.prize-tier-row').remove()" class="rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 text-xs font-bold transition" title="Hapus baris">
                                &times;
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="prize-tier-row grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-white/90 rounded-xl border border-amber-200/80 p-2.5">
                        <div class="sm:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Minimal Poin</label>
                            <input type="number" name="tiers[0][min_points]" min="1" max="1000000" value="" placeholder="Contoh: 300"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-black text-[#0B6E4F]">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Hadiah yang Didapat</label>
                            <input type="text" name="tiers[0][prize_name]" maxlength="120" value="" placeholder="Contoh: Payung / Tumbler / Voucher"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-[#0F291E]">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Keterangan / Kuota (Opsional)</label>
                            <input type="text" name="tiers[0][notes]" maxlength="200" value="" placeholder="Contoh: Selama persediaan masih ada"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs">
                        </div>
                        <div class="sm:col-span-1 flex justify-end sm:pt-4">
                            <button type="button" onclick="this.closest('.prize-tier-row').remove()" class="rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 text-xs font-bold transition" title="Hapus baris">
                                &times;
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    @if(count($configuredTiers) > 0)
                        <span class="text-[11px] font-bold text-amber-900">Aktif Ditampilkan ke Pengunjung:</span>
                        @foreach($configuredTiers as $ct)
                            <span class="rounded-full bg-white border border-amber-300 px-2.5 py-0.5 text-[11px] font-extrabold text-amber-950">
                                {{ number_format($ct['min_points']) }} Poin &rarr; {{ $ct['prize_name'] }}
                            </span>
                        @endforeach
                    @else
                        <span class="text-[11px] text-amber-900/70">Belum ada target hadiah yang disimpan.</span>
                    @endif
                </div>
                <button type="submit" class="rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white px-4 py-2 text-xs font-bold shadow-xs transition">
                    Simpan Informasi Hadiah
                </button>
            </div>
        </form>

        @if($visitorRankings->isNotEmpty())
            <!-- Podium Top 3 Pengunjung -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($visitorRankings->take(3) as $topV)
                    @php
                        $vPodiumBg = match($topV->rank) {
                            1 => 'bg-gradient-to-br from-amber-50 to-amber-100/50 border-amber-300',
                            2 => 'bg-gradient-to-br from-slate-50 to-slate-100/70 border-slate-300',
                            default => 'bg-gradient-to-br from-emerald-50/70 to-emerald-100/40 border-emerald-200',
                        };
                        $vMatchedPrize = $bazaarEvent->getHighestMatchedPrize($topV->total_points);
                    @endphp
                    <div class="rounded-2xl border p-4 space-y-2 {{ $vPodiumBg }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $topV->rank === 1 ? 'bg-amber-400 text-amber-950' : ($topV->rank === 2 ? 'bg-slate-300 text-slate-800' : 'bg-[#0B6E4F] text-white') }}">
                                <span>Peringkat #{{ $topV->rank }}</span>
                            </span>
                            <span class="text-sm font-black text-[#0B6E4F]">{{ number_format($topV->total_points) }} pts</span>
                        </div>
                        <div>
                            <p class="text-sm font-black text-[#0F291E] truncate">{{ $topV->user->name }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ $topV->user->subtype_label }}</p>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-1.5 pt-2 border-t border-black/5 text-[10px] font-bold">
                            <span class="text-slate-600">{{ $topV->stand_visits_count }} stand &bull; {{ $topV->transactions_count }} trx</span>
                            @if($vMatchedPrize)
                                <span class="rounded-full bg-white border border-amber-300 px-2 py-0.5 text-amber-900">
                                    Target: {{ $vMatchedPrize['prize_name'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Grafik Visual Ranking Pembeli / Pengunjung -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <div class="lg:col-span-8 rounded-2xl border border-slate-200/80 bg-[#F8FAFC]/60 p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-[#0F291E]">Grafik Ranking Poin Pengunjung (Top 10)</h3>
                        <p class="text-[11px] text-slate-400">Komposisi perolehan poin dari Hadir Bazar, Kunjungan Stand, Transaksi, dan Bonus Panitia.</p>
                    </div>
                    <div class="relative h-64 sm:h-72 bg-white rounded-xl border border-slate-100 p-3">
                        <canvas id="bazaarVisitorRankChart"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-4 rounded-2xl border border-slate-200/80 bg-[#F8FAFC]/60 p-4 space-y-3">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-[#0F291E]">Distribusi Aktivitas Pengunjung</h3>
                        <p class="text-[11px] text-slate-400">Total kontribusi poin per jenis aktivitas bazar.</p>
                    </div>
                    <div class="relative h-64 sm:h-72 bg-white rounded-xl border border-slate-100 p-3">
                        <canvas id="bazaarVisitorBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        @endif

        <!-- Penyesuaian Poin Manual Panitia (Opsional untuk Kuis/Koreksi) -->
        <form action="{{ route('admin.bazaar.points.adjust', $bazaarEvent) }}" method="POST"
              class="rounded-xl bg-[#F8FAFC] border border-slate-200/80 p-3.5 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end text-xs">
            @csrf
            <div class="sm:col-span-4">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Tambah / Koreksi Poin Pengunjung (Bonus Kuis / Panitia)</label>
                <select name="user_id" required class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold">
                    <option value="">-- Pilih Akun Pengunjung / Pembeli --</option>
                    @foreach($selectableVisitors ?? [] as $sv)
                        <option value="{{ $sv->id }}">{{ $sv->name }} ({{ $sv->subtype_label }})</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Jumlah Poin (+/-)</label>
                <input type="number" name="points" required value="25" min="-10000" max="10000"
                       placeholder="Misal: 25 atau -10"
                       class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-black text-[#0B6E4F]">
            </div>
            <div class="sm:col-span-4">
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Keterangan Panitia (Opsional)</label>
                <input type="text" name="description" maxlength="200"
                       placeholder="Contoh: Bonus kuis acara bazar / koreksi scan"
                       class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="w-full rounded-lg bg-slate-800 hover:bg-slate-900 text-white px-3 py-2 text-xs font-bold transition shadow-xs">
                    Simpan Poin Manual
                </button>
            </div>
        </form>

        <div class="overflow-x-auto border border-slate-100 rounded-xl">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                        <th class="py-3 px-4">PERINGKAT</th>
                        <th class="py-3 px-4">PENGUNJUNG / PEMBELI</th>
                        <th class="py-3 px-4 text-center">HADIR BAZAR</th>
                        <th class="py-3 px-4 text-center">KUNJUNGI STAND</th>
                        <th class="py-3 px-4 text-center">TRANSAKSI</th>
                        <th class="py-3 px-4 text-right">TOTAL POIN</th>
                        <th class="py-3 px-4 text-right">DOORPRIZE PANITIA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($visitorRankings as $vRow)
                        @php
                            $matchedPrize = $bazaarEvent->getHighestMatchedPrize($vRow->total_points);
                        @endphp
                        <tr class="hover:bg-slate-50/70 {{ $vRow->doorprize_winner ? 'bg-amber-50/40' : '' }}">
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full font-black text-xs {{ $vRow->rank === 1 ? 'bg-amber-400 text-amber-950' : ($vRow->rank <= 3 ? 'bg-emerald-100 text-[#0B6E4F]' : 'bg-slate-100 text-slate-500') }}">
                                    #{{ $vRow->rank }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-bold text-[#0F291E]">{{ $vRow->user->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $vRow->user->subtype_label }} &bull; {{ $vRow->user->phone ?: $vRow->user->email }}</p>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="font-bold {{ $vRow->checkin_count > 0 ? 'text-[#0B6E4F]' : 'text-slate-300' }}">
                                    {{ $vRow->checkin_count > 0 ? '+' . $vRow->checkin_points . ' pts' : 'Belum scan' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-semibold text-blue-700">
                                {{ $vRow->stand_visits_count }} stand (+{{ $vRow->stand_visit_points }} pts)
                            </td>
                            <td class="py-3 px-4 text-center font-semibold text-amber-700">
                                {{ $vRow->transactions_count }}x (+{{ $vRow->transaction_points }} pts)
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="inline-block rounded-lg bg-[#EBF9F2] px-2.5 py-1 font-black text-sm text-[#0B6E4F]">
                                    {{ number_format($vRow->total_points) }}
                                </span>
                                @if($matchedPrize)
                                    <p class="text-[10px] font-bold text-amber-800 mt-0.5">
                                        Capai: {{ $matchedPrize['prize_name'] }}
                                    </p>
                                @endif
                                @if(($vRow->bonus_points ?? 0) !== 0)
                                    <p class="text-[10px] font-bold mt-0.5 {{ $vRow->bonus_points > 0 ? 'text-indigo-700' : 'text-rose-600' }}">
                                        {{ $vRow->bonus_points > 0 ? '+' : '' }}{{ $vRow->bonus_points }} panitia
                                    </p>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($vRow->doorprize_winner)
                                    <div class="inline-flex flex-col items-end gap-1">
                                        <span class="rounded-full bg-amber-100 border border-amber-300 px-2.5 py-0.5 text-[10px] font-black text-amber-900">
                                            Menang: {{ $vRow->doorprize_winner->prize_name }}
                                        </span>
                                        <form action="{{ route('admin.bazaar.doorprize.revoke', [$bazaarEvent, $vRow->doorprize_winner]) }}" method="POST"
                                              onsubmit="return confirm('Batalkan penetapan pemenang doorprize untuk pengunjung ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] font-bold text-rose-600 hover:underline">Batalkan Hadiah</button>
                                        </form>
                                    </div>
                                @else
                                    <form action="{{ route('admin.bazaar.doorprize.award', $bazaarEvent) }}" method="POST" class="inline-flex items-center gap-1.5 justify-end">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $vRow->user->id }}">
                                        <input type="hidden" name="rank_at_award" value="{{ $vRow->rank }}">
                                        <input type="hidden" name="total_points_at_award" value="{{ $vRow->total_points }}">
                                        <input type="text" name="prize_name" value="{{ $matchedPrize ? $matchedPrize['prize_name'] : 'Doorprize Peringkat #' . $vRow->rank }}"
                                               class="w-36 rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold">
                                        <button type="submit" class="rounded-lg bg-amber-600 hover:bg-amber-700 text-white px-2.5 py-1 text-[11px] font-bold transition">
                                            Tetapkan
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada pengunjung yang melakukan scan kehadiran atau transaksi pada acara bazar ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function filterAddStoreCheckboxes(query) {
            const kw = (query || '').trim().toLowerCase();
            document.querySelectorAll('#add-store-list .add-store-opt').forEach(el => {
                el.classList.toggle('hidden', kw !== '' && !(el.dataset.search || '').includes(kw));
            });
        }

        let _prizeTierIdx = {{ max(1, count($configuredTiers)) }};

        function addPrizeTierRow(minPts = '', prize = '', notes = '') {
            const container = document.getElementById('prize-tiers-rows');
            if (!container) return;
            const idx = _prizeTierIdx++;
            const row = document.createElement('div');
            row.className = 'prize-tier-row grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-white/90 rounded-xl border border-amber-200/80 p-2.5';

            row.innerHTML = `
                <div class="sm:col-span-3">
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Minimal Poin</label>
                    <input type="number" name="tiers[${idx}][min_points]" min="1" max="1000000" placeholder="Contoh: 300"
                           class="tier-pts w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-black text-[#0B6E4F]">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Hadiah yang Didapat</label>
                    <input type="text" name="tiers[${idx}][prize_name]" maxlength="120" placeholder="Contoh: Payung Lipat"
                           class="tier-prize w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-[#0F291E]">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Keterangan / Kuota (Opsional)</label>
                    <input type="text" name="tiers[${idx}][notes]" maxlength="200" placeholder="Contoh: Tukar di meja panitia"
                           class="tier-notes w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs">
                </div>
                <div class="sm:col-span-1 flex justify-end sm:pt-4">
                    <button type="button" onclick="this.closest('.prize-tier-row').remove()" class="rounded-lg border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1.5 text-xs font-bold transition" title="Hapus baris">&times;</button>
                </div>
            `;
            row.querySelector('.tier-pts').value = minPts;
            row.querySelector('.tier-prize').value = prize;
            row.querySelector('.tier-notes').value = notes;
            container.appendChild(row);
        }

        function fillSamplePrizeTiers() {
            const container = document.getElementById('prize-tiers-rows');
            if (!container) return;
            container.innerHTML = '';
            addPrizeTierRow(100, 'Gantungan Kunci / Stiker Hilalkraf', 'Untuk pengunjung yang mencapai 100 poin');
            addPrizeTierRow(200, 'Tumbler Sekolah SMK PAL', 'Selama persediaan masih ada');
            addPrizeTierRow(300, 'Payung Lipat Eksklusif', 'Tukar di meja panitia bazar');
            addPrizeTierRow(500, 'Voucher Belanja Karya Siswa Rp 50.000', 'Hadiah Utama Poin Tertinggi');
        }

        const _storeChartData = @json($storeChartData);
        const _visitorChartData = @json($visitorChartData);
        let _storeRankChartInstance = null;

        function fmtRp(val) {
            return 'Rp ' + Math.round(Number(val || 0)).toLocaleString('id-ID');
        }

        function switchStoreChartMode(mode, btnEl) {
            document.querySelectorAll('.store-chart-tab').forEach(b => {
                b.classList.remove('bg-[#0B6E4F]', 'text-white');
                b.classList.add('bg-white', 'border', 'border-slate-200', 'text-slate-600');
            });
            if (btnEl) {
                btnEl.classList.add('bg-[#0B6E4F]', 'text-white');
                btnEl.classList.remove('bg-white', 'border', 'border-slate-200', 'text-slate-600');
            }
            renderStoreRankChart(mode);
        }

        function renderStoreRankChart(mode = 'composite') {
            const canvas = document.getElementById('bazaarStoreRankChart');
            if (!canvas || typeof Chart === 'undefined' || !_storeChartData.labels || !_storeChartData.labels.length) return;

            let datasets = [];
            if (mode === 'activity') {
                datasets = [
                    { label: 'Kunjungan Stand (QR)', data: _storeChartData.stand_visits, backgroundColor: '#2563EB', borderRadius: 6 },
                    { label: 'Transaksi Bazar', data: _storeChartData.orders, backgroundColor: '#D97706', borderRadius: 6 },
                ];
            } else if (mode === 'views') {
                datasets = [
                    { label: 'View Katalog Periode Bazar', data: _storeChartData.catalog_views, backgroundColor: '#0B6E4F', borderRadius: 6 },
                ];
            } else {
                datasets = [
                    { label: 'Skor Komposit Toko', data: _storeChartData.composite_scores, backgroundColor: '#0B6E4F', borderRadius: 6 },
                ];
            }

            if (_storeRankChartInstance) {
                _storeRankChartInstance.data.datasets = datasets;
                _storeRankChartInstance.update();
                return;
            }

            _storeRankChartInstance = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: _storeChartData.labels,
                    datasets: datasets,
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                    scales: { x: { beginAtZero: true } },
                },
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            renderStoreRankChart('{{ $sortBy === "visits" || $sortBy === "orders" ? "activity" : ($sortBy === "views" ? "views" : "composite") }}');

            const revCanvas = document.getElementById('bazaarStoreRevenueChart');
            if (revCanvas && _storeChartData.labels && _storeChartData.labels.length) {
                new Chart(revCanvas, {
                    type: 'bar',
                    data: {
                        labels: _storeChartData.labels,
                        datasets: [{
                            label: 'Omzet Bazar (Rp)',
                            data: _storeChartData.revenues,
                            backgroundColor: 'rgba(11, 110, 79, 0.85)',
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => 'Omzet: ' + fmtRp(ctx.raw) } },
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { callback: (v) => fmtRp(v), font: { size: 10 } } },
                            y: { ticks: { font: { size: 10 } } },
                        },
                    },
                });
            }

            const vRankCanvas = document.getElementById('bazaarVisitorRankChart');
            if (vRankCanvas && _visitorChartData.labels && _visitorChartData.labels.length) {
                new Chart(vRankCanvas, {
                    type: 'bar',
                    data: {
                        labels: _visitorChartData.labels,
                        datasets: [
                            { label: 'Hadir Bazar', data: _visitorChartData.checkin_points, backgroundColor: '#0B6E4F', borderRadius: 4 },
                            { label: 'Kunjungan Stand', data: _visitorChartData.stand_visit_points, backgroundColor: '#2563EB', borderRadius: 4 },
                            { label: 'Transaksi Bazar', data: _visitorChartData.transaction_points, backgroundColor: '#D97706', borderRadius: 4 },
                            { label: 'Bonus Panitia', data: _visitorChartData.bonus_points, backgroundColor: '#6366F1', borderRadius: 4 },
                        ],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                            tooltip: { mode: 'index', intersect: false },
                        },
                        scales: {
                            x: { stacked: true, beginAtZero: true, title: { display: true, text: 'Total Poin', font: { size: 10 } } },
                            y: { stacked: true, ticks: { font: { size: 11 } } },
                        },
                    },
                });
            }

            const vBreakCanvas = document.getElementById('bazaarVisitorBreakdownChart');
            if (vBreakCanvas && _visitorChartData.breakdown_totals) {
                const bt = _visitorChartData.breakdown_totals;
                const vals = [bt.checkin, bt.stand, bt.transaction, bt.bonus];
                const hasVals = vals.some(v => v > 0);
                new Chart(vBreakCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: hasVals ? ['Hadir Bazar', 'Kunjungan Stand', 'Transaksi Bazar', 'Bonus Panitia'] : ['Belum Ada Poin'],
                        datasets: [{
                            data: hasVals ? vals : [1],
                            backgroundColor: hasVals ? ['#0B6E4F', '#2563EB', '#D97706', '#6366F1'] : ['#E2E8F0'],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                        },
                    },
                });
            }
        });
    </script>
@endsection
