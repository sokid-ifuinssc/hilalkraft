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

    /**
     * Parsing nama jurusan menjadi:
     * - [0] base name bersih tanpa tanda kurung (lowercase)
     * - [1] singkatan / akronim di dalam tanda kurung (lowercase)
     * - [2] full string lowercase
     */
    public static function cleanMajorName(?string $val): array
    {
        $trimmed = trim((string) $val);
        if ($trimmed === '') {
            return ['', '', ''];
        }

        $acronym = '';
        if (preg_match('/\((.*?)\)/', $trimmed, $m)) {
            $acronym = strtolower(trim($m[1]));
        }

        $base = strtolower(trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $trimmed)));
        return [$base, $acronym, strtolower($trimmed)];
    }

    /**
     * Mencari kecocokan nama jurusan dari master database yang diinputkan Super Admin.
     * Mendukung variasi dengan/tanpa singkatan tanda kurung, case-insensitive, dan substring.
     */
    public static function findMatchingMasterMajor(?string $input, $masterList = null): ?string
    {
        if (empty($input)) {
            return null;
        }

        $masters = $masterList ?? static::pluck('major_name')->filter()->unique()->values();
        if ($masters->isEmpty()) {
            return $input;
        }

        [$inputBase, $inputAcro, $inputLower] = static::cleanMajorName($input);

        // 1. Exact match (case-insensitive)
        foreach ($masters as $m) {
            if (strtolower(trim($m)) === $inputLower) {
                return $m;
            }
        }

        // 2. Base name match (mengabaikan singkatan di dalam tanda kurung)
        foreach ($masters as $m) {
            [$mBase, $mAcro] = static::cleanMajorName($m);
            if (! empty($mBase) && $mBase === $inputBase) {
                return $m;
            }
        }

        // 3. Substring match pada base name (misal: "Teknik Komputer dan Jaringan" vs "Teknik Komputer dan Jaringan Telekomunikasi")
        foreach ($masters as $m) {
            [$mBase, $mAcro] = static::cleanMajorName($m);
            if (! empty($mBase) && ! empty($inputBase)) {
                if (str_contains($mBase, $inputBase) || str_contains($inputBase, $mBase)) {
                    return $m;
                }
            }
        }

        // 4. Akronim / Singkatan match (misal: "TKJT" atau "AKL")
        foreach ($masters as $m) {
            [$mBase, $mAcro] = static::cleanMajorName($m);
            if (! empty($mAcro) && ($inputLower === $mAcro || $inputAcro === $mAcro)) {
                return $m;
            }
        }

        return $input;
    }

    /**
     * Mencari kecocokan tingkat kelas siswa dari master database yang diinputkan Super Admin.
     */
    public static function findMatchingMasterClass(?string $input, $classList = null): ?string
    {
        if (empty($input)) {
            return null;
        }

        $classes = $classList ?? static::pluck('class_name')->filter()->unique()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['X', 'XI', 'XII']);
        }

        $inputTrim = trim($input);
        $romanMap = [
            '10' => 'X', '11' => 'XI', '12' => 'XII',
            'KELAS 10' => 'X', 'KELAS 11' => 'XI', 'KELAS 12' => 'XII',
            'KELAS X' => 'X', 'KELAS XI' => 'XI', 'KELAS XII' => 'XII',
        ];

        $normalizedInput = $romanMap[strtoupper($inputTrim)] ?? strtoupper($inputTrim);

        // 1. Match langsung (case-insensitive)
        foreach ($classes as $c) {
            if (strcasecmp($c, $inputTrim) === 0 || strcasecmp($c, $normalizedInput) === 0) {
                return $c;
            }
        }

        // 2. Prefix/Suffix match
        foreach ($classes as $c) {
            if (stripos($inputTrim, $c) !== false || stripos($c, $inputTrim) !== false) {
                return $c;
            }
        }

        return $inputTrim;
    }

    /**
     * Menyelaraskan seluruh data `class_name` & `major_name` di tabel `users` dan `stores`
     * agar persis sama dengan master database `class_majors` yang diinputkan oleh Super Admin.
     */
    public static function syncUsersWithMaster(): int
    {
        try {
            $masterMajors = static::pluck('major_name')->filter()->unique()->values();
            $masterClasses = static::pluck('class_name')->filter()->unique()->values();

            if ($masterMajors->isEmpty() && $masterClasses->isEmpty()) {
                return 0;
            }

            $affectedCount = 0;

            // 1. Sinkronisasi Jurusan Pengguna
            if ($masterMajors->isNotEmpty()) {
                $usersWithMajor = User::whereNotNull('major_name')
                    ->where('major_name', '!=', '')
                    ->get();

                foreach ($usersWithMajor as $user) {
                    $origMajor = trim($user->major_name);
                    $matchedMajor = static::findMatchingMasterMajor($origMajor, $masterMajors);

                    if ($matchedMajor && $matchedMajor !== $origMajor) {
                        $user->update(['major_name' => $matchedMajor]);
                        $affectedCount++;

                        // Sinkronkan juga pada toko pengguna jika terdaftar
                        if ($user->store && $user->store->student_class) {
                            $newStudentClass = str_ireplace($origMajor, $matchedMajor, $user->store->student_class);
                            $user->store->update(['student_class' => $newStudentClass]);
                        }
                    }
                }
            }

            // 2. Sinkronisasi Kelas Pengguna
            if ($masterClasses->isNotEmpty()) {
                $usersWithClass = User::whereNotNull('class_name')
                    ->where('class_name', '!=', '')
                    ->get();

                foreach ($usersWithClass as $user) {
                    $origClass = trim($user->class_name);
                    $matchedClass = static::findMatchingMasterClass($origClass, $masterClasses);

                    if ($matchedClass && $matchedClass !== $origClass) {
                        $user->update(['class_name' => $matchedClass]);
                        $affectedCount++;

                        if ($user->store && $user->store->student_class) {
                            $newStudentClass = str_ireplace("Kelas {$origClass}", "Kelas {$matchedClass}", $user->store->student_class);
                            $user->store->update(['student_class' => $newStudentClass]);
                        }
                    }
                }
            }

            return $affectedCount;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

