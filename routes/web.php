<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\KosController;
use App\Http\Controllers\PencarianController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });


//admin
Route::get('/admin/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

// Daftar kos
Route::get('/admin/kos', [KosController::class, 'index'])->name('kos.index');

// // Form tambah kos
// Route::get('/admin/kos/create', [KosController::class, 'create'])->name('kos.create');

// Simpan kos baru
Route::post('/admin/kos', [KosController::class, 'store'])->name('kos.store');

// Tampilkan detail kos
Route::get('/admin/kos/{kos}', [KosController::class, 'show'])->name('kos.show');

// Form edit kos
Route::get('/admin/kos/{kos}/edit', [KosController::class, 'edit'])->name('kos.edit');

// Update data kos
Route::put('/admin/kos/{kos}', [KosController::class, 'update'])->name('kos.update');
Route::patch('/admin/kos/{kos}', [KosController::class, 'update']); // optional

// Hapus kos
Route::delete('/admin/kos/{kos}', [KosController::class, 'destroy'])->name('kos.destroy');

use App\Http\Controllers\FasilitasController;

// Halaman index
Route::get('/admin/fasilitas', [FasilitasController::class, 'index'])->name('fasilitas.index');

// Simpan fasilitas baru
Route::post('/admin/fasilitas', [FasilitasController::class, 'store'])->name('fasilitas.store');

// Update fasilitas
Route::put('/admin/fasilitas/{fasilitas}', [FasilitasController::class, 'update'])->name('fasilitas.update');

// Hapus fasilitas
Route::delete('/admin/fasilitas/{fasilitas}', [FasilitasController::class, 'destroy'])->name('fasilitas.destroy');

//user
Route::get('/',[HomeController::class, 'index']);

Route::get('/pencarian', [PencarianController::class, 'index'])->name('pencarian.index');
Route::get('/pencarian/filter', [PencarianController::class, 'filter'])->name('pencarian.filter');