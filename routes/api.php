<?php
use App\Http\Controllers\Api\Admin\LaporanBarangController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\BarangController;
use App\Http\Controllers\Api\Admin\UpahController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\KaryawanController;
use App\Http\Controllers\Api\Admin\KategoriController;
use App\Http\Controllers\Api\Admin\BarangHarianController;
use Illuminate\Http\Request;

// Auth Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');

// Public Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);
});

// Routes (Admin)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // Acitivity Log
    Route::get('/logs', function (Request $request) {
        $query = \Spatie\Activitylog\Models\Activity::query();

        $query->whereHas('causer', function ($query) {
            $query->where('role', 'Staff');
        });

        if ($request->has('action')) {
            $query->where('properties->action', $request->action);
        }

        if ($request->has('actions')) {
            $actions = explode(',', $request->actions);
            $query->whereIn('properties->action', $actions);
        }

        $logs = $query->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Log aktivitas berhasil diambil',
            'data' => $logs,
        ]);
    })->name('admin.logs');



    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/{id}', [UserController::class, 'show'])->name('users.show');
        Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Karyawan Management
    Route::prefix('karyawan')->group(function () {
        Route::get('/', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::post('/', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::get('/{id}', [KaryawanController::class, 'show'])->name('karyawan.show');
        Route::put('/{id}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
        Route::get('/search', [KaryawanController::class, 'search'])->name('karyawan.search');
    });

    // Upah Management
    Route::prefix('upah')->group(function () {
        Route::get('/', [UpahController::class, 'index'])->name('upah.index');
        Route::get('/{id}', [UpahController::class, 'show'])->name('upah.show');
        Route::get('/week/{weekNumber}', [UpahController::class, 'getByWeek'])->name('upah.getByWeek');
        Route::post('/', [UpahController::class, 'store'])->name('upah.store');
        Route::put('/{id}', [UpahController::class, 'update'])->name('upah.update');
        Route::delete('/{id}', [UpahController::class, 'destroy'])->name('upah.destroy');
    });

    // Kategori Management
    Route::prefix('kategori')->group(function () {
        Route::get('/', [KategoriController::class, 'index'])->name('kategori.index');
        Route::get('/{id}', [KategoriController::class, 'show'])->name('kategori.show');
        Route::post('/', [KategoriController::class, 'store'])->name('kategori.store');
        Route::put('/{id}', [KategoriController::class, 'update'])->name('kategori.update');
        Route::delete('/{id}', [KategoriController::class, 'destroy'])->name('kategori.destroy');
    });

    // Barang Management
    Route::prefix('barang')->group(function () {
        Route::get('/', [BarangController::class, 'index'])->name('barang.index');
        Route::get('/{id}', [BarangController::class, 'show'])->name('barang.show');
        Route::post('/', [BarangController::class, 'store'])->name('barang.store');
        Route::put('/{id}', [BarangController::class, 'update'])->name('barang.update');
        Route::delete('/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');
    });

    // Barang Harian Management
    Route::prefix('barang-harian')->group(function () {
        Route::get('/', [BarangHarianController::class, 'index'])->name('barang-harian.index');
        Route::get('/{id}', [BarangHarianController::class, 'show'])->name('barang-harian.show');
        Route::post('/', [BarangHarianController::class, 'store'])->name('barang-harian.store');
        Route::put('/{id}', [BarangHarianController::class, 'update'])->name('barang-harian.update');
        Route::delete('/{id}', [BarangHarianController::class, 'destroy'])->name('barang-harian.destroy');
    });

    Route::prefix('stock')->group(function () {
        Route::post('/{id}', [BarangController::class, 'addStock'])->name('admin.stock.add');
    });

    // Laporan Barang Management
    Route::prefix('laporan-barang')->group(function () {
        Route::get('/barang', [LaporanBarangController::class, 'generateLaporanBarang']);
        Route::get('/ringkasan-barang', [LaporanBarangController::class, 'ringkasanPergerakanBarang']);
        Route::get('/download-pdf', [LaporanBarangController::class, 'downloadPDF']);
        Route::get('/download-all-pdf', [LaporanBarangController::class, 'downloadAllPDF']);
    });

});

// Routes (Staff)
Route::prefix('staff')->middleware(['auth:sanctum', 'staff', 'log.activity'])->group(function () {

    Route::prefix('users')->group(function () {
        Route::post('/register/karyawan', [UserController::class, 'store'])
            ->name('staff.users.registerKaryawan');
    });

    // Kategori Management
    Route::prefix('kategori')->group(function () {
        Route::post('/', [KategoriController::class, 'store'])->name('staff.kategori.store');
        Route::get('/', [KategoriController::class, 'index'])->name('staff.kategori.index');
        Route::get('/{id}', [KategoriController::class, 'show'])->name('staff.kategori.show');
    });

    // Upah Management
    Route::prefix('upah')->group(function () {
        Route::post('/', [UpahController::class, 'store'])->name('staff.upah.store');
    });

    // Barang Management
    Route::prefix('barang')->group(function () {
        Route::post('/', [BarangController::class, 'store'])->name('staff.barang.store');
        Route::get('/', [BarangController::class, 'index'])->name('staff.barang.index');
        Route::get('/{id}', [BarangController::class, 'show'])->name('staff.barang.show');
    });

    // Stock Management
    Route::prefix('stock')->group(function () {
        Route::post('/{id}', [BarangController::class, 'addStock'])->name('staff.stock.add');
    });
});
