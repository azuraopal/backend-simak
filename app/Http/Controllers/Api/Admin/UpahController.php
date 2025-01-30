<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Karyawan;
use App\Models\Upah;
use App\Models\BarangHarian;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpahController extends Controller
{

    public function index()
    {
        try {
            $upahQuery = Upah::with(['karyawan.user:id,nama_lengkap,email,created_at']);

            if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::Staff])) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();

                if (!$karyawan) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data karyawan tidak ditemukan'
                    ], 404);
                }

                $upahQuery->where('karyawan_id', $karyawan->id);
            }

            $upahList = $upahQuery->orderBy('periode_mulai', 'desc')->get();

            if ($upahList->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan',
                ], 404);
            }

            $upahList = $upahList->map(function ($upah) {
                $detailPerhitungan = DB::table('barang_harian as bh')
                    ->join('barang as b', 'b.id', '=', 'bh.barang_id')
                    ->where('bh.karyawan_id', $upah->karyawan_id)
                    ->whereBetween('bh.tanggal', [$upah->periode_mulai, $upah->periode_selesai])
                    ->select(
                        'b.nama as nama_barang',
                        'b.upah as upah_per_kodi',
                        'bh.tanggal',
                        'bh.jumlah_dikerjakan',
                        DB::raw('(bh.jumlah_dikerjakan * b.upah) as subtotal')
                    )
                    ->orderBy('bh.tanggal')
                    ->get();

                return [
                    'id' => $upah->id,
                    'karyawan' => $upah->karyawan,
                    'minggu_ke' => $upah->minggu_ke,
                    'total_dikerjakan' => $upah->total_dikerjakan,
                    'total_upah' => $upah->total_upah,
                    'periode_mulai' => $upah->periode_mulai,
                    'periode_selesai' => $upah->periode_selesai,
                    'detail_perhitungan' => $detailPerhitungan
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diambil',
                'data' => $upahList
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }


    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::Staff])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'karyawan_id' => [
                    'required',
                    'exists:karyawan,id',
                    function ($attribute, $value, $fail) {
                        $karyawan = Karyawan::find($value);
                        if (!$karyawan || !$karyawan->user) {
                            $fail('Data karyawan tidak lengkap atau tidak valid.');
                        }
                    },
                ],
                'periode_mulai' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        $date = Carbon::parse($value);
                        if ($date->isWeekend() && config('app.env') === 'production') {
                            $fail('Tanggal mulai tidak boleh di akhir pekan.');
                        }
                        if ($date->gt(Carbon::now())) {
                            $fail('Tanggal mulai tidak boleh lebih dari hari ini.');
                        }
                    },
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $karyawan = Karyawan::with('user')->findOrFail($request->karyawan_id);

            $periode = $this->calculatePeriodDates($request->periode_mulai);

            if (!$this->validatePeriod($periode['start'], $periode['end'], $karyawan) && config('app.env') === 'production') {
                return response()->json([
                    'status' => false,
                    'message' => 'Periode tidak valid untuk karyawan ini'
                ], 422);
            }

            $existingUpah = $this->checkExistingUpah($karyawan->id, $periode['start'], $periode['end']);
            if ($existingUpah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sudah ada data upah untuk periode ini'
                ], 422);
            }

            $mingguKe = $this->calculateWeekNumber(
                $karyawan->user->created_at,
                $periode['start']
            );


            $barangHarian = $this->validateBarangHarian(
                $karyawan->id,
                $periode['start'],
                $periode['end']
            );

            if (!$barangHarian['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $barangHarian['message']
                ], 422);
            }

            $totals = $this->calculateTotals(
                $karyawan->id,
                $periode['start'],
                $periode['end']
            );

            $upah = Upah::create([
                'karyawan_id' => $karyawan->id,
                'minggu_ke' => $mingguKe,
                'total_dikerjakan' => $totals->total_dikerjakan,
                'total_upah' => $totals->total_upah,
                'periode_mulai' => $periode['start'],
                'periode_selesai' => $periode['end']
            ]);


            $detailPerhitungan = $this->getDetailPerhitungan(
                $karyawan->id,
                $periode['start'],
                $periode['end']
            );

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil ditambahkan',
                'data' => [
                    'upah' => $upah,
                    'detail_perhitungan' => $detailPerhitungan,
                    'periode' => [
                        'minggu_ke' => $mingguKe,
                        'tanggal_mulai' => $periode['start'],
                        'tanggal_selesai' => $periode['end']
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $upah = Upah::with(['karyawan.user'])->findOrFail($id);

            $detailPerhitungan = $upah->detailPerhitungan()
                ->whereDate('barang_harian.tanggal', '>=', $upah->periode_mulai)
                ->whereDate('barang_harian.tanggal', '<=', $upah->periode_selesai)
                ->get()
                ->map(function ($item) {
                    return [
                        'nama_barang' => $item->nama_barang,
                        'upah_per_kodi' => $item->upah_per_kodi,
                        'tanggal' => $item->tanggal,
                        'jumlah_dikerjakan' => $item->jumlah_dikerjakan,
                        'subtotal' => $item->jumlah_dikerjakan * $item->upah_per_kodi
                    ];
                });

            $periode = [
                'minggu_ke' => $upah->minggu_ke,
                'tanggal_mulai' => $upah->periode_mulai->format('Y-m-d'),
                'tanggal_selesai' => $upah->periode_selesai->format('Y-m-d')
            ];

            return response()->json([
                'status' => true,
                'message' => 'Detail upah berhasil diambil',
                'data' => [
                    'upah' => $upah,
                    'detail_perhitungan' => $detailPerhitungan,
                    'periode' => $periode
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil detail upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $upah = Upah::findOrFail($id);

            $upah->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function calculateWeekNumber($userCreatedAt, $targetDate)
    {
        $startDate = Carbon::parse($userCreatedAt)->startOfDay();
        $targetDate = Carbon::parse($targetDate)->startOfDay();

        if ($targetDate->lt($startDate)) {
            return 0;
        }

        $currentDate = $startDate->copy();
        $weekCount = 1;
        $workDaysInCurrentWeek = 0;

        while ($currentDate->lte($targetDate)) {
            if (!$currentDate->isWeekend()) {
                $workDaysInCurrentWeek++;

                if ($workDaysInCurrentWeek == 5) {
                    if ($currentDate->lt($targetDate)) {
                        $weekCount++;
                    }
                    $workDaysInCurrentWeek = 0;
                }
            }
            $currentDate->addDay();
        }

        return $weekCount;
    }



    private function calculatePeriodDates($startDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();


        while ($start->isWeekend()) {
            $start->addDay();
        }

        $end = $start->copy();
        $workDays = 0;

        while ($workDays < 5) {
            if (!$end->isWeekend()) {
                $workDays++;
            }
            if ($workDays < 5) {
                $end->addDay();
            }
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }

    private function validatePeriod($startDate, $endDate, $karyawan)
    {
        $start = Carbon::parse($startDate);
        $joinDate = Carbon::parse($karyawan->user->created_at);


        if ($start->lt($joinDate)) {
            return false;
        }

        return true;
    }

    private function checkExistingUpah($karyawanId, $startDate, $endDate)
    {
        return Upah::where('karyawan_id', $karyawanId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('periode_mulai', [$startDate, $endDate])
                    ->orWhereBetween('periode_selesai', [$startDate, $endDate]);
            })->first();
    }

    private function validateBarangHarian($karyawanId, $startDate, $endDate)
    {
        $barangHarian = BarangHarian::where('karyawan_id', $karyawanId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->count();

        if ($barangHarian === 0) {
            return [
                'status' => false,
                'message' => 'Tidak ada data barang yang dikerjakan pada periode ini'
            ];
        }

        return ['status' => true];
    }

    private function calculateTotals($karyawanId, $startDate, $endDate)
    {
        return DB::table('barang_harian as bh')
            ->join('barang as b', 'b.id', '=', 'bh.barang_id')
            ->where('bh.karyawan_id', $karyawanId)
            ->whereBetween('bh.tanggal', [$startDate, $endDate])
            ->select(
                DB::raw('COALESCE(SUM(bh.jumlah_dikerjakan), 0) as total_dikerjakan'),
                DB::raw('COALESCE(SUM(bh.jumlah_dikerjakan * b.upah), 0) as total_upah')
            )
            ->first();
    }

    private function getDetailPerhitungan($karyawanId, $startDate, $endDate)
    {
        return DB::table('barang_harian as bh')
            ->join('barang as b', 'b.id', '=', 'bh.barang_id')
            ->where('bh.karyawan_id', $karyawanId)
            ->whereBetween('bh.tanggal', [$startDate, $endDate])
            ->select(
                'b.nama as nama_barang',
                'b.upah as upah_per_kodi',
                'bh.tanggal',
                'bh.jumlah_dikerjakan',
                DB::raw('(bh.jumlah_dikerjakan * b.upah) as subtotal')
            )
            ->orderBy('bh.tanggal')
            ->get();
    }

    private function handleException(\Exception $e)
    {
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan sistem',
            'error' => $e->getMessage()
        ], 500);
    }
}