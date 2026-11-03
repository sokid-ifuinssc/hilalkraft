<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ============================================================================
 * SEEDER: DATABASE SEEDER (DATA AWAL 4 ROLE + TOKO & PRODUK SAMPEL)
 * ============================================================================
 * Fungsi:
 *   Mengisi database dengan akun awal Super Admin, Admin, Pemilik Toko (Siswa),
 *   dan Pembeli beserta kredensial real-time (`password_plain`) dan produk contoh.
 *
 * Algoritma:
 *   Sequential Entity Seeding dengan sinkronisasi `password` (Bcrypt Hash)
 *   dan `password_plain` (`password123`).
 * ============================================================================
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        User::create([
            'name'           => 'Super Admin SMK Plus Al Hilal',
            'email'          => 'superadmin@hilalkraft.sch.id',
            'password'       => Hash::make('password123'),
            'password_plain' => 'password123',
            'role'           => 'super_admin',
            'phone'          => '081234567890',
            'is_active'      => true,
        ]);

        // 2. Admin Moderator Sekolah
        User::create([
            'name'           => 'Admin Kesiswaan Al Hilal',
            'email'          => 'admin@hilalkraft.sch.id',
            'password'       => Hash::make('password123'),
            'password_plain' => 'password123',
            'role'           => 'admin',
            'phone'          => '081234567891',
            'is_active'      => true,
        ]);

        // 3. Pemilik Toko (Siswa Kreator)
        $seller = User::create([
            'name'           => 'Ahmad Fauzan (XII Kriya & DKV)',
            'email'          => 'fauzan.siswa@hilalkraft.sch.id',
            'password'       => Hash::make('password123'),
            'password_plain' => 'password123',
            'role'           => 'seller',
            'phone'          => '085712345678',
            'is_active'      => true,
        ]);

        $store = Store::create([
            'user_id'        => $seller->id,
            'store_name'     => 'Al-Hilal Wood & Kaligrafi Studio',
            'slug'           => 'al-hilal-wood-kaligrafi-studio',
            'student_class'  => 'XII Desain Komunikasi Visual',
            'description'    => 'Menyediakan karya kriya kayu ukir laser, kaligrafi timbul, dan souvenir eksklusif buatan siswa SMK Plus Al Hilal.',
            'contact_number' => '085712345678',
            'is_verified'    => true,
        ]);

        Product::create([
            'store_id'     => $store->id,
            'product_name' => 'Kaligrafi Kayu Jati Ayat Kursi Laser Cut',
            'slug'         => 'kaligrafi-kayu-jati-ayat-kursi-laser-cut',
            'description'  => 'Kaligrafi kayu jati pilihan hasil karya praktik siswa SMK Plus Al Hilal dengan finishing natural glossy dan ukiran presisi tinggi.',
            'price'        => 185000,
            'stock'        => 12,
            'image'        => 'products/sample-kaligrafi.jpg',
            'category'     => 'Seni Kaligrafi & Islami',
            'is_active'    => true,
        ]);

        Product::create([
            'store_id'     => $store->id,
            'product_name' => 'Totebag Kanvas Lukis Tangan Motif Nusantara',
            'slug'         => 'totebag-kanvas-lukis-tangan-motif-nusantara',
            'description'  => 'Totebag kanvas tebal premium dengan lukisan tangan asli karya siswa jurusan Kriya & Busana SMK Plus Al Hilal.',
            'price'        => 65000,
            'stock'        => 25,
            'image'        => 'products/sample-totebag.jpg',
            'category'     => 'Kerajinan Tangan',
            'is_active'    => true,
        ]);

        // 4. Pembeli
        User::create([
            'name'           => 'Budi Santoso (Wali Murid / Umum)',
            'email'          => 'pembeli@hilalkraft.sch.id',
            'password'       => Hash::make('password123'),
            'password_plain' => 'password123',
            'role'           => 'buyer',
            'phone'          => '081398765432',
            'is_active'      => true,
        ]);
    }
}
