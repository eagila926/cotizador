<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class ActivoTemp extends Model
{
    protected $table = 'activo_temps';
    protected $fillable = [
        'user_id','cod_odoo','activo','cantidad','unidad'
        ];


    public function activoRef()
    {
       return $this->belongsTo(Activo::class, 'cod_odoo', 'cod_odoo');
    }
}