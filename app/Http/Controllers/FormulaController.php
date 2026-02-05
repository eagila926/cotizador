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
    // Cápsula única de 465 mg
    private const CAPS_MG_POR_UND = 475;

    // Códigos de inventario
    private const COD_CAPSULA_465 = 3392; // CÁPSULA 465 mg
    private const COD_PASTILLERO  = 3396; // PASTILLERO

    // Insumos por pastillero (existentes)
    private const COD_TAPA_SEG    = 3397; // Tapa de seguridad
    private const COD_LINNER      = 3395; // Linner espumado
    private const COD_ETIQUETA    = 3393; // Etiqueta

    // Insumos adicionales por pastillero (nuevos)
    private const COD_EXTRA_1     = 3434;
    private const COD_EXTRA_2     = 3435;
    private const COD_EXTRA_3     = 3436;

    // Relleno
    private const COD_CELULOSA    = 3291; // CELULOSA

    // Regla de capacidad de pastillero (unidades al mes)
    private const PAST_CAP_SMALL = 60;
    private const PAST_CAP_LARGE = 150;

    /**
     * Conversiones especiales UI -> mg (por cod_odoo)
     * - 3388 Vit D3: 1 UI = 0.025 ug = 0.000025 mg
     * - 3381 Vit A : 1 UI = 0.55 ug  = 0.00055 mg
     * - 3375 Vit E : 1 UI = 1 mg     = 1 mg
     */
    private const UI_TO_MG = [
        3388 => 0.000025,
        3381 => 0.00055,
        3375 => 1.0,
    ];

    private const UI_FALLBACK_TO_MG = 0.00067;

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
            ->get(['cod_odoo', 'nombre', 'unidad']);

        $html = '';
        foreach ($items as $it) {
            $html .= '<div class="suggest-element" '
                .'data-cod_odoo="'.e($it->cod_odoo).'" '
                .'data-nombre="'.e($it->nombre).'" '
                .'data-unidad="'.e($it->unidad).'" '
                .'style="padding:6px; cursor:pointer; border-bottom:1px solid #eee">'
                .e($it->nombre).' <small style="opacity:.6">('.e($it->unidad).')</small>'
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
            // unidad se fuerza desde DB
        ]);

        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) return response('NO_USER', 401);

        $activoDB = Activo::query()
            ->where('cod_odoo', (int)$request->cod_odoo)
            ->first(['cod_odoo', 'nombre', 'unidad']);

        if (!$activoDB) {
            return response()->json(['error' => 'ACTIVO_NO_EXISTE'], 422);
        }

        $exists = ActivoTemp::where('user_id', $userId)
            ->where('cod_odoo', (int)$activoDB->cod_odoo)
            ->exists();

        if ($exists) return response('duplicado', 200);

        ActivoTemp::create([
            'user_id'  => $userId,
            'cod_odoo' => (int)$activoDB->cod_odoo,
            // Si quieres forzar nombre oficial, usa $activoDB->nombre:
            'activo'   => (string)$request->activo,
            'cantidad' => (float)$request->cantidad, // por día
            'unidad'   => (string)$activoDB->unidad, // unidad oficial desde DB
        ]);

        return response()->json(['status' => 'ok', 'unidad' => $activoDB->unidad]);
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

        $row = ActivoTemp::where('user_id', $userId)->where('id', $request->id)->first();
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
    private function buildCodFormula(): string
    {
        $random = random_int(100000, 999999);
        return "FORMU.{$random}";
    }

    private function isUnidadDiaria(?string $u): bool
    {
        return in_array($u, ['g','mg','mcg','UI'], true);
    }

    private function toMgDia(float $cantidad, string $unidad, int $cod_odoo): float
    {
        return match ($unidad) {
            'g'   => $cantidad * 1000.0,
            'mg'  => $cantidad,
            'mcg' => $cantidad / 1000.0,
            'UI'  => $cantidad * (self::UI_TO_MG[$cod_odoo] ?? self::UI_FALLBACK_TO_MG),
            default => 0.0,
        };
    }

    private function capsulasPorCapacidad(float $totalMgDia): array
    {
        if ($totalMgDia <= 0) {
            return ['tomas' => 0, 'caps_dia' => 0, 'caps_mes' => 0];
        }

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

    private function buildCatalog(array $cods)
    {
        return Activo::whereIn('cod_odoo', $cods)
            ->get(['cod_odoo','nombre','valor_costo'])
            ->keyBy('cod_odoo');
    }

    /**
     * Construye el resumen y retorna filas y metadatos.
     * mode:
     * - 'view': incluye valor_costo y mg_dia
     * - 'save': incluye masa_mes (g/mes) para guardar items
     */
    private function buildCapsuleSummary(int $userId, string $mode = 'view'): array
    {
        $withCost = ($mode === 'view');

        $items = ActivoTemp::where('user_id', $userId)->orderBy('id')->get();

        $cods = $items->pluck('cod_odoo')->map(fn($c)=>(int)$c)->all();
        $cods = array_merge($cods, [
            self::COD_CELULOSA,
            self::COD_CAPSULA_465,
            self::COD_PASTILLERO,
            self::COD_TAPA_SEG,
            self::COD_LINNER,
            self::COD_ETIQUETA,
            self::COD_EXTRA_1,
            self::COD_EXTRA_2,
            self::COD_EXTRA_3,
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
                'cantidad' => (float)$r->cantidad,
                'unidad'   => (string)$r->unidad,
                'mg_dia'   => $mgDia,
            ];

            if ($withCost) {
                $row['valor_costo'] = (float)($catalogo[(int)$r->cod_odoo]->valor_costo ?? 0);
            }

            if ($mode === 'save') {
                $row['masa_mes'] = (($mgDia * 30.0) / 1000.0); // g/mes
            }

            $rows->push($row);
        }

        // Cápsulas por capacidad
        $regla = $this->capsulasPorCapacidad($totalMgDia);
        $capsDia = (int)$regla['caps_dia'];
        $capsMes = (int)$regla['caps_mes'];
        $tomasDiarias = (int)$regla['tomas'];

        $totalMasaMesBase = ($totalMgDia * 30.0) / 1000.0;

        // Relleno con celulosa hasta el límite diario
        $limiteMgDia  = $capsDia * self::CAPS_MG_POR_UND;
        $rellenoMgDia = max(0.0, $limiteMgDia - $totalMgDia);

        if ($rellenoMgDia > 0) {
            $rowCel = [
                'cod_odoo' => self::COD_CELULOSA,
                'activo'   => $catalogo[self::COD_CELULOSA]->nombre ?? 'CELULOSA',
                'cantidad' => $rellenoMgDia,
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

        // Pastillero
        $pastCount = $this->pastillerosNecesarios($capsMes);

        // Cápsulas (und/mes)
        $rowCaps = [
            'cod_odoo' => self::COD_CAPSULA_465,
            'activo'   => $catalogo[self::COD_CAPSULA_465]->nombre ?? 'CÁPSULA 465 mg',
            'cantidad' => (float)$capsMes,
            'unidad'   => 'und',
            'mg_dia'   => null,
        ];
        if ($withCost) $rowCaps['valor_costo'] = (float)($catalogo[self::COD_CAPSULA_465]->valor_costo ?? 0);
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

        // Insumos por pastillero: tapa/linner/etiqueta + 3434/3435/3436
        $insumos = [
            [self::COD_TAPA_SEG, 'TAPA DE SEGURIDAD'],
            [self::COD_LINNER,   'LINNER ESPUMADO'],
            [self::COD_ETIQUETA, 'ETIQUETA'],
            [self::COD_EXTRA_1,  'CIF'],
            [self::COD_EXTRA_2,  'MOI'],
            [self::COD_EXTRA_3,  'MOD'],
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

    /**
     * Calcula precios a partir de filas del builder (con valor_costo).
     * Regla:
     * - diarios: mg/día -> g/mes -> g_mes * valor_costo
     * - und: cantidad * valor_costo
     * - pvp = ceil(total + total*3)
     * - medico = 80% pvp
     * - distribuidor = 65% pvp
     */
    private function computePricing($rows): array
    {
        $totalGeneral = 0.0;

        foreach ($rows as $r) {
            $unidad = $r['unidad'] ?? null;
            $valor  = (float)($r['valor_costo'] ?? 0);

            if ($this->isUnidadDiaria($unidad)) {
                $mg_dia = (float)($r['mg_dia'] ?? 0);
                $g_mes  = ($mg_dia * 30.0) / 1000.0;
                $totalGeneral += ($g_mes * $valor);
            } else {
                $und_mes = (float)($r['cantidad'] ?? 0);
                $totalGeneral += ($und_mes * $valor);
            }
        }

        $pvp  = (float) ceil($totalGeneral + ($totalGeneral * 3.0));
        $med  = round($pvp * 0.80, 2);
        $dis  = round($pvp * 0.65, 2);

        return [
            'totalGeneral' => $totalGeneral,
            'pvp'          => $pvp,
            'medico'       => $med,
            'distribuidor' => $dis,
        ];
    }

    private function renderResumenCapsulas(Request $request)
    {
        $userId = Auth::id() ?? $request->session()->get('user_id');
        if (!$userId) abort(401);

        // 1) Builder con costos para poder calcular precios en backend
        $dataView = $this->buildCapsuleSummary($userId, 'view');

        // 2) Precios calculados desde el controlador
        $pricing = $this->computePricing($dataView['rows']);

        return view('formulas.resumen_capsulas', [
            'rows'          => $dataView['rows'],
            'totalMgDia'    => $dataView['totalMgDia'],
            'totalMasaMes'  => $dataView['totalMasaMes'],
            'capsDia'       => $dataView['capsDia'],
            'capsMes'       => $dataView['capsMes'],
            'tomasDiarias'  => $dataView['tomasDiarias'],
            'codFormula'    => $this->buildCodFormula(),

            // precios para la vista
            'totalGeneral'  => $pricing['totalGeneral'],
            'precio_pvp_v'  => $pricing['pvp'],
            'precio_med_v'  => $pricing['medico'],
            'precio_dis_v'  => $pricing['distribuidor'],
        ]);
    }

    // ===================== Guardar =====================
    public function guardar(Request $request)
    {
        $request->validate([
            'cod_formula'     => ['required','string','max:30'],
            'nombre_etiqueta' => ['nullable','string','max:150'],
            'medico'          => ['nullable','string','max:150'],
            'paciente'        => ['nullable','string','max:150'],
        ]);

        $userId = Auth::id();
        if (!$userId) abort(401);

        $codigoBackend = (string) $request->input('cod_formula');
        $formulaCreada = null;

        DB::transaction(function () use ($request, $userId, $codigoBackend, &$formulaCreada) {

            // 1) Builder con costos para calcular precios server-side
            $dataView = $this->buildCapsuleSummary($userId, 'view');
            $pricing  = $this->computePricing($dataView['rows']);

            // 2) Builder para guardar items (masa_mes etc.)
            $dataSave = $this->buildCapsuleSummary($userId, 'save');

            $formulaCreada = Formula::create([
                'codigo'              => $codigoBackend,
                'nombre_etiqueta'     => $request->input('nombre_etiqueta'),
                'user_id'             => $userId,

                // precios: salen del cálculo backend
                'precio_medico'       => round((float)$pricing['medico'], 2),
                'precio_publico'      => round((float)$pricing['pvp'], 2),
                'precio_distribuidor' => round((float)$pricing['distribuidor'], 2),

                'medico'              => $request->input('medico'),
                'paciente'            => $request->input('paciente'),

                // tomas_diarias: desde el cálculo de cápsulas
                'tomas_diarias'       => (float)($dataSave['capsDia'] ?? 0),
            ]);

            // Insert items
            $rows = $dataSave['rows'];
            $now  = now();

            $insert = $rows->map(function ($r) use ($codigoBackend, $now) {
                return [
                    'codigo'     => $codigoBackend,
                    'cod_odoo'   => (int)($r['cod_odoo'] ?? 0),
                    'activo'     => (string)($r['activo'] ?? ''),
                    'unidad'     => $r['unidad'] ?? null,
                    'masa_mes'   => array_key_exists('masa_mes', $r)
                        ? (is_null($r['masa_mes']) ? null : (float)$r['masa_mes'])
                        : null,
                    'cantidad'   => array_key_exists('cantidad', $r)
                        ? (is_null($r['cantidad']) ? null : (float)$r['cantidad'])
                        : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            if (!empty($insert)) {
                FormulaItem::insert($insert);
            }

            // Limpia temporales
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

        // Excluir: celulosa + cápsula + pastillero + insumos fijos + extras
        $codsExcluir  = [
            self::COD_CELULOSA,
            self::COD_CAPSULA_465,
            self::COD_PASTILLERO,
            self::COD_TAPA_SEG,
            self::COD_LINNER,
            self::COD_ETIQUETA,
            self::COD_EXTRA_1,
            self::COD_EXTRA_2,
            self::COD_EXTRA_3,
        ];

        $regexExcluir = '/(celulosa|capsula|cápsula|capsulas|cápsulas|pastillero|pastilleros|tapa|linner|etiqueta|3434|3435|3436)/i';

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
