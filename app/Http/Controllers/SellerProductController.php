<?php

namespace App\Http\Controllers;

use App\Models\BazaarEvent;
use App\Models\Category;
use App\Models\ClassMajor;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: SELLER PRODUCT CONTROLLER (SATU TOKO SATU AKUN, DISKON & PROGRES)
 * ============================================================================
 * Fungsi:
 *   Mengelola dashboard Pemilik Toko (baik Siswa maupun Guru), termasuk:
 *   1. Mengelola Profil Toko & Edit Profil Lengkap Pemilik Toko (Satu Toko Satu Akun).
 *   2. Mengelola Skema Diskon Jenis Pembeli (Pelajar, Keluarga SMK, Alumni, Umum)
 *      dan Diskon Tambahan saat Bazar SMK (`updateDiscounts`).
 *   3. Mengelola Progres Pesanan 4 Tahap:
 *      `pesan` (Dipesan) -> `kemas` (Dikemas) -> `kirim` (Dikirim / Dalam Perjalanan) -> `sampai` (Sampai).
 *   4. CRUD Produk Karya.
 * ============================================================================
 */
class SellerProductController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Pastikan prinsip Satu Toko Satu Akun (1-to-1)
        $store = $user->store()->withCount(['products', 'orders', 'comments'])->first();
        if (! $store) {
            $store = $user->store()->create([
                'store_name'     => 'Toko ' . $user->name,
                'slug'           => Str::slug('toko-' . $user->name) . '-' . Str::lower(Str::random(4)),
                'seller_type'    => $user->is_teacher ? 'guru' : ($user->user_subtype ?? 'siswa'),
                'student_class'  => trim(($user->class_name ?? '') . ' ' . ($user->major_name ?? 'SMK Plus Al Hilal')),
                'description'    => 'Toko Karya Resmi SMK Plus Al Hilal',
                'contact_number' => $user->phone ?? '081234567890',
                'is_verified'    => false,
            ]);
            $store->loadCount(['products', 'orders', 'comments']);
        }

        $products = $store->products()->withCount('comments')->latest()->paginate(10);
        $orders = $store->orders()->with(['buyer', 'items.product', 'bazaarEvent'])->latest()->get();
        $activeBazaarEvents = BazaarEvent::activeEvents();

        $classMajors = ClassMajor::where('is_active', true)->orderBy('class_name')->orderBy('major_name')->get();
        $classList = $classMajors->pluck('class_name')->unique()->values();
        $majorList = $classMajors->pluck('major_name')->unique()->values();

        return view('seller.dashboard', compact('user', 'store', 'products', 'orders', 'classList', 'majorList', 'activeBazaarEvents'));
    }

    /**
     * Fungsi:
     *   Menyimpan skema persentase diskon berdasarkan Jenis Pembeli (Pelajar, Keluarga SMK,
     *   Alumni, Umum) serta diskon ekstra toko saat acara Bazar SMK berlangsung.
     *
     * Algoritma:
     *   1. Validasi persentase diskon antara 0% sampai 90%.
     *   2. Simpan pada model `Store` milik penjual yang sedang login.
     */
    public function updateDiscounts(Request $request): RedirectResponse
    {
        $store = $request->user()->store;
        abort_unless($store !== null, 403);

        $validated = $request->validate([
            'discount_pelajar'     => ['required', 'integer', 'min:0', 'max:90'],
            'discount_keluarga'    => ['required', 'integer', 'min:0', 'max:90'],
            'discount_alumni'      => ['required', 'integer', 'min:0', 'max:90'],
            'discount_umum'        => ['required', 'integer', 'min:0', 'max:90'],
            'discount_bazar_extra' => ['required', 'integer', 'min:0', 'max:90'],
        ]);

        $store->update($validated);

        return back()->with('success', 'Skema diskon jenis pembeli & diskon bazar toko berhasil disimpan!');
    }

    /**
     * Fungsi:
     *   Memperbarui Profil Lengkap Pemilik Toko (Siswa/Guru) sekaligus Profil Toko
     *   dengan menjaga aturan Satu Toko Satu Akun.
     *
     * Algoritma (Role-Specific Profile Validation & 1-to-1 Sync):
     *   1. Mengecek apakah pemilik toko adalah Siswa (`seller_type === 'siswa'`) atau Guru (`guru`).
     *   2. Jika Siswa: wajib mengisi `name`, `nis_nisn`, `class_name`, `major_name`,
     *      `desa`, `kecamatan`, `kabupaten`, `kode_pos`, `phone`.
     *   3. Jika Guru: wajib mengisi `name`, `nuptk`, `desa`, `kecamatan`, `kabupaten`,
     *      `kode_pos`, `phone`.
     *   4. Memperbarui record `User` dan record tunggal `Store` miliknya.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $store = $user->store;
        $sellerType = $request->input('seller_type', $store?->seller_type ?? 'siswa');

        $rules = [
            'name'           => ['required', 'string', 'max:120'],
            'phone'          => ['required', 'string', 'max:25'],
            'seller_type'    => ['required', 'in:siswa,guru'],
            'desa'           => ['required', 'string', 'max:100'],
            'kecamatan'      => ['required', 'string', 'max:100'],
            'kabupaten'      => ['required', 'string', 'max:100'],
            'kode_pos'       => ['required', 'string', 'max:15'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'store_name'     => ['required', 'string', 'max:120'],
            'description'    => ['required', 'string', 'max:500'],
        ];

        if ($sellerType === 'siswa') {
            $rules['nis_nisn']   = ['required', 'string', 'max:30'];
            $rules['class_name'] = ['required', 'string', 'max:50'];
            $rules['major_name'] = ['required', 'string', 'max:120'];
        } else {
            $rules['nuptk']      = ['required', 'string', 'max:30'];
            $rules['major_name'] = ['nullable', 'string', 'max:120'];
        }

        $validated = $request->validate($rules);

        $user->update([
            'name'           => $validated['name'],
            'phone'          => $validated['phone'],
            'user_subtype'   => $sellerType,
            'is_teacher'     => $sellerType === 'guru',
            'nis_nisn'       => $sellerType === 'siswa' ? $validated['nis_nisn'] : $user->nis_nisn,
            'nuptk'          => $sellerType === 'guru' ? $validated['nuptk'] : $user->nuptk,
            'class_name'     => $sellerType === 'siswa' ? $validated['class_name'] : $user->class_name,
            'major_name'     => $validated['major_name'] ?? $user->major_name,
            'desa'           => $validated['desa'],
            'kecamatan'      => $validated['kecamatan'],
            'kabupaten'      => $validated['kabupaten'],
            'kode_pos'       => $validated['kode_pos'],
            'address_detail' => $validated['address_detail'] ?? null,
        ]);

        $studentClassLabel = $sellerType === 'siswa'
            ? trim(($validated['class_name'] ?? '') . ' - ' . ($validated['major_name'] ?? ''))
            : 'Guru / Pembina ' . ($validated['major_name'] ?? 'SMK Plus Al Hilal');

        $store->update([
            'store_name'     => $validated['store_name'],
            'seller_type'    => $sellerType,
            'student_class'  => Str::limit($studentClassLabel, 250, ''),
            'description'    => $validated['description'],
            'contact_number' => $validated['phone'],
        ]);

        return back()->with('success', 'Profil lengkap pemilik toko & identitas toko berhasil diperbarui!');
    }

    public function create(Request $request): View
    {
        $store = $request->user()->store;
        $categories = Category::orderBy('name')->pluck('name');

        return view('seller.product-form', [
            'store'      => $store,
            'product'    => null,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:150'],
            'category'     => ['required', 'string', 'max:100'],
            'price'        => ['required', 'numeric', 'min:1000'],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['required', 'string'],
            'image'        => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $imagePath = $request->file('image')->store('products', 'public');

        $store->products()->create([
            'product_name' => $validated['product_name'],
            'slug'         => Str::slug($validated['product_name']) . '-' . Str::lower(Str::random(5)),
            'category'     => $validated['category'],
            'price'        => $validated['price'],
            'stock'        => $validated['stock'],
            'description'  => $validated['description'],
            'image'        => $imagePath,
            'is_active'    => true,
        ]);

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Karya produk baru berhasil ditambahkan ke katalog toko Anda!');
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorizeProductOwner($request, $product);
        $categories = Category::orderBy('name')->pluck('name');

        return view('seller.product-form', [
            'store'      => $request->user()->store,
            'product'    => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProductOwner($request, $product);

        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:150'],
            'category'     => ['required', 'string', 'max:100'],
            'price'        => ['required', 'numeric', 'min:1000'],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['required', 'string'],
            'image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Data produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeProductOwner($request, $product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()
            ->route('seller.dashboard')
            ->with('success', 'Produk berhasil dihapus dari toko Anda.');
    }

    /**
     * Fungsi:
     *   Memperbarui progres pesanan pada toko (`pesan` -> `kemas` -> `kirim` -> `sampai` -> `dibatalkan`)
     *   beserta catatan progres/pengiriman untuk pembeli.
     *
     * Algoritma:
     *   1. Pastikan pesanan milik toko penjual yang sedang login.
     *   2. Validasi `progress_status` (atau fallback `status` lama) dan `progress_note`.
     *   3. Sinkronkan `progress_status` dengan kolom `status` serta timestamp tahapan
     *      (`packed_at`, `shipped_at`, `delivered_at`).
     */
    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->store_id === $request->user()->store?->id, 403);

        $validated = $request->validate([
            'progress_status' => ['nullable', 'in:pesan,kemas,kirim,sampai,dibatalkan'],
            'status'          => ['nullable', 'in:pending,confirmed,completed,cancelled'],
            'progress_note'   => ['nullable', 'string', 'max:255'],
        ]);

        $progress = $validated['progress_status'] ?? match ($validated['status'] ?? 'pending') {
            'confirmed' => 'kemas',
            'completed' => 'sampai',
            'cancelled' => 'dibatalkan',
            default     => 'pesan',
        };

        // Aturan: Penjual tidak dapat membatalkan pesanan jika pesanan sudah selesai (Sampai)
        if ($progress === 'dibatalkan' && ($order->effective_progress === 'sampai' || $order->status === 'completed')) {
            return back()->with('error', 'Pesanan yang sudah selesai (Sampai) tidak dapat dibatalkan.');
        }

        $legacyStatus = match ($progress) {
            'kemas', 'kirim' => 'confirmed',
            'sampai'         => 'completed',
            'dibatalkan'     => 'cancelled',
            default          => 'pending',
        };

        $updatePayload = [
            'progress_status' => $progress,
            'status'          => $legacyStatus,
        ];

        if ($request->has('progress_note')) {
            $updatePayload['progress_note'] = $validated['progress_note'];
        }

        if ($progress === 'kemas' && ! $order->packed_at) {
            $updatePayload['packed_at'] = now();
        } elseif ($progress === 'kirim') {
            $updatePayload['packed_at'] = $order->packed_at ?? now();
            $updatePayload['shipped_at'] = $order->shipped_at ?? now();
        } elseif ($progress === 'sampai') {
            $updatePayload['packed_at'] = $order->packed_at ?? now();
            $updatePayload['shipped_at'] = $order->shipped_at ?? now();
            $updatePayload['delivered_at'] = $order->delivered_at ?? now();
        } elseif ($progress === 'dibatalkan') {
            // Kembalikan stok jika pesanan dibatalkan oleh penjual
            if ($order->effective_progress !== 'dibatalkan') {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            }
        }

        $order->update($updatePayload);

        return back()->with('success', "Progres pesanan #{$order->order_code} berhasil diperbarui ke tahap: {$order->progress_label}.");
    }

    private function authorizeProductOwner(Request $request, Product $product): void
    {
        abort_unless(
            $request->user()->store && $product->store_id === $request->user()->store->id,
            403,
            'Anda hanya dapat mengelola produk milik toko Anda sendiri.'
        );
    }
}
