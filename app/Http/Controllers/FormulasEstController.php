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
    private const TIPO_ETIQUETA = [
        'Seleccionar','Dr Redin','Dr Li','Dr Walter','Dra Maria Delia',
        'Dra Viteri','Dra Bernarda','M Urdiales','Naturmed','Sobre'
    ];

    public function index(Request $request)
    {
        $items = $request->session()->get(self::SESSION_KEY, []); // [['id'=>1,'tipo'=>null], ...]
        $ids   = array_column($items, 'id');

        $formulas = $ids ? Formula::whereIn('id',$ids)
            ->get(['id','codigo','nombre_etiqueta','precio_medico','precio_publico','precio_distribuidor']) : collect();

        $rows = $formulas->map(function($f) use ($items){
            $tipo = collect($items)->firstWhere('id',$f->id)['tipo'] ?? null;
            return (object)[
                'id'=>$f->id,'codigo'=>$f->codigo,'nombre_etiqueta'=>$f->nombre_etiqueta,
                'precio_medico'=>(float)$f->precio_medico,
                'precio_distribuidor'=>(float)$f->precio_distribuidor,
                'precio_publico'=>(float)$f->precio_publico,
                'tipo'=>$tipo
            ];
        });

        return view('formulas.establecidas', ['rows'=>$rows, 'tipos'=>self::TIPO_ETIQUETA]);
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
            $items[] = ['id'=>(int)$request->formula_id, 'tipo'=>null];
            $request->session()->put(self::SESSION_KEY,$items);
        }
        return back();
    }

    public function updateTipo(Request $request)
    {
        $id   = (int) $request->input('formula_id');
        $tipo = trim((string)$request->input('tipo'));

        $items = $request->session()->get(self::SESSION_KEY, []);
        foreach ($items as &$it) {
            if ((int)$it['id'] === $id) {
                $it['tipo'] = $tipo;
                break;
            }
        }
        $request->session()->put(self::SESSION_KEY, $items);

        return response()->noContent();
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

        // Ítems de composición: excluimos cápsulas, estearato, pastilleros, sobres, etc.
        $excluir = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];
        $itemsComposicion = $formula->items
            ->filter(fn($it) => !in_array((int)$it->cod_odoo, $excluir))
            ->values();

        // QF fijo (igual que tu script). Si lo quieres dinámico, agrégalo a BD.
        $qf = 'Q.F. EVELYN GARCÍA';

        // 2) Recupera el tipo seleccionado desde la sesión fe_items
        $feItems = $request->session()->get(self::SESSION_KEY, []); // [['id'=>1,'tipo'=>'Dr Redin'], ...]
        $registro = collect($feItems)->firstWhere('id', $id);
        $tipo = $registro['tipo'] ?? null;

        // 3) Mapa Tipo -> nombre de vista
        $map = [
            'Seleccionar'   => null,
            'Dr Redin'      => 'redin',
            'Dr Li'         => 'li',
            'Dr Walter'     => 'walter',
            'Dra Maria Delia'=> 'maria_delia',
            'Dra Viteri'    => 'viteri',
            'Dra Bernarda'  => 'bernarda',
            'M Urdiales'    => 'urdiales',
            'Naturmed'      => 'naturmed',
            'Sobre'         => 'sobre',
        ];

        $slug = $map[$tipo] ?? null;
        $view = $slug ? "etiquetas.$slug" : "etiquetas.generica"; // fallback a "etiqueta a secas"

        // 4) Renderiza la vista elegida
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
            'Content-Disposition'=>'attachment; filename="formula_'.$f->codigo.'.csv"',
        ]);
    }

    public function items(int $id)
    {
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta']);

        $endCodes = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];

        $items = FormulaItem::where('codigo', $f->codigo)
            ->orderByRaw('CASE WHEN cod_odoo IN ('.implode(',', $endCodes).') THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->get(['cod_odoo','activo','cantidad','unidad','masa_mes']);

        return view('fe.items', [
            'f'     => $f,
            'items' => $items,
        ]);
    }


    // Exportar esos items a CSV
    // public function itemsExport(int $id)
    // {
    //     $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta']);

    //     $rows = FormulaItem::where('codigo', $f->codigo)
    //         ->orderBy('id','desc')
    //         ->get(['cod_odoo','activo','cantidad','unidad']);

    //     $csv  = "Codigo,\"Nombre Etiqueta\",cod_odoo,activo,cantidad,unidad\n";
    //     foreach ($rows as $r) {
    //         $cantidad = rtrim(rtrim(number_format((float)$r->cantidad, 6, '.', ''), '0'), '.');
    //         $csv .= "{$f->codigo},\"{$f->nombre_etiqueta}\",{$r->cod_odoo},\"{$r->activo}\",{$cantidad},{$r->unidad}\n";
    //     }

    //     return response($csv, 200, [
    //         'Content-Type'        => 'text/csv; charset=UTF-8',
    //         'Content-Disposition' => 'attachment; filename="items_'.$f->codigo.'.csv"',
    //     ]);
    // }

    public function itemsExportXlsx(int $id)
    {
        $f = Formula::findOrFail($id, ['id','codigo','nombre_etiqueta']);

        $endCodes = [70274,70272,70275,70273,1101,1078,1077,1219,70276,70271,71497];

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

        // StreamedResponse para no escribir en disco
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
}
