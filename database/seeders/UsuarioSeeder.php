<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('usuarios')->insert([
            'nombre'   => 'Admin',
            'apellido' => 'Escollanos',
            'correo'   => 'admin@escollanos.com',
            'rol'      => 'ADMIN',
            'password' => Hash::make('admin123'), // se guarda hasheada
            'estado'   => 1,
        ]);
    }
}
