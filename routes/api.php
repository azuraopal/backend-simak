<?php
use App\Enums\UserRole;
use App\Http\Controllers\Api\Admin\LaporanBarangController;
use App\Http\Controllers\Api\Admin\LaporanUpahController;
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\BarangController;
use App\Http\Controllers\Api\Admin\UpahController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\StaffProduksiController;
use App\Http\Controllers\Api\Admin\KategoriController;
use App\Http\Controllers\Api\Admin\BarangHarianController;
use Illuminate\Http\Request;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/users/forgot-password/email', [UserController::class, 'sendResetLinkEmail']);
Route::post('/users/forgot-password/reset/email', [UserController::class, 'reset']);
Route::post('/users/forgot-password/send-code', [ForgotPasswordController::class, 'sendResetCode']);
Route::post('/users/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/users/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::post('/users/change-password', [UserController::class, 'changePassword']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::get('/logs', function (Request $request) {
        $query = \Spatie\Activitylog\Models\Activity::query();

        $query->whereHas('causer', function ($q) {
            $q->where('role', UserRole::StaffAdministrasi);
        });

        if ($request->has('action')) {
            $query->where('properties->action', $request->action);
        }

        if ($request->has('actions')) {
            $actions = explode(',', $request->actions);
            $query->whereIn('properties->action', $actions);
        }

        if ($request->has('model')) {
            $modelClass = 'App\\Models\\' . ucfirst($request->model);
            $query->where('subject_type', $modelClass);
        }

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'message' => 'Log aktivitas berhasil diambil',
            'data' => $logs,
        ]);
    })->middleware(['auth:sanctum', 'admin'])->name('admin.logs');

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/{id}', [UserController::class, 'show'])->name('users.show');
        Route::put('/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::prefix('staff-produksi')->group(function () {
        Route::get('/', [StaffProduksiController::class, 'index'])->name('staffProduksi.index');
        Route::post('/', [StaffProduksiController::class, 'store'])->name('staffProduksi.store');
        Route::get('/{id}', [StaffProduksiController::class, 'show'])->name('staffProduksi.show');
        Route::put('/{id}', [StaffProduksiController::class, 'update'])->name('staffProduksi.update');
        Route::delete('/{id}', [StaffProduksiController::class, 'destroy'])->name('staffProduksi.destroy');
        Route::get('/search', [StaffProduksiController::class, 'search'])->name('staffProduksi.search');
    });

    Route::prefix('upah')->group(function () {
        Route::get('/', [UpahController::class, 'index'])->name('upah.index');
        Route::get('/{id}', [UpahController::class, 'show'])->name('upah.show');
        Route::get('/week/{weekNumber}', [UpahController::class, 'getByWeek'])->name('upah.getByWeek');
        Route::post('/', [UpahController::class, 'store'])->name('upah.store');
        Route::put('/{id}', [UpahController::class, 'update'])->name('upah.update');
        Route::delete('/{id}', [UpahController::class, 'destroy'])->name('upah.destroy');
    });

    Route::prefix('kategori')->group(function () {
        Route::get('/', [KategoriController::class, 'index'])->name('kategori.index');
        Route::get('/{id}', [KategoriController::class, 'show'])->name('kategori.show');
        Route::post('/', [KategoriController::class, 'store'])->name('kategori.store');
        Route::put('/{id}', [KategoriController::class, 'update'])->name('kategori.update');
        Route::delete('/{id}', [KategoriController::class, 'destroy'])->name('kategori.destroy');
    });

    Route::prefix('barang')->group(function () {
        Route::get('/', [BarangController::class, 'index'])->name('barang.index');
        Route::get('/{id}', [BarangController::class, 'show'])->name('barang.show');
        Route::post('/', [BarangController::class, 'store'])->name('barang.store');
        Route::put('/{id}', [BarangController::class, 'update'])->name('barang.update');
        Route::delete('/{id}', [BarangController::class, 'destroy'])->name('barang.destroy');
    });

    Route::prefix('barang-harian')->group(function () {
        Route::get('/', [BarangHarianController::class, 'index'])->name('barang-harian.index');
        Route::get('/{id}', [BarangHarianController::class, 'show'])->name('barang-harian.show');
        Route::post('/', [BarangHarianController::class, 'store'])->name('barang-harian.store');
        Route::put('/{id}', [BarangHarianController::class, 'update'])->name('barang-harian.update');
        Route::delete('/{id}', [BarangHarianController::class, 'destroy'])->name('barang-harian.destroy');
        Route::post('/{id}/approve', [BarangHarianController::class, 'approve'])->name('barang-harian.approve');
        Route::post('/{id}/reject', [BarangHarianController::class, 'reject'])->name('barang-harian.reject');
    });

    Route::prefix('stock')->group(function () {
        Route::post('/{id}', [BarangController::class, 'addStock'])->name('admin.stock.add');
    });

    Route::prefix('laporan-barang')->group(function () {
        Route::get('/barang', [LaporanBarangController::class, 'generateLaporanBarang']);
        Route::get('/ringkasan-barang', [LaporanBarangController::class, 'ringkasanPergerakanBarang']);
        Route::get('/download-pdf', [LaporanBarangController::class, 'downloadPDF']);
        Route::get('/download-all-pdf', [LaporanBarangController::class, 'downloadAllPDF']);
    });

    Route::prefix('laporan-upah')->group(function () {
        Route::get('/print-all', [LaporanUpahController::class, 'printAll'])->name('laporan-upah.print-all');
        Route::post('/print-filtered', [LaporanUpahController::class, 'printFiltered'])->name('laporan-upah.print-filtered');
    });
});

