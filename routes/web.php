<?php

use App\Http\Controllers\Admin\SegmentadorAdminController;
use App\Http\Controllers\SegmentoClienteController;
use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/segmentos/criar');
Route::redirect('/dashboard', '/segmentos/criar')->name('dashboard.redirect');

Route::get('/segmentos', [SegmentoClienteController::class, 'index'])->name('segmentos.index');
Route::get('/segmentos/criar', [SegmentoClienteController::class, 'create'])->name('segmentos.create');
Route::post('/segmentos/interpretar', [SegmentoClienteController::class, 'interpretar'])->name('segmentos.interpretar');
Route::post('/segmentos/manual', [SegmentoClienteController::class, 'manual'])->name('segmentos.manual');
Route::get('/segmentos/campo-opcoes', [SegmentoClienteController::class, 'campoOpcoes'])->name('segmentos.campoOpcoes');
Route::post('/segmentos/preview', [SegmentoClienteController::class, 'preview'])->name('segmentos.preview');
Route::post('/segmentos', [SegmentoClienteController::class, 'store'])->name('segmentos.store');

Route::get('/segmentos/modelos', [SegmentoClienteController::class, 'presets'])->name('segmentos.modelos');
Route::redirect('/segmentos/presets', '/segmentos/modelos')->name('segmentos.presets');
Route::post('/segmentos/presets/{id}/usar', [SegmentoClienteController::class, 'usarPreset'])->name('segmentos.presets.usar');

Route::get('/segmentos/{id}/tecnico', [SegmentoClienteController::class, 'tecnico'])->name('segmentos.tecnico');
Route::get('/segmentos/{id}', [SegmentoClienteController::class, 'show'])->name('segmentos.show');
Route::get('/segmentos/{id}/editar', [SegmentoClienteController::class, 'edit'])->name('segmentos.edit');
Route::put('/segmentos/{id}', [SegmentoClienteController::class, 'update'])->name('segmentos.update');
Route::delete('/segmentos/{id}', [SegmentoClienteController::class, 'destroy'])->name('segmentos.destroy');
Route::post('/segmentos/{id}/preview', [SegmentoClienteController::class, 'refreshPreview'])->name('segmentos.refreshPreview');
Route::post('/segmentos/{id}/validar', [SegmentoClienteController::class, 'validar'])->name('segmentos.validar');
Route::post('/segmentos/{id}/reprovar', [SegmentoClienteController::class, 'reprovar'])->name('segmentos.reprovar');
Route::get('/segmentos/{id}/exportar', [SegmentoClienteController::class, 'exportar'])->name('segmentos.exportar');

Route::redirect('/clientes', '/segmentos/criar');
Route::get('/clientes/importar', [ClienteController::class, 'importForm'])->name('clientes.importForm');
Route::post('/clientes/importar', [ClienteController::class, 'import'])->name('clientes.import');

Route::get('/admin/login', [SegmentadorAdminController::class, 'loginForm'])->name('admin.login');
Route::post('/admin/login', [SegmentadorAdminController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [SegmentadorAdminController::class, 'logout'])->name('admin.logout');

Route::middleware('segmentador.admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [SegmentadorAdminController::class, 'index'])->name('index');
    Route::get('/segmentos/{id}', [SegmentadorAdminController::class, 'show'])->name('show');
    Route::post('/segmentos/{id}/aprovar', [SegmentadorAdminController::class, 'aprovar'])->name('aprovar');
    Route::post('/segmentos/{id}/reprovar', [SegmentadorAdminController::class, 'reprovar'])->name('reprovar');
    Route::post('/segmentos/{id}/analise', [SegmentadorAdminController::class, 'emAnalise'])->name('analise');
    Route::delete('/segmentos/{id}', [SegmentadorAdminController::class, 'destroy'])->name('destroy');
    Route::get('/clientes', [SegmentadorAdminController::class, 'clientes'])->name('clientes');
    Route::get('/logs', [SegmentadorAdminController::class, 'logs'])->name('logs');
});
