<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'medicos';

    // Clave primaria
    protected $primaryKey = 'cedula';

    // Como la PK es varchar, le decimos a Eloquent que no es incremental
    public $incrementing = false;

    // Tipo de la clave primaria
    protected $keyType = 'string';

    // Si no usas created_at / updated_at
    public $timestamps = false;

    // Campos asignables en masa
    protected $fillable = [
        'cedula',
        'full_name',
        'centro_medico',
        'correo',
        'direccion',
        'telefono',
        'user_id',
    ];

    /**
     * Relación con la tabla usuarios (si existe).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }
}
