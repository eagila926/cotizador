<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\FormulasEstController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Rutas autenticadas
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */
    Route::get('/usuarios/crear', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');

    /*
    |--------------------------------------------------------------------------
    | Fórmulas (creación / edición)
    |--------------------------------------------------------------------------
    */
    Route::prefix('formulas')->name('formulas.')->group(function () {

        Route::view('/nuevas', 'formulas.nuevas')->name('nuevas');
        Route::get('/recientes', [FormulaController::class, 'recientes'])->name('recientes');

        Route::post('/buscar-producto', [FormulaController::class, 'buscarProducto'])->name('buscar');
        Route::post('/agregar-temp',    [FormulaController::class, 'agregarTemp'])->name('agregar');
        Route::get ('/listar-temp',     [FormulaController::class, 'listarTemp'])->name('listar');
        Route::post('/eliminar-temp',   [FormulaController::class, 'eliminarTemp'])->name('eliminar');
        Route::post('/eliminar-todos',  [FormulaController::class, 'eliminarTodos'])->name('eliminarTodos');

        Route::get ('/resumen-capsulas', [FormulaController::class, 'resumenCapsulas'])->name('resumen_capsulas');

        Route::post('/guardar', [FormulaController::class, 'guardar'])->name('guardar');

        // Cargar una fórmula para edición
        Route::get('/{id}/editar', [FormulaController::class, 'cargarParaEditar'])->name('editar.cargar');
    });

    /*
    |--------------------------------------------------------------------------
    | Fórmulas establecidas
    |--------------------------------------------------------------------------
    */
    Route::prefix('formulas/establecidas')->name('fe.')->group(function () {
        Route::get('/',            [FormulasEstController::class,'index'])->name('index');
        Route::get('/buscar',      [FormulasEstController::class,'buscar'])->name('buscar');
        Route::post('/add',        [FormulasEstController::class,'add'])->name('add');
        Route::post('/update-tipo',[FormulasEstController::class,'updateTipo'])->name('update');
        Route::delete('/{id}',     [FormulasEstController::class,'remove'])->name('remove');
        Route::delete('/clear/all',[FormulasEstController::class,'clear'])->name('clear');

        Route::get('/{id}/print',  [FormulasEstController::class,'print'])->name('print');
        Route::get('/{id}/excel',  [FormulasEstController::class,'excel'])->name('excel');

        // Ítems
        Route::get('/{id}/items', [FormulasEstController::class,'items'])->name('items');
        Route::get('/{id}/items/export', [FormulasEstController::class,'itemsExportXlsx'])->name('items.export');

        // Guardar precios
        Route::post('/update-prices', [FormulasEstController::class, 'updatePrices'])->name('updatePrices');

        // ✅ Celulosa 3291 editable (POST)
        Route::post('/{id}/celulosa', [FormulasEstController::class, 'updateCelulosa'])->name('updateCelulosa');
    });


    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
