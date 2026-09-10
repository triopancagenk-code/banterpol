<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DAFTAR AKUN - BANTERPOOL</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white flex justify-center items-center min-h-screen p-5 relative">

  <!-- Tombol Back -->
  <a href="{{ route('home') }}" class="absolute top-8 left-8 text-3xl text-gray-800 hover:text-[#d31818]">
    <i class="fa-solid fa-chevron-left"></i>
  </a>

  <div class="w-full max-w-[420px] text-center">
    <!-- Title -->
    <div class="mb-6">
      <h2 class="text-2xl font-black text-black">Daftar Akun</h2>
      <p class="text-xs text-gray-600 mt-1 leading-snug">Buat akun baru untuk terhubung dengan<br>WiFi BANTERPOOL</p>
    </div>

    <!-- Flash Error -->
    @if ($errors->any())
      <div class="mb-3 text-xs text-red-600 text-left">
        @foreach ($errors->all() as $error)
          <p>• {{ $error }}</p>
        @endforeach
      </div>
    @endif

    <!-- Form Register -->
    <form method="POST" action="{{ route('register') }}" class="space-y-3" x-data="{ showPass: false, showConfirm: false }">
      @csrf

      <div class="relative flex items-center">
        <i class="fa-regular fa-user absolute left-4 text-gray-500 text-lg"></i>
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama Lengkap" required autofocus
               class="w-full pl-12 pr-4 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
      </div>

      <div class="relative flex items-center">
        <i class="fa-regular fa-user absolute left-4 text-gray-500 text-lg"></i>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required
               class="w-full pl-12 pr-4 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
      </div>

      <div class="relative flex items-center">
        <i class="fa-regular fa-user absolute left-4 text-gray-500 text-lg"></i>
        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="Nomor HP" required
               class="w-full pl-12 pr-4 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
      </div>

      <div class="relative flex items-center">
        <i class="fa-regular fa-user absolute left-4 text-gray-500 text-lg"></i>
        <input :type="showPass ? 'text' : 'password'" name="password" placeholder="Kata sandi" required
               class="w-full pl-12 pr-10 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
        <i class="fa-regular fa-eye absolute right-4 text-gray-700 cursor-pointer text-lg" @click="showPass = !showPass"></i>
      </div>

      <!-- Password Hint -->
      <div class="flex items-center gap-2 text-[10px] text-gray-500 text-left -mt-1">
        <i class="fa-solid fa-circle-check text-[#cc0000] text-sm"></i>
        <span>Minimal 8 karakter dengan kombinasi huruf dan angka</span>
      </div>

      <div class="relative flex items-center">
        <i class="fa-regular fa-user absolute left-4 text-gray-500 text-lg"></i>
        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" placeholder="Konfirmasi Kata sandi" required
               class="w-full pl-12 pr-10 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
        <i class="fa-regular fa-eye absolute right-4 text-gray-700 cursor-pointer text-lg" @click="showConfirm = !showConfirm"></i>
      </div>

      <!-- Checkbox Syarat -->
      <div class="flex items-center gap-2 text-[11px] text-left my-2">
        <input type="checkbox" id="terms" required class="rounded border-gray-400 text-blue-600 focus:ring-blue-500 w-4 h-4">
        <label for="terms" class="text-gray-700">
          Saya setuju dengan <a href="#" class="text-blue-600 hover:underline">Syarat & Ketentuan</a> dan <a href="#" class="text-blue-600 hover:underline">Kebijakan Privasi</a>
        </label>
      </div>

      <button type="submit" class="w-full bg-[#cc0000] hover:bg-[#b30000] text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2 transition">
        <i class="fa-solid fa-user-plus"></i> Daftar
      </button>
    </form>

    <div class="my-3 text-xs text-gray-400">Atau</div>

    <a href="{{ route('auth.google') }}" class="w-full bg-white border border-gray-400 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 text-gray-800 hover:bg-gray-50 transition shadow-sm">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-4 h-4">
      Daftar Dengan Google
    </a>

    <p class="text-xs text-gray-800 mt-4">
      Sudah punya akun? <a href="{{ route('login') }}" class="text-[#d31818] font-semibold hover:underline">Masuk di sini</a>
    </p>
  </div>

</body>
</html>
