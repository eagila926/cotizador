<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Receta;
use App\Models\Formula;
use App\Models\Medico;

class RecetasLoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $recetaIds;

    public function __construct(array $recetaIds)
    {
        $this->recetaIds = $recetaIds;
    }

    public function build()
    {
        // Cargar recetas + relaciones (medico será el mismo, pero no asumimos)
        $recetas = Receta::whereIn('id_receta', $this->recetaIds)->get();

        // Trae fórmulas e items para cada receta
        $data = $recetas->map(function($r){
        $formula = Formula::with('items')->where('codigo', $r->codigo_formula)->first();
        $items   = $formula?->items ?? collect();
        $medico  = Medico::where('cedula', $r->cedula_medico)->first();

        // Nombre DR/A con fallbacks
        $doctorDisplay = '';
        if ($medico) {
            $doctorDisplay = trim(($medico->nombres ?? '').' '.($medico->apellidos ?? ''));
            if ($doctorDisplay === '') $doctorDisplay = $medico->nombre ?? '';
            if ($doctorDisplay === '') $doctorDisplay = $medico->name   ?? '';
        }
        if ($doctorDisplay === '' && $formula?->medico) {
            $doctorDisplay = $formula->medico;
        }

        // Firma base64 (ambas rutas posibles)
        $firmaBase64 = null;
        if ($medico) {
            if (!empty($medico->firma_path)) {
                $p = $medico->firma_path;
                $rel = \Illuminate\Support\Str::startsWith($p, 'public/') ? \Illuminate\Support\Str::after($p, 'public/') : $p;
                if (\Storage::disk('public')->exists($rel)) {
                    $firmaBase64 = base64_encode(\Storage::disk('public')->get($rel));
                }
                if (!$firmaBase64 && file_exists(public_path($p))) {
                    $firmaBase64 = base64_encode(file_get_contents(public_path($p)));
                }
                if (!$firmaBase64 && \Storage::exists($p)) {
                    $firmaBase64 = base64_encode(\Storage::get($p));
                }
            }
            if (!$firmaBase64) {
                $probe = 'firmas/'.$medico->cedula.'.png';
                if (\Storage::disk('public')->exists($probe)) {
                    $firmaBase64 = base64_encode(\Storage::disk('public')->get($probe));
                } else {
                    $probe2 = public_path('images/firmas/'.$medico->cedula.'.png');
                    if (file_exists($probe2)) {
                        $firmaBase64 = base64_encode(file_get_contents($probe2));
                    }
                }
            }
        }

        return [
            'r'             => $r,
            'formula'       => $formula,
            'items'         => $items,
            'medico'        => $medico,
            'doctorDisplay' => $doctorDisplay, // <—
            'firmaBase64'   => $firmaBase64,   // <—
        ];
    });


        // PDF multipágina: una “página” por receta
        $pdf = Pdf::loadView('recetas.lote_pdf', ['lote' => $data])->setPaper('a4');

        // $recetas ya lo tienes cargado arriba
        $sos = $recetas->pluck('so')->filter()->unique()->values();

        if ($sos->count() === 1) {
            $soText = $sos[0];
        } else {
            // Muestra hasta 3 SOs y el resto como “+N más”
            $soText = $sos->take(3)->implode(', ');
            if ($sos->count() > 3) {
                $soText .= ' +' . ($sos->count() - 3) . ' más';
            }
        }

        $asunto = 'Nuevas recetas SO ' . $soText . ' (' . count($this->recetaIds) . ')';
        $nombreAdj = 'Recetas-'.now()->format('Ymd_His').'.pdf';

        // Usamos una vista simple para el cuerpo del correo
        return $this->subject($asunto)
            ->markdown('emails.recetas.lote', [
                'total' => count($this->recetaIds),
            ])
            ->attachData($pdf->output(), $nombreAdj, ['mime' => 'application/pdf']);
    }
}
