<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'recetas';       // Nombre de la tabla
    protected $primaryKey = 'id_receta'; // Clave primaria

    public $timestamps = false; // No tienes campos created_at / updated_at

    protected $fillable = [
        'so',
        'codigo_formula',
        'fecha',
        'cedula_medico',
        'paciente',
    ];
}
