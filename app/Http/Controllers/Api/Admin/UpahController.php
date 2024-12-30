<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Karyawan;
use App\Models\Upah;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UpahController extends Controller
{
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
        $startDate = Carbon::parse($startDate)->startOfDay();

        while ($startDate->isWeekend()) {
            $startDate->addDay();
        }

        $endDate = $startDate->copy();
        $workDays = 0;

        while ($workDays < 4) {
            $endDate->addDay();
            if (!$endDate->isWeekend()) {
                $workDays++;
            }
        }

        return [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ];
    }

    private function getWorkingDays($startDate, $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $workingDays = 0;

        foreach ($period as $date) {
            if (!$date->isWeekend()) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    public function index()
    {
        try {
            $query = Upah::with([
                'karyawan.user' => function ($query) {
                    $query->select('id', 'nama_lengkap', 'email', 'created_at');
                }
            ]);

            if (Auth::user()->role !== UserRole::Admin) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data karyawan tidak ditemukan'
                    ], 404);
                }
                $query->where('id_karyawan', $karyawan->id);
            }

            $upah = $query->orderBy('periode_mulai', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diambil',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_karyawan' => 'required|exists:karyawan,id',
            'total_dikerjakan' => 'required|integer|min:0',
            'total_upah' => 'required|integer|min:0',
            'periode_mulai' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $karyawan = Karyawan::with('user')->find($request->id_karyawan);
            if (!$karyawan || !$karyawan->user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data karyawan tidak lengkap'
                ], 404);
            }

            $periode = $this->calculatePeriodDates($request->periode_mulai);
            $mingguKe = $this->calculateWeekNumber(
                $karyawan->user->created_at,
                $periode['start']
            );

            $existingUpah = Upah::where('id_karyawan', $request->id_karyawan)
                ->where(function ($query) use ($periode) {
                    $query->whereBetween('periode_mulai', [$periode['start'], $periode['end']])
                        ->orWhereBetween('periode_selesai', [$periode['start'], $periode['end']]);
                })->first();

            if ($existingUpah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sudah ada data upah untuk periode ini'
                ], 422);
            }

            $data = array_merge($request->all(), [
                'minggu_ke' => $mingguKe,
                'periode_mulai' => $periode['start'],
                'periode_selesai' => $periode['end']
            ]);

            $upah = Upah::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil ditambahkan',
                'data' => $upah
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $upah = Upah::with([
                'karyawan.user' => function ($query) {
                    $query->select('id', 'nama_lengkap', 'email', 'created_at');
                }
            ])->find($id);

            if (!$upah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan'
                ], 404);
            }

            if (Auth::user()->role !== UserRole::Admin) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan || $upah->id_karyawan !== $karyawan->id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized access'
                    ], 403);
                }
            }

            $upah->working_days = $this->getWorkingDays(
                $upah->periode_mulai,
                $upah->periode_selesai
            );

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diambil',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByWeek($weekNumber)
    {
        try {
            $query = Upah::with([
                'karyawan.user' => function ($query) {
                    $query->select('id', 'nama_lengkap', 'email', 'created_at');
                }
            ])->where('minggu_ke', $weekNumber);

            if (Auth::user()->role !== UserRole::Admin) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data karyawan tidak ditemukan'
                    ], 404);
                }
                $query->where('id_karyawan', $karyawan->id);
            }

            $upah = $query->orderBy('periode_mulai', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diambil',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data upah',
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

        $validator = Validator::make($request->all(), [
            'id_karyawan' => 'required|exists:karyawan,id',
            'total_dikerjakan' => 'required|integer|min:0',
            'total_upah' => 'required|integer|min:0',
            'periode_mulai' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $upah = Upah::find($id);
            if (!$upah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan'
                ], 404);
            }

            $karyawan = Karyawan::with('user')->find($request->id_karyawan);
            if (!$karyawan || !$karyawan->user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data karyawan tidak lengkap'
                ], 404);
            }

            $periode = $this->calculatePeriodDates($request->periode_mulai);
            $mingguKe = $this->calculateWeekNumber(
                $karyawan->user->created_at,
                $periode['start']
            );

            $existingUpah = Upah::where('id_karyawan', $request->id_karyawan)
                ->where('id', '!=', $id)
                ->where(function ($query) use ($periode) {
                    $query->whereBetween('periode_mulai', [$periode['start'], $periode['end']])
                        ->orWhereBetween('periode_selesai', [$periode['start'], $periode['end']]);
                })->first();

            if ($existingUpah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sudah ada data upah untuk periode ini'
                ], 422);
            }

            $data = array_merge($request->all(), [
                'minggu_ke' => $mingguKe,
                'periode_mulai' => $periode['start'],
                'periode_selesai' => $periode['end']
            ]);

            $upah->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diupdate',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $upah = Upah::find($id);
            if (!$upah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan'
                ], 404);
            }

            $upah->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}