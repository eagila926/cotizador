<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Formula;
use App\Models\FormulaItem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormulasEstController extends Controller
{
    private const SESSION_KEY = 'fe_items';

    // Ajusta estos códigos a tus “insumos al final”.
    private const NEW_END_CODES = [3994, 3796, 3397, 3395, 3393];
    private const OLD_END_CODES = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];

    private const COD_CELULOSA = 3291;

    private const PRINT_EXCLUDE_CODES = [
        3392, 3396, 3398, 3395, 3393, 3434, 3435, 3436,
    ];

    // Para detectar “cápsula” en caso de que tus códigos varíen
    private const CAPSULA_CODES_FALLBACK = [3392, 3994, 70274];

    public function index(Request $request)
    {
        $items = $request->session()->get(self::SESSION_KEY, []);
        $ids   = array_column($items, 'id');

        $formulas = $ids
            ? Formula::whereIn('id', $ids)->get(['id','codigo','nombre_etiqueta','precio_medico','precio_publico','precio_distribuidor'])
            : collect();

        $rows = $formulas->map(function($f){
            return (object)[
                'id'                 => $f->id,
                'codigo'             => $f->codigo,
                'nombre_etiqueta'    => $f->nombre_etiqueta,
                'precio_medico'      => (float)$f->precio_medico,
                'precio_distribuidor'=> (float)$f->precio_distribuidor,
                'precio_publico'     => (float)$f->precio_publico,
                'tipo'               => null,
            ];
        });

        return view('formulas.establecidas', ['rows'=>$rows, 'tipos'=>[]]);
    }

    public function buscar(Request $request)
    {
        $q = trim((string)$request->query('q',''));
        if ($q === '') return response()->json([]);

        $data = Formula::where('codigo','like',"%{$q}%")
            ->orWhere('nombre_etiqueta','like',"%{$q}%")
            ->orderBy('codigo')
            ->limit(12)
            ->get(['id','codigo','nombre_etiqueta'])
            ->map(fn($f)=>[
                'id'=>$f->id,
                'display'=>$f->codigo.' — '.$f->nombre_etiqueta,
            ]);

        return response()->json($data);
    }

    public function add(Request $request)
    {
        $request->validate(['formula_id'=>'required|integer|exists:formulas,id']);

        $items = $request->session()->get(self::SESSION_KEY, []);
        if (!collect($items)->firstWhere('id', (int)$request->formula_id)) {
            $items[] = ['id'=>(int)$request->formula_id];
            $request->session()->put(self::SESSION_KEY, $items);
        }
        return back();
    }

    public function updateTipo(Request $request)
    {
        return response()->noContent();
    }

    public function remove(Request $request, int $id)
    {
        $items = $request->session()->get(self::SESSION_KEY, []);
        $items = array_values(array_filter($items, fn($it)=>(int)$it['id'] !== $id));
        $request->session()->put(self::SESSION_KEY, $items);
        return back();
    }

    public function clear(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);
        return back();
    }

    public function print(Request $request, int $id)
    {
        $formula = Formula::with('items')->findOrFail($id);

        $excluir = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);
        $itemsComposicion = $formula->items
            ->filter(fn($it) => !in_array((int)$it->cod_odoo, $excluir, true))
            ->values();

        $qf = 'Q.F. Jose Perez';

        return view('etiquetas.generica', [
            'formula'           => $formula,
            'items'             => $itemsComposicion,
            'qf'                => $qf,
            'fechaElaboracion'  => now()->format('d-m-Y'),
        ]);
    }

    public function items(int $id)
    {
        // ✅ necesitamos tomas_diarias para la tabla resumen
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta','tomas_diarias']);

        $endCodes = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);

        $items = FormulaItem::where('codigo', $f->codigo)
            ->orderByRaw('CASE WHEN cod_odoo = 3291 THEN 1 ELSE 0 END ASC')
            ->orderBy('id', 'ASC') // o tu orden actual (created_at, etc.)
            ->get();

        // ====== Cálculos resumen (tipo imagen 1–3) ======
        $tomasDia = (float)($f->tomas_diarias ?? 0);
        if ($tomasDia <= 0) $tomasDia = 1.0;

        // Celulosa mg/día (editable)
        $cel = $items->firstWhere('cod_odoo', self::COD_CELULOSA);
        $celMgDia = $cel ? (float)($cel->cantidad ?? 0) : 0.0;

        // Total principios activos (mg/día) excluyendo celulosa:
        // Usamos masa_mes (g/mes) -> mg/día = (g/mes*1000)/30
        $totalPrincipiosMgDia = 0.0;

        foreach ($items as $it) {
            if ((int)$it->cod_odoo === self::COD_CELULOSA) continue;

            // si tiene masa_mes, es “pesado” (incluye probióticos aunque unidad sea UFC)
            if (!is_null($it->masa_mes)) {
                $mgDia = (((float)$it->masa_mes) * 1000.0) / 30.0;
                $totalPrincipiosMgDia += $mgDia;
            }
        }

        $dosisPorCapsMg = $totalPrincipiosMgDia / $tomasDia;
        $celPorCapsMg   = $celMgDia / $tomasDia;
        $contenidoCaps  = $dosisPorCapsMg + $celPorCapsMg;

        // Presentación: detectar cápsulas (und/mes)
        $capsMes = 0.0;
        $capsItem = $items->first(function($it){
            $cod = (int)$it->cod_odoo;
            $name = mb_strtolower((string)$it->activo);
            return in_array($cod, self::CAPSULA_CODES_FALLBACK, true) || str_contains($name, 'capsul');
        });
        if ($capsItem) {
            $capsMes = (float)($capsItem->cantidad ?? 0);
        }

        $resumen = [
            'total_principios_mg_dia' => $totalPrincipiosMgDia,
            'dosis_caps_mg'           => $dosisPorCapsMg,
            'celulosa_caps_mg'        => $celPorCapsMg,
            'contenido_caps_mg'       => $contenidoCaps,
            'presentacion_caps'       => $capsMes,
            'dosificacion_caps_dia'   => $tomasDia,
        ];

        return view('fe.items', [
            'f'       => $f,
            'items'   => $items,
            'resumen' => $resumen,
        ]);
    }

    /**
     * Actualiza celulosa (3291) en mg/día y recalcula masa_mes (g/mes)
     * masa_mes = (mg_dia * 30) / 1000
     */
    public function updateCelulosa(Request $request, int $id)
    {
        $data = $request->validate([
            'mg_dia' => ['required', 'numeric', 'min:0'],
        ]);

        $formula = Formula::findOrFail($id);

        $item = FormulaItem::where('codigo', $formula->codigo)
            ->where('cod_odoo', self::COD_CELULOSA)
            ->first();

        if (!$item) {
            return response()->json(['ok' => false, 'message' => 'No se encontró el ítem 3291 (celulosa) en esta fórmula.'], 404);
        }

        $mgDia  = (float)$data['mg_dia'];
        $masaG  = ($mgDia * 30.0) / 1000.0;

        $item->cantidad = $mgDia;  // mg/día
        $item->unidad   = 'mg';
        $item->masa_mes = $masaG;  // g/mes
        $item->save();

        // devolvemos también datos para refrescar el resumen
        $tomasDia = (float)($formula->tomas_diarias ?? 0);
        if ($tomasDia <= 0) $tomasDia = 1.0;

        $celPorCaps = $mgDia / $tomasDia;

        return response()->json([
            'ok' => true,
            'mg_dia' => $mgDia,
            'masa_g' => round($masaG, 4),
            'celulosa_caps_mg' => round($celPorCaps, 2),
        ]);
    }

    public function itemsExportXlsx(int $id)
    {
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta']);
        $endCodes = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);

        $rows = FormulaItem::where('codigo', $f->codigo)
            ->orderByRaw('CASE WHEN cod_odoo IN ('.implode(',', $endCodes).') THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->get(['cod_odoo','activo','cantidad','unidad','masa_mes']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Exportación Odoo');

        $sheet->setCellValue('A1', 'Líneas de LdM/Componente/Id. de la BD');
        $sheet->setCellValue('B1', 'Líneas de LdM/Cantidad');
        $sheet->setCellValue('C1', 'Líneas de lista de materiales/Unidad de medida del producto');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $it) {
            $u = strtolower((string)$it->unidad);

            // ✅ IMPORTANTE: probióticos (UFC) se exportan como masa (g) usando masa_mes
            $esMasa = in_array($u, ['mg','mcg','ui','g','ufc'], true);

            $cantidadExport = $esMasa
                ? (float)($it->masa_mes ?? 0) // g/mes
                : (float)($it->cantidad ?? 0);

            $unidadExport = $esMasa
                ? 'g'
                : (($u === 'und') ? 'Unidades' : ($it->unidad ?? ''));

            $sheet->setCellValue("A{$r}", (int)$it->cod_odoo);
            $sheet->setCellValue("B{$r}", $cantidadExport);
            $sheet->setCellValue("C{$r}", $unidadExport);

            $sheet->getStyle("B{$r}")->getNumberFormat()->setFormatCode('0.0000');
            $r++;
        }

        foreach (range('A','C') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $filename = 'export_odoo_'.$f->codigo.'.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    public function updatePrices(Request $request)
    {
        $data = $request->validate([
            'id' => ['required','integer','exists:formulas,id'],
            'precio_medico'       => ['nullable','numeric','min:0'],
            'precio_distribuidor' => ['nullable','numeric','min:0'],
            'precio_publico'      => ['nullable','numeric','min:0'],
        ]);

        $f = Formula::findOrFail((int)$data['id']);

        if ($data['precio_medico'] !== null)       $f->precio_medico       = round((float)$data['precio_medico'], 2);
        if ($data['precio_distribuidor'] !== null) $f->precio_distribuidor = round((float)$data['precio_distribuidor'], 2);
        if ($data['precio_publico'] !== null)      $f->precio_publico      = round((float)$data['precio_publico'], 2);

        $f->save();

        return response()->json(['ok' => true]);
    }

    public function itemsPrint(int $id)
    {
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta','tomas_diarias']);

        $endCodes = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);

        $itemsAll = FormulaItem::where('codigo', $f->codigo)
            ->orderByRaw('CASE WHEN cod_odoo IN ('.implode(',', $endCodes).') THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->get(['id','cod_odoo','activo','cantidad','unidad','masa_mes']);

        // ✅ excluir para impresión
        $items = $itemsAll->reject(fn($it) => in_array((int)$it->cod_odoo, self::PRINT_EXCLUDE_CODES, true))
                        ->values();

        // (si tu resumen lo quieres basado SOLO en los imprimibles, usa $items; si no, usa $itemsAll)
        $tomasDia = (float)($f->tomas_diarias ?? 1);
        if ($tomasDia <= 0) $tomasDia = 1.0;

        $cel = $itemsAll->firstWhere('cod_odoo', self::COD_CELULOSA); // celulosa sí cuenta para el resumen
        $celMgDia = $cel ? (float)($cel->cantidad ?? 0) : 0.0;

        $totalPrincipiosMgDia = 0.0;
        foreach ($itemsAll as $it) {
            $cod = (int)$it->cod_odoo;
            if ($cod === self::COD_CELULOSA) continue;
            // OJO: si hay items UND/UFC, solo sumamos los que tengan masa_mes (g/mes)
            if (!is_null($it->masa_mes)) {
                $totalPrincipiosMgDia += (((float)$it->masa_mes) * 1000.0) / 30.0; // mg/día
            }
        }

        $dosisPorCapsMg = $totalPrincipiosMgDia / $tomasDia;
        $celPorCapsMg   = $celMgDia / $tomasDia;
        $contenidoCaps  = $dosisPorCapsMg + $celPorCapsMg;

        $resumen = [
            'total_principios_mg_dia' => $totalPrincipiosMgDia,
            'dosis_caps_mg'           => $dosisPorCapsMg,
            'celulosa_caps_mg'        => $celPorCapsMg,
            'contenido_caps_mg'       => $contenidoCaps,
            'dosificacion_caps_dia'   => $tomasDia,
        ];

        return view('fe.items_print', [
            'f'       => $f,
            'items'   => $items,   // ✅ ya filtrados
            'resumen' => $resumen,
        ]);
    }
}
