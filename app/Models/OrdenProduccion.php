<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenProduccion extends Model
{
    protected $table = 'ordenes_produccion';

    protected $fillable = [
        'numero',
        'fecha_produccion',
        'created_by',
        'transferencia',
        'lote_interno',
        'lote',
    ];

    protected $casts = [
        'fecha_produccion' => 'datetime',
        'lote' => 'integer',
    ];

    public $timestamps = true; // si tienes created_at/updated_at
}
