<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="h4 mb-3 text-center">Acceder</h1>

            @if ($errors->any())
              <div class="alert alert-danger small">
                @foreach ($errors->all() as $e)
                  <div>{{ $e }}</div>
                @endforeach
              </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control" value="{{ old('correo') }}" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Recordarme</label>
              </div>
              <button type="submit" class="btn btn-primary w-100">Ingresar</button>
            </form>
          </div>
        </div>
        <p class="text-center text-muted mt-3 mb-0 small">© Escollanos</p>
      </div>
    </div>
  </div>
</body>
</html>
