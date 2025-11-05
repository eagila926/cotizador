<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('formulas_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 30)->index();         // Código de fórmula (p.ej. FOEAG9.000123)
            $table->unsignedInteger('cod_odoo');           // 2da columna
            $table->string('activo', 255);                 // 3ra columna
            $table->string('unidad', 20)->nullable();      // 4ta columna
            $table->decimal('masa_mes', 18, 6)->nullable();// 9na columna (g) — puede ser NULL
            $table->timestamps();

            $table->index(['codigo', 'cod_odoo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulas_items');
    }
};

