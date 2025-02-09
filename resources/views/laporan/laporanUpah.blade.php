<!DOCTYPE html>
<html>

<head>
    <title>Laporan Upah Staff Produksi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .periode {
            font-size: 14px;
            color: #666;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0;
            padding-left: 5px;
            border-left: 4px solid #3498db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
        }

        th {
            background: #3498db;
            color: white;
            padding: 12px;
            font-size: 13px;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 12px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .empty-data {
            text-align: center;
            color: #666;
            padding: 20px;
            font-style: italic;
        }

        .footer {
            text-align: right;
            margin-top: 30px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="header">
        @php
        $path = public_path('images/logo.png');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        @endphp

        <img src="{{ $base64 }}" alt="Logo" style="width: 100px; height: auto;">
        <h1>Laporan Upah Staff Produksi</h1>
        <div class="periode">
            @if(isset($request))
            Periode: {{ \Carbon\Carbon::parse($request->periode_mulai)->format('d/m/Y') }} - {{
            \Carbon\Carbon::parse($request->periode_selesai)->format('d/m/Y') }}
            @else
            Semua Periode
            @endif
        </div>
    </div>

    <div class="section-title">Detail Upah</div>
    <table>
        <thead>
            <tr>
                <th>Nama Staff Produksi</th>
                <th>Total Dikerjakan</th>
                <th>Total Upah</th>
                <th>Periode Mulai</th>
                <th>Periode Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($upahList as $upah)
            <tr>
                <td>{{ $upah->staff_produksi->user->nama_lengkap }}</td>
                <td style="text-align: center;">{{ $upah->total_dikerjakan }}</td>
                <td>Rp {{ number_format($upah->total_upah, 0, ',', '.') }}</td>
                <td>{{ \Carbon\Carbon::parse($upah->periode_mulai)->format('d/m/Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($upah->periode_selesai)->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="empty-data">Tidak ada data upah</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>