<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BazaarDoorprizeWinner;
use App\Models\BazaarEvent;
use App\Models\BazaarStoreParticipant;
use App\Models\BazaarVisitorPoint;
use App\Models\Store;
use App\Models\User;
use App\Services\BazaarService;
use App\Services\QrCodeSvg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ============================================================================
 * CONTROLLER: BAZAAR MANAGEMENT CONTROLLER
 * ============================================================================
 * Mengelola seluruh ekosistem Acara Bazar:
 *   1. Admin & Super Admin:
 *      - Kelola peserta toko (pilih langsung jadi peserta resmi atau setujui/tolak pengajuan toko)
 *      - Pengaturan bobot poin (Check-in Hadir, Scan Kunjungan Stand, Transaksi)
 *      - Ranking Toko KHUSUS event bazar (Kunjungan Stand, Transaksi, Omzet, View, Rating)
 *      - Ranking Pengunjung/Pembeli & Penetapan Pemenang Doorprize Panitia
 *      - Cetak Barcode/QR Kehadiran Event Bazar & QR Stand Toko Peserta
 *   2. Pemilik Toko (Seller):
 *      - Mengajukan toko untuk mengikuti event bazar (`sellerRequestJoin`)
 *      - Mencetak QR Code Stand Tokonya setelah disetujui (`sellerPrintStandQr`)
 *   3. Pembeli / Pengunjung Berakun (Buyer & Semua User Login):
 *      - Halaman Scan QR Kamera & Input Kode Singkat + Progress Poin Bazar (`scanPage`)
 *      - Endpoint Scan Otomatis dari Kamera HP (`handleTokenScan`) & Manual/AJAX (`submitManualScan`)
 * ============================================================================
 */
class BazaarManagementController extends Controller
{
    public function __construct(private readonly BazaarService $bazaarService)
    {
    }

    /* ======================================================================
     * 1. PANEL ADMIN & SUPER ADMIN: KELOLA BAZAR, PESERTA, RANKING & DOORPRIZE
     * ====================================================================== */

    public function show(Request $request, BazaarEvent $bazaarEvent): View
    {
        $bazaarEvent->ensureTokensExist();

        $sortBy = (string) $request->query('sort', 'score');
        if (! in_array($sortBy, ['score', 'visits', 'orders', 'revenue', 'views', 'rating'], true)) {
            $sortBy = 'score';
        }

        $participants = BazaarStoreParticipant::with(['store.owner', 'requester', 'approver'])
            ->where('bazaar_event_id', $bazaarEvent->id)
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END")
            ->orderBy('stand_number')
            ->get();

        $participatingStoreIds = $participants->pluck('store_id')->all();
        $availableStores = Store::with('owner')
            ->whereNotIn('id', $participatingStoreIds)
            ->orderBy('store_name')
            ->get();

        $storeRankings      = $this->bazaarService->getStoreRankings($bazaarEvent, $sortBy);
        $visitorRankings    = $this->bazaarService->getVisitorRankings($bazaarEvent);
        $selectableVisitors = User::orderBy('name')->get(['id', 'name', 'email', 'role', 'user_subtype']);

        $eventCheckinUrl = route('bazaar.scan.token', $bazaarEvent->checkin_token);
        $eventQrSvg = QrCodeSvg::render($eventCheckinUrl, 210);

        $standQrSvgs = [];
        foreach ($participants->where('status', 'approved') as $part) {
            $standQrSvgs[$part->id] = QrCodeSvg::render(route('bazaar.scan.token', $part->stand_token), 120);
        }

        $summary = [
            'approved_stores' => $participants->where('status', 'approved')->count(),
            'pending_stores'  => $participants->where('status', 'pending')->count(),
            'total_checkins'  => BazaarVisitorPoint::where('bazaar_event_id', $bazaarEvent->id)->where('activity_type', 'event_checkin')->count(),
            'total_stands'    => (int) $storeRankings->sum('stand_visits_count'),
            'total_orders'    => (int) $storeRankings->sum('orders_count'),
            'total_revenue'   => (float) $storeRankings->sum('revenue'),
            'total_visitors'  => $visitorRankings->count(),
            'winners_count'   => $bazaarEvent->doorprizeWinners()->count(),
        ];

        return view('admin.bazaar-show', compact(
            'bazaarEvent',
            'participants',
            'availableStores',
            'storeRankings',
            'visitorRankings',
            'selectableVisitors',
            'sortBy',
            'eventCheckinUrl',
            'eventQrSvg',
            'standQrSvgs',
            'summary'
        ));
    }

