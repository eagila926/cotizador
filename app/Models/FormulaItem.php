<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulaItem extends Model
{
    protected $table = 'formulas_items';

    protected $fillable = [
        'codigo','cod_odoo','activo','unidad','masa_mes','cantidad',
    ];

    // Al revés: el item "pertenece" a la fórmula vía 'codigo'
    public function formula()
    {
        return $this->belongsTo(Formula::class, 'codigo', 'codigo');
    }
}