Route::prefix('staff-administrasi')->middleware(['auth:sanctum', 'staff', 'log.activity'])->group(function () {

    Route::prefix('users')->group(function () {
        Route::post('/register/staff-produksi', [UserController::class, 'store'])
            ->name('staff-administrasi.users.registerStaffProduksi');
    });

    Route::prefix('kategori')->group(function () {
        Route::post('/', [KategoriController::class, 'store'])->name('staff-administrasi.kategori.store');
        Route::get('/', [KategoriController::class, 'index'])->name('staff-administrasi.kategori.index');
        Route::put('/{id}', [KategoriController::class, 'update'])->name('staff-administrasi.kategori.update');
        Route::get('/{id}', [KategoriController::class, 'show'])->name('staff-administrasi.kategori.show');
    });

    Route::prefix('staff-produksi')->group(function () {
        Route::post('/', [StaffProduksiController::class, 'store'])->name('staff-administrasi.store');
        Route::get('/', [StaffProduksiController::class, 'index'])->name('staff-administrasi.index');
        Route::put('/{id}', [StaffProduksiController::class, 'update'])->name('staff-administrasi.update');
        Route::get('/{id}', [StaffProduksiController::class, 'show'])->name('staff-administrasi.show');
    });

    Route::prefix('upah')->group(function () {
        Route::post('/', [UpahController::class, 'store'])->name('staff-administrasi.upah.store');
        Route::get('/{id}', [UpahController::class, 'show'])->name('staff-administrasi.upah.show');
        Route::get('/', [UpahController::class, 'index'])->name('staff-administrasi.upah.index');
    });

    Route::prefix('barang')->group(function () {
        Route::post('/', [BarangController::class, 'store'])->name('staff-administrasi.barang.store');
        Route::get('/', [BarangController::class, 'index'])->name('staff-administrasi.barang.index');
        Route::put('/{id}', [BarangController::class, 'update'])->name('staff-administrasi.barang.update');
        Route::post('/{id}', [BarangController::class, 'show'])->name('staff-administrasi.barang.show');
    });

    Route::prefix('barang-harian')->group(function () {
        Route::post('/', [BarangHarianController::class, 'store'])->name('staff-administrasi.barang-harian.store');
        Route::get('/', [BarangHarianController::class, 'index'])->name('staff-administrasi.barang-harian.index');
        Route::put('/{id}', [BarangHarianController::class, 'update'])->name('staff-administrasi.barang-harian.update');
        Route::get('/{id}', [BarangHarianController::class, 'show'])->name('staff-administrasi.barang-harian.show');
        Route::get('/pengajuan/list', [BarangHarianController::class, 'pendingList'])->name('staff-administrasi.barang-harian.pendinglist');
        Route::post('/{id}/approve', [BarangHarianController::class, 'approve'])->name('staff-administrasi.barang-harian.approve');
        Route::post('/{id}/reject', [BarangHarianController::class, 'reject'])->name('staff-administrasi.barang-harian.reject');
    });

    Route::prefix('stock')->group(function () {
        Route::post('/{id}', [BarangController::class, 'addStock'])->name('staff-administrasi.stock.add');
    });
});

Route::prefix('staff-produksi')->middleware(['auth:sanctum', 'log.activity'])->group(function () {

    Route::prefix('barang')->group(function () {
        Route::get('/', [BarangController::class, 'index'])->name('staff-produksi.barang.index');
        Route::get('/{id}', [BarangController::class, 'show'])->name('staff-produksi.barang.show');
    });

    Route::prefix('upah')->group(function () {
        Route::get('/', [UpahController::class, 'indexSelf'])->name('staff-produksi.upah.index');
        Route::get('/{id}', [UpahController::class, 'showSelf'])->name('staff-produksi.upah.show');
    });

    Route::prefix('barang-harian')->group(function () {
        Route::get('/', [BarangHarianController::class, 'indexSelf'])->name('staff-produksi.barang-harian.index');
        Route::get('/{id}', [BarangHarianController::class, 'showSelf'])->name('staff-produksi.barang-harian.show');
        Route::post('/pengajuan', [BarangHarianController::class, 'pengajuanStore'])->name('staff-produksi.barang-harian.pengajuan.store');
    });

    Route::prefix('kategori')->group(function () {
        Route::get('/', [KategoriController::class, 'index'])->name('staff-produksi.kategori.index');
        Route::get('/{id}', [KategoriController::class, 'show'])->name('staff-produksi.kategori.show');
    });

});