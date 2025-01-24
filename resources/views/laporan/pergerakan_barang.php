<!DOCTYPE html>
<html>

<head>
    <title>Laporan Pergerakan Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Pergerakan Barang</h1>
        <p>Periode: {{ $tanggalMulai->format('d/m/Y') }} - {{ $tanggalSelesai->format('d/m/Y') }}</p>
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
                <td>{{ $item->stock }}</td>
                <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center;">Tidak ada data pemasukan</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Laporan Pengeluaran Barang</div>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>Jumlah Stok Keluar</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($laporanPengeluaran as $item)
            <tr>
                <td>{{ $item->barang->nama }}</td>
                <td>{{ abs($item->stock) }}</td>
                <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center;">Tidak ada data pengeluaran</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 20px;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>

</html>