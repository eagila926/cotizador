@php
  use Illuminate\Support\Facades\Auth;

  $user = Auth::user();
  $fullName = $user ? trim($user->nombre.' '.$user->apellido) : 'Usuario';
  $initials = strtoupper(mb_substr($user?->nombre ?? 'U',0,1));

  // === Rol ===
  $isAdmin = $user && strtoupper((string)($user->rol ?? '')) === 'ADMIN';
@endphp

<div class="sidebar d-flex flex-column p-3 text-white shadow">
  <a href="{{ route('home') }}" class="d-flex align-items-center mb-3 mb-md-0 text-white text-decoration-none">
    <img style="border-radius:10px" src="{{ asset('images/BioProductosLogo.jpg') }}" alt="Logo" class="brand-logo me-2"><br>
  </a>

  <hr>
  <span class="fs-5 fw-bold">Cotizador</span>

  <ul class="nav nav-pills flex-column mb-auto">
    <li class="nav-item">
      <a href="{{ route('home') }}" class="nav-link text-white {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="bi bi-house-door me-2"></i>Inicio
      </a>
    </li>

    <li>
      <a href="#submenuProduccion" data-bs-toggle="collapse"
         class="nav-link text-white dropdown-toggle {{ request()->routeIs('formulas.*') ? 'active' : '' }}">
        <i class="bi bi-grid-3x3-gap me-2"></i>Laboratorio
      </a>
      <ul class="collapse nav flex-column ms-3" id="submenuProduccion">
        <li><a href="{{ route('fe.index') }}" class="nav-link text-white-50">Buscar Formulas</a></li>
        <li><a href="{{ route('formulas.nuevas') }}" class="nav-link text-white-50">Fórmulas Nuevas</a></li>
        <li><a href="{{ route('formulas.recientes') }}" class="nav-link text-white-50">Fórmulas Del Usuario</a></li>
      </ul>
    </li>

    {{-- === USUARIOS: SOLO ADMIN === --}}
    @if($isAdmin)
    <li>
      <a href="#submenuUsuarios"
         data-bs-toggle="collapse"
         class="nav-link text-white dropdown-toggle {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
        <i class="bi bi-people me-2"></i>Usuarios
      </a>

      <ul class="collapse nav flex-column ms-3" id="submenuUsuarios">
        <li>
          <a href="{{ route('usuarios.create') }}"
             class="nav-link text-white-50 {{ request()->routeIs('usuarios.create') ? 'active' : '' }}">
            <i class="bi bi-person-plus me-2"></i>Crear usuario
          </a>
        </li>
      </ul>
    </li>
    @endif

  </ul>

  <hr>

  <div class="dropdown">
    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
      <span class="avatar-initial me-2">{{ $initials }}</span>
      <strong>{{ $fullName }}</strong>
    </a>
    <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
      <li><h6 class="dropdown-header">{{ $fullName }}</h6></li>
      <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Mi perfil (próx.)</a></li>
      <li><hr class="dropdown-divider"></li>
      <li>
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Salir</button>
        </form>
      </li>
    </ul>
  </div>
</div>

<style>
  /* Sidebar vertical */
  .sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 240px;
    height: 100vh;
    background: #157d33;
    background: linear-gradient(90deg,rgba(21, 125, 51, 1) 0%, rgba(9, 121, 24, 1) 35%, rgba(0, 68, 82, 1) 100%);
  }
  .sidebar .brand-logo {
    height: 40px;
    width: auto;
  }
  .sidebar .nav-link {
    font-weight: 500;
    padding: .6rem 1rem;
  }
  .sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
    border-radius: .5rem;
  }
  .avatar-initial {
    width: 32px; height: 32px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #000000 0%, #4b4b00 40%, #ffd700 100%);
    font-weight: 600;
    color: #fff;
    width: 36px; height: 36px; border-radius: 50%;
    background: #0d6efd;
    display: grid;
    place-items: center;
    font-weight: bold;
    color: #fff;
  }
  body {
    margin-left: 240px; /* deja espacio para la barra lateral */
  }
</style>
