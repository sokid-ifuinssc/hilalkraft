{{--
============================================================================
VIEW: SCAN QR BAZAR, POIN KUNJUNGAN STAND & LEADERBOARD DOORPRIZE
============================================================================
Halaman untuk Pembeli / Pengunjung berakun (`/pembeli/bazar-poin`):
  1. Scanner kamera QR langsung di browser + input kode manual singkat
  2. Progres poin bazar (Hadir +50, Stand +20/toko, Transaksi +30)
  3. Daftar stand toko peserta (sudah dikunjungi vs belum)
  4. Papan peringkat Top 10 Doorprize & status pemenang
============================================================================
--}}
@extends('layouts.panel')

@section('title', 'Scan QR & Poin Bazar Doorprize — hilalkraf')
@section('panel_title', 'Scan QR & Poin Bazar')

@section('panel')
    @if(session('success'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs sm:text-sm font-bold text-emerald-800 flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs sm:text-sm font-bold text-rose-700 flex items-center gap-2.5">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Area Scanner Kamera QR & Input Kode Manual -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri: Kamera Scanner -->
        <div class="lg:col-span-6 rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-base sm:text-lg font-black text-[#0F291E]">Scan Barcode / QR Code Bazar</h1>
                    <p class="text-xs text-slate-500">Arahkan kamera ke barcode pintu masuk bazar atau stand toko peserta.</p>
                </div>
                <span class="w-9 h-9 rounded-xl bg-[#EBF9F2] text-[#0B6E4F] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </span>
            </div>

            <div id="qr-reader-container" class="relative rounded-2xl overflow-hidden bg-slate-900 border border-slate-700/80 min-h-[260px] flex flex-col items-center justify-center text-center p-3 shadow-inner">
                <div id="qr-reader" class="w-full max-w-sm mx-auto overflow-hidden rounded-xl"></div>
                <div id="qr-placeholder" class="space-y-3 py-8 px-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto text-emerald-400">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-200">Kamera Pemindai Cepat Barcode & QR Code Stand Bazar</p>
                    <p class="text-[11px] text-slate-400 max-w-xs mx-auto">Mendukung kamera belakang HP otomatis dengan akselerasi pendeteksi hardware BarcodeDetector.</p>
                </div>

                <!-- Laser Animasi & Indikator Aktif saat Kamera Menyala -->
                <div id="qr-scanning-overlay" class="hidden pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <div class="relative w-64 h-64 border-2 border-dashed border-emerald-400/60 rounded-2xl flex items-center justify-center overflow-hidden">
                        <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent shadow-[0_0_12px_#34d399] animate-scanner-laser"></div>
                        <span class="absolute top-2 left-2 w-4 h-4 border-t-2 border-l-2 border-emerald-400"></span>
                        <span class="absolute top-2 right-2 w-4 h-4 border-t-2 border-r-2 border-emerald-400"></span>
                        <span class="absolute bottom-2 left-2 w-4 h-4 border-b-2 border-l-2 border-emerald-400"></span>
                        <span class="absolute bottom-2 right-2 w-4 h-4 border-b-2 border-r-2 border-emerald-400"></span>
                    </div>
                    <span class="mt-3 text-[11px] font-bold tracking-wide text-emerald-300 bg-slate-900/85 px-3 py-1 rounded-full border border-emerald-500/30">
                        Arahkan Barcode / QR ke dalam kotak
                    </span>
                </div>
            </div>

            <div id="qr-scan-feedback" class="hidden rounded-xl px-3.5 py-2.5 text-xs font-bold"></div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="btn-start-scan" onclick="startBazaarCamera()"
                        class="flex-1 rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white font-extrabold text-xs py-2.5 px-4 transition shadow-xs">
                    Aktifkan Kamera Scanner
                </button>
                <button type="button" id="btn-stop-scan" onclick="stopBazaarCamera()"
                        class="hidden rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs py-2.5 px-4 transition">
                    Matikan Kamera
                </button>
            </div>
        </div>

        <!-- Kolom Kanan: Input Manual Kode Singkat & Panduan Poin -->
        <div class="lg:col-span-6 space-y-5">
            <form action="{{ route('bazaar.scan.submit') }}" method="POST"
                  class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-3">
                @csrf
                <div>
                    <h2 class="text-sm font-black text-[#0F291E]">Atau Masukkan Kode Barcode Secara Manual</h2>
                    <p class="text-xs text-slate-400">Ketik kode singkat yang tercetak di bawah QR Code (contoh: <span class="font-mono font-bold">BZ-XXXXXX</span> atau <span class="font-mono font-bold">ST-XXXXXX</span>).</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="text" name="code" required placeholder="Masukkan kode BZ-... / ST-..."
                           class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-4 py-2.5 font-mono text-xs sm:text-sm font-bold uppercase focus:bg-white focus:border-[#0B6E4F] focus:outline-none">
                    <button type="submit" class="rounded-xl bg-[#0B6E4F] hover:bg-[#08583F] text-white font-extrabold text-xs px-5 py-2.5 transition shadow-xs">
                        Klaim Poin
                    </button>
                </div>
            </form>

            <!-- Skema Cara Dapat Poin Doorprize -->
            <div class="rounded-2xl bg-[#EBF9F2] border border-[#CDEFE0] p-5 space-y-3">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-[#0B6E4F]">Cara Mengumpulkan Poin Doorprize</h3>
                <div class="space-y-2 text-xs text-slate-700">
                    <div class="flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-white text-[#0B6E4F] font-black flex items-center justify-center shrink-0 border border-emerald-200">1</span>
                        <p><strong>Scan QR Kehadiran Acara Bazar:</strong> Dapat poin kehadiran saat datang di pintu masuk acara (misal <strong>+50 poin</strong>, 1x per acara).</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-white text-blue-700 font-black flex items-center justify-center shrink-0 border border-blue-200">2</span>
                        <p><strong>Scan QR di Stand Toko Peserta:</strong> Kunjungi setiap stand toko siswa dan scan QR di meja stand untuk poin ekstra per stand!</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-lg bg-white text-amber-700 font-black flex items-center justify-center shrink-0 border border-amber-200">3</span>
                        <p><strong>Belanja / Transaksi di Bazar:</strong> Setiap pesanan sah pada acara bazar otomatis menambah poin transaksi menuju peringkat teratas Doorprize!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progres Poin & Daftar Stand per Event Bazar Aktif -->
    @forelse($eventSummaries as $sum)
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-5">
            @if($sum->doorprizeWinner)
                <div class="rounded-2xl bg-amber-50 border-2 border-amber-300 p-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        </span>
                        <div>
                            <p class="text-xs font-black uppercase text-amber-900">Selamat! Anda Ditetapkan Sebagai Pemenang Doorprize</p>
                            <p class="text-sm font-extrabold text-[#0F291E]">{{ $sum->doorprizeWinner->prize_name }} (Peringkat #{{ $sum->doorprizeWinner->rank_at_award ?? $sum->myRank }})</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-white border border-amber-300 px-3 py-1 text-xs font-black text-amber-900">
                        Tunjukkan ke Panitia Bazar
                    </span>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-base sm:text-lg font-black text-[#0F291E]">{{ $sum->event->title }}</h2>
                    <p class="text-xs text-slate-400">
                        Hadir (+{{ $sum->event->points_checkin }}) &bull; Scan Stand (+{{ $sum->event->points_stand_visit }}/stand) &bull; Transaksi (+{{ $sum->event->points_transaction }}/trx)
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-[#EBF9F2] border border-[#CDEFE0] px-4 py-2 text-center">
                        <p class="text-[10px] font-bold uppercase text-[#0B6E4F]">Total Poin Anda</p>
                        <p class="text-xl font-black text-[#0B6E4F]">{{ number_format($sum->totalPoints) }}</p>
                    </div>
                    <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-2 text-center">
                        <p class="text-[10px] font-bold uppercase text-amber-700">Peringkat Sementara</p>
                        <p class="text-xl font-black text-amber-900">{{ $sum->myRank ? '#' . $sum->myRank : '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Informasi Hadiah Poin Pengunjung (Target Poin -> Hadiah Doorprize) -->
            @php
                $eventPrizeTiers = $sum->event->normalized_prize_tiers;
            @endphp
            @if(count($eventPrizeTiers) > 0)
                <div class="rounded-2xl bg-amber-50/60 border border-amber-200/90 p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-xs font-black uppercase tracking-wider text-amber-950 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                            <span>Informasi Hadiah Poin Pengunjung</span>
                        </h3>
                        <span class="text-[11px] font-semibold text-amber-800">Kumpulkan poin dari hadir, scan stand &amp; belanja</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        @foreach($eventPrizeTiers as $tier)
                            @php
                                $targetPts = max(1, (int) $tier['min_points']);
                                $reached = $sum->totalPoints >= $targetPts;
                                $pct = min(100, (int) round(($sum->totalPoints / $targetPts) * 100));
                                $remaining = max(0, $targetPts - $sum->totalPoints);
                            @endphp
                            <div class="rounded-xl border p-3 space-y-2 {{ $reached ? 'bg-emerald-50/90 border-emerald-300' : 'bg-white border-amber-200/80' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-black {{ $reached ? 'bg-[#0B6E4F] text-white' : 'bg-amber-100 text-amber-900' }}">
                                            {{ number_format($targetPts) }} Poin
                                        </span>
                                        <p class="text-xs sm:text-sm font-black text-[#0F291E] mt-1">{{ $tier['prize_name'] }}</p>
                                        @if(!empty($tier['notes']))
                                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $tier['notes'] }}</p>
                                        @endif
                                    </div>
                                    @if($reached)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-extrabold text-[#0B6E4F] shrink-0">
                                            Tercapai
                                        </span>
                                    @endif
                                </div>
                                <div class="space-y-1">
                                    <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $reached ? 'bg-[#0B6E4F]' : 'bg-amber-500' }}" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="text-[10px] font-semibold {{ $reached ? 'text-[#0B6E4F]' : 'text-slate-500' }}">
                                        {{ $reached ? 'Target poin terpenuhi! Silakan konfirmasi ke panitia bazar.' : 'Kurang ' . number_format($remaining) . ' poin lagi (' . $pct . '%)' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Grid Stand Peserta & Top 10 Leaderboard -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Daftar Stand Peserta -->
                <div class="lg:col-span-7 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">
                            Daftar Stand Toko Peserta ({{ count($sum->visitedStoreIds) }} / {{ $sum->approvedStands->count() }} Dikunjungi)
                        </h3>
                        <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $sum->hasCheckedIn ? 'bg-emerald-100 text-[#0B6E4F]' : 'bg-slate-100 text-slate-500' }}">
                            {{ $sum->hasCheckedIn ? 'Sudah Check-in Acara' : 'Belum Check-in Pintu Masuk' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @forelse($sum->approvedStands as $stand)
                            @php
                                $visited = in_array($stand->store_id, $sum->visitedStoreIds, true);
                            @endphp
                            <div class="rounded-xl border p-3 flex items-center justify-between gap-2 text-xs {{ $visited ? 'bg-emerald-50/70 border-emerald-200' : 'bg-[#F8FAFC] border-slate-200' }}">
                                <div class="min-w-0">
                                    <span class="inline-block font-mono text-[10px] font-extrabold {{ $visited ? 'text-[#0B6E4F]' : 'text-slate-500' }}">
                                        {{ $stand->stand_number ?: 'STAND' }}
                                    </span>
                                    <p class="font-bold text-[#0F291E] truncate">{{ $stand->store?->store_name ?? 'Toko Peserta' }}</p>
                                    <p class="text-[10px] text-slate-400 truncate">{{ $stand->store?->student_class ?: 'Karya Siswa' }}</p>
                                </div>
                                @if($visited)
                                    <span class="shrink-0 rounded-lg bg-[#0B6E4F] text-white px-2 py-1 text-[10px] font-bold">
                                        Dikunjungi
                                    </span>
                                @else
                                    <a href="{{ $stand->store ? route('stores.show', $stand->store->slug) : '#' }}"
                                       class="shrink-0 rounded-lg border border-slate-200 bg-white hover:border-[#0B6E4F] text-slate-600 px-2 py-1 text-[10px] font-bold transition">
                                        Lihat Toko
                                    </a>
                                @endif
                            </div>
                        @empty
                            <p class="col-span-full text-xs text-slate-400">Belum ada stand toko peserta yang diumumkan.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Top 10 Peringkat Doorprize + Grafik -->
                <div class="lg:col-span-5 space-y-3">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Grafik &amp; Top 10 Peringkat Doorprize</h3>
                    @if($sum->topVisitors->isNotEmpty())
                        <div class="rounded-xl border border-slate-100 bg-[#F8FAFC] p-3 h-48">
                            <canvas class="buyer-bazaar-chart" data-chart='@json($sum->chartData)'></canvas>
                        </div>
                    @endif
                    <div class="rounded-xl border border-slate-100 divide-y divide-slate-100 overflow-hidden">
                        @forelse($sum->topVisitors as $topRow)
                            <div class="px-3.5 py-2.5 flex items-center justify-between gap-2 text-xs {{ $topRow->user->id === auth()->id() ? 'bg-emerald-50/70' : 'bg-white' }}">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-6 h-6 rounded-full font-black text-[10px] flex items-center justify-center shrink-0 {{ $topRow->rank === 1 ? 'bg-amber-400 text-amber-950' : ($topRow->rank <= 3 ? 'bg-emerald-100 text-[#0B6E4F]' : 'bg-slate-100 text-slate-500') }}">
                                        #{{ $topRow->rank }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-[#0F291E] truncate">
                                            {{ $topRow->user->name }}
                                            @if($topRow->user->id === auth()->id())
                                                <span class="text-[#0B6E4F] text-[10px]">(Anda)</span>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-slate-400">{{ $topRow->stand_visits_count }} stand &bull; {{ $topRow->transactions_count }} transaksi</p>
                                    </div>
                                </div>
                                <span class="font-black text-[#0B6E4F] shrink-0">{{ number_format($topRow->total_points) }} pts</span>
                            </div>
                        @empty
                            <p class="p-4 text-center text-xs text-slate-400">Belum ada peserta poin saat ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200/80 p-8 text-center space-y-2">
            <p class="text-sm font-bold text-[#0F291E]">Belum Ada Acara Bazar yang Sedang Berlangsung Hari Ini</p>
            <p class="text-xs text-slate-400">Silakan cek pengumuman atau kalender acara bazar sekolah di halaman utama.</p>
        </div>
    @endforelse

    <!-- Riwayat Perolehan Poin -->
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 space-y-3">
        <h3 class="text-sm font-extrabold text-[#0F291E]">Riwayat Poin Kunjungan & Transaksi Anda</h3>
        <div class="overflow-x-auto border border-slate-100 rounded-xl">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#F8FAFC] border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400">
                        <th class="py-2.5 px-4">WAKTU</th>
                        <th class="py-2.5 px-4">ACARA BAZAR</th>
                        <th class="py-2.5 px-4">AKTIVITAS</th>
                        <th class="py-2.5 px-4 text-right">POIN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($myLogs as $log)
                        <tr>
                            <td class="py-2.5 px-4 text-slate-400 whitespace-nowrap">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                            <td class="py-2.5 px-4 font-semibold text-[#0F291E]">{{ $log->bazaarEvent?->title ?? 'Bazar SMK' }}</td>
                            <td class="py-2.5 px-4 text-slate-600">{{ $log->description ?: $log->activity_label }}</td>
                            <td class="py-2.5 px-4 text-right font-black text-[#0B6E4F]">+{{ $log->points }} pts</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">Belum ada riwayat poin bazar tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        @keyframes scannerLaser {
            0% { top: 6%; opacity: 0.3; }
            50% { top: 90%; opacity: 1; }
            100% { top: 6%; opacity: 0.3; }
        }
        .animate-scanner-laser {
            animation: scannerLaser 2s ease-in-out infinite;
        }
        #qr-reader video {
            object-fit: cover !important;
            border-radius: 0.75rem !important;
            width: 100% !important;
            max-height: 380px !important;
        }
        #qr-reader__scan_region {
            background: transparent !important;
        }
        #qr-reader__dashboard {
            padding: 8px !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        let _bzScanner = null;
        let _bzBusy = false;

        // Feedback Audio Bip Ringan saat QR berhasil terdeteksi kamera
        function playScanBeep() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } catch (e) {}
        }

        function showScanFeedback(ok, msg) {
            const box = document.getElementById('qr-scan-feedback');
            if (!box) return;
            box.innerHTML = msg;
            box.className = 'rounded-xl px-3.5 py-2.5 text-xs font-bold ' + (ok
                ? 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border border-rose-200 text-rose-700');
            box.classList.remove('hidden');
        }

        function startBazaarCamera() {
            if (typeof Html5Qrcode === 'undefined') {
                showScanFeedback(false, 'Library kamera tidak dapat dimuat. Silakan gunakan input kode manual di samping atau scan langsung dari kamera HP.');
                return;
            }
            const placeholder = document.getElementById('qr-placeholder');
            const overlay = document.getElementById('qr-scanning-overlay');
            const startBtn = document.getElementById('btn-start-scan');
            const stopBtn = document.getElementById('btn-stop-scan');
            if (placeholder) placeholder.classList.add('hidden');
            if (overlay) overlay.classList.remove('hidden');
            if (startBtn) startBtn.classList.add('hidden');
            if (stopBtn) stopBtn.classList.remove('hidden');

            // Gunakan akselerasi hardware BarcodeDetector & format QR_CODE agar fokus instan dan responsif
            _bzScanner = new Html5Qrcode('qr-reader', {
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.QR_CODE
                ],
                verbose: false
            });

            const qrConfig = {
                fps: 20,
                qrbox: function(viewfinderWidth, viewfinderHeight) {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    const edge = Math.floor(minEdge * 0.78);
                    return { width: edge, height: edge };
                },
                aspectRatio: 1.0,
                videoConstraints: {
                    facingMode: { ideal: 'environment' },
                    focusMode: 'continuous',
                    width: { min: 640, ideal: 1280, max: 1920 }
                }
            };

            _bzScanner.start(
                { facingMode: 'environment' },
                qrConfig,
                function onScanSuccess(decodedText) {
                    if (_bzBusy) return;
                    _bzBusy = true;

                    // Audio dan getar HP saat terbaca
                    playScanBeep();
                    if (navigator.vibrate) {
                        try { navigator.vibrate([80, 40, 80]); } catch (e) {}
                    }

                    showScanFeedback(true, 'Kode QR terdeteksi! Sedang memvalidasi poin...');

                    fetch('{{ route('bazaar.scan.submit') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ code: decodedText })
                    })
                    .then(async (response) => {
                        // Deteksi proteksi keamanan sesi kedaluwarsa (419 / 401)
                        if (response.status === 419 || response.status === 401) {
                            showScanFeedback(false, 'Sesi login Anda telah kedaluwarsa demi keamanan. Mengalihkan ke login...');
                            stopBazaarCamera();
                            setTimeout(() => {
                                window.location.href = '{{ route('login') }}';
                            }, 1500);
                            return;
                        }

                        // Deteksi proteksi rate-limiting jika ada upaya spam scan
                        if (response.status === 429) {
                            showScanFeedback(false, 'Terlalu banyak permintaan scan dalam 1 menit (Rate Limit). Harap tunggu sejenak demi keamanan.');
                            setTimeout(() => { _bzBusy = false; }, 3000);
                            return;
                        }

                        let res;
                        try {
                            res = await response.json();
                        } catch (e) {
                            throw new Error('Respon tidak valid');
                        }

                        showScanFeedback(res.ok, res.message);
                        if (res.ok) {
                            stopBazaarCamera();
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            setTimeout(() => { _bzBusy = false; }, 2000);
                        }
                    })
                    .catch(() => {
                        showScanFeedback(false, 'Gagal memproses hasil scan. Periksa koneksi internet atau gunakan input kode manual.');
                        _bzBusy = false;
                    });
                }
            ).catch(() => {
                showScanFeedback(false, 'Akses kamera ditolak atau tidak tersedia pada perangkat ini. Anda tetap bisa menginput kode manual di sebelah kanan.');
                stopBazaarCamera();
            });
        }

        function stopBazaarCamera() {
            const placeholder = document.getElementById('qr-placeholder');
            const overlay = document.getElementById('qr-scanning-overlay');
            const startBtn = document.getElementById('btn-start-scan');
            const stopBtn = document.getElementById('btn-stop-scan');
            if (_bzScanner) {
                _bzScanner.stop().then(() => _bzScanner.clear()).catch(() => {});
                _bzScanner = null;
            }
            if (overlay) overlay.classList.add('hidden');
            if (placeholder) placeholder.classList.remove('hidden');
            if (startBtn) startBtn.classList.remove('hidden');
            if (stopBtn) stopBtn.classList.add('hidden');
        }

        // Jaga sesi tetap aktif selama pengguna berada di halaman scanner (ping tiap 8 menit)
        setInterval(() => {
            fetch('{{ route('bazaar.scan.page') }}', { method: 'HEAD' }).catch(() => {});
        }, 8 * 60 * 1000);

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;
            document.querySelectorAll('.buyer-bazaar-chart').forEach(canvas => {
                try {
                    const cfg = JSON.parse(canvas.getAttribute('data-chart') || '{}');
                    if (!cfg.labels || !cfg.labels.length) return;
                    const colors = (cfg.isMe || []).map(me => me ? '#D97706' : '#0B6E4F');
                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: cfg.labels,
                            datasets: [{
                                label: 'Total Poin',
                                data: cfg.points,
                                backgroundColor: colors,
                                borderRadius: 5,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, ticks: { font: { size: 10 } } },
                                y: { ticks: { font: { size: 10 } } },
                            },
                        },
                    });
                } catch (e) {}
            });
        });
    </script>
@endsection
