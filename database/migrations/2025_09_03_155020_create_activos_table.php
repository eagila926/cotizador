<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // crea sólo si no existe (útil si ya la tienes en MySQL)
        if (!Schema::hasTable('activos')) {
            Schema::create('activos', function (Blueprint $table) {
                // PK manual (no autoincremental)
                $table->integer('cod_odoo')->primary();

                $table->string('nombre', 255);

                // muchos decimales en valor_costo -> 15,8 suele ser seguro
                $table->decimal('valor_costo', 15, 8)->nullable();

                // factor: con 4 decimales suele bastar
                $table->decimal('factor', 10, 4)->default(1);

                // en tu BD se ven valores como "25 mg", "500 mg" => guardar como texto
                $table->string('minimo', 50)->nullable();
                $table->string('maximo', 50)->nullable();

                $table->string('unidad', 10)->nullable();

                // factor_venta: 4 decimales suele ser suficiente
                $table->decimal('factor_venta', 10, 4)->nullable();

                // densidad: 4 decimales suele ser suficiente
                $table->decimal('densidad', 10, 4)->nullable();


                // sin timestamps (no aparecen en tu tabla)
                // $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Si es una tabla “legada”, podrías NO eliminarla en down()
        Schema::dropIfExists('activos');
    }
};
