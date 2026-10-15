<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Stok Barang</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        .header-title {
            font-size: 16pt;
            font-weight: bold;
            color: #b91c1c;
            text-align: center;
        }
        .sub-title {
            font-size: 10pt;
            color: #475569;
            text-align: center;
        }
        .table-header {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #cbd5e1;
            padding: 8px;
        }
        .data-cell {
            border: 0.5pt solid #cbd5e1;
            padding: 6px;
            font-size: 10pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bg-safe { background-color: #ecfdf5; color: #047857; font-weight: bold; }
        .bg-warning { background-color: #fffbeb; color: #b45309; font-weight: bold; }
        .bg-danger { background-color: #fef2f2; color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="10" class="header-title">LAPORAN INVENTARIS STOK BARANG BANTERPOOL</td>
        </tr>
        <tr>
            <td colspan="10" class="sub-title">Diekspor pada: {{ date('d F Y, H:i:s') }} WIB | Filter Kategori: {{ $categoryFilter === 'all' ? 'Semua' : $categoryFilter }} | Status: {{ ucfirst($statusFilter) }}</td>
        </tr>
        <tr>
            <td colspan="10"></td>
        </tr>
        <thead>
            <tr>
                <th class="table-header" style="width: 40px;">No</th>
                <th class="table-header" style="width: 120px;">Kode Barang</th>
                <th class="table-header" style="width: 250px;">Nama Barang</th>
                <th class="table-header" style="width: 150px;">Kategori</th>
                <th class="table-header" style="width: 80px;">Satuan</th>
                <th class="table-header" style="width: 90px;">Stok Fisik</th>
                <th class="table-header" style="width: 90px;">Batas Min</th>
                <th class="table-header" style="width: 100px;">Status</th>
                <th class="table-header" style="width: 130px;">Harga Satuan (Rp)</th>
                <th class="table-header" style="width: 140px;">Total Nilai Aset (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalUnits = 0;
                $totalAsset = 0;
            @endphp
            @forelse($materials as $index => $m)
                @php
                    $totalUnits += $m->stock;
                    $subtotal = $m->stock * ($m->unit_price ?? 0);
                    $totalAsset += $subtotal;
                    $statusClass = $m->stock <= 0 ? 'bg-danger' : ($m->stock <= $m->min_stock ? 'bg-warning' : 'bg-safe');
                    $statusText = $m->stock <= 0 ? 'Habis' : ($m->stock <= $m->min_stock ? 'Menipis' : 'Aman');
                @endphp
                <tr>
                    <td class="data-cell text-center">{{ $index + 1 }}</td>
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $m->code }}</td>
                    <td class="data-cell text-left">{{ $m->name }}</td>
                    <td class="data-cell text-center">{{ $m->category }}</td>
                    <td class="data-cell text-center">{{ $m->unit }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ $m->stock }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ $m->min_stock }}</td>
                    <td class="data-cell text-center {{ $statusClass }}">{{ $statusText }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ $m->unit_price }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ $subtotal }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="data-cell text-center">Tidak ada data barang yang cocok.</td>
                </tr>
            @endforelse
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="5" class="data-cell text-center">TOTAL KESELURUHAN</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ $totalUnits }}</td>
                <td colspan="3" class="data-cell text-center">-</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ $totalAsset }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
