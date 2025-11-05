<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class Activo extends Model
{
    protected $table = 'activos';
    protected $primaryKey = 'cod_odoo';
    public $incrementing = false; // PK manual
    protected $keyType = 'int';
    public $timestamps = false; // según tu tabla


    protected $fillable = [
        'cod_odoo','nombre','valor_costo','factor','minimo',
        'maximo','unidad','factor_venta','densidad'
        ];
}