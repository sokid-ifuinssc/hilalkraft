<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * MIGRATION: PERLEBAR KOLOM STUDENT_CLASS PADA TABEL STORES & USERS
 * ============================================================================
 * Fungsi:
 *   Memperlebar kapasitas kolom `student_class` pada tabel `stores` dari 50
 *   menjadi 255 karakter, agar dapat menampung identitas lengkap kelas,
 *   jurusan, maupun gelar guru/pembina (seperti:
 *   "Guru / Pembina Teknik Komputer dan Jaringan Telekomunikasi (TKJT)").
 *   Juga memperlebar kolom terkait pada tabel `users`.
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Perlebar student_class pada tabel stores
        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'student_class')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('student_class', 255)->nullable()->change();
            });
        }

        // 2. Perlebar kolom kelas & jurusan pada tabel users jika diperlukan
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'class_name')) {
                    $table->string('class_name', 100)->nullable()->change();
                }
                if (Schema::hasColumn('users', 'major_name')) {
                    $table->string('major_name', 255)->nullable()->change();
                }
                if (Schema::hasColumn('users', 'family_student_class')) {
                    $table->string('family_student_class', 100)->nullable()->change();
                }
                if (Schema::hasColumn('users', 'family_student_major')) {
                    $table->string('family_student_major', 255)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'student_class')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('student_class', 50)->nullable()->change();
            });
        }
    }
};
