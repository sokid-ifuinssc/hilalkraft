<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ============================================================================
 * MODEL: CLASS_MAJOR (MASTER DATA KELAS & JURUSAN SMK PLUS AL HILAL)
 * ============================================================================
 * Fungsi:
 *   Merepresentasikan tabel `class_majors` yang menyimpan kombinasi Kelas/Tingkat
 *   (`X`, `XI`, `XII`, `Guru`, `Jurusan`) dan Nama Jurusan/Kompetensi Keahlian.
 *
 * Algoritma (Composite Label Accessor):
 *   Pada `getFullNameAttribute()`: Menggabungkan `class_name` dan `major_name`
 *   menjadi satu label utuh (contoh: "XII - Desain Komunikasi Visual (DKV)").
 * ============================================================================
 */
class ClassMajor extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_name',
        'major_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->class_name . ' - ' . $this->major_name);
    }
}

