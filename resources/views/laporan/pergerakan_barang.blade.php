<!DOCTYPE html>
<html>

<head>
    <title>Laporan Pergerakan Barang</title>
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

        <?php
        $path = public_path('images/logo.png');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        ?>

        <img src="{{ $base64 }}" alt="Logo" style="width: 100px; height: auto;">

        <h1>Laporan Pergerakan Barang</h1>
        <div class="periode">
            Periode: {{ $tanggalMulai->format('d/m/Y') }} - {{ $tanggalSelesai->format('d/m/Y') }}
        </div>
    </div>

    <div class="section-title">Laporan Pemasukan Barang</div>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Jumlah Stok Masuk</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($laporanPemasukan as $item)
                <tr>
                    <td>{{ $item->barang->nama }}</td>
                    <td style="text-align: center;">{{ $item->stock }}</td>
                    <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty-data">Tidak ada data pemasukan</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>
