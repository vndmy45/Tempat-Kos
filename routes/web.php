<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KategoriFasilitasController;
use App\Http\Controllers\KosController;
use App\Http\Controllers\PencarianController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PengujianController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SurveyKepuasanController;
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


Route::post('/komentar', [KosController::class, 'simpanKomentar'])->name('komentar.store');


Route::get('/logout', [AuthController::class, 'logout'])->name('logout');


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin'
])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:user'
])->group(function () {
    Route::get('/user/dashboard', [HomeController::class, 'index'])->name('user.index');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:user'
])->group(function () {
    Route::get('/pencarian', [PencarianController::class, 'index'])->name('pencarian.index');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group( function ()  {
    Route::get('/home', [RoleController::class, 'redirectUser'])->name('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:user'
])->group(function () {
    Route::get('/detailkos/{id}', [KosController::class, 'showUserKos'])->name('user.kos.show');
});


Route::post('/survey', [SurveyKepuasanController::class, 'store'])->name('survey.store');

Route::get('/pengujian/mae', [PengujianController::class, 'mae'])->name('pengujian.mae');

Route::resource('kategori-fasilitas', KategoriFasilitasController::class)->middleware('auth');

