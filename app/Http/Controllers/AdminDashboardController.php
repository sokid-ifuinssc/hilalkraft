<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BazaarEvent;
use App\Models\Category;
use App\Models\ClassMajor;
use App\Models\CmsContent;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\Store;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: ADMIN DASHBOARD & CMS CONTROLLER (SUPER ADMIN & ADMIN)
 * ============================================================================
 * Fungsi:
 *   Mengelola seluruh fitur CMS (Content Management System), administrasi,
 *   laporan, bazar diskon SMK, dan analitik untuk Super Admin & Admin:
 *   1. CMS Konten Website (`storeCms`, `updateCms`, `destroyCms`).
 *   2. CMS Karya Siswa / Produk (`storeProduct`, `updateProduct`, `destroyProduct`).
 *   3. Grafik Analitik & Filter Toko Teratas (Pelihat Terbanyak 👁️, Like Terbanyak ❤️,
 *      Komentar Terbanyak 💬, Rating Bintang Teratas ★, Pesanan Terbanyak 🛒).
 *   4. Skema Bazar & Event Diskon SMK (`storeBazaarEvent`, `updateBazaarEvent`, `destroyBazaarEvent`).
 *   5. Laporan Pengunjung, Penetapan Guru sebagai Admin, CRUD Pengguna, Kategori, Kelas & Jurusan.
 * ============================================================================
 */
