<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Formula;
use App\Models\FormulaItem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class FormulasEstController extends Controller
{
    private const SESSION_KEY = 'fe_items';

    // OJO: aquí tenías repetido 3994 y faltaban varios; ajusta según tus códigos reales.
    // Si 3393 (etiqueta) también va al final, inclúyelo.
    private const NEW_END_CODES = [3994, 3796, 3397, 3395, 3393]; // cápsula, pastillero, tapa, linner, etiqueta
    private const OLD_END_CODES = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];

    private const COD_CELULOSA = 3291;

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
        $formula = \App\Models\Formula::with('items')->findOrFail($id);

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

    public function excel(Request $request, int $id)
    {
        $f = Formula::findOrFail($id, ['codigo','nombre_etiqueta','precio_medico','precio_publico','precio_distribuidor']);
        $csv = "Codigo,Nombre Etiqueta,Precio Médico,Precio Distribuidor,Precio Paciente\n";
        $csv.= "{$f->codigo},\"{$f->nombre_etiqueta}\",{$f->precio_medico},{$f->precio_distribuidor},{$f->precio_publico}\n";

        return response($csv, 200, [
            'Content-Type'=>'text/csv; charset=UTF-8',
            'Content-Disposition'=>'attachment; filename="formula_'.$f->codigo.'.csv"',
        ]);
    }

    public function items(int $id)
    {
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta']);
        $endCodes = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);

        $items = FormulaItem::where('codigo', $f->codigo)
            ->orderByRaw('CASE WHEN cod_odoo IN ('.implode(',', $endCodes).') THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->get(['id','cod_odoo','activo','cantidad','unidad','masa_mes']);

        return view('fe.items', [
            'f'     => $f,
            'items' => $items,
        ]);
    }

    /**
     * ✅ NUEVO: Actualiza celulosa (cod 3291) en mg/día y recalcula masa_mes (g/mes)
     * masa_mes = (mg_dia * 30) / 1000
     */
    public function updateCelulosa(Request $request, int $id)
    {
        // $id = id de Formula
        $data = $request->validate([
            'mg_dia' => ['required', 'numeric', 'min:0'],
        ]);

        $formula = Formula::findOrFail($id);

        // buscamos el item 3291 de esa fórmula por codigo
        $item = FormulaItem::where('codigo', $formula->codigo)
            ->where('cod_odoo', 3291)
            ->first();

        if (!$item) {
            return response()->json(['ok' => false, 'message' => 'No se encontró el ítem 3291 (celulosa) en esta fórmula.'], 404);
        }

        $mgDia = (float)$data['mg_dia'];

        // Reglas:
        // - cantidad se guarda como mg/día (porque tu tabla la muestra como "Cantidad" con unidad mg)
        // - masa_mes se guarda como g/mes => (mg/día * 30) / 1000
        $masaG = ($mgDia * 30.0) / 1000.0;

        $item->cantidad = $mgDia;
        $item->unidad   = 'mg';
        $item->masa_mes = $masaG;
        $item->save();

        return response()->json([
            'ok' => true,
            'mg_dia' => $mgDia,
            'masa_g' => round($masaG, 4),
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
            $esMasa = in_array($u, ['mg','mcg','ui','g'], true);

            $cantidadExport = $esMasa
                ? (float)($it->masa_mes ?? 0) // exporta en g (mes)
                : (float)($it->cantidad ?? 0);

            $unidadExport = $esMasa ? 'g' : (($u === 'und') ? 'Unidades' : ($it->unidad ?? ''));

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

}
