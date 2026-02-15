<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Listar usuarios
    public function index()
    {
        $this->authorizeAdmin();

        $usuarios = User::orderBy('nombre')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    // Mostrar formulario de registro
    public function create()
    {
        $this->authorizeAdmin();

        return view('usuarios.register');
    }

    // Guardar usuario en la BD
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo|unique:usuarios,email',
            'rol'      => 'required|string|max:50',
            'password' => 'required|string|min:6|confirmed',
            'estado'   => 'required|boolean'
        ]);

        User::create([
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'correo'   => $request->correo,
            'email'    => $request->correo, // <--- SE AGREGA
            'rol'      => $request->rol,
            'password' => Hash::make($request->password),
            'estado'   => $request->estado
        ]);


        return redirect()->route('usuarios.create')
            ->with('success', 'Usuario registrado correctamente');
    }

    // Formulario de edición
    public function edit(User $usuario)
    {
        $this->authorizeAdmin();

        return view('usuarios.edit', compact('usuario'));
    }

    // Actualizar usuario
    public function update(Request $request, User $usuario)
    {
        $this->authorizeAdmin();

        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo,' . $usuario->id_user . ',id_user'.'|unique:usuarios,email,' . $usuario->id_user . ',id_user',
            'rol'      => 'required|string|max:50',
            'password' => 'nullable|string|min:6|confirmed',
            'estado'   => 'required|boolean'
        ]);

        $usuario->nombre   = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->correo   = $request->correo;
        $usuario->email    = $request->correo;
        $usuario->rol      = $request->rol;
        $usuario->estado   = $request->estado;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    // Pequeño helper para asegurar Admin
    protected function authorizeAdmin()
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole(['ADMIN'])) {
            abort(403, 'No tiene permisos para gestionar usuarios.');
        }
    }
}
