<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function buscar(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        // Selecciona SOLO columnas existentes. Alias "label" para el autocompletar.
        $rows = Medico::query()
            ->selectRaw('full_name AS label, cedula')
            ->where('full_name', 'like', "%{$q}%")
            ->orWhere('cedula', 'like', "%{$q}%")
            ->orderBy('full_name')
            ->limit(10)
            ->get();

        // $rows ya es un array de objetos {label, cedula}
        return response()->json($rows);
    }
}
