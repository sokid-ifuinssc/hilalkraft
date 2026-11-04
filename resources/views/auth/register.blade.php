{{--
============================================================================
VIEW: REGISTRASI SPLIT-SCREEN SESUAI FIGMA (auth/register.blade.php)
============================================================================
Fungsi:
  Menampilkan halaman pendaftaran sesuai referensi `Daftar pembeli · Desktop.png`
  dan `Daftar pemilik toko · Desktop.png` ("Mulai cerita karyamu.") dengan
  mempertahankan seluruh pilihan dan atribut wajib:
  A. Pembeli / Pengunjung (`role = buyer`):
     1. Pelajar (NIS/NISN, Asal Sekolah, Kelas)
     2. Keluarga SMK Plus Al Hilal (NIK, Nama Siswa, Kelas & Jurusan DB)
     3. Alumni (NIK, Tahun Lulus)
     4. Pengunjung Umum (NIK)
  B. Pemilik Toko (`role = seller`, Satu Toko Satu Akun):
     - Siswa (NIS/NISN, Kelas & Jurusan DB, Nama Toko)
     - Guru (NUPTK, Jurusan/Unit, Nama Toko)
  Beserta Nama Lengkap, Email, Password, Nomor WhatsApp/Telepon, dan Alamat
  Lengkap (Desa, Kecamatan, Kabupaten/Kota, Kode Pos).
Algoritma:
  Fungsi JS `updateRegisterFormState()` mengatur visibilitas input, atribut
  `required`, serta teks tombol submit secara dinamis.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Daftar Akun — hilalkraf (Inkubator Bisnis & Wirausaha Muda SMK PAL)')
@section('hide_navbar', true)
@section('hide_footer', true)

@section('content')
@php
    $effectiveClasses = $classList->count() > 0 ? $classList : collect(['X', 'XI', 'XII']);
    $effectiveMajors  = $majorList->count() > 0 ? $majorList : collect([
        'Teknik Komputer dan Jaringan Telekomunikasi (TKJT)',
        'Teknik Otomotif (TO)',
        'Akuntansi dan Keuangan Lembaga (AKL)',
    ]);
@endphp

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-12 bg-white">

    <!-- Panel Kiri: Editorial Mint Visual -->
    <div class="hidden lg:flex lg:col-span-5 bg-gradient-to-b from-[#D5F5E6] via-[#EBF9F2] to-white p-10 xl:p-14 flex-col justify-between border-r border-slate-100 sticky top-0 h-screen">
        <div class="space-y-8">
            <div class="pb-5 border-b-2 border-[#0B6E4F]">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div class="flex items-center gap-2 rounded-2xl bg-white border border-emerald-500/25 px-3 py-1.5 shadow-sm">
                        <img src="{{ asset('images/logo-hilalkraf.jpg') }}"
                             alt="hilalkraf"
                             class="h-12 w-auto object-contain">
                        <span class="h-7 w-[1px] bg-slate-200"></span>
                        <img src="{{ asset('images/logo-smk-alhilal.png') }}"
                             alt="SMK Plus Al Hilal"
                             class="h-10 w-10 object-contain">
                    </div>
                    <div>
                        <span class="text-xl font-extrabold tracking-tight text-[#0F291E] block leading-none">hilalkraf</span>
                        <span class="text-[10px] font-bold text-[#0B6E4F]">Inkubator Bisnis & Wirausaha Muda SMK PAL</span>
                    </div>
                </a>
            </div>

            <div class="space-y-3 pt-2">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-[#0B6E4F]">
                    RUANG UNTUK BERTUMBUH
                </p>
                <h1 class="text-3xl xl:text-4xl font-extrabold text-[#0F291E] leading-[1.15] tracking-tight">
                    Karya sederhana.<br>
                    Kemungkinan<br>
                    tak terbatas.
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-sm">
                    Bersama <strong>hilalkraf</strong> — unit resmi milik <strong>SMK Plus Al Hilal</strong> di bawah <strong>Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</strong>.
                </p>
            </div>

            <div class="rounded-2xl bg-white border border-slate-100 shadow-sm overflow-hidden max-w-md">
                <div class="aspect-[16/10] bg-slate-100 overflow-hidden">
                    <img
                        src="{{ asset('images/products/gantungan-kunci-bunga.jpg') }}"
                        alt="Karya Siswa SMK Plus Al Hilal"
                        class="w-full h-full object-cover"
                    >
                </div>
                <div class="p-5 space-y-2">
                    <p class="text-xs sm:text-sm font-medium text-[#0F291E] leading-relaxed">
                        "Gantungan Kunci Bunga — Manis, simple, bikin semangat! Teman kecil yang selalu bikin hari lebih berwarna."
                    </p>
                    <p class="text-[11px] font-bold text-[#0B6E4F]">
                        Khoiriyah Muntazah & Yulianti • Kelas XI TKJT
                    </p>
                </div>
            </div>
        </div>

        <div class="pt-6 space-y-0.5">
            <p class="text-[11px] font-bold text-[#0B6E4F]">Milik Resmi SMK Plus Al Hilal Arjawinangun</p>
            <p class="text-[10px] text-slate-500">Kepala Lembaga Inkubator Bisnis dan Wirausaha Muda SMK PAL</p>
        </div>
    </div>

    <!-- Panel Kanan: Formulir "Mulai cerita karyamu." -->
    <div class="lg:col-span-7 p-6 sm:p-10 xl:p-14 overflow-y-auto">
        <div class="max-w-lg w-full mx-auto space-y-6">

            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-[#0B6E4F] transition">
                <span>←</span>
                <span>Kembali ke marketplace</span>
            </a>

            <div class="space-y-1">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#0F291E] tracking-tight">
                    Mulai cerita karyamu.
                </h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Satu akun untuk mendukung karya dan membuka peluang baru.
                </p>
            </div>

            <form action="{{ route('register.store') }}" method="POST" class="space-y-4" id="register-form">
                @csrf

                <!-- Kartu Pilihan Peran Sesuai Desain Figma -->
                <div class="space-y-2.5">
                    <label class="block text-xs font-bold text-[#0F291E]">Saya ingin bergabung sebagai</label>

                    <!-- Opsi 1: Daftar sebagai Pembeli -->
                    <label class="block cursor-pointer">
                        <input type="radio" name="role" value="buyer" class="peer sr-only" {{ old('role', 'buyer') === 'buyer' ? 'checked' : '' }} onchange="updateRegisterFormState()">
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3.5 peer-checked:border-[#0B6E4F] peer-checked:bg-[#EBF9F2] transition">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-white border border-slate-200/80 text-[#0B6E4F] flex items-center justify-center text-sm">🛍️</span>
                                <div>
                                    <p class="text-xs sm:text-sm font-bold text-[#0F291E]">Daftar sebagai Pembeli</p>
                                    <p class="text-[11px] text-slate-500">Temukan dan belanja karya siswa (Pelajar, Keluarga, Alumni, Umum).</p>
                                </div>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 border-slate-300 peer-checked:border-[#0B6E4F] flex items-center justify-center">
                                <span class="w-2 h-2 rounded-full bg-[#0B6E4F] opacity-0 peer-checked:opacity-100"></span>
                            </span>
                        </div>
                    </label>

                    <!-- Opsi 2: Daftar sebagai Pemilik Toko (Siswa / Guru) -->
                    <label class="block cursor-pointer">
                        <input type="radio" name="role" value="seller" class="peer sr-only" {{ old('role') === 'seller' ? 'checked' : '' }} onchange="updateRegisterFormState()">
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3.5 peer-checked:border-[#0B6E4F] peer-checked:bg-[#EBF9F2] transition">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg bg-white border border-slate-200/80 text-[#0B6E4F] flex items-center justify-center text-sm">🏪</span>
                                <div>
                                    <p class="text-xs sm:text-sm font-bold text-[#0F291E]">Daftar sebagai Pemilik Toko (Siswa / Guru)</p>
                                    <p class="text-[11px] text-slate-500">Tampilkan karya dan mulai berjualan (Satu Toko Satu Akun).</p>
                                </div>
                            </div>
                            <span class="w-4 h-4 rounded-full border-2 border-slate-300 peer-checked:border-[#0B6E4F] flex items-center justify-center">
                                <span class="w-2 h-2 rounded-full bg-[#0B6E4F] opacity-0 peer-checked:opacity-100"></span>
                            </span>
                        </div>
                    </label>
                </div>

                <!-- Field Utama: Nama, Email, Password, WhatsApp -->
                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Masukkan nama lengkap" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1">Email</label>
                    <div class="relative flex items-center">
                        <input type="email" name="email" id="reg-email-input" value="{{ old('email') }}" required placeholder="nama@email.com" class="w-full rounded-xl border border-slate-200 bg-white pl-4 pr-10 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                        <svg class="w-4 h-4 text-slate-400 absolute right-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#0F291E] mb-1">Password</label>
                        <input type="password" name="password" required minlength="6" placeholder="Buat password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#0F291E] mb-1">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required minlength="6" placeholder="Ulangi password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 -mt-2">Minimal 6 karakter, gunakan kombinasi huruf dan angka.</p>

                <div>
                    <label class="block text-xs font-bold text-[#0F291E] mb-1">Nomor WhatsApp/Telepon</label>
                    <div class="relative flex items-center">
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="+62 812-3456-7890" class="w-full rounded-xl border border-slate-200 bg-white pl-4 pr-10 py-2.5 text-xs sm:text-sm focus:border-[#0B6E4F] focus:outline-none">
                        <svg class="w-4 h-4 text-slate-400 absolute right-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                </div>

                <!-- BLOK A: PILIHAN & ATRIBUT PEMBELI / PENGUNJUNG -->
                <div id="section-buyer" class="space-y-3 rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80">
                    <div>
                        <label class="block text-xs font-bold text-[#0F291E] mb-2">Kategori Pembeli / Pengunjung</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach(['pelajar' => '1. Pelajar', 'keluarga' => '2. Keluarga', 'alumni' => '3. Alumni', 'umum' => '4. Umum'] as $bKey => $bLabel)
                                <label class="cursor-pointer">
                                    <input type="radio" name="buyer_type" value="{{ $bKey }}" class="peer sr-only" {{ old('buyer_type', 'pelajar') === $bKey ? 'checked' : '' }} onchange="updateRegisterFormState()">
                                    <div class="rounded-lg border border-slate-200 bg-white py-2 px-2 text-center text-[11px] font-semibold text-slate-600 peer-checked:bg-[#0B6E4F] peer-checked:text-white peer-checked:border-[#0B6E4F] transition">
                                        {{ $bLabel }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- 1. Pelajar -->
                    <div id="buyer-pelajar-fields" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <input type="text" name="nis_nisn" id="input-buyer-nis" value="{{ old('nis_nisn') }}" placeholder="NIS / NISN *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="school_origin" id="input-school-origin" value="{{ old('school_origin') }}" placeholder="Asal Sekolah *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="class_name" id="input-buyer-class" value="{{ old('class_name') }}" placeholder="Kelas *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                    </div>

                    <!-- NIK (Keluarga, Alumni, Umum) -->
                    <div id="buyer-nik-field" class="hidden">
                        <input type="text" name="nik" id="input-nik" value="{{ old('nik') }}" placeholder="Nomor Induk Kependudukan (NIK 16 Digit) *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                    </div>

                    <!-- 2. Keluarga SMK Plus Al Hilal -->
                    <div id="buyer-keluarga-fields" class="hidden grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <input type="text" name="family_student_name" id="input-family-name" value="{{ old('family_student_name') }}" placeholder="Nama Siswa (Keluarga) *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <select name="family_student_class" id="input-family-class" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Kelas Siswa *</option>
                            @foreach($effectiveClasses as $cl)
                                <option value="{{ $cl }}" @selected(old('family_student_class') === $cl)>{{ $cl }}</option>
                            @endforeach
                        </select>
                        <select name="family_student_major" id="input-family-major" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Jurusan Siswa *</option>
                            @foreach($effectiveMajors as $mj)
                                <option value="{{ $mj }}" @selected(old('family_student_major') === $mj)>{{ $mj }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Alumni -->
                    <div id="buyer-alumni-fields" class="hidden">
                        <input type="number" name="graduation_year" id="input-grad-year" min="2000" max="{{ date('Y') }}" value="{{ old('graduation_year') }}" placeholder="Tahun Lulus (Contoh: 2024) *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                </div>

                <!-- BLOK B: PILIHAN & ATRIBUT PEMILIK TOKO (SISWA ATAU GURU) -->
                <div id="section-seller" class="hidden space-y-3 rounded-xl bg-[#F8FAFC] p-4 border border-slate-200/80">
                    <div>
                        <label class="block text-xs font-bold text-[#0F291E] mb-2">Kategori Pemilik Toko</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="seller_type" value="siswa" class="peer sr-only" {{ old('seller_type', 'siswa') === 'siswa' ? 'checked' : '' }} onchange="updateRegisterFormState()">
                                <div class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-center text-xs font-semibold text-slate-600 peer-checked:bg-[#0B6E4F] peer-checked:text-white peer-checked:border-[#0B6E4F] transition">
                                    Siswa (NIS/NISN, Kelas & Jurusan)
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="seller_type" value="guru" class="peer sr-only" {{ old('seller_type') === 'guru' ? 'checked' : '' }} onchange="updateRegisterFormState()">
                                <div class="rounded-lg border border-slate-200 bg-white py-2 px-3 text-center text-xs font-semibold text-slate-600 peer-checked:bg-[#0B6E4F] peer-checked:text-white peer-checked:border-[#0B6E4F] transition">
                                    Guru (NUPTK)
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Siswa: NIS/NISN, Kelas DB, Jurusan DB -->
                    <div id="seller-siswa-fields" class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <input type="text" name="nis_nisn" id="input-seller-nis" value="{{ old('nis_nisn') }}" placeholder="NIS / NISN Siswa *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <select name="class_name" id="input-seller-class" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Pilih Kelas *</option>
                            @foreach($effectiveClasses as $cl)
                                <option value="{{ $cl }}" @selected(old('class_name') === $cl)>{{ $cl }}</option>
                            @endforeach
                        </select>
                        <select name="major_name" id="input-seller-major" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Pilih Jurusan *</option>
                            @foreach($effectiveMajors as $mj)
                                <option value="{{ $mj }}" @selected(old('major_name') === $mj)>{{ $mj }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Guru: NUPTK & Jurusan -->
                    <div id="seller-guru-fields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <input type="text" name="nuptk" id="input-seller-nuptk" value="{{ old('nuptk') }}" placeholder="NUPTK Guru *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <select name="major_name" id="input-guru-major" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                            <option value="">Jurusan / Unit Ampuan</option>
                            @foreach($effectiveMajors as $mj)
                                <option value="{{ $mj }}" @selected(old('major_name') === $mj)>{{ $mj }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Nama & Deskripsi Toko -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <input type="text" name="store_name" id="input-store-name" value="{{ old('store_name') }}" placeholder="Nama Toko *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="description" value="{{ old('description') }}" placeholder="Deskripsi Singkat Toko" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                    </div>

                    <!-- Kotak Upload Identitas Siswa Sesuai `Daftar pemilik toko · Desktop.png` -->
                    <div class="pt-1 space-y-1.5">
                        <label class="block text-xs font-bold text-[#0F291E]">Upload Identitas Siswa / Guru (Opsional)</label>
                        <label class="flex flex-col items-center justify-center rounded-xl border border-dashed border-[#0B6E4F]/50 bg-[#EBF9F2]/40 py-5 px-4 text-center cursor-pointer hover:bg-[#EBF9F2] transition">
                            <span class="text-lg text-[#0B6E4F]">🪪</span>
                            <span class="text-xs font-bold text-[#0B6E4F] mt-1">Pilih foto kartu pelajar / identitas</span>
                            <span class="text-[10px] text-slate-400 mt-0.5">JPG, PNG, atau PDF • Maks. 5 MB</span>
                            <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                        </label>
                        <p class="text-[10px] text-slate-500 flex items-center gap-1">
                            <span class="text-[#0B6E4F] font-bold">✓</span>
                            <span>Identitas hanya digunakan untuk verifikasi sivitas SMK Plus Al Hilal oleh pengelola.</span>
                        </p>
                    </div>
                </div>

                <!-- Alamat Lengkap Wajib (Desa, Kecamatan, Kabupaten/Kota, Kode Pos) -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-[#0F291E]">Alamat Lengkap</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <input type="text" name="desa" value="{{ old('desa') }}" required placeholder="Desa / Kel. *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="kecamatan" value="{{ old('kecamatan') }}" required placeholder="Kecamatan *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="kabupaten" value="{{ old('kabupaten') }}" required placeholder="Kabupaten *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                        <input type="text" name="kode_pos" value="{{ old('kode_pos') }}" required placeholder="Kode Pos *" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                    </div>
                    <input type="text" name="address_detail" value="{{ old('address_detail') }}" placeholder="Detail jalan / RT / RW (opsional)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-[#0B6E4F] focus:outline-none">
                </div>

                <!-- Persetujuan Syarat & Ketentuan -->
                <label class="inline-flex items-center gap-2 text-xs text-slate-500 cursor-pointer pt-1">
                    <input type="checkbox" required checked class="rounded border-slate-300 text-[#0B6E4F] focus:ring-[#0B6E4F]">
                    <span>Saya menyetujui Syarat & Ketentuan dan Kebijakan Privasi hilalkraft.</span>
                </label>

                <button type="submit" id="reg-submit-btn" class="w-full rounded-xl bg-[#0B6E4F] py-3 text-xs sm:text-sm font-semibold text-white hover:bg-[#08583F] transition shadow-sm flex items-center justify-center gap-2">
                    <span>→</span>
                    <span id="reg-submit-label">Daftar sebagai Pembeli</span>
                </button>
            </form>

            <div class="space-y-3 text-center pt-1">
                <p class="text-xs text-slate-500">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-bold text-[#0B6E4F] hover:underline">Masuk di sini</a>
                </p>
                <p class="text-[11px] text-slate-400">
                    🔒 Data pribadimu aman bersama hilalkraf SMK PAL.
                </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px] text-slate-400">
                <span>&copy; {{ date('Y') }} hilalkraf • SMK Plus Al Hilal</span>
                <span class="font-semibold text-[#0B6E4F]">Inkubator Bisnis SMK PAL</span>
            </div>
        </div>
    </div>
</div>

<script>
    function setFieldActive(id, active, required = false) {
        const el = document.getElementById(id);
        if (!el) return;
        el.disabled = !active;
        el.required = active && required;
    }

    function updateRegisterFormState() {
        const role = document.querySelector('input[name="role"]:checked')?.value || 'buyer';
        const buyerType = document.querySelector('input[name="buyer_type"]:checked')?.value || 'pelajar';
        const sellerType = document.querySelector('input[name="seller_type"]:checked')?.value || 'siswa';

        const secBuyer = document.getElementById('section-buyer');
        const secSeller = document.getElementById('section-seller');
        const submitLabel = document.getElementById('reg-submit-label');
        const emailInput = document.getElementById('reg-email-input');

        if (role === 'buyer') {
            secBuyer.classList.remove('hidden');
            secSeller.classList.add('hidden');
            if (submitLabel) submitLabel.textContent = 'Daftar sebagai Pembeli';
            if (emailInput) emailInput.placeholder = 'nama@email.com';

            setFieldActive('input-seller-nis', false);
            setFieldActive('input-seller-class', false);
            setFieldActive('input-seller-major', false);
            setFieldActive('input-seller-nuptk', false);
            setFieldActive('input-guru-major', false);
            setFieldActive('input-store-name', false);

            const isPelajar = buyerType === 'pelajar';
            const isKeluarga = buyerType === 'keluarga';
            const isAlumni = buyerType === 'alumni';
            const needNik = !isPelajar;

            document.getElementById('buyer-pelajar-fields').classList.toggle('hidden', !isPelajar);
            document.getElementById('buyer-nik-field').classList.toggle('hidden', !needNik);
            document.getElementById('buyer-keluarga-fields').classList.toggle('hidden', !isKeluarga);
            document.getElementById('buyer-alumni-fields').classList.toggle('hidden', !isAlumni);

            setFieldActive('input-buyer-nis', isPelajar, true);
            setFieldActive('input-school-origin', isPelajar, true);
            setFieldActive('input-buyer-class', isPelajar, true);
            setFieldActive('input-nik', needNik, true);
            setFieldActive('input-family-name', isKeluarga, true);
            setFieldActive('input-family-class', isKeluarga, true);
            setFieldActive('input-family-major', isKeluarga, true);
            setFieldActive('input-grad-year', isAlumni, true);
        } else {
            secBuyer.classList.add('hidden');
            secSeller.classList.remove('hidden');
            if (submitLabel) submitLabel.textContent = 'Daftar & Ajukan Verifikasi';
            if (emailInput) emailInput.placeholder = 'nama@siswa.sch.id';

            setFieldActive('input-buyer-nis', false);
            setFieldActive('input-school-origin', false);
            setFieldActive('input-buyer-class', false);
            setFieldActive('input-nik', false);
            setFieldActive('input-family-name', false);
            setFieldActive('input-family-class', false);
            setFieldActive('input-family-major', false);
            setFieldActive('input-grad-year', false);

            setFieldActive('input-store-name', true, true);

            const isSiswa = sellerType === 'siswa';
            document.getElementById('seller-siswa-fields').classList.toggle('hidden', !isSiswa);
            document.getElementById('seller-guru-fields').classList.toggle('hidden', isSiswa);

            setFieldActive('input-seller-nis', isSiswa, true);
            setFieldActive('input-seller-class', isSiswa, true);
            setFieldActive('input-seller-major', isSiswa, true);
            setFieldActive('input-seller-nuptk', !isSiswa, true);
            setFieldActive('input-guru-major', !isSiswa, false);
        }
    }

    document.addEventListener('DOMContentLoaded', updateRegisterFormState);
</script>
@endsection
