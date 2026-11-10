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
        $isTeacherOrAdmin = $user->is_teacher || in_array($user->role, ['admin', 'super_admin']);
        $store = $user->store()->withCount(['products', 'orders', 'comments'])->first();
        if (! $store) {
            $store = $user->store()->create([
                'store_name'     => 'Toko ' . $user->name,
                'slug'           => Str::slug('toko-' . $user->name) . '-' . Str::lower(Str::random(4)),
                'seller_type'    => $isTeacherOrAdmin ? 'guru' : ($user->user_subtype ?? 'siswa'),
                'student_class'  => $isTeacherOrAdmin ? 'Guru SMK Plus Al Hilal' : trim(($user->class_name ?? '') . ' ' . ($user->major_name ?? 'SMK Plus Al Hilal')),
                'description'    => $isTeacherOrAdmin ? 'Toko Karya Resmi Guru SMK Plus Al Hilal' : 'Toko Karya Resmi SMK Plus Al Hilal',
                'contact_number' => $user->phone ?? '081234567890',
                'is_verified'    => $isTeacherOrAdmin ? true : false,
            ]);
            $store->loadCount(['products', 'orders', 'comments']);
        } elseif ($isTeacherOrAdmin && ! $store->is_verified) {
            // Toko guru admin otomatis terverifikasi
            $store->update(['is_verified' => true]);
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

        return redirect()->to(route('seller.dashboard') . '#section-diskon')->with('success', 'Skema diskon jenis pembeli & diskon bazar toko berhasil disimpan!');
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

        // Normalisasi major_name dan class_name (hindari bentrok elemen form HTML)
        $majorName = $request->input('major_name');
        if (empty($majorName) && $request->filled('teacher_major_name')) {
            $majorName = $request->input('teacher_major_name');
        }
        if (empty($majorName)) {
            $majorName = $user->major_name ?: ($store?->student_class ? trim(explode('-', $store->student_class)[1] ?? '') : null);
        }

        $className = $request->input('class_name') ?: $user->class_name;
        $nisNisn   = $request->input('nis_nisn') ?: $user->nis_nisn;
        $nuptk     = $request->input('nuptk') ?: $user->nuptk;

        $request->merge([
            'major_name' => $majorName,
            'class_name' => $className,
            'nis_nisn'   => $nisNisn,
            'nuptk'      => $nuptk,
        ]);

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
            'class_name'     => ['nullable', 'string', 'max:50'],
            'major_name'     => ['nullable', 'string', 'max:120'],
            'nis_nisn'       => ['nullable', 'string', 'max:30'],
            'nuptk'          => ['nullable', 'string', 'max:30'],
            'bank_name'              => ['nullable', 'string', 'max:50'],
            'bank_account_number'   => ['nullable', 'string', 'max:50'],
            'bank_account_name'     => ['nullable', 'string', 'max:100'],
            'ewallet_name'           => ['nullable', 'string', 'max:50'],
            'ewallet_account_number' => ['nullable', 'string', 'max:50'],
            'ewallet_account_name'   => ['nullable', 'string', 'max:100'],
            'payment_instructions'   => ['nullable', 'string', 'max:500'],
        ];

        $messages = [
            'name.required'        => 'Nama lengkap pemilik toko wajib diisi.',
            'phone.required'       => 'Nomor WhatsApp pemilik toko wajib diisi.',
            'seller_type.required' => 'Pilih peran pemilik toko (Siswa atau Guru).',
            'store_name.required'  => 'Nama toko karya siswa/guru wajib diisi.',
            'description.required' => 'Deskripsi toko wajib diisi.',
            'desa.required'        => 'Desa / Kelurahan tempat tinggal wajib diisi.',
            'kecamatan.required'   => 'Kecamatan tempat tinggal wajib diisi.',
            'kabupaten.required'   => 'Kabupaten / Kota tempat tinggal wajib diisi.',
            'kode_pos.required'    => 'Kode pos wajib diisi.',
        ];

        $validated = $request->validate($rules, $messages);

        $user->update([
            'name'           => $validated['name'],
            'phone'          => $validated['phone'],
            'user_subtype'   => $sellerType,
            'is_teacher'     => $sellerType === 'guru',
            'nis_nisn'       => $sellerType === 'siswa' ? ($validated['nis_nisn'] ?? $user->nis_nisn) : $user->nis_nisn,
            'nuptk'          => $sellerType === 'guru' ? ($validated['nuptk'] ?? $user->nuptk) : $user->nuptk,
            'class_name'     => $sellerType === 'siswa' ? ($validated['class_name'] ?? $user->class_name) : $user->class_name,
            'major_name'     => $validated['major_name'] ?? $user->major_name,
            'desa'           => $validated['desa'],
            'kecamatan'      => $validated['kecamatan'],
            'kabupaten'      => $validated['kabupaten'],
            'kode_pos'       => $validated['kode_pos'],
            'address_detail' => $validated['address_detail'] ?? null,
        ]);

        $finalMajor = $validated['major_name'] ?? $user->major_name ?? 'SMK Plus Al Hilal';
        $finalClass = $validated['class_name'] ?? $user->class_name ?? 'Siswa';

        $studentClassLabel = $sellerType === 'siswa'
            ? trim($finalClass . ' - ' . $finalMajor)
            : 'Guru / Pembina ' . $finalMajor;

        $store->update([
            'store_name'             => $validated['store_name'],
            'seller_type'            => $sellerType,
            'student_class'          => Str::limit($studentClassLabel, 250, ''),
            'description'            => $validated['description'],
            'contact_number'         => $validated['phone'],
            'bank_name'              => $validated['bank_name'] ?? null,
            'bank_account_number'    => $validated['bank_account_number'] ?? null,
            'bank_account_name'      => $validated['bank_account_name'] ?? null,
            'ewallet_name'           => $validated['ewallet_name'] ?? null,
            'ewallet_account_number' => $validated['ewallet_account_number'] ?? null,
            'ewallet_account_name'   => $validated['ewallet_account_name'] ?? null,
            'payment_instructions'   => $validated['payment_instructions'] ?? null,
        ]);

        return redirect()->to(route('seller.dashboard') . '#section-profil')->with('success', 'Profil lengkap pemilik toko & identitas toko berhasil disimpan!');
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
            'product_name'             => ['required', 'string', 'max:150'],
            'category'                 => ['required', 'string', 'max:100'],
            'price'                    => ['required', 'numeric', 'min:1000'],
            'stock'                    => ['required', 'integer', 'min:0'],
            'description'              => ['required', 'string'],
            'image'                    => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'allowed_delivery_methods' => ['nullable', 'array'],
            'allowed_delivery_methods.*' => ['string', 'in:pickup,delivery_lokal,shipping_transfer'],
        ], [
            'image.max' => 'Ukuran file foto produk maksimal 2 MB.',
            'image.uploaded' => 'Ukuran file foto produk melebihi batas maksimal 2 MB.',
        ]);

        $imagePath = $this->compressAndStoreProductImage($request->file('image'));

        $allowedMethods = $request->input('allowed_delivery_methods');
        if (empty($allowedMethods)) {
            $allowedMethods = Product::ALL_DELIVERY_METHODS;
        }

        // Format Judul Produk Otomatis Menjadi Capital Case & Sanitasi Deskripsi (Anti XSS)
        $capitalName = mb_convert_case(trim($validated['product_name']), MB_CASE_TITLE, 'UTF-8');
        $cleanDesc = strip_tags(trim($validated['description']));

        $store->products()->create([
            'product_name'             => $capitalName,
            'slug'                     => Str::slug($capitalName) . '-' . Str::lower(Str::random(5)),
            'category'                 => $validated['category'],
            'price'                    => $validated['price'],
            'stock'                    => $validated['stock'],
            'description'              => $cleanDesc,
            'image'                    => $imagePath,
            'allowed_delivery_methods' => array_values($allowedMethods),
            'is_active'                => true,
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
            'product_name'             => ['required', 'string', 'max:150'],
            'category'                 => ['required', 'string', 'max:100'],
            'price'                    => ['required', 'numeric', 'min:1000'],
            'stock'                    => ['required', 'integer', 'min:0'],
            'description'              => ['required', 'string'],
            'image'                    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'allowed_delivery_methods' => ['nullable', 'array'],
            'allowed_delivery_methods.*' => ['string', 'in:pickup,delivery_lokal,shipping_transfer'],
        ], [
            'image.max' => 'Ukuran file foto produk maksimal 2 MB.',
            'image.uploaded' => 'Ukuran file foto produk melebihi batas maksimal 2 MB.',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $this->compressAndStoreProductImage($request->file('image'));
        }

        $allowedMethods = $request->input('allowed_delivery_methods');
        if (empty($allowedMethods)) {
            $allowedMethods = Product::ALL_DELIVERY_METHODS;
        }
        $validated['allowed_delivery_methods'] = array_values($allowedMethods);

        // Format Judul Produk Otomatis Menjadi Capital Case & Sanitasi Deskripsi
        $validated['product_name'] = mb_convert_case(trim($validated['product_name']), MB_CASE_TITLE, 'UTF-8');
        $validated['description']  = strip_tags(trim($validated['description']));

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
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'courier_name'    => ['nullable', 'string', 'max:100'],
        ]);

        $progress = $validated['progress_status'] ?? match ($validated['status'] ?? 'pending') {
            'confirmed' => 'kemas',
            'completed' => 'sampai',
            'cancelled' => 'dibatalkan',
            default     => 'pesan',
        };

        // Jika metode pickup (datang ke sekolah), bila dipilih kirim otomatis sesuaikan ke sampai atau tolak jika tidak ada kurir
        if ($order->delivery_method === 'pickup' && $progress === 'kirim') {
            $progress = 'sampai';
        }

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

        if ($request->filled('tracking_number')) {
            $updatePayload['tracking_number'] = $validated['tracking_number'];
        }
        if ($request->filled('courier_name')) {
            $updatePayload['courier_name'] = $validated['courier_name'];
        }

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

        return redirect()->to(route('seller.dashboard') . '#section-pesanan')->with('success', "Progres pesanan #{$order->order_code} berhasil disimpan (Tahap: {$order->progress_label}).");
    }

    private function authorizeProductOwner(Request $request, Product $product): void
    {
        abort_unless(
            $request->user()->store && $product->store_id === $request->user()->store->id,
            403,
            'Anda hanya dapat mengelola produk milik toko Anda sendiri.'
        );
    }

    /**
     * Kompresi gambar produk otomatis & batasi dimensi maksimal 1200px
     * untuk menjaga ketajaman resolusi sekaligus menghemat kapasitas storage.
     */
    private function compressAndStoreProductImage($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = 'products/' . Str::random(40) . '.jpg';
        $destinationPath = storage_path('app/public/' . $filename);

        if (! file_exists(dirname($destinationPath))) {
            @mkdir(dirname($destinationPath), 0755, true);
        }

        try {
            if (extension_loaded('gd')) {
                $image = match ($extension) {
                    'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                    'png'         => @imagecreatefrompng($file->getRealPath()),
                    'webp'        => @imagecreatefromwebp($file->getRealPath()),
                    default       => null,
                };

                if ($image) {
                    $origWidth = imagesx($image);
                    $origHeight = imagesy($image);

                    $maxDim = 1200;
                    if ($origWidth > $maxDim || $origHeight > $maxDim) {
                        if ($origWidth >= $origHeight) {
                            $newWidth = $maxDim;
                            $newHeight = (int) round(($origHeight / $origWidth) * $maxDim);
                        } else {
                            $newHeight = $maxDim;
                            $newWidth = (int) round(($origWidth / $origHeight) * $maxDim);
                        }

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                        imagedestroy($image);
                        $image = $resized;
                    }

                    imagejpeg($image, $destinationPath, 82);
                    imagedestroy($image);

                    return $filename;
                }
            }
        } catch (\Throwable $e) {
            // Fallback ke penyimpanan default
        }

        return $file->store('products', 'public');
    }
}
