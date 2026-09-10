@extends('layouts.app')

@section('title', 'WiFi Banterpool - Pengaturan Akun')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header Section -->
  <div class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-black text-black">Pengaturan Profil & Keamanan</h1>
    <p class="text-xs sm:text-sm text-gray-500 mt-1">Kelola foto profil, informasi data diri, dan kata sandi akun Banterpool Anda.</p>
  </div>

  <!-- Alert Status Sukses Update -->
  @if (session('status') === 'profile-updated')
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-xs text-emerald-800 font-bold">
      <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
      <span>Profil Anda berhasil diperbarui!</span>
    </div>
  @elseif (session('status') === 'password-updated')
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-xs text-emerald-800 font-bold">
      <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
      <span>Kata sandi Anda berhasil diubah!</span>
    </div>
  @endif

  <div class="space-y-8">

    <!-- Card 1: Ganti Foto Profil & Data Diri -->
    <div class="border border-black rounded-2xl p-6 sm:p-8 bg-white shadow-sm" x-data="{
        photoPreview: null,
        updatePreview(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { this.photoPreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        }
    }">
      <div class="border-b border-gray-100 pb-4 mb-6">
        <h2 class="text-base font-black text-black flex items-center gap-2">
          <i class="fa-regular fa-id-card text-brand"></i> Informasi Data Diri & Foto Profil
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">Perbarui foto profil, nama lengkap, dan alamat email akun Anda.</p>
      </div>

      <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('patch')

        <!-- Komponen Upload Foto Profil -->
        <div class="flex flex-col sm:flex-row items-center gap-6 pb-4 border-b border-gray-100">
          <div class="relative">
            <!-- Tampilan Foto Default / Foto dari Storage -->
            <template x-if="!photoPreview">
              @if($user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full object-cover border-2 border-brand shadow-sm">
              @else
                <div class="w-24 h-24 rounded-full bg-gray-100 border-2 border-gray-300 flex items-center justify-center text-gray-400 text-3xl">
                  <i class="fa-regular fa-user"></i>
                </div>
              @endif
            </template>

            <!-- Tampilan Preview Foto Baru Sebelum Disimpan -->
            <template x-if="photoPreview">
              <img :src="photoPreview" class="w-24 h-24 rounded-full object-cover border-2 border-brand shadow-sm">
            </template>
          </div>

          <div class="space-y-2 text-center sm:text-left">
            <label class="block text-xs font-bold text-black">Foto Profil</label>
            <div class="flex items-center gap-3">
              <label for="avatar" class="cursor-pointer bg-gray-100 hover:bg-gray-200 text-black border border-gray-300 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-upload"></i> Pilih Foto Baru
              </label>
              <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden" @change="updatePreview">
            </div>
            <p class="text-[10px] text-gray-400">Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB.</p>
            @error('avatar')
              <p class="text-[10px] text-red-600 font-semibold">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Input Nama Lengkap -->
          <div>
            <label for="name" class="block text-xs font-bold text-black mb-1">Nama Lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-brand focus:border-brand">
            @error('name')
              <p class="text-[10px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
            @enderror
          </div>

          <!-- Input Email -->
          <div>
            <label for="email" class="block text-xs font-bold text-black mb-1">Alamat Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-brand focus:border-brand">
            @error('email')
              <p class="text-[10px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
            @enderror
          </div>

          <!-- Input Nomor WhatsApp / Telepon -->
          <div class="sm:col-span-2">
            <label for="phone" class="block text-xs font-bold text-black mb-1">Nomor Telepon / WhatsApp</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx"
                   class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-brand focus:border-brand">
            @error('phone')
              <p class="text-[10px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="pt-2 flex justify-end">
          <button type="submit" class="bg-brand hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs transition">
            Simpan Perubahan Data
          </button>
        </div>
      </form>
    </div>

    <!-- Card 2: Ganti Password -->
    <div class="border border-black rounded-2xl p-6 sm:p-8 bg-white shadow-sm">
      <div class="border-b border-gray-100 pb-4 mb-6">
        <h2 class="text-base font-black text-black flex items-center gap-2">
          <i class="fa-solid fa-key text-brand"></i> Perbarui Kata Sandi
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.</p>
      </div>

      <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div class="space-y-4">
          <div>
            <label for="current_password" class="block text-xs font-bold text-black mb-1">Kata Sandi Saat Ini</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                   class="w-full sm:w-1/2 px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-brand focus:border-brand">
            @error('current_password', 'updatePassword')
              <p class="text-[10px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
            @enderror
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="password" class="block text-xs font-bold text-black mb-1">Kata Sandi Baru</label>
              <input type="password" id="password" name="password" autocomplete="new-password"
                     class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-brand focus:border-brand">
              @error('password', 'updatePassword')
                <p class="text-[10px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label for="password_confirmation" class="block text-xs font-bold text-black mb-1">Konfirmasi Kata Sandi Baru</label>
              <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                     class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-brand focus:border-brand">
              @error('password_confirmation', 'updatePassword')
                <p class="text-[10px] text-red-600 mt-1 font-semibold">{{ $message }}</p>
              @enderror
            </div>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <button type="submit" class="bg-brand hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs transition">
            Ubah Kata Sandi
          </button>
        </div>
      </form>
    </div>

  </div>

</div>
@endsection