    public function updateSettings(Request $request, BazaarEvent $bazaarEvent): RedirectResponse
    {
        $validated = $request->validate([
            'points_checkin'      => ['required', 'integer', 'min:0', 'max:10000'],
            'points_stand_visit'  => ['required', 'integer', 'min:0', 'max:10000'],
            'points_transaction'  => ['required', 'integer', 'min:0', 'max:10000'],
            'allow_self_register' => ['nullable', 'boolean'],
        ]);

        $bazaarEvent->update([
            'points_checkin'      => (int) $validated['points_checkin'],
            'points_stand_visit'  => (int) $validated['points_stand_visit'],
            'points_transaction'  => (int) $validated['points_transaction'],
            'allow_self_register' => $request->boolean('allow_self_register'),
        ]);

        return back()->with('success', 'Pengaturan bobot poin & pendaftaran peserta bazar berhasil diperbarui.');
    }

    public function addStoreParticipants(Request $request, BazaarEvent $bazaarEvent): RedirectResponse
    {
        $validated = $request->validate([
            'store_ids'    => ['required', 'array', 'min:1'],
            'store_ids.*'  => ['integer', 'exists:stores,id'],
            'stand_prefix' => ['nullable', 'string', 'max:15'],
        ]);

        $actor = $request->user();
        $prefix = trim((string) ($validated['stand_prefix'] ?? 'ST-'));
        $existingCount = BazaarStoreParticipant::where('bazaar_event_id', $bazaarEvent->id)->count();
        $added = 0;

        foreach (array_values(array_unique(array_map('intval', $validated['store_ids']))) as $storeId) {
            $already = BazaarStoreParticipant::where('bazaar_event_id', $bazaarEvent->id)
                ->where('store_id', $storeId)
                ->first();

            $existingCount++;
            $autoStand = $prefix . str_pad((string) $existingCount, 2, '0', STR_PAD_LEFT);

            if ($already) {
                $already->update([
                    'status'           => 'approved',
                    'stand_number'     => $already->stand_number ?: $autoStand,
                    'approved_by'      => $actor->id,
                    'approved_at'      => now(),
                    'rejection_reason' => null,
                ]);
            } else {
                BazaarStoreParticipant::create([
                    'bazaar_event_id' => $bazaarEvent->id,
                    'store_id'        => $storeId,
                    'stand_number'    => $autoStand,
                    'status'          => 'approved',
                    'requested_by'    => $actor->id,
                    'approved_by'     => $actor->id,
                    'approved_at'     => now(),
                ]);
            }
            $added++;
        }

        ActivityLog::record(
            'bazaar_participant_add',
            'Penambahan Peserta Toko Bazar',
            "{$actor->name} memilih {$added} toko sebagai peserta resmi bazar '{$bazaarEvent->title}'."
        );

        return back()->with('success', "{$added} toko berhasil ditambahkan ke daftar peserta resmi bazar.");
    }

    public function approveParticipant(Request $request, BazaarEvent $bazaarEvent, BazaarStoreParticipant $participant): RedirectResponse
    {
        abort_unless($participant->bazaar_event_id === $bazaarEvent->id, 404);

        $validated = $request->validate([
            'stand_number' => ['nullable', 'string', 'max:30'],
        ]);

        $standNum = trim((string) ($validated['stand_number'] ?? ''));
        if ($standNum === '') {
            $nextNum = BazaarStoreParticipant::where('bazaar_event_id', $bazaarEvent->id)->where('status', 'approved')->count() + 1;
            $standNum = $participant->stand_number ?: ('ST-' . str_pad((string) $nextNum, 2, '0', STR_PAD_LEFT));
        }

        $participant->update([
            'status'           => 'approved',
            'stand_number'     => $standNum,
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ]);

        $storeName = $participant->store?->store_name ?? 'Toko';

        return back()->with('success', "Pengajuan toko '{$storeName}' disetujui untuk stand {$standNum}. Barcode stand siap dicetak.");
    }

