@php
  use Illuminate\Support\Facades\Auth;
  $user = Auth::user();
  $fullName = $user ? trim($user->nombre.' '.$user->apellido) : 'Usuario';
  $initials = strtoupper(mb_substr($user?->nombre ?? 'U',0,1));
@endphp

<nav class="navbar navbar-expand-lg navbar-dark navbar-gradient sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
      <img src="{{ asset('images/logo-dark.png') }}" alt="Escollanos" class="brand-logo">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('home') ? 'fw-semibold' : '' }}" href="{{ route('home') }}">
            <i class="bi bi-house-door"></i> Inicio
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle {{ request()->routeIs('formulas.*') ? 'fw-semibold' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-grid-3x3-gap"></i> Producción
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('fe.index') }}">Fórmulas Establecidas</a></li>
            <li><a class="dropdown-item" href="{{ route('formulas.nuevas') }}">Fórmulas Nuevas</a></li>
            <li><a class="dropdown-item" href="{{ route('formulas.recientes') }}">Fórmulas Recientes</a></li>
          </ul>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
        <li class="nav-item dropdown">
          <a class="nav-link d-flex align-items-center gap-2 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <span class="avatar-initial">{{ $initials }}</span>
            <span class="d-none d-sm-inline">{{ $fullName }}</span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">{{ $fullName }}</h6></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Mi perfil (próx.)</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Salir</button>
              </form>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
  /* Ajustes de navbar */
  .navbar-gradient{
    background: linear-gradient(135deg,#0d6efd,#198754);
  }
  .brand-logo{
    height: 36px;      /* controla la altura del navbar */
    width: auto;       /* evita deformaciones */
    display: block;
  }
  .navbar-brand{ padding-top:0; padding-bottom:0; }
  .avatar-initial{
    width: 32px; height: 32px; border-radius: 9999px;
    background:#0d6efd; color:#fff; display:grid; place-items:center; font-weight:600;
  }
</style>
