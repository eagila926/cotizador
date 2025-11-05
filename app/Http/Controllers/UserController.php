<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Mostrar formulario
    public function create()
    {
        return view('usuarios.register');
    }

    // Guardar usuario en la BD
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo'   => 'required|email|unique:usuarios,correo',
            'rol'      => 'required|string|max:50',
            'password' => 'required|string|min:6|confirmed',
            'estado'   => 'required|boolean'
        ]);

        User::create([
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'correo'   => $request->correo,
            'rol'      => $request->rol,
            'password' => Hash::make($request->password),
            'estado'   => $request->estado,
        ]);

        return redirect()->route('usuarios.create')->with('success', 'Usuario registrado correctamente');
    }
}
