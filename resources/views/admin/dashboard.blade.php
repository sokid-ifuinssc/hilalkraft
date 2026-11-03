{{--
============================================================================
VIEW: DASHBOARD ADMIN & SUPER ADMIN (admin/dashboard.blade.php)
============================================================================
Fungsi:
  Menampilkan panel kendali Super Admin & Admin yang mencakup:
  1. Kartu statistik sistem.
  2. Validasi & Kurasi Toko (Siswa, Guru, dan Jurusan).
  3. Manajemen Kategori Produk (Tambah, Ubah/Edit, Hapus oleh Super Admin & Admin).
  4. Manajemen Kelas & Jurusan (Tambah, Ubah/Edit, Hapus oleh Super Admin & Admin).
  5. Manajemen Pengguna & Kredensial Realtime (termasuk pembuatan akun Toko
     untuk Siswa, Guru, maupun Jurusan).
Algoritma:
  1. Role-Gated Credential Visibility: Kolom kredensial (`email` & `password_plain`)
     hanya dirender apabila `auth()->user()->role === 'super_admin'`.
  2. Dynamic Store Field Toggle on Admin Create User: Ketika Super Admin/Admin
     memilih role `seller` pada form Tambah Pengguna, otomatis muncul opsi
     `seller_type` (`Siswa`, `Guru`, `Jurusan`), `store_name`, dan `student_class`.
  3. Interactive Modal Editing: Menyediakan modal dinamis untuk Edit User,
     Edit Kategori Produk, serta Edit Kelas & Jurusan.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Dashboard Admin & Super Admin — hilalkraft')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    <!-- Header Banner Panel Admin -->
    <div class="rounded-3xl bg-gradient-to-r from-[#10B981] to-[#059669] p-6 sm:p-8 text-white shadow-lg flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider">
                Panel Kontrol {{ auth()->user()->role === 'super_admin' ? 'Super Admin (Akses Penuh & Kredensial Realtime)' : 'Admin Moderator' }}
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold mt-2">Manajemen Ekosistem hilalkraft</h1>
            <p class="text-sm text-emerald-50 mt-1">
                Kelola pengguna, pantau kredensial realtime, validasi toko (Siswa, Guru & Jurusan), kelola Kategori Produk, serta Kelas & Jurusan.
            </p>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Total Pengguna</span>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['total_users'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Total Toko</span>
            <p class="text-2xl font-extrabold text-[#059669] mt-1">{{ $stats['total_stores'] }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 p-5 border border-amber-200 shadow-sm">
            <span class="text-xs text-amber-800 font-semibold">Menunggu Verifikasi</span>
            <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $stats['pending_stores'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Kategori Produk</span>
            <p class="text-2xl font-extrabold text-[#059669] mt-1">{{ $stats['total_categories'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Total Produk</span>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $stats['total_products'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Total Transaksi</span>
            <p class="text-2xl font-extrabold text-[#059669] mt-1">{{ $stats['total_orders'] }}</p>
        </div>
    </div>

    <!-- Bagian 1: Validasi Toko (Siswa, Guru & Jurusan) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h2 class="text-lg font-extrabold text-slate-900 mb-1">Validasi & Kurasi Toko (Siswa, Guru & Jurusan)</h2>
        <p class="text-xs text-slate-500 mb-5">Setujui toko milik Siswa, Guru, maupun Unit Jurusan agar produknya tampil di katalog publik hilalkraft.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase text-slate-500 bg-[#F8FAFC]">
                        <th class="py-3 px-4">Nama Toko & Tipe</th>
                        <th class="py-3 px-4">Pemilik & Kelas/Jurusan</th>
                        <th class="py-3 px-4">Kontak WA</th>
                        <th class="py-3 px-4">Produk</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Aksi Validasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($stores as $store)
                        <tr>
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-slate-900">{{ $store->store_name }}</p>
                                <span class="inline-block mt-0.5 rounded-md bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold text-[#059669]">
                                    {{ $store->seller_type_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-800">{{ $store->owner->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $store->student_class ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">{{ $store->contact_number }}</td>
                            <td class="py-3.5 px-4 font-semibold">{{ $store->products_count }} karya</td>
                            <td class="py-3.5 px-4">
                                @if($store->is_verified)
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-[#059669]">Terverifikasi</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Menunggu</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <form action="{{ route('admin.stores.verify', $store) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="rounded-xl px-3.5 py-1.5 text-xs font-bold transition {{ $store->is_verified ? 'border border-slate-200 text-slate-600 hover:bg-slate-100' : 'bg-gradient-to-r from-[#10B981] to-[#059669] text-white' }}">
                                        {{ $store->is_verified ? 'Nonaktifkan' : '✓ Verifikasi Toko' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bagian 2: Kelola Kategori Produk & Kelola Kelas/Jurusan (Dapat Dikelola Super Admin & Admin) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Modul Kelola Kategori Produk -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#059669]">Master Katalog</span>
                <h2 class="text-lg font-extrabold text-slate-900">Kelola Kategori Produk</h2>
                <p class="text-xs text-slate-500">Tambah, ubah, atau hapus kategori karya produk di marketplace.</p>
            </div>

            <!-- Form Tambah Kategori -->
            <form action="{{ route('admin.categories.store') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <input type="text" name="name" required placeholder="Nama kategori baru (misal: Kerajinan Bambu)..."
                       class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
                <input type="text" name="description" placeholder="Keterangan singkat..."
                       class="sm:w-44 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-4 py-2 text-xs font-bold text-white shrink-0">
                    + Tambah
                </button>
            </form>

            <!-- Tabel Kategori Produk -->
            <div class="overflow-x-auto max-h-72 overflow-y-auto border border-slate-100 rounded-2xl">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500 bg-[#F8FAFC] sticky top-0">
                            <th class="py-2.5 px-3">Nama Kategori</th>
                            <th class="py-2.5 px-3">Keterangan</th>
                            <th class="py-2.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $cat)
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-slate-800">{{ $cat->name }}</td>
                                <td class="py-2.5 px-3 text-xs text-slate-500">{{ $cat->description ?? '-' }}</td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap space-x-1">
                                    <button type="button"
                                            data-name="{{ $cat->name }}"
                                            data-description="{{ $cat->description }}"
                                            data-url="{{ route('admin.categories.update', $cat) }}"
                                            onclick="openEditCategoryModal(this)"
                                            class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-[#059669]">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modul Kelola Kelas & Jurusan -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#059669]">Master Akademik & Unit</span>
                <h2 class="text-lg font-extrabold text-slate-900">Kelola Kelas, Jurusan & Unit</h2>
                <p class="text-xs text-slate-500">Digunakan untuk identitas toko Siswa, Guru, maupun Jurusan SMK Plus Al Hilal.</p>
            </div>

            <!-- Form Tambah Kelas & Jurusan -->
            <form action="{{ route('admin.class-majors.store') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <input type="text" name="class_name" required placeholder="Kelas/Tingkat (X, XI, XII, Guru, Jurusan)"
                       class="sm:w-44 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
                <input type="text" name="major_name" required placeholder="Nama Jurusan / Unit Produksi..."
                       class="flex-1 rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm focus:border-[#10B981] focus:bg-white focus:outline-none">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-4 py-2 text-xs font-bold text-white shrink-0">
                    + Tambah
                </button>
            </form>

            <!-- Tabel Kelas & Jurusan -->
            <div class="overflow-x-auto max-h-72 overflow-y-auto border border-slate-100 rounded-2xl">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500 bg-[#F8FAFC] sticky top-0">
                            <th class="py-2.5 px-3">Kelas / Unit</th>
                            <th class="py-2.5 px-3">Nama Jurusan / Kompetensi</th>
                            <th class="py-2.5 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($classMajors as $cm)
                            <tr>
                                <td class="py-2.5 px-3">
                                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-bold text-[#059669]">
                                        {{ $cm->class_name }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800 text-xs">{{ $cm->major_name }}</td>
                                <td class="py-2.5 px-3 text-right whitespace-nowrap space-x-1">
                                    <button type="button"
                                            data-class="{{ $cm->class_name }}"
                                            data-major="{{ $cm->major_name }}"
                                            data-url="{{ route('admin.class-majors.update', $cm) }}"
                                            onclick="openEditClassMajorModal(this)"
                                            class="rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-[#059669]">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.class-majors.destroy', $cm) }}" method="POST" class="inline" onsubmit="return confirm('Hapus Kelas & Jurusan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bagian 3: Manajemen User (CRUD User & Kredensial Realtime Super Admin) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Form Tambah User (Termasuk Pembuatan Akun Toko Siswa, Guru, & Jurusan) -->
        <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm h-fit">
            <h3 class="text-base font-extrabold text-slate-900 mb-1">Tambah Pengguna / Akun Toko</h3>
            <p class="text-xs text-slate-500 mb-4">
                Buat akun pengguna baru atau akun Pemilik Toko untuk <strong>Siswa, Guru, maupun Jurusan</strong>.
            </p>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap / Penanggung Jawab *</label>
                    <input type="text" name="name" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Username / Email Login *</label>
                    <input type="email" name="email" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="text" name="phone" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Role Hak Akses *</label>
                    <select name="role" onchange="toggleAdminSellerFields(this.value)" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                        <option value="seller">Pemilik Toko (Siswa / Guru / Jurusan)</option>
                        <option value="buyer">Pembeli</option>
                        @if(auth()->user()->role === 'super_admin')
                            <option value="admin">Admin Sekolah</option>
                            <option value="super_admin">Super Admin</option>
                        @endif
                    </select>
                </div>

                <!-- Opsi Tambahan Saat Membuat Akun Pemilik Toko (Siswa, Guru, atau Jurusan) -->
                <div id="admin-seller-fields" class="space-y-3 rounded-2xl bg-emerald-50/60 p-3.5 border border-emerald-500/20">
                    <div>
                        <label class="block text-xs font-bold text-emerald-950 mb-1">Kategori Pemilik Toko *</label>
                        <select name="seller_type" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800">
                            <option value="siswa">🎓 Siswa Kreator</option>
                            <option value="guru">👨‍🏫 Guru / Pembina</option>
                            <option value="jurusan">🏫 Unit Produksi Jurusan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-950 mb-1">Nama Toko</label>
                        <input type="text" name="store_name" placeholder="Contoh: Galeri DKV Al Hilal" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-emerald-950 mb-1">Kelas / Jurusan / Unit</label>
                        <select name="student_class" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs">
                            @foreach($classMajors as $cm)
                                <option value="{{ $cm->full_name }}">{{ $cm->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Akun *</label>
                    <input type="text" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm font-mono">
                </div>
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] py-2.5 text-xs font-bold text-white shadow-md hover:opacity-95 transition">
                    + Simpan Pengguna Baru
                </button>
            </form>
        </div>

        <!-- Tabel Daftar User & Kredensial Realtime -->
        <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Daftar Pengguna & Kredensial Realtime</h3>
                    <p class="text-xs text-slate-500">
                        @if(auth()->user()->role === 'super_admin')
                            Menampilkan username/email & password realtime (baik akun awal yang belum diedit maupun yang sudah diedit).
                        @else
                            Menampilkan daftar pengguna Pemilik Toko (Siswa/Guru/Jurusan) dan Pembeli.
                        @endif
                    </p>
                </div>
                @if(auth()->user()->role === 'super_admin')
                    <button type="button" onclick="toggleAllPasswords()" id="btn-toggle-all"
                            class="rounded-xl border border-emerald-500/30 bg-emerald-50/70 px-3.5 py-2 text-xs font-bold text-[#059669] hover:bg-emerald-100 transition">
                        👁️ Sembunyikan / Tampilkan Semua Password
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase text-slate-500 bg-[#F8FAFC]">
                            <th class="py-3 px-3">Nama Pengguna & Toko</th>
                            @if(auth()->user()->role === 'super_admin')
                                <th class="py-3 px-3">Kredensial Realtime (Username & Password)</th>
                            @else
                                <th class="py-3 px-3">Email & Telepon</th>
                            @endif
                            <th class="py-3 px-3">Role & Status</th>
                            <th class="py-3 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $user)
                            @php
                                $isEdited = $user->updated_at && $user->created_at && $user->updated_at->gt($user->created_at);
                                $realtimePassword = $user->password_plain ?? 'password123';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">WA: {{ $user->phone ?? '-' }}</p>
                                    @if($user->store)
                                        <span class="inline-block mt-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-[#059669]">
                                            {{ $user->store->seller_type_label }}: {{ $user->store->store_name }}
                                        </span>
                                    @endif
                                </td>

                                @if(auth()->user()->role === 'super_admin')
                                    <td class="py-3.5 px-3">
                                        <div class="rounded-2xl bg-[#F8FAFC] border border-emerald-500/20 p-2.5 space-y-1.5">
                                            <div class="flex items-center justify-between gap-2 text-xs">
                                                <span class="text-slate-400 font-semibold">User/Email:</span>
                                                <code class="font-mono font-bold text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200">{{ $user->email }}</code>
                                            </div>
                                            <div class="flex items-center justify-between gap-2 text-xs">
                                                <span class="text-slate-400 font-semibold">Password:</span>
                                                <div class="flex items-center gap-1.5">
                                                    <code data-pw="{{ $realtimePassword }}" class="pw-display font-mono font-extrabold text-[#059669] bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ $realtimePassword }}</code>
                                                    <button type="button" onclick="toggleSinglePassword(this)" class="text-[11px] text-slate-500 hover:text-slate-800" title="Sembunyikan/Lihat">
                                                        👁️
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60">
                                                @if($isEdited)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-full">
                                                        ✏️ Sudah Diedit ({{ $user->updated_at->diffForHumans() }})
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full">
                                                        ✓ Belum Diedit (Kredensial Awal)
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @else
                                    <td class="py-3.5 px-3">
                                        <p class="text-xs font-semibold text-slate-700">{{ $user->email }}</p>
                                        <p class="text-xs text-slate-400">{{ $user->phone ?? '-' }}</p>
                                    </td>
                                @endif

                                <td class="py-3.5 px-3">
                                    <span class="inline-block rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-[#059669] uppercase">
                                        {{ $user->role }}
                                    </span>
                                    <span class="block text-[11px] mt-1 font-semibold {{ $user->is_active ? 'text-emerald-600' : 'text-rose-500' }}">
                                        {{ $user->is_active ? '● Aktif' : '○ Nonaktif' }}
                                    </span>
                                </td>

                                <td class="py-3.5 px-3 text-right space-x-1.5 whitespace-nowrap">
                                    <button type="button"
                                            data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}"
                                            data-phone="{{ $user->phone }}"
                                            data-role="{{ $user->role }}"
                                            data-active="{{ $user->is_active ? 1 : 0 }}"
                                            data-password="{{ $realtimePassword }}"
                                            data-url="{{ route('admin.users.update', $user) }}"
                                            onclick="openEditUserModal(this)"
                                            class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-bold text-[#059669] hover:bg-emerald-100 transition">
                                        ✏️ Edit
                                    </button>

                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-100 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Edit Akun & Kredensial Realtime -->
<div id="edit-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#059669]">Sinkronisasi Kredensial Realtime</span>
                <h3 class="text-lg font-extrabold text-slate-900">Edit Akun & Password</h3>
            </div>
            <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        </div>

        <form id="edit-user-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="name" id="edit-name" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Username / Email Login *</label>
                <input type="email" name="email" id="edit-email" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm font-mono">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp</label>
                    <input type="text" name="phone" id="edit-phone" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status Akun</label>
                    <select name="is_active" id="edit-is-active" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Role Hak Akses *</label>
                <select name="role" id="edit-role" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
                    @if(auth()->user()->role === 'super_admin')
                        <option value="super_admin">Super Admin</option>
                        <option value="admin">Admin Sekolah</option>
                    @endif
                    <option value="seller">Pemilik Toko (Siswa / Guru / Jurusan)</option>
                    <option value="buyer">Pembeli</option>
                </select>
            </div>

            <div class="rounded-2xl bg-emerald-50/70 border border-emerald-500/20 p-3.5 space-y-2">
                <label class="block text-xs font-extrabold text-emerald-950">
                    Password Akun (Ubah atau Biarkan Sesuai Nilai Saat Ini)
                </label>
                <input type="text" name="password" id="edit-password" minlength="6"
                       class="w-full rounded-xl border border-emerald-300 bg-white px-3.5 py-2 text-sm font-mono font-bold text-[#059669]">
                <p class="text-[11px] text-emerald-800">
                    Jika Anda mengubah password di atas dan klik Simpan, password login & tampilan kredensial akan langsung terupdate secara realtime.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditUserModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600">
                    Batal
                </button>
                <button type="submit" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-2 text-xs font-bold text-white shadow-md">
                    Simpan Perubahan Realtime
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Kategori Produk -->
<div id="edit-category-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-extrabold text-slate-900">Ubah Kategori Produk</h3>
            <button type="button" onclick="closeEditCategoryModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        </div>
        <form id="edit-category-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori *</label>
                <input type="text" name="name" id="edit-category-name" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Singkat</label>
                <input type="text" name="description" id="edit-category-desc" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditCategoryModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-2 text-xs font-bold text-white">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Edit Kelas & Jurusan -->
<div id="edit-class-major-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-extrabold text-slate-900">Ubah Kelas & Jurusan / Unit</h3>
            <button type="button" onclick="closeEditClassMajorModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
        </div>
        <form id="edit-class-major-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kelas / Tingkat / Unit *</label>
                <input type="text" name="class_name" id="edit-cm-class" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Jurusan / Kompetensi *</label>
                <input type="text" name="major_name" id="edit-cm-major" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditClassMajorModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] px-5 py-2 text-xs font-bold text-white">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    let allMasked = false;

    function toggleSinglePassword(btn) {
        const codeEl = btn.previousElementSibling;
        const realPw = codeEl.getAttribute('data-pw');
        codeEl.textContent = (codeEl.textContent === '••••••••') ? realPw : '••••••••';
    }

    function toggleAllPasswords() {
        allMasked = !allMasked;
        document.querySelectorAll('.pw-display').forEach(el => {
            el.textContent = allMasked ? '••••••••' : el.getAttribute('data-pw');
        });
    }

    function toggleAdminSellerFields(role) {
        const el = document.getElementById('admin-seller-fields');
        if (role === 'seller') {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }

    function openEditUserModal(btn) {
        const modal = document.getElementById('edit-user-modal');
        const form = document.getElementById('edit-user-form');
        form.action = btn.getAttribute('data-url');
        document.getElementById('edit-name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit-email').value = btn.getAttribute('data-email') || '';
        document.getElementById('edit-phone').value = btn.getAttribute('data-phone') || '';
        document.getElementById('edit-role').value = btn.getAttribute('data-role') || 'buyer';
        document.getElementById('edit-is-active').value = btn.getAttribute('data-active') || '1';
        document.getElementById('edit-password').value = btn.getAttribute('data-password') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditUserModal() {
        const modal = document.getElementById('edit-user-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditCategoryModal(btn) {
        const modal = document.getElementById('edit-category-modal');
        document.getElementById('edit-category-form').action = btn.getAttribute('data-url');
        document.getElementById('edit-category-name').value = btn.getAttribute('data-name') || '';
        document.getElementById('edit-category-desc').value = btn.getAttribute('data-description') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditCategoryModal() {
        const modal = document.getElementById('edit-category-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openEditClassMajorModal(btn) {
        const modal = document.getElementById('edit-class-major-modal');
        document.getElementById('edit-class-major-form').action = btn.getAttribute('data-url');
        document.getElementById('edit-cm-class').value = btn.getAttribute('data-class') || '';
        document.getElementById('edit-cm-major').value = btn.getAttribute('data-major') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditClassMajorModal() {
        const modal = document.getElementById('edit-class-major-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
