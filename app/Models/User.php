<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_user';

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'email',     // ← necesario para reset password
        'password',
        'rol',
        'estado'
    ];

    public function getEmailForPasswordReset() { return $this->correo; }

    public function hasRole($roles): bool
    {
        $roles = (array) $roles;

        // Admin siempre pasa
        if ($this->rol === 'Admin') {
            return true;
        }

        return in_array($this->rol, $roles, true);
    }
}
