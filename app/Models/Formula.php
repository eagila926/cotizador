<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formula extends Model
{
    protected $table = 'formulas';

    protected $fillable = [
        'codigo',             // FOEAG9.000001
        'nombre_etiqueta',
        'user_id',
        'precio_medico',
        'precio_publico',
        'precio_distribuidor',
        'medico',
        'paciente',
        'tomas_diarias',
    ];

    protected $casts = [
        'precio_medico'       => 'decimal:2',
        'precio_publico'      => 'decimal:2',
        'precio_distribuidor' => 'decimal:2',
        'tomas_diarias'       => 'decimal:2',
    ];

    // Relación por 'codigo' (localKey) -> 'codigo' (foreignKey en items)
    public function items()
    {
        return $this->hasMany(FormulaItem::class, 'codigo', 'codigo');
    }
}
