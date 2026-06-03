<?php

use App\Http\Controllers\SegmentoClienteController;
use App\Http\Controllers\ClienteController;
use App\Models\Cliente;
use App\Models\SegmentoCliente;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $totalClientes = Cliente::whereNull('excluido')->count();
    $totalSegmentos = SegmentoCliente::whereNull('excluido')->count();
    $segmentosValidados = SegmentoCliente::whereNull('excluido')->where('status_validacao', 'validada')->count();
    $clientesEmPrevias = (int) SegmentoCliente::whereNull('excluido')->sum('ultima_previa_qtd');
    $segmentosRecentes = SegmentoCliente::whereNull('excluido')->orderByDesc('segmento_cliente_id')->limit(8)->get();

    return view('dashboard', compact('totalClientes', 'totalSegmentos', 'segmentosValidados', 'clientesEmPrevias', 'segmentosRecentes'));
})->name('dashboard');

Route::get('/dashboard', fn () => redirect()->route('dashboard'))->name('dashboard.redirect');

Route::get('/segmentos', [SegmentoClienteController::class, 'index'])->name('segmentos.index');
Route::get('/segmentos/criar', [SegmentoClienteController::class, 'create'])->name('segmentos.create');
Route::post('/segmentos/interpretar', [SegmentoClienteController::class, 'interpretar'])->name('segmentos.interpretar');
Route::post('/segmentos/manual', [SegmentoClienteController::class, 'manual'])->name('segmentos.manual');
Route::get('/segmentos/campo-opcoes', [SegmentoClienteController::class, 'campoOpcoes'])->name('segmentos.campoOpcoes');
Route::post('/segmentos/preview', [SegmentoClienteController::class, 'preview'])->name('segmentos.preview');
Route::post('/segmentos', [SegmentoClienteController::class, 'store'])->name('segmentos.store');

Route::get('/segmentos/presets', [SegmentoClienteController::class, 'presets'])->name('segmentos.presets');
Route::post('/segmentos/presets/{id}/usar', [SegmentoClienteController::class, 'usarPreset'])->name('segmentos.presets.usar');

Route::get('/segmentos/{id}', [SegmentoClienteController::class, 'show'])->name('segmentos.show');
Route::get('/segmentos/{id}/editar', [SegmentoClienteController::class, 'edit'])->name('segmentos.edit');
Route::put('/segmentos/{id}', [SegmentoClienteController::class, 'update'])->name('segmentos.update');
Route::delete('/segmentos/{id}', [SegmentoClienteController::class, 'destroy'])->name('segmentos.destroy');
Route::post('/segmentos/{id}/preview', [SegmentoClienteController::class, 'refreshPreview'])->name('segmentos.refreshPreview');
Route::post('/segmentos/{id}/validar', [SegmentoClienteController::class, 'validar'])->name('segmentos.validar');
Route::post('/segmentos/{id}/reprovar', [SegmentoClienteController::class, 'reprovar'])->name('segmentos.reprovar');
Route::get('/segmentos/{id}/exportar', [SegmentoClienteController::class, 'exportar'])->name('segmentos.exportar');

Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
Route::get('/clientes/criar', [ClienteController::class, 'create'])->name('clientes.create');
Route::get('/clientes/importar', [ClienteController::class, 'importForm'])->name('clientes.importForm');
Route::post('/clientes/importar', [ClienteController::class, 'import'])->name('clientes.import');
Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
Route::get('/clientes/exportar-csv', [ClienteController::class, 'exportCsv'])->name('clientes.exportCsv');
Route::get('/clientes/{id}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
Route::delete('/clientes/{id}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
