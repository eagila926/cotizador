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
    // Cápsula única de 475 mg
    private const CAPS_MG_POR_UND = 475;

    // Códigos de inventario (ajústalos a tu catálogo real)
    private const COD_CAPSULA_475 = 3994; // CÁPSULA 475 mg
    private const COD_PASTILLERO  = 3396; // PASTILLERO

    // Insumos
    private const COD_TAPA_SEG    = 3397; // Tapa de seguridad
    private const COD_LINNER      = 3395; // Linner espumado
    private const COD_ETIQUETA    = 3393; // Etiqueta

    // Relleno
    private const COD_CELULOSA    = 3291; // CELULOSA

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

    // ===================== Resumen de CÁPSULAS =====================
    public function resumenCapsulas(Request $request)
    {
        return $this->renderResumenCapsulas($request);
    }

    // ===================== Utilitarios =====================
    private function slugNoAcentos(string $texto): string
    {
        return (string) Str::of($texto)->squish()->lower()->ascii();
    }

    private function buildCodFormula(): string
    {
        // Mantengo tu formato actual (solo random)
        $random = random_int(100000, 999999);
        return "FORMU.{$random}";
    }

    private function toMgDia(float $cantidad, string $unidad, int $cod_odoo): float
    {
        return match ($unidad) {
            'g'   => $cantidad * 1000.0,
            'mg'  => $cantidad,
            'mcg' => $cantidad / 1000.0,
            'UI'  => ($cod_odoo === 1343)
                        ? ($cantidad * 0.000025 / 1000.0)
                        : ($cantidad * 0.00067),
            default => 0.0,
        };
    }

    /**
     * Opción 1: tabla de rangos como tu imagen.
     * Retorna: tomas, caps_dia, caps_mes.
     */
    private function capsulasPorRango(float $totalMgDia): array
    {
        if ($totalMgDia <= 0) {
            return ['tomas' => 0, 'caps_dia' => 0, 'caps_mes' => 0];
        }

        $rangos = [
            ['desde' => 0,    'hasta' => 475,  'tomas' => 1, 'caps_mes' => 30],
            ['desde' => 476,  'hasta' => 950,  'tomas' => 2, 'caps_mes' => 60],
            ['desde' => 951,  'hasta' => 1425, 'tomas' => 3, 'caps_mes' => 90],
            ['desde' => 1426, 'hasta' => 1900, 'tomas' => 4, 'caps_mes' => 120],
            ['desde' => 1901, 'hasta' => 2850, 'tomas' => 6, 'caps_mes' => 180],
            ['desde' => 2851, 'hasta' => 4275, 'tomas' => 9, 'caps_mes' => 270],
        ];

        foreach ($rangos as $r) {
            if ($totalMgDia >= $r['desde'] && $totalMgDia <= $r['hasta']) {
                return [
                    'tomas'    => (int)$r['tomas'],
                    'caps_dia' => (int)$r['tomas'],
                    'caps_mes' => (int)$r['caps_mes'],
                ];
            }
        }

        // Fallback si supera el último rango
        $capsDia = (int) ceil($totalMgDia / self::CAPS_MG_POR_UND);

        return [
            'tomas'    => $capsDia,
            'caps_dia' => $capsDia,
            'caps_mes' => $capsDia * 30,
        ];
    }

    private function pastillerosNecesarios(int $capsMes): int
    {
        $needed = max(0, $capsMes);

        if ($needed <= self::PAST_CAP_SMALL) return 1;
        if ($needed <= self::PAST_CAP_LARGE) return 1;

        return (int) ceil($needed / self::PAST_CAP_LARGE);
    }

    private function buildCatalog(array $cods): \Illuminate\Support\Collection
    {
        return Activo::whereIn('cod_odoo', $cods)
            ->get(['cod_odoo','nombre','valor_costo'])
            ->keyBy('cod_odoo');
    }

    /**
     * Builder único. Construye todas las filas finales y los totales.
     * mode:
     * - 'view': incluye valor_costo y mg_dia (para tus tablas)
     * - 'save': incluye masa_mes (g/mes) para los items guardados
     */
    private function buildCapsuleSummary(int $userId, string $mode = 'view'): array
    {
        $withCost = ($mode === 'view');

        $items = ActivoTemp::where('user_id', $userId)->orderBy('id')->get();

        $cods = $items->pluck('cod_odoo')->map(fn($c)=>(int)$c)->all();
        $cods = array_merge($cods, [
            self::COD_CELULOSA,
            self::COD_CAPSULA_475,
            self::COD_PASTILLERO,
            self::COD_TAPA_SEG,
            self::COD_LINNER,
            self::COD_ETIQUETA,
        ]);
        $cods = array_values(array_unique($cods));

        $catalogo = $this->buildCatalog($cods);

        $rows = collect();
        $totalMgDia = 0.0;

        // Activos base
        foreach ($items as $r) {
            $mgDia = $this->toMgDia((float)$r->cantidad, (string)$r->unidad, (int)$r->cod_odoo);
            $totalMgDia += $mgDia;

            $row = [
                'cod_odoo' => (int)$r->cod_odoo,
                'activo'   => (string)$r->activo,
                'cantidad' => (float)$r->cantidad,  // por día (referencial)
                'unidad'   => (string)$r->unidad,
                'mg_dia'   => $mgDia,
            ];

            if ($withCost) {
                $row['valor_costo'] = (float)($catalogo[(int)$r->cod_odoo]->valor_costo ?? 0);
            }

            if ($mode === 'save') {
                // masa_mes en g (mg_dia * 30 / 1000)
                $row['masa_mes'] = (($mgDia * 30.0) / 1000.0);
            }

            $rows->push($row);
        }

        // Regla cápsulas por rango
        $regla = $this->capsulasPorRango($totalMgDia);
        $capsDia = (int)$regla['caps_dia'];
        $capsMes = (int)$regla['caps_mes'];
        $tomasDiarias = (int)$regla['tomas'];

        // Total masa mes (g/mes) de activos base (sin cápsulas/pastillero/insumos)
        // Nota: puedes decidir si sumar celulosa también; por defecto lo dejo como "base".
        $totalMasaMesBase = ($totalMgDia * 30.0) / 1000.0;

        // Relleno con celulosa hasta el límite del rango (mg/día)
        $limiteMgDia  = $capsDia * self::CAPS_MG_POR_UND;
        $rellenoMgDia = max(0.0, $limiteMgDia - $totalMgDia);

        if ($rellenoMgDia > 0) {
            $rowCel = [
                'cod_odoo' => self::COD_CELULOSA,
                'activo'   => $catalogo[self::COD_CELULOSA]->nombre ?? 'CELULOSA',
                'cantidad' => $rellenoMgDia, // mg/día
                'unidad'   => 'mg',
                'mg_dia'   => $rellenoMgDia,
            ];

            if ($withCost) {
                $rowCel['valor_costo'] = (float)($catalogo[self::COD_CELULOSA]->valor_costo ?? 0);
            }

            if ($mode === 'save') {
                $rowCel['masa_mes'] = (($rellenoMgDia * 30.0) / 1000.0);
            }

            $rows->push($rowCel);
        }

        // Pastillero según cápsulas/mes
        $pastCount = $this->pastillerosNecesarios($capsMes);

        // Cápsulas (und/mes)
        $rowCaps = [
            'cod_odoo' => self::COD_CAPSULA_475,
            'activo'   => $catalogo[self::COD_CAPSULA_475]->nombre ?? 'CÁPSULA 475 mg',
            'cantidad' => (float)$capsMes,
            'unidad'   => 'und',
            'mg_dia'   => null,
        ];
        if ($withCost) $rowCaps['valor_costo'] = (float)($catalogo[self::COD_CAPSULA_475]->valor_costo ?? 0);
        if ($mode === 'save') $rowCaps['masa_mes'] = null;
        $rows->push($rowCaps);

        // Pastillero (und)
        $rowPast = [
            'cod_odoo' => self::COD_PASTILLERO,
            'activo'   => $catalogo[self::COD_PASTILLERO]->nombre ?? 'PASTILLERO',
            'cantidad' => (float)$pastCount,
            'unidad'   => 'und',
            'mg_dia'   => null,
        ];
        if ($withCost) $rowPast['valor_costo'] = (float)($catalogo[self::COD_PASTILLERO]->valor_costo ?? 0);
        if ($mode === 'save') $rowPast['masa_mes'] = null;
        $rows->push($rowPast);

        // Insumos por pastillero
        $insumos = [
            [self::COD_TAPA_SEG, 'TAPA DE SEGURIDAD'],
            [self::COD_LINNER,   'LINNER ESPUMADO'],
            [self::COD_ETIQUETA, 'ETIQUETA'],
        ];

        foreach ($insumos as [$cod, $fallbackNombre]) {
            $rowIns = [
                'cod_odoo' => (int)$cod,
                'activo'   => $catalogo[(int)$cod]->nombre ?? $fallbackNombre,
                'cantidad' => (float)$pastCount,
                'unidad'   => 'und',
                'mg_dia'   => null,
            ];
            if ($withCost) $rowIns['valor_costo'] = (float)($catalogo[(int)$cod]->valor_costo ?? 0);
            if ($mode === 'save') $rowIns['masa_mes'] = null;

            $rows->push($rowIns);
        }

        return [
            'rows'          => $rows,
            'totalMgDia'    => $totalMgDia,
            'totalMasaMes'  => $totalMasaMesBase,
            'capsDia'       => $capsDia,
            'capsMes'       => $capsMes,
            'tomasDiarias'  => $tomasDiarias,
        ];
    }

    private function renderResumenCapsulas(Request $request)
    {
        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) abort(401);

        $data = $this->buildCapsuleSummary($userId, 'view');

        return view('formulas.resumen_capsulas', [
            'rows'          => $data['rows'],
            'totalMgDia'    => $data['totalMgDia'],
            'totalMasaMes'  => $data['totalMasaMes'],
            'capsDia'       => $data['capsDia'],
            'capsMes'       => $data['capsMes'],
            'tomasDiarias'  => $data['tomasDiarias'],
            'codFormula'    => $this->buildCodFormula(),
        ]);
    }

    // ===================== Guardar =====================
    public function guardar(Request $request)
    {
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

        $codigoBackend = (string) $request->input('cod_formula');
        $formulaCreada = null;

        DB::transaction(function () use ($request, $userId, $codigoBackend, &$formulaCreada) {

            $precio_medico       = max(0, (float)$request->input('precio_medico', 0));
            $precio_publico      = (float)$request->input('precio_publico', 0);
            $precio_distribuidor = (float)$request->input('precio_distribuidor', 0);

            $formulaCreada = Formula::create([
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

            // Builder único para guardar (incluye masa_mes)
            $data = $this->buildCapsuleSummary($userId, 'save');
            $rows = $data['rows'];

            $now = now();
            $insert = $rows->map(function ($r) use ($codigoBackend, $now) {
                return [
                    'codigo'     => $codigoBackend,
                    'cod_odoo'   => (int)($r['cod_odoo'] ?? 0),
                    'activo'     => (string)($r['activo'] ?? ''),
                    'unidad'     => $r['unidad'] ?? null,
                    'masa_mes'   => array_key_exists('masa_mes', $r) ? (is_null($r['masa_mes']) ? null : (float)$r['masa_mes']) : null,
                    'cantidad'   => array_key_exists('cantidad', $r) ? (is_null($r['cantidad']) ? null : (float)$r['cantidad']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            if (!empty($insert)) {
                FormulaItem::insert($insert);
            }

            ActivoTemp::where('user_id', $userId)->delete();
        });

        if (!$formulaCreada) {
            return redirect()->route('formulas.nuevas')
                ->with('ok', 'Fórmula guardada, pero no se pudo cargar en establecidas.');
        }

        // Agregar a sesión fe_items para que aparezca en establecidas
        $items = $request->session()->get('fe_items', []);
        if (!collect($items)->firstWhere('id', (int)$formulaCreada->id)) {
            $items[] = ['id' => (int)$formulaCreada->id];
            $request->session()->put('fe_items', $items);
        }

        return redirect()
            ->route('fe.index')
            ->with('ok', 'Fórmula guardada y agregada a Fórmulas Establecidas.');
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

        $formula = Formula::findOrFail($id);

        // Excluir: celulosa + cápsulas + pastillero + insumos fijos
        $codsExcluir  = [
            self::COD_CELULOSA,
            self::COD_CAPSULA_475,
            self::COD_PASTILLERO,
            self::COD_TAPA_SEG,
            self::COD_LINNER,
            self::COD_ETIQUETA,
        ];

        $regexExcluir = '/(celulosa|capsula|cápsula|capsulas|cápsulas|pastillero|pastilleros|tapa|linner|etiqueta)/i';

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
}