    public function rejectParticipant(Request $request, BazaarEvent $bazaarEvent, BazaarStoreParticipant $participant): RedirectResponse
    {
        abort_unless($participant->bazaar_event_id === $bazaarEvent->id, 404);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $participant->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? 'Belum memenuhi kuota/kriteria bazar.',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
        ]);

        return back()->with('success', 'Pengajuan keikutsertaan toko telah ditolak.');
    }

    public function updateStandNumber(Request $request, BazaarEvent $bazaarEvent, BazaarStoreParticipant $participant): RedirectResponse
    {
        abort_unless($participant->bazaar_event_id === $bazaarEvent->id, 404);

        $validated = $request->validate([
            'stand_number' => ['required', 'string', 'max:30'],
        ]);

        $participant->update(['stand_number' => trim($validated['stand_number'])]);

        return back()->with('success', 'Nomor stand toko berhasil diperbarui.');
    }

    public function removeParticipant(BazaarEvent $bazaarEvent, BazaarStoreParticipant $participant): RedirectResponse
    {
        abort_unless($participant->bazaar_event_id === $bazaarEvent->id, 404);

        $storeName = $participant->store?->store_name ?? 'Toko';
        $participant->delete();

        return back()->with('success', "Kepesertaan '{$storeName}' dihapus dari acara bazar ini.");
    }

    public function awardDoorprize(Request $request, BazaarEvent $bazaarEvent): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'               => ['required', 'integer', 'exists:users,id'],
            'prize_name'            => ['required', 'string', 'max:120'],
            'rank_at_award'         => ['nullable', 'integer', 'min:1'],
            'total_points_at_award' => ['nullable', 'integer', 'min:0'],
        ]);

        $targetUser = User::findOrFail((int) $validated['user_id']);
        $totalPoints = $validated['total_points_at_award'] ?? $this->bazaarService->getUserTotalPoints($bazaarEvent, $targetUser);

        BazaarDoorprizeWinner::updateOrCreate(
            ['bazaar_event_id' => $bazaarEvent->id, 'user_id' => $targetUser->id],
            [
                'prize_name'            => trim($validated['prize_name']),
                'rank_at_award'         => $validated['rank_at_award'] ?? null,
                'total_points_at_award' => (int) $totalPoints,
                'awarded_by'            => $request->user()->id,
                'awarded_at'            => now(),
            ]
        );

        return back()->with('success', "Selamat! {$targetUser->name} ditetapkan sebagai pemenang doorprize '{$validated['prize_name']}'.");
    }

    public function revokeDoorprize(BazaarEvent $bazaarEvent, BazaarDoorprizeWinner $winner): RedirectResponse
    {
        abort_unless($winner->bazaar_event_id === $bazaarEvent->id, 404);

        $name = $winner->user?->name ?? 'Pengunjung';
        $winner->delete();

        return back()->with('success', "Status pemenang doorprize untuk {$name} telah dibatalkan.");
    }

    public function adjustVisitorPoints(Request $request, BazaarEvent $bazaarEvent): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'     => ['required', 'integer', 'exists:users,id'],
            'points'      => ['required', 'integer', 'min:-10000', 'max:10000', 'not_in:0'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $targetUser = User::findOrFail((int) $validated['user_id']);
        $actor      = $request->user();
        $delta      = (int) $validated['points'];
        $note       = trim((string) ($validated['description'] ?? ''));

        if ($note === '') {
            $note = $delta > 0 ? "Bonus poin panitia (+{$delta})" : "Koreksi poin panitia ({$delta})";
        }

        BazaarVisitorPoint::create([
            'bazaar_event_id' => $bazaarEvent->id,
            'user_id'         => $targetUser->id,
            'store_id'        => null,
            'order_id'        => null,
            'activity_type'   => 'bonus_admin',
            'points'          => $delta,
            'description'     => "{$note} — oleh {$actor->name}",
        ]);

        ActivityLog::record(
            'bazaar_points_adjust',
            'Penyesuaian Poin Pengunjung Bazar',
            "{$actor->name} mengubah poin {$targetUser->name} sebesar {$delta} poin pada bazar '{$bazaarEvent->title}'."
        );

        $sign = $delta > 0 ? "+{$delta}" : (string) $delta;

        return back()->with('success', "Berhasil mencatat {$sign} poin untuk {$targetUser->name} ({$note}).");
    }

    /* ======================================================================
     * 2. HALAMAN CETAK BARCODE / QR CODE (EVENT CHECK-IN & STAND TOKO)
     * ====================================================================== */

    public function printEventQr(BazaarEvent $bazaarEvent): View
    {
        $bazaarEvent->ensureTokensExist();
        $scanUrl = route('bazaar.scan.token', $bazaarEvent->checkin_token);
        $qrSvg   = QrCodeSvg::render($scanUrl, 320, 4);

        return view('bazaar.print-qr', [
            'mode'        => 'event',
            'bazaarEvent' => $bazaarEvent,
            'cards'       => [
                [
                    'badge'     => 'CHECK-IN KEHADIRAN BAZAR',
                    'title'     => $bazaarEvent->title,
                    'subtitle'  => $bazaarEvent->location ?: 'SMK Plus Al Hilal',
                    'points'    => $bazaarEvent->points_checkin ?? 50,
                    'shortCode' => $bazaarEvent->short_code,
                    'scanUrl'   => $scanUrl,
                    'qrSvg'     => $qrSvg,
                    'footer'    => 'Scan barcode ini saat tiba di area acara bazar menggunakan kamera HP atau menu Scan & Poin Bazar di akun Hilalkraf Anda.',
                ],
            ],
        ]);
    }

    public function printStandQr(BazaarEvent $bazaarEvent, BazaarStoreParticipant $participant): View
    {
        abort_unless($participant->bazaar_event_id === $bazaarEvent->id, 404);
        $participant->loadMissing('store.owner');

        $scanUrl = route('bazaar.scan.token', $participant->stand_token);
        $qrSvg   = QrCodeSvg::render($scanUrl, 300, 4);
        $standLabel = $participant->stand_number ? "Stand {$participant->stand_number}" : 'Stand Peserta';

        return view('bazaar.print-qr', [
            'mode'        => 'stand',
            'bazaarEvent' => $bazaarEvent,
            'cards'       => [
                [
                    'badge'     => strtoupper($standLabel),
                    'title'     => $participant->store?->store_name ?? 'Toko Karya Siswa',
                    'subtitle'  => ($participant->store?->student_class ?: 'Karya Siswa SMK PAL') . ' • ' . $bazaarEvent->title,
                    'points'    => $bazaarEvent->points_stand_visit ?? 20,
                    'shortCode' => $participant->stand_code,
                    'scanUrl'   => $scanUrl,
                    'qrSvg'     => $qrSvg,
                    'footer'    => 'Scan barcode stand toko ini saat berkunjung untuk mendapatkan poin tambahan pengunjung & peluang Doorprize!',
                ],
            ],
        ]);
    }

    public function printAllStandsQr(BazaarEvent $bazaarEvent): View
    {
        $bazaarEvent->ensureTokensExist();
        $approved = BazaarStoreParticipant::with('store')
            ->where('bazaar_event_id', $bazaarEvent->id)
            ->where('status', 'approved')
            ->orderBy('stand_number')
            ->get();

        $cards = [];
        // Kartu Pertama: QR Kehadiran Utama
        $eventUrl = route('bazaar.scan.token', $bazaarEvent->checkin_token);
        $cards[] = [
            'badge'     => 'QR PINTU MASUK ACARA',
            'title'     => $bazaarEvent->title,
            'subtitle'  => $bazaarEvent->location ?: 'SMK Plus Al Hilal',
            'points'    => $bazaarEvent->points_checkin ?? 50,
            'shortCode' => $bazaarEvent->short_code,
            'scanUrl'   => $eventUrl,
            'qrSvg'     => QrCodeSvg::render($eventUrl, 220, 3),
            'footer'    => 'Scan sekali saat hadir di acara bazar untuk klaim poin kehadiran.',
        ];

        foreach ($approved as $part) {
            $standUrl = route('bazaar.scan.token', $part->stand_token);
            $standLabel = $part->stand_number ? "STAND {$part->stand_number}" : 'STAND PESERTA';
            $cards[] = [
                'badge'     => $standLabel,
                'title'     => $part->store?->store_name ?? 'Toko Peserta',
                'subtitle'  => $part->store?->student_class ?: 'SMK Plus Al Hilal',
                'points'    => $bazaarEvent->points_stand_visit ?? 20,
                'shortCode' => $part->stand_code,
                'scanUrl'   => $standUrl,
                'qrSvg'     => QrCodeSvg::render($standUrl, 220, 3),
                'footer'    => 'Scan saat mengunjungi stand toko ini untuk menambah poin doorprize.',
            ];
        }

        return view('bazaar.print-qr', [
            'mode'        => 'all',
            'bazaarEvent' => $bazaarEvent,
            'cards'       => $cards,
        ]);
    }

    public function exportCsv(Request $request, BazaarEvent $bazaarEvent): StreamedResponse
    {
        $type = $request->query('type', 'stores');
        $slug = Str::slug($bazaarEvent->title);

        if ($type === 'visitors') {
            $rows = $this->bazaarService->getVisitorRankings($bazaarEvent);
            $filename = "ranking-pengunjung-doorprize-{$slug}.csv";

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Peringkat', 'Nama Pengunjung', 'Kategori', 'Email', 'No HP', 'Poin Hadir', 'Jml Stand', 'Poin Stand', 'Jml Transaksi', 'Poin Transaksi', 'Total Poin', 'Status Doorprize']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->rank,
                        $row->user->name,
                        $row->user->subtype_label,
                        $row->user->email,
                        $row->user->phone ?? '-',
                        $row->checkin_points,
                        $row->stand_visits_count,
                        $row->stand_visit_points,
                        $row->transactions_count,
                        $row->transaction_points,
                        $row->total_points,
                        $row->doorprize_winner ? 'Pemenang: ' . $row->doorprize_winner->prize_name : '-',
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $rows = $this->bazaarService->getStoreRankings($bazaarEvent, 'score');
        $filename = "ranking-toko-bazar-{$slug}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Peringkat', 'No Stand', 'Nama Toko', 'Kelas/Jurusan', 'Kunjungan Stand (Scan QR)', 'View Katalog Periode Bazar', 'Transaksi Bazar', 'Item Terjual', 'Omzet Bazar (Rp)', 'Rating Bazar', 'Skor Komposit']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->rank,
                    $row->stand_number,
                    $row->store->store_name,
                    $row->store->student_class ?: '-',
                    $row->stand_visits_count,
                    $row->catalog_views_count,
                    $row->orders_count,
                    $row->items_sold,
                    (int) $row->revenue,
                    $row->avg_rating,
                    $row->composite_score,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ======================================================================
     * 3. PEMILIK TOKO (SELLER): AJUKAN IKUT BAZAR & CETAK QR STAND SENDIRI
     * ====================================================================== */

    public function sellerRequestJoin(Request $request, BazaarEvent $bazaarEvent): RedirectResponse
    {
        $store = $request->user()->store;
        abort_unless($store !== null, 403, 'Anda harus memiliki toko untuk mengajukan diri.');

        if (! $bazaarEvent->allow_self_register) {
            return back()->with('error', "Pendaftaran mandiri toko untuk bazar '{$bazaarEvent->title}' saat ini ditutup oleh panitia.");
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = BazaarStoreParticipant::where('bazaar_event_id', $bazaarEvent->id)
            ->where('store_id', $store->id)
            ->first();

        if ($existing && $existing->status === 'approved') {
            return back()->with('info', "Toko Anda sudah terdaftar resmi sebagai peserta '{$bazaarEvent->title}'.");
        }

        BazaarStoreParticipant::updateOrCreate(
            ['bazaar_event_id' => $bazaarEvent->id, 'store_id' => $store->id],
            [
                'status'           => 'pending',
                'notes'            => $validated['notes'] ?? null,
                'rejection_reason' => null,
                'requested_by'     => $request->user()->id,
            ]
        );

        return back()->with('success', "Pengajuan toko '{$store->store_name}' untuk mengikuti '{$bazaarEvent->title}' telah dikirim. Menunggu persetujuan Admin / Super Admin.");
    }

    public function sellerPrintStandQr(Request $request, BazaarEvent $bazaarEvent): View|RedirectResponse
    {
        $store = $request->user()->store;
        abort_unless($store !== null, 403);

        $participant = BazaarStoreParticipant::where('bazaar_event_id', $bazaarEvent->id)
            ->where('store_id', $store->id)
            ->where('status', 'approved')
            ->first();

        if (! $participant) {
            return back()->with('error', 'Toko Anda belum berstatus disetujui sebagai peserta pada acara bazar ini.');
        }

        return $this->printStandQr($bazaarEvent, $participant);
    }

    /* ======================================================================
     * 4. PEMBELI / PENGUNJUNG BERAKUN: SCAN BARCODE, POIN & LEADERBOARD
     * ====================================================================== */

    public function scanPage(Request $request): View
    {
        $user = $request->user();
        $activeEvents = BazaarEvent::activeEvents();

        $eventSummaries = $activeEvents->map(function (BazaarEvent $ev) use ($user) {
            $ev->ensureTokensExist();

            $approvedStands = BazaarStoreParticipant::with('store')
                ->where('bazaar_event_id', $ev->id)
                ->where('status', 'approved')
                ->orderBy('stand_number')
                ->get();

            $visitedStoreIds = Schema::hasTable('bazaar_visitor_points')
                ? BazaarVisitorPoint::where('bazaar_event_id', $ev->id)
                    ->where('user_id', $user->id)
                    ->where('activity_type', 'stand_visit')
                    ->pluck('store_id')
                    ->all()
                : [];

            $rankings = Schema::hasTable('bazaar_visitor_points')
                ? $this->bazaarService->getVisitorRankings($ev)
                : collect();

            $myRankRow = $rankings->first(fn ($r) => $r->user->id === $user->id);

            return (object) [
                'event'           => $ev,
                'approvedStands'  => $approvedStands,
                'visitedStoreIds' => $visitedStoreIds,
                'hasCheckedIn'    => ($myRankRow?->checkin_count ?? 0) > 0,
                'totalPoints'     => $myRankRow?->total_points ?? 0,
                'myRank'          => $myRankRow?->rank ?? null,
                'topVisitors'     => $rankings->take(10),
                'doorprizeWinner' => $myRankRow?->doorprize_winner ?? null,
            ];
        });

        $myLogs = Schema::hasTable('bazaar_visitor_points')
            ? BazaarVisitorPoint::with(['bazaarEvent', 'store'])
                ->where('user_id', $user->id)
                ->latest()
                ->take(30)
                ->get()
            : collect();

        return view('buyer.bazaar-scan', compact('eventSummaries', 'myLogs'));
    }

    public function handleTokenScan(Request $request, string $token): RedirectResponse
    {
        $result = $this->bazaarService->resolveScanInput($token, $request->user());

        $flashType = $result['ok'] ? 'success' : 'error';

        return redirect()->route('bazaar.scan.page')
            ->with($flashType, $result['message']);
    }

    public function submitManualScan(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->bazaarService->resolveScanInput($validated['code'], $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok'           => $result['ok'],
                'status'       => $result['status'],
                'points'       => $result['points'] ?? 0,
                'total_points' => $result['total_points'] ?? 0,
                'message'      => $result['message'],
            ], $result['ok'] ? 200 : 422);
        }

        return redirect()->route('bazaar.scan.page')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
