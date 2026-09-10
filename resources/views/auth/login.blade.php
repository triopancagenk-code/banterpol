<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LOGIN - BANTERPOOL</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white flex justify-center items-center min-h-screen p-5">

  <div class="w-full max-w-[420px] text-center">
    <!-- Logo Header -->
    <div class="mb-4">
      <div class="flex items-center justify-center gap-2">
        <i class="fa-solid fa-wifi text-[#d31818] text-3xl"></i>
        <h1 class="text-3xl font-black tracking-wider text-black">BANTER<span class="text-[#d31818] italic">POOL</span></h1>
      </div>
      <p class="text-[11px] text-gray-700 mt-1">WiFi Super Kencang, Koneksi Tanpa Batas</p>
      <img src="{{ asset('image/router.png') }}" alt="Router" class="w-44 mx-auto mt-3">
    </div>

    <!-- Title -->
    <div class="mb-5">
      <h2 class="text-2xl font-black text-black">Selamat Datang!</h2>
      <p class="text-xs text-gray-600 mt-1 leading-snug">Masuk dan nikmati koneksi terbaik<br>dari BANTERPOOL</p>
    </div>

    <!-- Flash Error & Info -->
    @if (session('status'))
      <div class="mb-3 text-xs text-green-600 text-left bg-green-50 p-2.5 rounded-lg border border-green-200">
        {{ session('status') }}
      </div>
    @endif
    @if (session('info'))
      <div class="mb-3 text-xs text-blue-600 text-left bg-blue-50 p-2.5 rounded-lg border border-blue-200">
        {{ session('info') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="mb-3 text-xs text-red-600 text-left bg-red-50 p-2.5 rounded-lg border border-red-200">
        @foreach ($errors->all() as $error)
          <p>• {{ $error }}</p>
        @endforeach
      </div>
    @endif

    <!-- Form Login -->
    <form method="POST" action="{{ route('login') }}" class="space-y-3">
      @csrf

      <div class="relative flex items-center">
        <i class="fa-regular fa-user absolute left-4 text-gray-500 text-lg"></i>
        <input type="text" name="email" value="{{ old('email') }}" placeholder="Email atau Nomer HP" required autofocus
               class="w-full pl-12 pr-4 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
      </div>

      <div class="relative flex items-center">
        <i class="fa-solid fa-lock absolute left-4 text-gray-500 text-lg"></i>
        <input type="password" name="password" placeholder="Kata sandi" required
               class="w-full pl-12 pr-4 py-3 border border-gray-400 rounded-xl text-sm focus:ring-[#d31818] focus:border-[#d31818] placeholder-gray-400">
      </div>

      <div class="text-right">
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="text-xs text-[#d31818] hover:underline font-medium">Lupa Kata sandi?</a>
        @endif
      </div>

      <button type="submit" class="w-full bg-[#cc0000] hover:bg-[#b30000] text-white font-bold py-3 rounded-xl text-sm flex items-center justify-center gap-2 transition">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk
      </button>
    </form>

    <div class="my-3 text-xs text-gray-400">Atau</div>

    <a href="{{ route('auth.google') }}" class="w-full bg-white border border-gray-400 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 text-gray-800 hover:bg-gray-50 transition shadow-sm">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-4 h-4">
      Masuk Dengan Google
    </a>

    <p class="text-xs text-gray-800 mt-4">
      Belum punya akun? <a href="{{ route('register') }}" class="text-[#d31818] font-semibold hover:underline">Daftar sekarang</a>
    </p>
  </div>

</body>
</html>
