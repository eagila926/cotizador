<?php

namespace App\Mail;

use App\Models\Receta;
use App\Models\Formula;
use App\Models\Medico;
use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str; // <— agrega

class RecetaCreadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $recetaId;

    public function __construct(int $recetaId) { $this->recetaId = $recetaId; }

    public function build()
    {
        $receta  = Receta::findOrFail($this->recetaId);
        $formula = Formula::with('items')->where('codigo', $receta->codigo_formula)->first();
        $items   = $formula?->items ?? collect();
        $medico  = Medico::where('cedula', $receta->cedula_medico)->first();

        // === Nombre DR/A con fallbacks
        $doctorDisplay = '';
        if ($medico) {
            $doctorDisplay = trim(($medico->nombres ?? '').' '.($medico->apellidos ?? ''));
            if ($doctorDisplay === '') $doctorDisplay = $medico->nombre  ?? '';
            if ($doctorDisplay === '') $doctorDisplay = $medico->name    ?? '';
        }
        if ($doctorDisplay === '' && $formula?->medico) {
            $doctorDisplay = $formula->medico;
        }

        // === Firma base64: intenta storage/public y public/images/firmas
        $firmaBase64 = null;
        if ($medico) {
            // 1) si hay firma_path en BD
            if (!empty($medico->firma_path)) {
                $p = $medico->firma_path;

                // a) disco 'public' (quita prefijo 'public/' si viene)
                $rel = Str::startsWith($p, 'public/') ? Str::after($p, 'public/') : $p;
                if (Storage::disk('public')->exists($rel)) {
                    $firmaBase64 = base64_encode(Storage::disk('public')->get($rel));
                }

                // b) carpeta public/… (por ejemplo images/firmas/…)
                if (!$firmaBase64 && file_exists(public_path($p))) {
                    $firmaBase64 = base64_encode(file_get_contents(public_path($p)));
                }

                // c) último intento: ruta tal cual en storage por si acaso
                if (!$firmaBase64 && Storage::exists($p)) {
                    $firmaBase64 = base64_encode(Storage::get($p));
                }
            }

            // 2) convención por cédula
            if (!$firmaBase64) {
                // storage/app/public/firmas/{cedula}.png
                $probe = 'firmas/'.$medico->cedula.'.png';
                if (Storage::disk('public')->exists($probe)) {
                    $firmaBase64 = base64_encode(Storage::disk('public')->get($probe));
                } else {
                    // public/images/firmas/{cedula}.png
                    $probe2 = public_path('images/firmas/'.$medico->cedula.'.png');
                    if (file_exists($probe2)) {
                        $firmaBase64 = base64_encode(file_get_contents($probe2));
                    }
                }
            }
        }

        // Generar PDF
        $pdf = Pdf::loadView('recetas.pdf', [
            'receta'        => $receta,
            'formula'       => $formula,
            'items'         => $items,
            'medico'        => $medico,
            'doctorDisplay' => $doctorDisplay, // <—
            'firmaBase64'   => $firmaBase64,   // <—
        ])->setPaper('a4');

        $nombreAdj = 'Receta-'.$receta->codigo_formula.'-SO'.$receta->so.'.pdf';

        return $this->subject('Receta SO '.$receta->so.' — '.$receta->codigo_formula)
            ->markdown('emails.recetas.creada', [
                'receta'     => $receta,
                'formula'    => $formula,
                'items'      => $items,
                'medico'     => $medico,
                'linkReceta' => route('recetas.show', $receta),
            ])
            ->attachData($pdf->output(), $nombreAdj, ['mime' => 'application/pdf']);
    }
}
