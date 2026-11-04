<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: PROFIL LENGKAP MULTI-KATEGORI, KOMENTAR, LIKE, VIEWS & ANALITIK
 * ============================================================================
 * Fungsi:
 *   1. Memperluas tabel `users` untuk menyimpan atribut profil lengkap:
 *      - Pemilik Toko (Siswa: `nis_nisn`, `class_name`, `major_name`; Guru: `nuptk`, `is_teacher`)
 *      - Pengunjung/Pembeli (`buyer_type`: `pelajar`, `keluarga`, `alumni`, `umum`)
 *      - Identitas spesifik (`nik`, `school_origin`, `student_class_origin`,
 *        `family_student_name`, `family_student_class`, `family_student_major`, `graduation_year`)
 *      - Alamat terstruktur (`desa`, `kecamatan`, `kabupaten`, `kode_pos`, `address_detail`)
 *   2. Menambahkan penghitung `views_count` & `likes_count` pada `products` dan `stores`.
 *   3. Membuat tabel `product_comments` untuk komentar pengunjung berakun pada produk.
 *   4. Membuat tabel `product_likes` untuk fitur suka/like produk.
 *   5. Membuat tabel `visitor_logs` untuk melacak kunjungan produk/toko (baik Guest
 *      maupun User berakun) guna laporan asal pengunjung & grafik analitik Admin.
 *
 * Algoritma (Additive Non-Destructive Schema Evolution):
 *   Mengecek keberadaan setiap kolom/tabel dengan `Schema::hasColumn` dan
 *   `Schema::hasTable` sebelum mengeksekusi DDL agar aman dijalankan pada
 *   database yang sudah berisi data maupun database baru.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Perluasan Tabel Users untuk Profil Lengkap Toko (Siswa/Guru) & 4 Kategori Pengunjung
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'user_subtype')) {
                    $table->string('user_subtype', 30)->nullable()->comment('siswa, guru, jurusan, pelajar, keluarga, alumni, umum');
                }
                if (! Schema::hasColumn('users', 'is_teacher')) {
                    $table->boolean('is_teacher')->default(false)->comment('Penanda akun Guru yang dapat diangkat menjadi Admin oleh Super Admin');
                }
                if (! Schema::hasColumn('users', 'nis_nisn')) {
                    $table->string('nis_nisn', 30)->nullable()->comment('NIS/NISN untuk Siswa SMK Plus Al Hilal atau Pelajar');
                }
                if (! Schema::hasColumn('users', 'nuptk')) {
                    $table->string('nuptk', 30)->nullable()->comment('NUPTK wajib bagi Guru');
                }
                if (! Schema::hasColumn('users', 'nik')) {
                    $table->string('nik', 30)->nullable()->comment('NIK untuk Keluarga, Alumni, dan Pengunjung Umum');
                }
                if (! Schema::hasColumn('users', 'class_name')) {
                    $table->string('class_name', 50)->nullable()->comment('Kelas Siswa / Pelajar');
                }
                if (! Schema::hasColumn('users', 'major_name')) {
                    $table->string('major_name', 120)->nullable()->comment('Jurusan Siswa / Guru');
                }
                // Field khusus Kategori Pengunjung
                if (! Schema::hasColumn('users', 'school_origin')) {
                    $table->string('school_origin', 150)->nullable()->comment('Asal Sekolah (khusus Pelajar)');
                }
                if (! Schema::hasColumn('users', 'family_student_name')) {
                    $table->string('family_student_name', 120)->nullable()->comment('Nama Siswa di SMK Plus Al Hilal (khusus Keluarga)');
                }
                if (! Schema::hasColumn('users', 'family_student_class')) {
                    $table->string('family_student_class', 50)->nullable()->comment('Kelas Siswa Keluarga');
                }
                if (! Schema::hasColumn('users', 'family_student_major')) {
                    $table->string('family_student_major', 120)->nullable()->comment('Jurusan Siswa Keluarga');
                }
                if (! Schema::hasColumn('users', 'graduation_year')) {
                    $table->string('graduation_year', 10)->nullable()->comment('Tahun Lulus (khusus Alumni)');
                }
                // Field Alamat Lengkap Terstruktur
                if (! Schema::hasColumn('users', 'desa')) {
                    $table->string('desa', 100)->nullable();
                }
                if (! Schema::hasColumn('users', 'kecamatan')) {
                    $table->string('kecamatan', 100)->nullable();
                }
                if (! Schema::hasColumn('users', 'kabupaten')) {
                    $table->string('kabupaten', 100)->nullable();
                }
                if (! Schema::hasColumn('users', 'kode_pos')) {
                    $table->string('kode_pos', 15)->nullable();
                }
                if (! Schema::hasColumn('users', 'address_detail')) {
                    $table->text('address_detail')->nullable();
                }
            });
        }

        // 2. Counter Views pada Stores (1 Toko 1 Akun)
        if (Schema::hasTable('stores')) {
            Schema::table('stores', function (Blueprint $table) {
                if (! Schema::hasColumn('stores', 'views_count')) {
                    $table->unsignedBigInteger('views_count')->default(0);
                }
            });
        }

        // 3. Counter Views & Likes pada Products (Ikon Mata & Suka)
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'views_count')) {
                    $table->unsignedBigInteger('views_count')->default(0);
                }
                if (! Schema::hasColumn('products', 'likes_count')) {
                    $table->unsignedBigInteger('likes_count')->default(0);
                }
            });
        }

        // 4. Tabel Komentar Produk (Khusus Pengunjung yang Memiliki Akun)
        if (! Schema::hasTable('product_comments')) {
            Schema::create('product_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('comment');
                $table->unsignedTinyInteger('rating')->default(5);
                $table->timestamps();
            });
        }

        // 5. Tabel Suka / Like Produk
        if (! Schema::hasTable('product_likes')) {
            Schema::create('product_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        // 6. Tabel Log Kunjungan (Mencatat Pengunjung Guest maupun Berakun)
        if (! Schema::hasTable('visitor_logs')) {
            Schema::create('visitor_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
                $table->string('visitor_type', 40)->default('guest')->comment('guest, pelajar, keluarga, alumni, umum, siswa, guru');
                $table->string('visitor_name', 120)->default('Pengunjung Tamu (Guest)');
                $table->string('origin_region', 200)->nullable()->comment('Desa, Kecamatan, Kabupaten');
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
        Schema::dropIfExists('product_likes');
        Schema::dropIfExists('product_comments');
    }
};