class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = $request->user() ?: auth()->user();

        $usersQuery = User::with('store')->latest('updated_at');
        if ($currentUser && $currentUser->role === 'admin') {
            $usersQuery->whereIn('role', ['seller', 'buyer']);
        }

        if ($request->filled('role')) {
            $roleFilter = $request->query('role');
            if (in_array($roleFilter, ['super_admin', 'admin', 'seller', 'buyer'])) {
                $usersQuery->where('role', $roleFilter);
            }
        }

        $users = $usersQuery->paginate(50);

        $stores = Store::withTrashed()
            ->with(['owner', 'products', 'orders', 'comments'])
            ->withCount(['products', 'orders', 'comments'])
            ->get()
            ->map(function (Store $store) {
                $productViews = (int) $store->products->sum('views_count');
                $store->metric_views = (int) $store->views_count + $productViews;
                $store->metric_comments = (int) $store->comments_count;
                $store->metric_likes = (int) $store->products->sum('likes_count');
                $store->metric_orders = (int) $store->orders_count;
                $store->metric_revenue = (float) $store->orders->sum('total_price');

                $orderRatings = $store->orders->whereNotNull('buyer_rating')->pluck('buyer_rating')->filter(fn ($v) => (int) $v > 0);
                $commentRatings = $store->comments->whereNotNull('rating')->pluck('rating')->filter(fn ($v) => (int) $v > 0);
                $allRatings = $orderRatings->concat($commentRatings);

                $store->metric_rating = $allRatings->isNotEmpty() ? round((float) $allRatings->avg(), 1) : 0.0;
                $store->metric_rating_count = $allRatings->count();

                return $store;
            })
            ->sortByDesc('metric_views')
            ->values();

        $chartData = [
            'labels'          => $stores->pluck('store_name')->values(),
            'views'           => $stores->pluck('metric_views')->values(),
            'comments'        => $stores->pluck('metric_comments')->values(),
            'likes'           => $stores->pluck('metric_likes')->values(),
            'orders'          => $stores->pluck('metric_orders')->values(),
            'ratings'         => $stores->pluck('metric_rating')->values(),
            'rating_counts'   => $stores->pluck('metric_rating_count')->values(),
            'revenues'        => $stores->pluck('metric_revenue')->values(),
            'seller_types'    => $stores->pluck('seller_type')->values(),
            'student_classes' => $stores->pluck('student_class')->values(),
        ];

        $registeredVisitors = User::whereIn('role', ['buyer', 'seller'])
            ->latest()
            ->get();

        $visitorByCategory = [
            'Pelajar'                    => User::where('user_subtype', 'pelajar')->count(),
            'Keluarga SMK Plus Al Hilal' => User::where('user_subtype', 'keluarga')->count(),
            'Alumni'                     => User::where('user_subtype', 'alumni')->count(),
            'Pengunjung Umum'            => User::where('user_subtype', 'umum')->count(),
            'Siswa Pemilik Toko'         => User::where('user_subtype', 'siswa')->count(),
            'Guru SMK Plus Al Hilal'     => User::where('is_teacher', true)->orWhere('user_subtype', 'guru')->count(),
            'Kunjungan Tamu (Guest)'     => VisitorLog::where('visitor_type', 'guest')->count(),
        ];

        $visitorByRegion = User::whereNotNull('kabupaten')
            ->where('kabupaten', '!=', '')
            ->select('kabupaten', 'kecamatan', DB::raw('count(*) as total'))
            ->groupBy('kabupaten', 'kecamatan')
            ->orderByDesc('total')
            ->get();

        $recentVisitorLogs = VisitorLog::with(['product', 'store', 'user'])
            ->latest()
            ->take(15)
            ->get();

        $teachers = User::with('store')
            ->where(function ($q) {
                $q->where('is_teacher', true)
                  ->orWhere('user_subtype', 'guru')
                  ->orWhereNotNull('nuptk');
            })
            ->get();

        $categories = Category::orderBy('name')->get();
        $classMajors = ClassMajor::orderBy('class_name')->orderBy('major_name')->get();

        $cmsContents = Schema::hasTable('cms_contents')
            ? CmsContent::orderBy('sort_order')->orderBy('id')->get()
            : collect();

        $bazaarEvents = Schema::hasTable('bazaar_events')
            ? BazaarEvent::latest()->get()
            : collect();

        $allProducts = Product::with(['store.owner'])
            ->withCount('comments')
            ->latest()
            ->get();

        $stats = [
            'total_users'     => User::count(),
            'total_stores'    => Store::withTrashed()->count(),
            'active_stores'   => Store::where('is_verified', true)->count(),
            'inactive_stores' => Store::where('is_verified', false)->count(),
            'deleted_stores'  => Store::onlyTrashed()->count(),
            'pending_stores'  => Store::where('is_verified', false)->count(),
            'total_products'  => Product::count(),
            'total_views'     => (int) Product::sum('views_count') + (int) Store::sum('views_count'),
            'total_likes'     => (int) Product::sum('likes_count'),
            'total_comments'  => ProductComment::count(),
            'total_orders'    => Order::count(),
        ];

        return view('admin.dashboard', compact(
            'users',
            'stores',
            'chartData',
            'registeredVisitors',
            'visitorByCategory',
            'visitorByRegion',
            'recentVisitorLogs',
            'teachers',
            'categories',
            'classMajors',
            'cmsContents',
            'bazaarEvents',
            'allProducts',
            'stats'
        ));
    }

    /**
     * Fungsi: Menambahkan konten / seksi / iklan mitra baru ke dalam CMS Website.
     * Algoritma: Validasi payload (termasuk durasi tampil & masa kontrak iklan) -> Upload gambar jika ada -> Simpan ke `cms_contents`.
     */
    public function storeCms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:150'],
            'section'          => ['required', 'string', 'max:50'],
            'key'              => ['nullable', 'string', 'max:100'],
            'partner_name'     => ['nullable', 'string', 'max:120'],
            'badge_text'       => ['nullable', 'string', 'max:100'],
            'content'          => ['nullable', 'string'],
            'image_url'        => ['nullable', 'string', 'max:500'],
            'image_file'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'link_url'         => ['nullable', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'contract_start'   => ['nullable', 'date'],
            'contract_end'     => ['nullable', 'date'],
            'theme_color'      => ['nullable', 'string', 'max:40'],
        ]);

        $prefix = $validated['section'] === 'iklan' ? 'iklan_' : 'cms_';
        $key = ! empty($validated['key'])
            ? Str::slug($validated['key'], '_')
            : $prefix . Str::slug($validated['title'], '_') . '_' . Str::lower(Str::random(4));

        $imagePath = $validated['image_url'] ?? null;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('cms', 'public');
        }

        CmsContent::updateOrCreate(
            ['key' => $key],
            [
                'section'          => $validated['section'],
                'title'            => $validated['title'],
                'partner_name'     => $validated['partner_name'] ?? null,
                'badge_text'       => $validated['badge_text'] ?? null,
                'content'          => $validated['content'] ?? '',
                'image'            => $imagePath,
                'link_url'         => $validated['link_url'] ?? null,
                'duration_seconds' => (int) ($validated['duration_seconds'] ?? 5),
                'contract_start'   => $validated['contract_start'] ?? null,
                'contract_end'     => $validated['contract_end'] ?? null,
                'theme_color'      => $validated['theme_color'] ?? 'emerald',
                'is_active'        => true,
                'sort_order'       => (int) CmsContent::max('sort_order') + 1,
            ]
        );

        CmsContent::clearCache();
        \Database\Seeders\DatabaseSeeder::syncLiveSnapshotToStorage();

        return redirect()->to(route('admin.dashboard') . '#sec-cms')->with('success', "Konten / Iklan CMS '{$validated['title']}' berhasil ditambahkan dan disimpan.");
    }

    /**
     * Fungsi: Mengedit konten atau iklan mitra CMS yang sudah ada.
     */
    public function updateCms(Request $request, CmsContent $cmsContent): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:150'],
            'section'          => ['required', 'string', 'max:50'],
            'partner_name'     => ['nullable', 'string', 'max:120'],
            'badge_text'       => ['nullable', 'string', 'max:100'],
            'content'          => ['nullable', 'string'],
            'image_url'        => ['nullable', 'string', 'max:500'],
            'image_file'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'link_url'         => ['nullable', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'contract_start'   => ['nullable', 'date'],
            'contract_end'     => ['nullable', 'date'],
            'theme_color'      => ['nullable', 'string', 'max:40'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $imagePath = $cmsContent->image;
        if ($request->filled('image_url')) {
            $imagePath = $validated['image_url'];
        }
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('cms', 'public');
        }

        $cmsContent->update([
            'title'            => $validated['title'],
            'section'          => $validated['section'],
            'partner_name'     => $validated['partner_name'] ?? $cmsContent->partner_name,
            'badge_text'       => $validated['badge_text'] ?? $cmsContent->badge_text,
            'content'          => $validated['content'] ?? '',
            'image'            => $imagePath,
            'link_url'         => $validated['link_url'] ?? null,
            'duration_seconds' => (int) ($validated['duration_seconds'] ?? $cmsContent->duration_seconds ?? 5),
            'contract_start'   => $validated['contract_start'] ?? null,
            'contract_end'     => $validated['contract_end'] ?? null,
            'theme_color'      => $validated['theme_color'] ?? $cmsContent->theme_color ?? 'emerald',
            'is_active'        => $request->has('is_active') ? $request->boolean('is_active') : $cmsContent->is_active,
        ]);

        CmsContent::clearCache();
        \Database\Seeders\DatabaseSeeder::syncLiveSnapshotToStorage();

        return redirect()->to(route('admin.dashboard') . '#sec-cms')->with('success', "Konten / Iklan CMS '{$cmsContent->title}' berhasil disimpan.");
    }

    /**
     * Fungsi: Menghapus item konten CMS.
     */
    public function destroyCms(CmsContent $cmsContent): RedirectResponse
    {
        $title = $cmsContent->title;
        $cmsContent->delete();
        CmsContent::clearCache();
        \Database\Seeders\DatabaseSeeder::syncLiveSnapshotToStorage();

        return redirect()->to(route('admin.dashboard') . '#sec-cms')->with('success', "Konten CMS '{$title}' berhasil dihapus.");
    }

    /**
     * Fungsi: Menambahkan Karya Siswa / Produk baru langsung dari CMS Super Admin / Admin.
     */
    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id'     => ['required', 'exists:stores,id'],
            'product_name' => ['required', 'string', 'max:150'],
            'category'     => ['required', 'string', 'max:100'],
            'price'        => ['required', 'numeric', 'min:1000'],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['required', 'string'],
            'image_file'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'image_url'    => ['nullable', 'string', 'max:500'],
            'allowed_delivery_methods' => ['nullable', 'array'],
        ]);

        $imagePath = $validated['image_url'] ?? '/images/product-karya-1.jpg';
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('products', 'public');
        }

        $allowedMethods = $request->input('allowed_delivery_methods');
        if (empty($allowedMethods)) {
            $allowedMethods = Product::ALL_DELIVERY_METHODS;
        }

        Product::create([
            'store_id'     => $validated['store_id'],
            'product_name' => $validated['product_name'],
            'slug'         => Str::slug($validated['product_name']) . '-' . Str::lower(Str::random(4)),
            'category'     => $validated['category'],
            'price'        => $validated['price'],
            'stock'        => $validated['stock'],
            'description'  => $validated['description'],
            'image'        => $imagePath,
            'allowed_delivery_methods' => array_values($allowedMethods),
            'is_active'    => true,
        ]);

        return redirect()->to(route('admin.dashboard') . '#sec-cms-produk')->with('success', "Karya produk '{$validated['product_name']}' berhasil ditambahkan dan disimpan melalui CMS.");
    }

    /**
     * Fungsi: Mengedit Karya Siswa / Produk langsung dari CMS Super Admin / Admin.
     */
    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'store_id'     => ['required', 'exists:stores,id'],
            'product_name' => ['required', 'string', 'max:150'],
            'category'     => ['required', 'string', 'max:100'],
            'price'        => ['required', 'numeric', 'min:1000'],
            'stock'        => ['required', 'integer', 'min:0'],
            'description'  => ['required', 'string'],
            'image_file'   => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'image_url'    => ['nullable', 'string', 'max:500'],
            'allowed_delivery_methods' => ['nullable', 'array'],
        ]);

        $imagePath = $product->image;
        if ($request->filled('image_url')) {
            $imagePath = $validated['image_url'];
        }
        if ($request->hasFile('image_file')) {
            if ($product->image && ! Str::startsWith($product->image, 'http')) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image_file')->store('products', 'public');
        }

        $allowedMethods = $request->input('allowed_delivery_methods');
        if (empty($allowedMethods)) {
            $allowedMethods = Product::ALL_DELIVERY_METHODS;
        }

        $product->update([
            'store_id'     => $validated['store_id'],
            'product_name' => $validated['product_name'],
            'category'     => $validated['category'],
            'price'        => $validated['price'],
            'stock'        => $validated['stock'],
            'description'  => $validated['description'],
            'image'        => $imagePath,
            'allowed_delivery_methods' => array_values($allowedMethods),
        ]);

        return redirect()->to(route('admin.dashboard') . '#sec-cms-produk')->with('success', "Karya produk '{$product->product_name}' berhasil disimpan melalui CMS.");
    }

    public function promoteTeacherToAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403, 'Hanya Super Admin yang dapat menetapkan Guru sebagai Admin.');

        $newRole = $user->role === 'admin' ? 'seller' : 'admin';
        $user->update([
            'role'       => $newRole,
            'is_teacher' => true,
        ]);

        $msg = $newRole === 'admin'
            ? "Guru '{$user->name}' berhasil ditetapkan sebagai Admin Sekolah oleh Super Admin!"
            : "Hak akses Admin untuk Guru '{$user->name}' telah dikembalikan menjadi Pemilik Toko.";

        return redirect()->to(route('admin.dashboard') . '#sec-manajemen-user')->with('success', $msg);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        $allowedRoles = $currentUser->role === 'super_admin'
            ? ['super_admin', 'admin', 'seller', 'buyer']
            : ['seller', 'buyer'];

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'role'          => ['required', Rule::in($allowedRoles)],
            'password'      => ['required', 'string', 'min:6'],
            'seller_type'   => ['nullable', 'in:siswa,guru,jurusan'],
            'store_name'    => ['nullable', 'string', 'max:120'],
            'student_class' => ['nullable', 'string', 'max:250'],
            'nis_nisn'      => ['nullable', 'string', 'max:30'],
            'nuptk'         => ['nullable', 'string', 'max:30'],
            'desa'          => ['nullable', 'string', 'max:100'],
            'kecamatan'     => ['nullable', 'string', 'max:100'],
            'kabupaten'     => ['nullable', 'string', 'max:100'],
            'kode_pos'      => ['nullable', 'string', 'max:15'],
        ]);

        DB::transaction(function () use ($validated) {
            $sellerType = $validated['seller_type'] ?? 'siswa';
            $isTeacher = ($validated['role'] === 'seller' && $sellerType === 'guru') || ! empty($validated['nuptk']);

            $user = User::create([
                'name'           => $validated['name'],
                'email'          => $validated['email'],
                'phone'          => $validated['phone'] ?? null,
                'role'           => $validated['role'],
                'user_subtype'   => $validated['role'] === 'seller' ? $sellerType : 'umum',
                'is_teacher'     => $isTeacher,
                'nis_nisn'       => $validated['nis_nisn'] ?? null,
                'nuptk'          => $validated['nuptk'] ?? null,
                'desa'           => $validated['desa'] ?? null,
                'kecamatan'      => $validated['kecamatan'] ?? null,
                'kabupaten'      => $validated['kabupaten'] ?? null,
                'kode_pos'       => $validated['kode_pos'] ?? null,
                'password'       => Hash::make($validated['password']),
                'password_plain' => $validated['password'],
                'is_active'      => true,
            ]);

            if ($validated['role'] === 'seller') {
                $storeName = ! empty($validated['store_name'])
                    ? $validated['store_name']
                    : 'Toko ' . $validated['name'];

                Store::create([
                    'user_id'        => $user->id,
                    'store_name'     => $storeName,
                    'slug'           => Str::slug($storeName) . '-' . Str::lower(Str::random(4)),
                    'seller_type'    => $sellerType,
                    'student_class'  => $validated['student_class'] ?? 'SMK Plus Al Hilal',
                    'description'    => 'Toko resmi ' . $sellerType . ' di hilalkraft SMK Plus Al Hilal.',
                    'contact_number' => $validated['phone'] ?? '081234567890',
                    'is_verified'    => true,
                ]);
            }
        });

        return redirect()->to(route('admin.dashboard') . '#sec-manajemen-user')->with('success', 'Pengguna baru berhasil ditambahkan dan disimpan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->role === 'admin' && in_array($user->role, ['super_admin', 'admin'], true)) {
            abort(403, 'Admin tidak diizinkan mengubah data Super Admin atau sesama Admin.');
        }

        $allowedRoles = $currentUser->role === 'super_admin'
            ? ['super_admin', 'admin', 'seller', 'buyer']
            : ['seller', 'buyer'];

        $messages = [
            'name.required'  => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email pengguna wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email tersebut sudah digunakan oleh akun lain.',
            'role.required'  => 'Peran pengguna (Role) wajib dipilih.',
            'password.min'   => 'Password baru minimal terdiri dari 6 karakter.',
        ];

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'     => ['nullable', 'string', 'max:20'],
            'role'      => ['required', Rule::in($allowedRoles)],
            'is_active' => ['nullable', 'boolean'],
            'password'  => ['nullable', 'string', 'min:6'],
        ], $messages);

        $updateData = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? null,
            'role'      => $validated['role'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $user->is_active,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($validated['password']);
            $updateData['password_plain'] = $validated['password'];
        }

        $user->update($updateData);

        return redirect()->to(route('admin.dashboard') . '#sec-manajemen-user')->with('success', "Data & kredensial akun '{$user->name}' berhasil disimpan.");
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return redirect()->to(route('admin.dashboard') . '#sec-manajemen-user')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($currentUser->role === 'admin' && in_array($user->role, ['super_admin', 'admin'], true)) {
            abort(403, 'Admin hanya dapat menghapus akun Pemilik Toko atau Pembeli.');
        }

        $user->delete();

        return redirect()->to(route('admin.dashboard') . '#sec-manajemen-user')->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleVerifyStore(Store $store): RedirectResponse
    {
        $store->update([
            'is_verified' => ! $store->is_verified,
            'status'      => (! $store->is_verified) ? 'active' : 'inactive',
        ]);

        $message = $store->is_verified
            ? "Toko '{$store->store_name}' telah diverifikasi dan aktif di katalog."
            : "Status toko '{$store->store_name}' dinonaktifkan dari katalog.";

        ActivityLog::record(
            'store_update',
            'Perubahan Status Toko',
            "Status toko '{$store->store_name}' diubah menjadi: " . ($store->is_verified ? 'Aktif' : 'Non-Aktif') . " oleh " . auth()->user()->name,
            auth()->user()
        );

        return redirect()->to(route('admin.dashboard') . '#sec-kelola-toko')->with('success', $message);
    }

    /**
     * Fungsi: Menghapus toko (Soft Delete) dan memindahkan ke daftar Toko Terhapus.
     */
    public function destroyStore(Request $request, $id): RedirectResponse
    {
        $store = Store::withTrashed()->findOrFail($id);
        $storeName = $store->store_name;
        $store->delete(); // Soft delete

        ActivityLog::record(
            'store_delete',
            'Penghapusan Toko',
            "Toko '{$storeName}' dipindahkan ke daftar Toko Terhapus oleh " . $request->user()->name . " ({$request->user()->role}).",
            $request->user()
        );

        return redirect()->to(route('admin.dashboard') . '#sec-kelola-toko')
            ->with('success', "Toko '{$storeName}' berhasil dihapus dan dipindahkan ke daftar Toko Terhapus.");
    }

    /**
     * Fungsi: Memulihkan toko dari status terhapus kembali ke daftar toko.
     */
    public function restoreStore(Request $request, $id): RedirectResponse
    {
        $store = Store::onlyTrashed()->findOrFail($id);
        $storeName = $store->store_name;
        $store->restore();

        ActivityLog::record(
            'store_restore',
            'Pemulihan Toko Terhapus',
            "Toko '{$storeName}' berhasil dipulihkan kembali oleh " . $request->user()->name . " ({$request->user()->role}).",
            $request->user()
        );

        return redirect()->to(route('admin.dashboard') . '#sec-kelola-toko')
            ->with('success', "Toko '{$storeName}' berhasil dipulihkan kembali.");
    }

    /**
     * Fungsi: Menghapus toko secara permanen dari database (Khusus Super Admin).
     */
    public function forceDeleteStore(Request $request, $id): RedirectResponse
    {
        abort_unless($request->user()->role === 'super_admin', 403, 'Hanya Super Admin yang diizinkan menghapus toko secara permanen.');

        $store = Store::onlyTrashed()->findOrFail($id);
        $storeName = $store->store_name;
        $store->forceDelete();

        ActivityLog::record(
            'store_delete',
            'Penghapusan Toko Permanen',
            "Toko '{$storeName}' telah dihapus secara permanen dari basis data oleh Super Admin (" . $request->user()->name . ").",
            $request->user()
        );

        return redirect()->to(route('admin.dashboard') . '#sec-kelola-toko')
            ->with('success', "Toko '{$storeName}' telah dihapus secara permanen.");
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->to(route('admin.dashboard') . '#sec-cms-produk')->with('success', 'Karya produk berhasil dihapus.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Category::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']) . '-' . Str::lower(Str::random(3)),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->to(route('admin.dashboard') . '#sec-master-data')->with('success', "Kategori produk '{$validated['name']}' berhasil ditambahkan dan disimpan.");
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $oldName = $category->name;

        $category->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']) . '-' . Str::lower(Str::random(3)),
            'description' => $validated['description'] ?? null,
        ]);

        if ($oldName !== $validated['name']) {
            Product::where('category', $oldName)->update(['category' => $validated['name']]);
        }

        return redirect()->to(route('admin.dashboard') . '#sec-master-data')->with('success', "Kategori produk berhasil disimpan menjadi '{$validated['name']}'.");
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return redirect()->to(route('admin.dashboard') . '#sec-master-data')->with('success', "Kategori produk '{$name}' berhasil dihapus.");
    }

    public function storeClassMajor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50'],
            'major_name' => ['required', 'string', 'max:120'],
        ]);

        ClassMajor::create([
            'class_name' => $validated['class_name'],
            'major_name' => $validated['major_name'],
            'is_active'  => true,
        ]);

        return redirect()->to(route('admin.dashboard') . '#sec-master-data')->with('success', "Data Kelas/Unit '{$validated['class_name']} - {$validated['major_name']}' berhasil ditambahkan dan disimpan.");
    }

    public function updateClassMajor(Request $request, ClassMajor $classMajor): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50'],
            'major_name' => ['required', 'string', 'max:120'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $classMajor->update([
            'class_name' => $validated['class_name'],
            'major_name' => $validated['major_name'],
            'is_active'  => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()->to(route('admin.dashboard') . '#sec-master-data')->with('success', 'Data Kelas & Jurusan berhasil disimpan.');
    }

    public function destroyClassMajor(ClassMajor $classMajor): RedirectResponse
    {
        $classMajor->delete();
        return redirect()->to(route('admin.dashboard') . '#sec-master-data')->with('success', 'Data Kelas & Jurusan berhasil dihapus.');
    }

    /**
     * Fungsi:
     *   Menambahkan Acara Bazar / Event Diskon SMK Plus Al Hilal.
     *
     * Algoritma:
     *   1. Validasi nama acara bazar, kode voucher (`voucher_code`), lokasi,
     *      persentase diskon (`discount_percent`), batas maksimum potongan (`max_discount_amount`),
     *      serta rentang tanggal berlaku (`start_date` s/d `end_date`).
     *   2. Simpan ke tabel `bazaar_events`.
     */
    public function storeBazaarEvent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:150'],
            'voucher_code'        => ['required', 'string', 'max:40'],
            'location'            => ['nullable', 'string', 'max:150'],
            'description'         => ['nullable', 'string', 'max:500'],
            'discount_percent'    => ['required', 'integer', 'min:1', 'max:90'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date'          => ['nullable', 'date'],
            'end_date'            => ['nullable', 'date'],
        ]);

        BazaarEvent::updateOrCreate(
            ['voucher_code' => strtoupper(trim($validated['voucher_code']))],
            [
                'title'               => $validated['title'],
                'location'            => $validated['location'] ?? 'Halaman Utama SMK Plus Al Hilal (SMK PAL)',
                'description'         => $validated['description'] ?? 'Dapatkan diskon spesial dengan berbelanja langsung atau memasukkan kode bazar pada acara resmi SMK Plus Al Hilal.',
                'discount_percent'    => (int) $validated['discount_percent'],
                'max_discount_amount' => $validated['max_discount_amount'] ?? null,
                'start_date'          => $validated['start_date'] ?? null,
                'end_date'            => $validated['end_date'] ?? null,
                'is_active'           => true,
            ]
        );

        return redirect()->to(route('admin.dashboard') . '#sec-bazar-event')->with('success', "Acara Bazar / Event '{$validated['title']}' (Kode: " . strtoupper(trim($validated['voucher_code'])) . ") berhasil disimpan!");
    }

    /**
     * Fungsi:
     *   Memperbarui Acara Bazar / Event Diskon SMK Plus Al Hilal.
     */
    public function updateBazaarEvent(Request $request, BazaarEvent $bazaarEvent): RedirectResponse
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:150'],
            'voucher_code'        => ['required', 'string', 'max:40', Rule::unique('bazaar_events', 'voucher_code')->ignore($bazaarEvent->id)],
            'location'            => ['nullable', 'string', 'max:150'],
            'description'         => ['nullable', 'string', 'max:500'],
            'discount_percent'    => ['required', 'integer', 'min:1', 'max:90'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date'          => ['nullable', 'date'],
            'end_date'            => ['nullable', 'date'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $bazaarEvent->update([
            'title'               => $validated['title'],
            'voucher_code'        => strtoupper(trim($validated['voucher_code'])),
            'location'            => $validated['location'] ?? $bazaarEvent->location,
            'description'         => $validated['description'] ?? $bazaarEvent->description,
            'discount_percent'    => (int) $validated['discount_percent'],
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'start_date'          => $validated['start_date'] ?? null,
            'end_date'            => $validated['end_date'] ?? null,
            'is_active'           => $request->has('is_active') ? $request->boolean('is_active') : $bazaarEvent->is_active,
        ]);

        return redirect()->to(route('admin.dashboard') . '#sec-bazar-event')->with('success', "Acara Bazar '{$bazaarEvent->title}' berhasil disimpan.");
    }

    /**
     * Fungsi:
     *   Menghapus Acara Bazar / Event Diskon SMK Plus Al Hilal.
     */
    public function destroyBazaarEvent(BazaarEvent $bazaarEvent): RedirectResponse
    {
        $title = $bazaarEvent->title;
        $bazaarEvent->delete();

        return redirect()->to(route('admin.dashboard') . '#sec-bazar-event')->with('success', "Acara Bazar '{$title}' berhasil dihapus.");
    }

    /**
     * ========================================================================
     * MENU LOG: DETEKSI SIAPA SAJA YANG MASUK & MENGAKSES (SEPERTI HILALEDU)
     * ========================================================================
     * Fungsi:
     *   Menampilkan halaman log sistem lengkap untuk Admin & Super Admin:
     *   - Siapa yang masuk / login / logout / registrasi akun
     *   - Kapan mengakses (tanggal & jam lengkap)
     *   - Dari alamat IP mana
     *   - Perangkat & browser apa yang digunakan (Windows, Android, iOS, Chrome, dll.)
     *   - Aktivitas apa yang dilakukan (Kelola Toko, Produk, CMS, Transaksi, dll.)
     *   - Dilengkapi filter pencarian, filter role, filter jenis aksi, dan filter tanggal.
     * ========================================================================
     */
    public function activityLogs(Request $request): View
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('q')) {
            $search = trim($request->query('q'));
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('device', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type') && $request->query('type') !== 'all') {
            $query->where('activity_type', $request->query('type'));
        }

        if ($request->filled('role') && $request->query('role') !== 'all') {
            $query->where('user_role', $request->query('role'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->query('end_date'));
        }

        $logs = $query->paginate(35)->withQueryString();

        $stats = [
            'total_logs'   => ActivityLog::count(),
            'today_logs'   => ActivityLog::whereDate('created_at', today())->count(),
            'logins_today' => ActivityLog::where('activity_type', 'login')->whereDate('created_at', today())->count(),
            'unique_users' => ActivityLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'unique_ips'   => ActivityLog::whereNotNull('ip_address')->distinct('ip_address')->count('ip_address'),
        ];

        return view('admin.logs', compact('logs', 'stats'));
    }
}
