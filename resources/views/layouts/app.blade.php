<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Ortomed')</title>

  {{-- Bootstrap + Icons (CDN, sin Vite) --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">

  


  <style>
  .navbar-gradient {
    background: #000000;
    background: linear-gradient(135deg, #000000 0%, #4b4b00 40%, #ffd700 100%);
  }

  .navbar-gradient .nav-link {
    color: #fff !important;
    transition: color .2s ease;
  }
  .navbar-gradient .nav-link:hover {
    color: #ffd700 !important;
  }

  .navbar-gradient .navbar-brand,
  .navbar-gradient .dropdown-item {
    color: #fff !important;
  }

  .navbar-gradient .dropdown-menu {
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, .3);
  }

  .avatar-initial {
    width: 32px; height: 32px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #000000 0%, #4b4b00 40%, #ffd700 100%);
    font-weight: 600;
    color: #fff;
  }
</style>


  @stack('head')
</head>
<body class="bg-light">

  @include('partials.navbar')

  <main class="container py-4">
    @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // --- Dark mode toggle (Bootstrap 5.3 data-bs-theme) ---
    (function(){
      const html = document.documentElement;
      const saved = localStorage.getItem('theme');
      if(saved){ html.setAttribute('data-bs-theme', saved); }

      document.addEventListener('click', e=>{
        const t = e.target.closest('[data-toggle-theme]');
        if(!t) return;
        const curr = html.getAttribute('data-bs-theme') || 'light';
        const next = curr === 'light' ? 'dark' : 'light';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
      });

      // Fullscreen
      document.addEventListener('click', e=>{
        const f = e.target.closest('[data-fullscreen]');
        if(!f) return;
        if (!document.fullscreenElement) {
          document.documentElement.requestFullscreen?.();
        } else {
          document.exitFullscreen?.();
        }
      });
    })();
  </script>

  @stack('scripts')
</body>
</html>
