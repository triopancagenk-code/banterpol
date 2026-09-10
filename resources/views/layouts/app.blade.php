<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'WiFi Banterpool')</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    /* Latar Belakang Garis Merah Dinamis dengan Animasi Scroll */
    body {
      background-color: #ffffff;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .bg-wave-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      pointer-events: none;
      z-index: 0;
    }

    .bg-wave-pattern {
      position: absolute;
      top: 0;
      left: -15%;
      width: 130%;
      height: 260%;
      background-image: url('{{ asset('image/bg-red-wave.jpg') }}');
      background-size: cover;
      background-position: center top;
      background-repeat: repeat-y;
      opacity: 0.32;
      pointer-events: none;
      will-change: transform;
      transform-origin: center center;
      transition: opacity 0.3s ease;
    }

    /* Ambient soft red glow to accentuate wave curvature */
    .bg-wave-glow {
      position: absolute;
      inset: -10%;
      background: radial-gradient(circle at 65% 35%, rgba(220, 38, 38, 0.06) 0%, rgba(239, 68, 68, 0.02) 40%, transparent 70%);
      pointer-events: none;
      will-change: transform;
    }

    /* Active dynamic pulsing glow when user is scrolling */
    body.is-scrolling-down .bg-wave-pattern {
      opacity: 0.40;
    }
  </style>

  @stack('styles')
</head>
<body class="font-sans antialiased text-gray-800 min-h-screen flex flex-col relative selection:bg-brand selection:text-white">

  <!-- Background Gelombang Merah dengan Animasi Scroll Interaktif -->
  <div class="bg-wave-container" aria-hidden="true">
    <div id="bgWavePattern" class="bg-wave-pattern"></div>
    <div id="bgWaveGlow" class="bg-wave-glow"></div>
  </div>

  @include('layouts.navigation')

  <main class="flex-1 relative">
    @yield('content')
  </main>

  @hasSection('footer')
    @yield('footer')
  @else
    @include('layouts.footer')
  @endif

  <!-- Script Animasi Background Garis Merah saat Scroll (Bergerak Naik / Scroll ke Atas) -->
  <script>
    (function () {
      const wave = document.getElementById('bgWavePattern');
      const glow = document.getElementById('bgWaveGlow');
      if (!wave) return;

      let lastScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
      let currentScrollY = lastScrollY;
      let targetScrollY = lastScrollY;
      let isAnimating = false;
      let scrollTimer = null;
      let wavePhase = 0;

      function renderWave() {
        // Interpolasi halus (lerp) untuk pergerakan mulus & ber-inersia
        currentScrollY += (targetScrollY - currentScrollY) * 0.085;
        wavePhase += 0.015;

        // 1. Pergeseran Parallax Vertikal: bergerak naik / scroll ke atas
        const translateY = -(currentScrollY * 0.32);

        // 2. Animasi Gelombang Berkelok Horisontal (Sinusoidal wave drift)
        const waveSin = Math.sin(currentScrollY * 0.005 + wavePhase) * 45;

        // 3. Efek rotasi organik & pembesaran (scale) dinamis
        const rotateDeg = Math.sin(currentScrollY * 0.003) * 2.2;
        const scale = 1 + Math.min(currentScrollY * 0.00008, 0.09);

        // Terapkan transformasi GPU-accelerated pada latar bergaris merah
        wave.style.transform = `translate3d(${waveSin.toFixed(2)}px, ${translateY.toFixed(2)}px, 0) scale(${scale.toFixed(4)}) rotate(${rotateDeg.toFixed(2)}deg)`;

        if (glow) {
          const glowY = -(currentScrollY * 0.18);
          const glowX = Math.cos(currentScrollY * 0.004) * 25;
          glow.style.transform = `translate3d(${glowX.toFixed(2)}px, ${glowY.toFixed(2)}px, 0)`;
        }

        // Lanjutkan animasi sampai posisi benar-benar mendekati target atau saat aktif scroll
        if (Math.abs(targetScrollY - currentScrollY) > 0.1 || document.body.classList.contains('is-scrolling-down')) {
          requestAnimationFrame(renderWave);
          isAnimating = true;
        } else {
          isAnimating = false;
        }
      }

      function onScroll() {
        const newScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
        targetScrollY = newScrollY;

        // Deteksi arah scroll ke bawah
        if (newScrollY > lastScrollY && newScrollY > 10) {
          document.body.classList.add('is-scrolling-down');
        } else {
          document.body.classList.remove('is-scrolling-down');
        }
        lastScrollY = newScrollY;

        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function () {
          document.body.classList.remove('is-scrolling-down');
        }, 300);

        if (!isAnimating) {
          isAnimating = true;
          requestAnimationFrame(renderWave);
        }
      }

      window.addEventListener('scroll', onScroll, { passive: true });
      // Inisialisasi posisi awal saat halaman dibuka
      requestAnimationFrame(renderWave);
    })();
  </script>

  @stack('scripts')
</body>
</html>
