<?php

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Models\ActivoTemp;
use App\Models\Formula;
use App\Models\FormulaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormulaController extends Controller
{
    // ===================== Constantes de negocio =====================
    // Cápsula única de 550 mg
    private const CAPS_MG_POR_UND = 550;

    // Códigos de inventario (ajústalos a tu catálogo real)
    private const COD_CAPSULA_550 = 3739; // CÁPSULA 550 mg
    private const COD_PASTILLERO  = 3743; // PASTILLERO

    // NUEVOS INSUMOS
    private const COD_TAPA_SEG    = 3744; // Tapa de seguridad
    private const COD_LINNER      = 3742; // Linner espumado
    private const COD_ETIQUETA    = 3740; // Etiqueta

    // Regla de capacidad de pastillero (unidades)
    private const PAST_CAP_SMALL = 60;
    private const PAST_CAP_LARGE = 150;

    // ===================== Vistas =====================
    public function index(Request $request)
    {
        return view('formulas.nuevas');
    }

    // ===================== Autocompletar productos =====================
    public function buscarProducto(Request $request)
    {
        $term = trim($request->input('producto', ''));
        if ($term === '') return '';

        $items = Activo::query()
            ->where('nombre', 'like', "%{$term}%")
            ->orWhere('cod_odoo', 'like', "%{$term}%")
            ->orderBy('nombre')
            ->limit(15)
            ->get(['cod_odoo', 'nombre']);

        $html = '';
        foreach ($items as $it) {
            $html .= '<div class="suggest-element" '
                .'data-cod_odoo="'.e($it->cod_odoo).'" '
                .'style="padding:6px; cursor:pointer; border-bottom:1px solid #eee">'
                .e($it->nombre)
                .'</div>';
        }
        return $html;
    }

    // ===================== Temporales =====================
    public function agregarTemp(Request $request)
    {
        $request->validate([
            'activo'   => 'required|string|max:255',
            'cod_odoo' => 'required|integer',
            'cantidad' => 'required|numeric|min:0.0001',
            // Permitimos g, mg, mcg, UI
            'unidad'   => 'required|in:g,mg,mcg,UI',
        ]);

        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) return response('NO_USER', 401);

        $exists = ActivoTemp::where('user_id',$userId)
            ->where('cod_odoo',$request->cod_odoo)
            ->exists();
        if ($exists) return response('duplicado', 200);

        ActivoTemp::create([
            'user_id'  => $userId,
            'cod_odoo' => (int)$request->cod_odoo,
            'activo'   => (string)$request->activo,
            'cantidad' => (float)$request->cantidad,
            'unidad'   => (string)$request->unidad,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function listarTemp(Request $request)
    {
        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) return '';

        $rows = ActivoTemp::where('user_id', $userId)
            ->orderBy('id')
            ->get();

        if ($request->boolean('json')) {
            return response()->json($rows);
        }

        $i = 1; $html = '';
        foreach ($rows as $r) {
            $html .= '<tr>';
            $html .= '<td class="d-none d-md-table-cell">'.($i++).'</td>';
            $html .= '<td class="d-none d-md-table-cell">'.e($r->cod_odoo).'</td>';
            $html .= '<td>'.e($r->activo).'</td>';
            $html .= '<td>'.e($r->cantidad).'</td>';
            $html .= '<td>'.e($r->unidad).'</td>';
            $html .= '<td><button class="btn btn-sm btn-danger" onclick="eliminarFila('.$r->id.')">X</button></td>';
            $html .= '</tr>';
        }
        return $html;
    }

    public function eliminarTemp(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) return response('NO_USER', 401);

        $row = ActivoTemp::where('user_id',$userId)->where('id',$request->id)->first();
        if (!$row) return response('NOT_FOUND', 404);

        $row->delete();
        return response('ok', 200);
    }

    public function eliminarTodos(Request $request)
    {
        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) return response('NO_USER', 401);

        ActivoTemp::where('user_id', $userId)->delete();
        return response('OK', 200);
    }

    // ===================== Resumen de CÁPSULAS (único modo) =====================
    public function resumenCapsulas(Request $request)
    {
        return $this->renderResumenCapsulas($request);
    }

    private function slugNoAcentos(string $texto): string
    {
        return (string) Str::of($texto)->squish()->lower()->ascii();
    }

    private function buildCodFormula(): string
    {
        $user = Auth::user();

        $nombre   = trim((string)($user->nombre ?? ''));
        $apellido = trim((string)($user->apellido ?? ''));

        $iniNombre   = mb_substr($this->slugNoAcentos($nombre), 0, 1);
        $iniApellido = mb_substr($this->slugNoAcentos($apellido), 0, 2);
        $iniciales   = mb_strtoupper($iniNombre.$iniApellido, 'UTF-8'); // ej. EAG

        $mes    = now()->format('n'); // 1..12
        $random = random_int(100000, 999999); // 6 dígitos

        return "FORMU.{$random}";
    }

    private function toMgDia(float $cantidad, string $unidad, int $cod_odoo): float
    {
        // Conversión a mg/día (sin densidades ni factores)
        return match ($unidad) {
            'g'   => $cantidad * 1000.0,
            'mg'  => $cantidad,
            'mcg' => $cantidad / 1000.0,
            'UI'  => ($cod_odoo === 1343)
                        ? ($cantidad * 0.000025 / 1000.0) // ej. colecalciferol
                        : ($cantidad * 0.00067),          // aproximación genérica previa
            default => 0.0,
        };
    }

    private function renderResumenCapsulas(Request $request)
    {
        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) abort(401);

        $items = ActivoTemp::where('user_id', $userId)->orderBy('id')->get();

        // 1) Total mg/día de activos + filas de activos (añadir valor_costo)
        $totalMgDia = 0.0;

        // Armar catálogo para obtener valor_costo de activos + cápsula + pastillero + nuevos insumos
        $cods = $items->pluck('cod_odoo')->map(fn($c)=>(int)$c)->all();
        $cods = array_merge(
            $cods,
            [
                self::COD_CAPSULA_550,
                self::COD_PASTILLERO,
                self::COD_TAPA_SEG,
                self::COD_LINNER,
                self::COD_ETIQUETA,
            ]
        );
        $cods = array_values(array_unique($cods));

        $catalogo = Activo::whereIn('cod_odoo', $cods)
            ->get(['cod_odoo','nombre','valor_costo'])
            ->keyBy('cod_odoo');

        $rows = $items->map(function ($r) use (&$totalMgDia, $catalogo) {
            $mgDia = $this->toMgDia((float)$r->cantidad, (string)$r->unidad, (int)$r->cod_odoo);
            $totalMgDia += $mgDia;

            $valor_costo = (float)($catalogo[$r->cod_odoo]->valor_costo ?? 0);

            return [
                'cod_odoo'    => (int)$r->cod_odoo,
                'activo'      => (string)$r->activo,
                'cantidad'    => (float)$r->cantidad,   // por día (referencial)
                'unidad'      => (string)$r->unidad,
                'mg_dia'      => $mgDia,
                'valor_costo' => $valor_costo,
            ];
        });

        // 2) Cálculo de cápsulas (por día y por mes)
        $capsDia   = (int) ceil($totalMgDia / self::CAPS_MG_POR_UND);
        $capsMes   = $capsDia * 30;

        // 3) Pastillero por unidades mensuales de cápsulas
        $needed = $capsMes;
        if ($needed <= self::PAST_CAP_SMALL) {
            $pastCount = 1;
        } elseif ($needed <= self::PAST_CAP_LARGE) {
            $pastCount = 1;
        } else {
            $pastCount = (int) ceil($needed / self::PAST_CAP_LARGE);
        }

        $rows = collect($rows);

        // Cápsulas (unidades/mes)
        $rows->push([
            'cod_odoo'    => self::COD_CAPSULA_550,
            'activo'      => $catalogo[self::COD_CAPSULA_550]->nombre ?? 'CÁPSULA 550 mg',
            'cantidad'    => $capsMes,
            'unidad'      => 'und',
            'mg_dia'      => null,
            'valor_costo' => (float)($catalogo[self::COD_CAPSULA_550]->valor_costo ?? 0),
        ]);

        // Pastillero (unidades)
        $rows->push([
            'cod_odoo'    => self::COD_PASTILLERO,
            'activo'      => $catalogo[self::COD_PASTILLERO]->nombre ?? 'PASTILLERO',
            'cantidad'    => $pastCount,
            'unidad'      => 'und',
            'mg_dia'      => null,
            'valor_costo' => (float)($catalogo[self::COD_PASTILLERO]->valor_costo ?? 0),
        ]);

        // INSUMOS POR CADA PASTILLERO (cantidad = $pastCount)
        $rows->push([
            'cod_odoo'    => self::COD_TAPA_SEG,
            'activo'      => $catalogo[self::COD_TAPA_SEG]->nombre ?? 'TAPA DE SEGURIDAD',
            'cantidad'    => $pastCount,
            'unidad'      => 'und',
            'mg_dia'      => null,
            'valor_costo' => (float)($catalogo[self::COD_TAPA_SEG]->valor_costo ?? 0),
        ]);

        $rows->push([
            'cod_odoo'    => self::COD_LINNER,
            'activo'      => $catalogo[self::COD_LINNER]->nombre ?? 'LINNER ESPUMADO',
            'cantidad'    => $pastCount,
            'unidad'      => 'und',
            'mg_dia'      => null,
            'valor_costo' => (float)($catalogo[self::COD_LINNER]->valor_costo ?? 0),
        ]);

        $rows->push([
            'cod_odoo'    => self::COD_ETIQUETA,
            'activo'      => $catalogo[self::COD_ETIQUETA]->nombre ?? 'ETIQUETA',
            'cantidad'    => $pastCount,
            'unidad'      => 'und',
            'mg_dia'      => null,
            'valor_costo' => (float)($catalogo[self::COD_ETIQUETA]->valor_costo ?? 0),
        ]);


        // 4) Render a la vista
        return view('formulas.resumen_capsulas', [
            'rows'       => $rows,
            'totalMgDia' => $totalMgDia,
            'capsDia'    => $capsDia,
            'capsMes'    => $capsMes,
            'codFormula' => $this->buildCodFormula(),
        ]);
    }

    // ===================== Guardar Fórmula (solo cápsulas) =====================
    public function guardar(Request $request)
    {
        // Campo médico abierto (sin regex)
        $request->validate([
            'cod_formula'           => ['required','string','max:30'],
            'nombre_etiqueta'       => ['nullable','string','max:150'],
            'medico'                => ['nullable','string','max:150'],
            'paciente'              => ['nullable','string','max:150'],
            'precio_medico'         => ['nullable','numeric'],
            'precio_publico'        => ['nullable','numeric'],
            'precio_distribuidor'   => ['nullable','numeric'],
            'tomas_diarias'         => ['nullable','numeric'],
        ]);

        $userId = Auth::id();
        if (!$userId) abort(401);

        // Siempre re-generamos el código del backend
        $codigoBackend = $this->buildCodFormula();

        DB::transaction(function () use ($request, $userId, $codigoBackend) {

            // 1) Cabecera (los precios vienen de la vista)
            $precio_medico       = max(0, (float)$request->input('precio_medico', 0));
            $precio_publico      = (float)$request->input('precio_publico', 0);
            $precio_distribuidor = (float)$request->input('precio_distribuidor', 0);

            Formula::create([
                'codigo'              => $codigoBackend,
                'nombre_etiqueta'     => $request->input('nombre_etiqueta'),
                'user_id'             => $userId,
                'precio_medico'       => round($precio_medico, 2),
                'precio_publico'      => round($precio_publico, 2),
                'precio_distribuidor' => round($precio_distribuidor, 2),
                'medico'              => $request->input('medico'),
                'paciente'            => $request->input('paciente'),
                'tomas_diarias'       => (float)$request->input('tomas_diarias', 0),
            ]);

            // 2) Calcular filas (activos + cápsulas + pastillero + nuevos insumos)
            $rows = $this->calcularFilasParaGuardarCapsulas($userId);

            // 3) Insert masivo
            $now = now();
            $insert = $rows->map(function ($r) use ($codigoBackend, $now) {
                return [
                    'codigo'     => $codigoBackend,
                    'cod_odoo'   => (int)($r['cod_odoo'] ?? 0),
                    'activo'     => (string)($r['activo'] ?? ''),
                    'unidad'     => $r['unidad'] ?? null,
                    'masa_mes'   => isset($r['masa_mes']) ? (float)$r['masa_mes'] : null, // g/mes si aplica
                    'cantidad'   => isset($r['cantidad']) ? (float)$r['cantidad'] : null, // und/mes o por día referencial
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            if (!empty($insert)) {
                FormulaItem::insert($insert);
            }

            // 4) Limpiar temporales
            ActivoTemp::where('user_id', $userId)->delete();
        });

        return redirect()->route('formulas.nuevas')->with('ok', 'Fórmula guardada correctamente.');
    }

    private function calcularFilasParaGuardarCapsulas(int $userId): \Illuminate\Support\Collection
    {
        $items = ActivoTemp::where('user_id', $userId)->orderBy('id')->get();

        $rows = collect();
        $totalMgDia = 0.0;

        // 1) Activos base → mg/día, mg/mes, g/mes
        foreach ($items as $r) {
            $mgDia = $this->toMgDia((float)$r->cantidad, (string)$r->unidad, (int)$r->cod_odoo);
            $totalMgDia += $mgDia;

            $mgMes = $mgDia * 30.0;
            $gMes  = $mgMes / 1000.0;

            $rows->push([
                'cod_odoo'  => (int)$r->cod_odoo,
                'activo'    => (string)$r->activo,
                'unidad'    => (string)$r->unidad,     // unidad “de receta” (por día)
                'cantidad'  => (float)$r->cantidad,    // por día (referencial)
                'masa_mes'  => (float)$gMes,           // g/mes (para pesaje real)
            ]);
        }

        // 2) Cálculo cápsulas (unidades/mes)
        $capsDia   = (int) ceil($totalMgDia / self::CAPS_MG_POR_UND);
        $capsMes   = $capsDia * 30;

        $rows->push([
            'cod_odoo'  => self::COD_CAPSULA_550,
            'activo'    => 'CÁPSULA 550 mg',
            'unidad'    => 'und',
            'cantidad'  => (float)$capsMes, // und/mes
            'masa_mes'  => null,
        ]);

        // Pastillero según necesidad
        $needed = (int) $capsMes;
        if ($needed <= self::PAST_CAP_SMALL) {
            $pastCount = 1;
        } elseif ($needed <= self::PAST_CAP_LARGE) {
            $pastCount = 1;
        } else {
            $pastCount = (int) ceil($needed / self::PAST_CAP_LARGE);
        }

        $rows->push([
            'cod_odoo'  => self::COD_PASTILLERO,
            'activo'    => 'PASTILLERO',
            'unidad'    => 'und',
            'cantidad'  => (float)$pastCount,
            'masa_mes'  => null,
        ]);

        // INSUMOS POR CADA PASTILLERO (cantidad = $pastCount)
        $rows->push([
            'cod_odoo'  => self::COD_TAPA_SEG,
            'activo'    => 'TAPA DE SEGURIDAD',
            'unidad'    => 'und',
            'cantidad'  => (float)$pastCount,
            'masa_mes'  => null,
        ]);

        $rows->push([
            'cod_odoo'  => self::COD_LINNER,
            'activo'    => 'LINNER ESPUMADO',
            'unidad'    => 'und',
            'cantidad'  => (float)$pastCount,
            'masa_mes'  => null,
        ]);

        $rows->push([
            'cod_odoo'  => self::COD_ETIQUETA,
            'activo'    => 'ETIQUETA',
            'unidad'    => 'und',
            'cantidad'  => (float)$pastCount,
            'masa_mes'  => null,
        ]);


        return $rows;
    }

    // ===================== Recientes / Cargar para edición =====================
    public function recientes()
    {
        $formulas = \App\Models\Formula::where('user_id', auth()->id())
            ->orderByDesc('id')
            ->simplePaginate(15);

        return view('formulas.recientes', compact('formulas'));
    }

    public function cargarParaEditar(int $id)
    {
        $userId  = Auth::id();

        // Quitamos filtro por user_id, como antes
        $formula = Formula::findOrFail($id);

        // Excluir cápsulas, pastillero e insumos fijos de la carga a temporales
        $codsExcluir  = [
            self::COD_CAPSULA_550,
            self::COD_PASTILLERO,
            self::COD_TAPA_SEG,
            self::COD_LINNER,
            self::COD_ETIQUETA,
        ];
        $regexExcluir = '/(capsula|cápsula|capsulas|cápsulas|pastillero|pastilleros|tapa|linner|etiqueta)/i';

        DB::transaction(function () use ($userId, $formula, $codsExcluir, $regexExcluir) {
            ActivoTemp::where('user_id', $userId)->delete();

            $items = FormulaItem::query()
                ->where('codigo', $formula->codigo)
                ->whereNotIn('cod_odoo', $codsExcluir)
                ->get(['cod_odoo','activo','unidad','cantidad','masa_mes']);

            foreach ($items as $it) {
                $cod    = (int)($it->cod_odoo ?? 0);
                $nombre = (string)($it->activo   ?? '');

                if (preg_match($regexExcluir, $nombre)) continue;

                // Reconstruimos una "cantidad diaria" aproximada
                $unidad   = $it->unidad ?: 'mg';
                $cantidad = null;

                if (!is_null($it->cantidad) && $unidad !== 'und') {
                    $cantidad = (float)$it->cantidad;
                } elseif (!is_null($it->masa_mes)) {
                    // masa_mes en g ⇒ mg/mes ⇒ mg/día
                    $cantidad = ((float)$it->masa_mes * 1000.0) / 30.0;
                    $unidad   = 'mg';
                } else {
                    continue;
                }

                ActivoTemp::create([
                    'user_id'  => $userId,
                    'cod_odoo' => $cod,
                    'activo'   => $nombre,
                    'cantidad' => $cantidad,
                    'unidad'   => $unidad,
                ]);
            }
        });

        return redirect()
            ->route('formulas.nuevas')
            ->with('ok', 'Fórmula cargada para edición. Ajusta los activos y guarda.');
    }

    // ===================== SECCIONES ELIMINADAS =====================
    // - Estearato y sobres: eliminados
    // - buscarMedico(): eliminado (médico es libre)
}
