<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\BarangHarian;
use App\Models\StaffProduksi;
use App\Models\Stock;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BarangHarianController extends Controller
{
    private function handleException(\Exception $e)
    {
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan sistem',
            'error' => $e->getMessage()
        ], 500);
    }

    public function index()
    {
        try {
            $query = BarangHarian::with(['barang', 'staff_produksi.user']);

            if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
                $staffAdministrasi = StaffProduksi::where('users_id', Auth::id())->first();
                if (!$staffAdministrasi) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data Staff Produksi tidak ditemukan'
                    ], 404);
                }
                $query->where('staff_produksi_id', $staffAdministrasi->id);
            }

            $barangHarian = $query->orderBy('tanggal', 'desc')->get();

            if ($barangHarian->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil diambil',
                'data' => $barangHarian
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'staff_produksi_id' => 'required|exists:staff_produksi,id',
                'barang_id' => 'required|exists:barang,id',
                'tanggal' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        $date = Carbon::parse($value);
                        if ($date->isWeekend() && config('app.env') === 'production') {
                            $fail('Tanggal tidak boleh di akhir pekan.');
                        }
                        if ($date->gt(Carbon::now())) {
                            $fail('Tanggal tidak boleh lebih dari hari ini.');
                        }
                    },
                ],
                'jumlah_dikerjakan' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            return DB::transaction(function () use ($request) {
                $barang = Barang::with([
                    'stock' => function ($query) {
                        $query->lockForUpdate();
                    }
                ])->findOrFail($request->barang_id);

                if (!$barang->stock) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Barang tidak ditemukan atau stok belum diatur'
                    ], 404);
                }

                $stokTersedia = $barang->stock->fresh()->stock;

                if ($stokTersedia <= 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Stok barang habis, tidak bisa dikerjakan lagi.'
                    ], 400);
                }

                if ($stokTersedia < $request->jumlah_dikerjakan) {
                    return response()->json([
                        'status' => false,
                        'message' => "Stok barang tidak mencukupi. Stok tersedia: $stokTersedia"
                    ], 400);
                }

                $newStock = $stokTersedia - $request->jumlah_dikerjakan;
                $barang->stock()->update(['stock' => $newStock]);

                $barangHarian = BarangHarian::create([
                    'staff_produksi_id' => $request->staff_produksi_id,
                    'barang_id' => $request->barang_id,
                    'tanggal' => $request->tanggal,
                    'jumlah_dikerjakan' => $request->jumlah_dikerjakan
                ]);

                if ($request->user()->role === UserRole::StaffAdministrasi) {
                    $staffProduksi = StaffProduksi::with('user')->find($request->staff_produksi_id);
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($barangHarian)
                        ->withProperties([
                            'action' => 'store',
                            'added_by_name' => $request->user()->nama_lengkap,
                            'barang_harian_data' => [
                                'staff_produksi' => $staffProduksi->nama,
                                'barang' => $barang->nama,
                                'tanggal' => $request->tanggal,
                                'jumlah_dikerjakan' => $request->jumlah_dikerjakan,
                                'stok_sebelumnya' => $stokTersedia,
                                'stok_sesudah' => $newStock
                            ]
                        ])
                        ->log("Staff '{$request->user()->nama_lengkap}' menambahkan data Barang Harian untuk staff '{$staffProduksi->nama}'");
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Data barang harian berhasil ditambahkan',
                    'data' => $barangHarian->load(['barang', 'staff_produksi.user'])
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'staff_produksi_id' => [
                    'required',
                    'exists:staff_produksi,id',
                    function ($attribute, $value, $fail) {
                        $staff_produksi = StaffProduksi::find($value);
                        if (!$staff_produksi || !$staff_produksi->user) {
                            $fail('Data staff_produksi tidak lengkap atau tidak valid.');
                        }
                    },
                ],
                'barang_id' => 'required|exists:barang,id',
                'tanggal' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        $date = Carbon::parse($value);
                        if ($date->isWeekend() && config('app.env') === 'production') {
                            $fail('Tanggal tidak boleh di akhir pekan.');
                        }
                        if ($date->gt(Carbon::now())) {
                            $fail('Tanggal tidak boleh lebih dari hari ini.');
                        }
                    },
                ],
                'jumlah_dikerjakan' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $barangHarian = BarangHarian::with(['barang', 'staff_produksi'])->find($id);
            if (!$barangHarian instanceof BarangHarian) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            $oldData = [
                'staff_produksi_id' => $barangHarian->staff_produksi_id,
                'barang_id' => $barangHarian->barang_id,
                'tanggal' => $barangHarian->tanggal,
                'jumlah_dikerjakan' => $barangHarian->jumlah_dikerjakan
            ];

            $barangHarian->update($request->only(['staff_produksi_id', 'barang_id', 'tanggal', 'jumlah_dikerjakan']));

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                $staffProduksi = StaffProduksi::with('user')->find($request->staff_produksi_id);

                if ($staffProduksi instanceof StaffProduksi) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($barangHarian)
                        ->withProperties([
                            'action' => 'update',
                            'updated_by_name' => $request->user()->nama_lengkap,
                            'updated_by_role' => $request->user()->role,
                            'old_data' => $oldData,
                            'new_data' => $request->all(),
                            'staff_produksi' => $staffProduksi->nama,
                            'barang' => $barangHarian->barang->nama
                        ])
                        ->log("Staff '{$request->user()->nama_lengkap}' memperbarui data Barang Harian untuk staff '{$staffProduksi->nama}'");
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil diupdate',
                'data' => $barangHarian->load(['barang', 'staff_produksi.user'])
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }


    public function show($id)
    {
        try {
            $barangHarian = BarangHarian::with(['barang', 'staff_produksi.user'])->find($id);

            if (!$barangHarian) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
                $staff_produksi = StaffProduksi::where('users_id', Auth::id())->first();
                if (!$staff_produksi || $barangHarian->staff_produksi_id !== $staff_produksi->id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized access'
                    ], 403);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil diambil',
                'data' => $barangHarian
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Request $request, $id)
    {
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $barangHarian = BarangHarian::with(['barang', 'staff_produksi'])->find($id);

            if (!$barangHarian) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                if ($barangHarian instanceof BarangHarian) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($barangHarian)
                        ->withProperties([
                            'action' => 'delete',
                            'deleted_by_name' => $request->user()->nama_lengkap,
                            'deleted_by_role' => $request->user()->role,
                            'barang_harian_data' => [
                                'staff_produksi' => optional($barangHarian->staff_produksi)->nama ?? 'Unknown',
                                'barang' => optional($barangHarian->barang)->nama ?? 'Unknown',
                                'tanggal' => $barangHarian->tanggal,
                                'jumlah_dikerjakan' => $barangHarian->jumlah_dikerjakan
                            ]
                        ])
                        ->log("Staff '{$request->user()->nama_lengkap}' menghapus data Barang Harian untuk staff '{$barangHarian->staff_produksi->nama}'");
                }
            }

            $barangHarian->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

}