<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void
    {
        if (!Schema::hasTable('activo_temps')) {
        Schema::create('activo_temps', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('user_id');
        $table->integer('cod_odoo'); // FK lógica a activos.cod_odoo
        $table->string('activo', 255); // nombre mostrado (cache)
        $table->decimal('cantidad', 12, 3);
        $table->enum('unidad', ['g','mg','mcg','UI']);
        $table->timestamps();


        $table->unique(['user_id','cod_odoo']); // impide duplicados para el mismo usuario
        $table->index('cod_odoo');
        });
        }
    }


public function down(): void
{
Schema::dropIfExists('activo_temps');
}
};