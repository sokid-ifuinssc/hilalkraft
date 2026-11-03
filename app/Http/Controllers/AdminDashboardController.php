<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClassMajor;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * ============================================================================
 * CONTROLLER: ADMIN DASHBOARD CONTROLLER (SUPER ADMIN & ADMIN MODERATOR)
 * ============================================================================
 * Fungsi:
 *   Mengelola seluruh fitur administrasi sistem untuk Super Admin & Admin:
 *   1. CRUD Pengguna & Kredensial Realtime (termasuk pembuatan akun Toko untuk
 *      Siswa, Guru, dan Jurusan).
 *   2. Validasi & Kurasi Toko (Siswa, Guru, Jurusan).
 *   3. CRUD Kategori Produk (Tambah, Ubah dengan Cascade Update, Hapus).
 *   4. CRUD Kelas & Jurusan (Tambah, Ubah, Hapus).
 * ============================================================================
 */
class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        $usersQuery = User::with('store')->latest('updated_at');
        if ($currentUser->role === 'admin') {
            $usersQuery->whereIn('role', ['seller', 'buyer']);
        }

        $users = $usersQuery->paginate(10);
        $stores = Store::with('owner')->withCount('products')->latest()->get();
        $categories = Category::orderBy('name')->get();
        $classMajors = ClassMajor::orderBy('class_name')->orderBy('major_name')->get();

        $stats = [
            'total_users'      => User::count(),
            'total_stores'     => Store::count(),
            'pending_stores'   => Store::where('is_verified', false)->count(),
            'total_products'   => Product::count(),
            'total_categories' => $categories->count(),
            'total_orders'     => Order::count(),
        ];

        return view('admin.dashboard', compact('users', 'stores', 'categories', 'classMajors', 'stats'));
    }

    /**
     * Fungsi: Membuat pengguna baru (dan otomatis membuat Toko jika role = `seller`,
     * baik untuk Siswa, Guru, maupun Jurusan).
     *
     * Algoritma (ACID User + Store Provisioning):
     *   1. Memastikan role yang dibuat sesuai hirarki (`super_admin` vs `admin`).
     *   2. Menyimpan `User` beserta `password_plain`.
     *   3. Jika `role === 'seller'`, otomatis membuat record `Store` dengan
     *      `seller_type` (`siswa`, `guru`, atau `jurusan`), `store_name`, dan
     *      `student_class` (Kelas/Jurusan) serta langsung terverifikasi (`is_verified = true`).
     */
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
            'student_class' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'           => $validated['name'],
                'email'          => $validated['email'],
                'phone'          => $validated['phone'] ?? null,
                'role'           => $validated['role'],
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
                    'seller_type'    => $validated['seller_type'] ?? 'siswa',
                    'student_class'  => $validated['student_class'] ?? 'SMK Plus Al Hilal',
                    'description'    => 'Toko resmi ' . ($validated['seller_type'] ?? 'siswa') . ' di hilalkraft SMK Plus Al Hilal.',
                    'contact_number' => $validated['phone'] ?? '081234567890',
                    'is_verified'    => true,
                ]);
            }
        });

        return back()->with('success', 'Pengguna baru (beserta profil toko jika Pemilik Toko) berhasil ditambahkan.');
    }

    /**
     * Fungsi: Memperbarui data pengguna, kredensial realtime, serta profil toko.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->role === 'admin' && in_array($user->role, ['super_admin', 'admin'], true)) {
            abort(403, 'Admin tidak diizinkan mengubah data Super Admin atau sesama Admin.');
        }

        $allowedRoles = $currentUser->role === 'super_admin'
            ? ['super_admin', 'admin', 'seller', 'buyer']
            : ['seller', 'buyer'];

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'     => ['nullable', 'string', 'max:20'],
            'role'      => ['required', Rule::in($allowedRoles)],
            'is_active' => ['nullable', 'boolean'],
            'password'  => ['nullable', 'string', 'min:6'],
        ]);

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

        return back()->with('success', "Data & kredensial akun '{$user->name}' berhasil diperbarui secara realtime.");
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($currentUser->role === 'admin' && in_array($user->role, ['super_admin', 'admin'], true)) {
            abort(403, 'Admin hanya dapat menghapus akun Pemilik Toko atau Pembeli.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function toggleVerifyStore(Store $store): RedirectResponse
    {
        $store->update(['is_verified' => ! $store->is_verified]);

        $message = $store->is_verified
            ? "Toko '{$store->store_name}' telah diverifikasi dan aktif di katalog."
            : "Status verifikasi toko '{$store->store_name}' dinonaktifkan.";

        return back()->with('success', $message);
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        $product->delete();
        return back()->with('success', 'Konten produk berhasil dihapus oleh moderator.');
    }

    /*
    |--------------------------------------------------------------------------
    | MANAJEMEN KATEGORI PRODUK (TAMBAH, UBAH, HAPUS OLEH SUPER ADMIN & ADMIN)
    |--------------------------------------------------------------------------
    */

    /**
     * Fungsi: Menambahkan kategori produk baru.
     * Algoritma: Validasi keunikan nama kategori + pembuatan slug otomatis (`Str::slug`).
     */
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

        return back()->with('success', "Kategori produk '{$validated['name']}' berhasil ditambahkan.");
    }

    /**
     * Fungsi: Mengubah nama/deskripsi kategori produk dan memperbarui produk terkait.
     * Algoritma (Referential Category Cascade Update):
     *   1. Menyimpan nama kategori lama (`$oldName`).
     *   2. Memperbarui record di tabel `categories`.
     *   3. Jika nama kategori berubah, jalankan query `Product::where('category', $oldName)->update()`
     *      agar seluruh produk yang memakai kategori lama otomatis ikut terupdate.
     */
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

        return back()->with('success', "Kategori produk berhasil diperbarui menjadi '{$validated['name']}'.");
    }

    /**
     * Fungsi: Menghapus kategori produk.
     */
    public function destroyCategory(Category $category): RedirectResponse
    {
        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategori produk '{$name}' berhasil dihapus.");
    }

    /*
    |--------------------------------------------------------------------------
    | MANAJEMEN KELAS & JURUSAN (TAMBAH, UBAH, HAPUS OLEH SUPER ADMIN & ADMIN)
    |--------------------------------------------------------------------------
    */

    /**
     * Fungsi: Menambahkan master data Kelas & Jurusan / Unit baru.
     */
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

        return back()->with('success', "Data Kelas/Unit '{$validated['class_name']} - {$validated['major_name']}' berhasil ditambahkan.");
    }

    /**
     * Fungsi: Mengubah data Kelas & Jurusan / Unit yang sudah ada.
     */
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

        return back()->with('success', 'Data Kelas & Jurusan berhasil diperbarui.');
    }

    /**
     * Fungsi: Menghapus data Kelas & Jurusan.
     */
    public function destroyClassMajor(ClassMajor $classMajor): RedirectResponse
    {
        $classMajor->delete();
        return back()->with('success', 'Data Kelas & Jurusan berhasil dihapus.');
    }
}
