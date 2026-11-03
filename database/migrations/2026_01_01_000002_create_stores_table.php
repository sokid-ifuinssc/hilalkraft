<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: TABEL STORES (TOKO KARYA SISWA)
 * ============================================================================
 * Fungsi:
 *   Membangun tabel `stores` untuk menyimpan identitas toko milik siswa
 *   SMK Plus Al Hilal (User dengan role `seller`), termasuk nomor WhatsApp
 *   dan status verifikasi oleh Admin.
 *
 * Algoritma (Relational Foreign Key & Cascade Integrity):
 *   1. Mendefinisikan `user_id` sebagai Foreign Key yang mengacu ke `users.id`.
 *   2. Menerapkan algoritma Referential Integrity `cascadeOnDelete()`: jika akun
 *      pemilik toko dihapus oleh Admin/Super Admin, maka data toko otomatis terhapus
 *      tanpa meninggalkan orphan record.
 *   3. Menambahkan kolom boolean `is_verified` (default `false`) sebagai gerbang
 *      algoritma moderasi/kurasi Admin sebelum produk tampil di katalog publik.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Relasi 1-to-1 ke tabel users
            $table->string('store_name'); // Nama toko karya siswa
            $table->string('slug')->unique(); // URL-friendly identifier unik
            $table->string('student_class', 50)->nullable()->comment('Jurusan / Kelas Siswa SMK Plus Al Hilal');
            $table->text('description')->nullable(); // Deskripsi singkat profil toko
            $table->string('contact_number', 20); // Nomor WhatsApp untuk integrasi tombol kontak
            $table->string('logo')->nullable(); // Path file logo toko
            $table->boolean('is_verified')->default(false)->comment('Validasi oleh Admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
