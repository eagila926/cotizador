<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Formula;
use App\Models\FormulaItem;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Validator;

class FormulasEstController extends Controller
{
    private const SESSION_KEY = 'fe_items';

    // Códigos (nuevos) a excluir de composición / llevar al final
    private const NEW_END_CODES = [3739, 3743, 3744, 3742, 3740]; // cápsula, pastillero, tapa, linner, etiqueta
    // Códigos (antiguos) mantenidos por compatibilidad
    private const OLD_END_CODES = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];

    public function index(Request $request)
    {
        // La sesión solo guarda IDs (sin “tipo”)
        $items = $request->session()->get(self::SESSION_KEY, []); // [['id'=>1], ...]
        $ids   = array_column($items, 'id');

        $formulas = $ids ? Formula::whereIn('id',$ids)
            ->get(['id','codigo','nombre_etiqueta','precio_medico','precio_publico','precio_distribuidor']) : collect();

        // Enviamos “tipo” siempre null para no romper la vista (si lo usa)
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

        // Ya no hay lista de tipos; pasamos arreglo vacío para que el frontend no muestre selector
        return view('formulas.establecidas', ['rows'=>$rows, 'tipos'=>[]]);
    }

    public function buscar(Request $request)
    {
        $q = trim((string)$request->query('q',''));
        if ($q==='') return response()->json([]);
        $data = Formula::where('codigo','like',"%{$q}%")
            ->orWhere('nombre_etiqueta','like',"%{$q}%")
            ->orderBy('codigo')->limit(12)
            ->get(['id','codigo','nombre_etiqueta','precio_medico','precio_publico','precio_distribuidor'])
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
        if (!collect($items)->firstWhere('id',(int)$request->formula_id)) {
            $items[] = ['id'=>(int)$request->formula_id]; // sin 'tipo'
            $request->session()->put(self::SESSION_KEY,$items);
        }
        return back();
    }

    // Ya no se usa: lo dejamos como NO-OP por compatibilidad con el frontend
    public function updateTipo(Request $request)
    {
        return response()->noContent(); // 204
    }

    public function remove(Request $request,int $id)
    {
        $items = $request->session()->get(self::SESSION_KEY, []);
        $items = array_values(array_filter($items, fn($it)=>(int)$it['id']!==$id));
        $request->session()->put(self::SESSION_KEY,$items);
        return back();
    }

    public function clear(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);
        return back();
    }

    public function print(Request $request, int $id)
    {
        // 1) Obtiene la fórmula e ítems
        $formula = \App\Models\Formula::with('items')->findOrFail($id);

        // Ítems de composición: excluimos cápsulas/pastilleros/insumos y (por compat.) códigos antiguos
        $excluir = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);
        $itemsComposicion = $formula->items
            ->filter(fn($it) => !in_array((int)$it->cod_odoo, $excluir))
            ->values();

        // QF fijo (si lo quieres dinámico, llévalo a BD)
        $qf = 'Q.F. Jose Perez';

        // 2) Siempre usamos la vista por defecto (generica)
        return view('etiquetas.generica', [
            'formula'           => $formula,
            'items'             => $itemsComposicion,
            'qf'                => $qf,
            'fechaElaboracion'  => now()->format('d-m-Y'),
        ]);
    }

    public function excel(Request $request,int $id)
    {
        $f = Formula::findOrFail($id,['codigo','nombre_etiqueta','precio_medico','precio_publico','precio_distribuidor']);
        $csv = "Codigo,Nombre Etiqueta,Precio Médico,Precio Distribuidor,Precio Paciente\n";
        $csv.= "{$f->codigo},\"{$f->nombre_etiqueta}\",{$f->precio_medico},{$f->precio_distribuidor},{$f->precio_publico}\n";
        return response($csv,200,[
            'Content-Type'=>'text/csv; charset=UTF-8',
            'Content-Disposition'=>'attachment; filename=\"formula_{$f->codigo}.csv\"',
        ]);
    }

    public function items(int $id)
    {
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta']);

        $endCodes = array_merge(self::NEW_END_CODES, self::OLD_END_CODES);

        $items = FormulaItem::where('codigo', $f->codigo)
            ->orderByRaw('CASE WHEN cod_odoo IN ('.implode(',', $endCodes).') THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->get(['cod_odoo','activo','cantidad','unidad','masa_mes']);

        return view('fe.items', [
            'f'     => $f,
            'items' => $items,
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

        // Construimos el XLSX (encabezado y datos tal como "Tabla de Exportación Odoo")
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Exportación Odoo');

        // Encabezados
        $headers = [
            'A1' => 'Líneas de LdM/Componente/Id. de la BD',
            'B1' => 'Líneas de LdM/Cantidad',
            'C1' => 'Líneas de LdM/Unidad de medida del producto/ID',
        ];
        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col, $text);
        }
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        // Filas
        $r = 2;
        foreach ($rows as $it) {
            $u = strtolower((string)$it->unidad);
            $esMasa = in_array($u, ['mg','mcg','ui','g']);

            $cantidadExport = $esMasa
                ? (float)($it->masa_mes ?? 0)   // g/mes
                : (float)($it->cantidad ?? 0);  // ej. und

            $unidadExport = $esMasa ? 'g' : ($it->unidad ?? '');

            $sheet->setCellValue("A{$r}", (int)$it->cod_odoo);
            $sheet->setCellValue("B{$r}", $cantidadExport);
            $sheet->setCellValue("C{$r}", $unidadExport);

            // 4 decimales para Cantidad
            $sheet->getStyle("B{$r}")
                ->getNumberFormat()
                ->setFormatCode('0.0000');

            $r++;
        }

        // Auto-size columnas
        foreach (range('A','C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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

        $f->precio_medico       = $data['precio_medico'] !== null ? round((float)$data['precio_medico'], 2) : $f->precio_medico;
        $f->precio_distribuidor = $data['precio_distribuidor'] !== null ? round((float)$data['precio_distribuidor'], 2) : $f->precio_distribuidor;
        $f->precio_publico      = $data['precio_publico'] !== null ? round((float)$data['precio_publico'], 2) : $f->precio_publico;

        $f->save();

        return response()->json(['ok' => true]);
    }

}
