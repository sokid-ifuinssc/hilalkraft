{{--
============================================================================
VIEW: REGISTRASI AKUN PUBLIK (auth/register.blade.php)
============================================================================
Fungsi:
  Menyediakan form pendaftaran untuk Pembeli (`buyer`) maupun Pemilik Toko (`seller`)
  yang mencakup 3 kategori pemilik toko: Siswa, Guru, dan Jurusan SMK Plus Al Hilal.
Algoritma (Dynamic Form Toggling & Master ClassMajor Binding):
  1. Menggunakan fungsi JavaScript `toggleStoreFields(role)` untuk menampilkan
     atau menyembunyikan input khusus toko (`seller_type`, `store_name`, `student_class`).
  2. Memuat daftar Kelas & Jurusan dinamis (`$classMajors`) yang dikelola oleh
     Super Admin & Admin ke dalam elemen `<select name="student_class">`.
============================================================================
--}}
@extends('layouts.app')

@section('title', 'Daftar Akun — hilalkraft')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl border border-slate-200/80 p-8 shadow-sm space-y-6">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-900">Registrasi Akun hilalkraft</h1>
            <p class="text-xs text-slate-500">Daftar sebagai Pembeli atau Pemilik Toko (Siswa, Guru, & Jurusan SMK Plus Al Hilal)</p>
        </div>

        <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Pilihan Role Utama -->
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="buyer" class="peer sr-only" {{ old('role', 'buyer') === 'buyer' ? 'checked' : '' }} onchange="toggleStoreFields(this.value)">
                    <div class="rounded-2xl border-2 border-slate-200 p-3.5 text-center peer-checked:border-[#10B981] peer-checked:bg-emerald-50/50 transition">
                        <p class="text-sm font-extrabold text-slate-900">🛒 Pembeli</p>
                        <p class="text-[11px] text-slate-500">Belanja karya SMK Plus Al Hilal</p>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="seller" class="peer sr-only" {{ old('role') === 'seller' ? 'checked' : '' }} onchange="toggleStoreFields(this.value)">
                    <div class="rounded-2xl border-2 border-slate-200 p-3.5 text-center peer-checked:border-[#10B981] peer-checked:bg-emerald-50/50 transition">
                        <p class="text-sm font-extrabold text-slate-900">🏪 Pemilik Toko</p>
                        <p class="text-[11px] text-slate-500">Siswa, Guru, atau Jurusan</p>
                    </div>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap / Penanggung Jawab *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp Aktif *</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="0812xxxxxxxx" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email Login *</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm">
            </div>

            <!-- Field Khusus Pemilik Toko (Siswa, Guru, atau Jurusan) -->
            <div id="seller-fields" class="{{ old('role') === 'seller' ? '' : 'hidden' }} space-y-4 rounded-2xl bg-emerald-50/50 p-4 border border-emerald-500/20">
                <div>
                    <label class="block text-xs font-bold text-emerald-950 mb-1.5">Kategori Pemilik Toko *</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="seller_type" value="siswa" class="peer sr-only" {{ old('seller_type', 'siswa') === 'siswa' ? 'checked' : '' }}>
                            <div class="rounded-xl border border-emerald-300 bg-white py-2 px-2 text-center text-xs font-bold text-slate-700 peer-checked:bg-[#10B981] peer-checked:text-white peer-checked:border-[#10B981] transition">
                                🎓 Siswa
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="seller_type" value="guru" class="peer sr-only" {{ old('seller_type') === 'guru' ? 'checked' : '' }}>
                            <div class="rounded-xl border border-emerald-300 bg-white py-2 px-2 text-center text-xs font-bold text-slate-700 peer-checked:bg-[#10B981] peer-checked:text-white peer-checked:border-[#10B981] transition">
                                👨‍🏫 Guru
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="seller_type" value="jurusan" class="peer sr-only" {{ old('seller_type') === 'jurusan' ? 'checked' : '' }}>
                            <div class="rounded-xl border border-emerald-300 bg-white py-2 px-2 text-center text-xs font-bold text-slate-700 peer-checked:bg-[#10B981] peer-checked:text-white peer-checked:border-[#10B981] transition">
                                🏫 Jurusan
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-emerald-900 mb-1">Nama Toko (Siswa / Guru / Jurusan) *</label>
                    <input type="text" name="store_name" value="{{ old('store_name') }}" placeholder="Contoh: Studio Kriya Al Hilal / Galeri Jurusan DKV" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-emerald-900 mb-1">Pilih Kelas / Jurusan / Unit *</label>
                    <select name="student_class" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm">
                        <option value="">-- Pilih Kelas / Jurusan / Unit --</option>
                        @foreach($classMajors as $cm)
                            <option value="{{ $cm->full_name }}" @selected(old('student_class') === $cm->full_name)>
                                {{ $cm->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-emerald-900 mb-1">Deskripsi Singkat Toko</label>
                    <textarea name="description" rows="2" placeholder="Ceritakan produk unggulan toko Anda..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Sandi *</label>
                    <input type="password" name="password" required minlength="6" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
                    <input type="password" name="password_confirmation" required minlength="6" class="w-full rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm">
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-[#10B981] to-[#059669] py-3 text-sm font-bold text-white shadow-lg shadow-emerald-500/25">
                Daftar Sekarang
            </button>
        </form>
    </div>
</div>

<script>
    function toggleStoreFields(role) {
        const el = document.getElementById('seller-fields');
        if (role === 'seller') {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }
</script>
@endsection
