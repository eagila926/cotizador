<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenImpresion extends Model
{
    protected $table = 'ordenes_produccion_impresiones';

    protected $fillable = [
        'orden_id',
        'printed_by',
        'printed_at',
        'copies',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'copies' => 'integer',
    ];

    public $timestamps = true;
}
