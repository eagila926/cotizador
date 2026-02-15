@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Editar Usuario</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('usuarios.update', $usuario->id_user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <!-- Columna izquierda -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control"
                           value="{{ old('nombre', $usuario->nombre) }}" required>
                </div>

                <div class="mb-3">
                    <label>Apellido</label>
                    <input type="text" name="apellido" class="form-control"
                           value="{{ old('apellido', $usuario->apellido) }}" required>
                </div>

                <div class="mb-3">
                    <label>Correo</label>
                    <input type="email" name="correo" class="form-control"
                           value="{{ old('correo', $usuario->correo) }}" required>
                </div>

                <div class="mb-3">
                    <label>Rol</label>
                    <select name="rol" class="form-control" required>
                        <option value="">Seleccione un rol</option>
                        @foreach(['ADMIN1','VISITADOR'] as $rol)
                            <option value="{{ $rol }}" {{ old('rol', $usuario->rol) === $rol ? 'selected' : '' }}>
                                {{ $rol }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Columna derecha -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label>Nueva contraseña (opcional)</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">👁️</button>
                    </div>
                    <small class="text-muted">Déjalo en blanco si no deseas cambiarla.</small>
                </div>

                <div class="mb-3">
                    <label>Confirmar nueva contraseña</label>
                    <div class="input-group">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password_confirmation')">👁️</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Estado</label>
                    <select name="estado" class="form-control" required>
                        <option value="1" {{ old('estado', $usuario->estado) == 1 ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('estado', $usuario->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-between">
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}
</script>
@endsection
