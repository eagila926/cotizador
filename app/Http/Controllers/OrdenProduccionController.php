<?php

namespace App\Http\Controllers;

use App\Models\OrdenProduccion;
use App\Models\OrdenImpresion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrdenProduccionController extends Controller
{
    /**
     * Crea una OP nueva con número automático:
     * OP-YYYY-000001
     * Retorna JSON con la OP creada.
     */
    public function store(Request $request)
    {
        $request->validate([
            // si quieres permitir fecha manual, habilita este campo:
            // 'fecha_produccion' => ['nullable', 'date'],
        ]);

        $op = DB::transaction(function () use ($request) {
            $year = now()->format('Y');
            $nextSeq = $this->nextSequenceForYear($year);

            $numero = sprintf('OP-%s-%06d', $year, $nextSeq);

            $op = OrdenProduccion::create([
                'numero'          => $numero,
                'fecha_produccion'=> now(), // o $request->fecha_produccion ?? now()
                'created_by'      => $this->authId(),
            ]);

            return $op;
        });

        return response()->json([
            'ok' => true,
            'op' => $op,
        ]);
    }

    /**
     * Registra un evento de impresión (histórico).
     * Úsalo antes de ejecutar window.print() (o cuando el usuario confirme).
     */
    public function printLog(Request $request, int $id)
    {
        $request->validate([
            'copies' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $op = OrdenProduccion::findOrFail($id);

        OrdenImpresion::create([
            'orden_id'   => $op->id,
            'printed_by' => $this->authId(),
            'printed_at' => now(),
            'copies'     => (int)($request->input('copies', 1)),
            'ip'         => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 255),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Helper: obtiene ID del usuario autenticado considerando que tu PK puede ser id_user.
     */
    private function authId(): ?int
    {
        $u = Auth::user();
        if (!$u) return null;

        // tu tabla "usuarios" usa id_user
        if (isset($u->id_user)) return (int)$u->id_user;

        // fallback si tu modelo auth usa "id"
        if (isset($u->id)) return (int)$u->id;

        return null;
    }

    /**
     * Obtiene el siguiente consecutivo para el año YYYY,
     * leyendo el MAX actual y sumando 1.
     *
     * Formato esperado: OP-YYYY-000001
     */
    private function nextSequenceForYear(string $year): int
    {
        // Ej: OP-2026-000123 => secuencia = 123 (parte final)
        // SUBSTRING: "OP-"(3) + "YYYY"(4) + "-"(1) = 8 chars, la secuencia inicia en pos 9 (1-based)
        $row = DB::table('ordenes_produccion')
            ->selectRaw("MAX(CAST(SUBSTRING(numero, 9) AS UNSIGNED)) AS max_seq")
            ->where('numero', 'like', "OP-{$year}-%")
            ->lockForUpdate()
            ->first();

        $maxSeq = (int)($row->max_seq ?? 0);

        return $maxSeq + 1;
    }

    public function saveMeta(Request $request, int $id)
    {
        $data = $request->validate([
            'transferencia' => ['nullable', 'string', 'max:60'],
            'lote_interno'  => ['nullable', 'string', 'max:60'],
            'lote'          => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        $op = OrdenProduccion::findOrFail($id);

        $op->transferencia = $data['transferencia'] ?? null;
        $op->lote_interno  = $data['lote_interno'] ?? null;
        $op->lote          = $data['lote'] ?? null;

        $op->save();

        return response()->json(['ok' => true]);
    }

}
