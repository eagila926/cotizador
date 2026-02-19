<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\FormulasEstController;
use App\Http\Controllers\OrdenProduccionController;
use App\Models\OrdenProduccion;
use App\Models\OrdenImpresion;

Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware('auth')->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Usuarios (si aplica)
    Route::get('/usuarios',                   [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear',             [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios',                  [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar',  [UserController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{usuario}',         [UserController::class,'update'])->name('usuarios.update');

    // ===== Fórmulas nuevas =====
    Route::prefix('formulas')->name('formulas.')->group(function () {

        Route::view('/nuevas', 'formulas.nuevas')->name('nuevas');
        Route::get('/recientes', [FormulaController::class, 'recientes'])->name('recientes');

        Route::post('/buscar-producto', [FormulaController::class, 'buscarProducto'])->name('buscar');
        Route::post('/agregar-temp',    [FormulaController::class, 'agregarTemp'])->name('agregar');
        Route::get ('/listar-temp',     [FormulaController::class, 'listarTemp'])->name('listar');
        Route::post('/eliminar-temp',   [FormulaController::class, 'eliminarTemp'])->name('eliminar');
        Route::post('/eliminar-todos',  [FormulaController::class, 'eliminarTodos'])->name('eliminarTodos');

        Route::get('/resumen-capsulas', [FormulaController::class, 'resumenCapsulas'])->name('resumen_capsulas');

        Route::post('/guardar', [FormulaController::class, 'guardar'])->name('guardar');

        // Cargar ítems de una fórmula a activo_temps para edición
        Route::get('/{id}/editar', [FormulaController::class, 'cargarParaEditar'])->name('editar.cargar');
    });

    // ===== Fórmulas establecidas =====
    Route::prefix('formulas/establecidas')->name('fe.')->group(function () {

        Route::get('/',            [FormulasEstController::class, 'index'])->name('index');
        Route::get('/buscar',      [FormulasEstController::class, 'buscar'])->name('buscar');
        Route::post('/add',        [FormulasEstController::class, 'add'])->name('add');
        Route::post('/update-tipo',[FormulasEstController::class, 'updateTipo'])->name('update'); // NO-OP
        Route::delete('/{id}',     [FormulasEstController::class, 'remove'])->name('remove');
        Route::delete('/clear/all',[FormulasEstController::class, 'clear'])->name('clear');

        Route::get('/{id}/print',  [FormulasEstController::class, 'print'])->name('print');

        Route::get('/{id}/items',         [FormulasEstController::class, 'items'])->name('items');
        Route::get('/{id}/items/export',  [FormulasEstController::class, 'itemsExportXlsx'])->name('items.export');

        Route::post('/update-prices', [FormulasEstController::class, 'updatePrices'])->name('updatePrices');

        // actualizar celulosa dentro de la vista items
        Route::post('/{id}/celulosa', [FormulasEstController::class, 'updateCelulosa'])->name('updateCelulosa');

        Route::get('/{id}/items/print', [FormulasEstController::class, 'itemsPrint'])->name('items.print');
    });

    Route::prefix('ordenes-produccion')->name('op.')->group(function () {
        Route::post('/', [OrdenProduccionController::class, 'store'])->name('store');
        Route::post('/{id}/print-log', [OrdenProduccionController::class, 'printLog'])->name('printLog');
         // guardar transferencia / lote_interno / lote
        Route::post('/{id}/meta', [OrdenProduccionController::class, 'saveMeta'])->name('meta');
        });
        

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
