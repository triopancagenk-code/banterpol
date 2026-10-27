<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        $sheetTitle = substr('Laporan_Stok_Barang_' . strtolower(\Illuminate\Support\Str::slug($monthName ?? 'oktober')) . '_' . ($selectedYear ?? 2026), 0, 31);
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>' . e($sheetTitle) . '</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        .section-header {
            background-color: #D0CECE;
            color: #000000;
            font-weight: bold;
            font-size: 11pt;
            text-align: center;
            vertical-align: middle;
            border: 0.5pt solid #cbd5e1;
        }
        .th-cell {
            background-color: #ffffff;
            color: #000000;
            font-weight: normal;
            font-size: 11pt;
            text-align: center;
            vertical-align: middle;
            border: 0.5pt solid #cbd5e1;
            padding: 5px;
        }
        .th-spacer {
            border: none;
            background: transparent;
        }
        .data-cell {
            border: 0.5pt solid #e2e8f0;
            padding: 5px;
            font-size: 10pt;
            color: #000000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .bg-negative { background-color: #fee2e2; color: #dc2626; font-weight: bold; }
        .bg-zero { background-color: #fef2f2; color: #991b1b; }
        .bg-low { background-color: #fef3c7; color: #b45309; }
    </style>
</head>
<body>
    @php
        $maxRows = max($materials->count(), $incoming->count(), $outgoing->count());
    @endphp

    <table>
        <!-- Row 1: Section Headers (17 cols: A..Q) -->
        <tr>
            <td></td>
            <td colspan="2" class="section-header">LAPORAN STOK BARANG </td>
            <td colspan="6"></td>
            <td colspan="2" class="section-header">BARANG MASUK</td>
            <td colspan="3"></td>
            <td colspan="2" class="section-header">Barang Keluar</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="17"></td>
        </tr>
        <tr>
            <td class="font-bold">Tgl Stok Awal</td>
            <td>{{ $tglAwal ?? '00/00/2026' }}</td>
            <td colspan="15"></td>
        </tr>
        <tr>
            <td class="font-bold">Tgl Closing</td>
            <td>{{ $tglClosing ?? '00/00/2026' }}</td>
            <td colspan="15"></td>
        </tr>
        <tr>
            <td colspan="17"></td>
        </tr>

        <!-- Table Headers (17 Columns A..Q) -->
        <thead>
            <tr>
                <!-- Col A (1) -->
                <th class="th-cell" style="width: 250px;">Kode Barang</th>
                <!-- Col B (2) -->
                <th class="th-cell" style="width: 80px;">Bulan</th>
                <!-- Col C (3) -->
                <th class="th-cell" style="width: 300px;">Nama Barang</th>
                <!-- Col D (4) -->
                <th class="th-cell" style="width: 80px;">Stok Awal</th>
                <!-- Col E (5) -->
                <th class="th-cell" style="width: 95px;">Barang Masuk</th>
                <!-- Col F (6) -->
                <th class="th-cell" style="width: 95px;">Barang Keluar</th>
                <!-- Col G (7) -->
                <th class="th-cell" style="width: 80px;">Stok Akhir</th>

                <!-- Col H (8): Spacer without border -->
                <th class="th-spacer" style="width: 100px;"></th>

                <!-- Col I (9) -->
                <th class="th-cell" style="width: 85px;">Tgl</th>
                <!-- Col J (10) -->
                <th class="th-cell" style="width: 160px;">Nama Barang</th>
                <!-- Col K (11) -->
                <th class="th-cell" style="width: 95px;">Jumlah Masuk</th>
                <!-- Col L (12) -->
                <th class="th-cell" style="width: 120px;">Catatan</th>

                <!-- Col M (13): Spacer without border -->
                <th class="th-spacer" style="width: 90px;"></th>

                <!-- Col N (14) -->
                <th class="th-cell" style="width: 85px;">Tgl</th>
                <!-- Col O (15) -->
                <th class="th-cell" style="width: 160px;">Nama Barang</th>
                <!-- Col P (16) -->
                <th class="th-cell" style="width: 95px;">Jumlah Keluar</th>
                <!-- Col Q (17) -->
                <th class="th-cell" style="width: 120px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $maxRows; $i++)
                @php
                    $m = $materials->get($i);
                    $in = $incoming->get($i);
                    $out = $outgoing->get($i);

                    $stockClass = '';
                    if ($m) {
                        if ($m->stock < 0) {
                            $stockClass = 'bg-negative';
                        } elseif ($m->stock == 0) {
                            $stockClass = 'bg-zero';
                        } elseif ($m->stock <= $m->min_stock) {
                            $stockClass = 'bg-low';
                        }
                    }
                @endphp
                <tr>
                    <!-- Col A: Kode Barang -->
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $m ? $m->code : '' }}</td>
                    <!-- Col B: Bulan -->
                    <td class="data-cell text-center">{{ $m ? ($m->display_month ?? $m->month ?? ($monthName ?? 'Oktober')) : '' }}</td>
                    <!-- Col C: Nama Barang -->
                    <td class="data-cell text-left">{{ $m ? $m->name : '' }}</td>
                    <!-- Col D: Stok Awal -->
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ $m ? \App\Models\Material::formatQty($m->computed_initial_stock ?? $m->initial_stock) : '' }}</td>
                    <!-- Col E: Barang Masuk -->
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ $m ? \App\Models\Material::formatQty($m->computed_total_in ?? $m->total_in) : '' }}</td>
                    <!-- Col F: Barang Keluar -->
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ $m ? \App\Models\Material::formatQty($m->computed_total_out ?? $m->total_out) : '' }}</td>
                    <!-- Col G: Stok Akhir -->
                    <td class="data-cell text-right font-bold {{ $stockClass }}" style="mso-number-format:'\#\,\#\#0\.0';">{{ $m ? \App\Models\Material::formatQty($m->computed_stock ?? $m->stock) : '' }}</td>

                    <!-- Col H: Spacer -->
                    <td style="border: none; background: transparent;"></td>

                    <!-- Col I: Tgl Masuk -->
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $in ? ($in->transaction_date ? $in->transaction_date->format('d/m/Y') : $in->created_at->format('d/m/Y')) : '' }}</td>
                    <!-- Col J: Nama Barang Masuk -->
                    <td class="data-cell text-left">{{ $in && $in->material ? $in->material->name : ($in ? '-' : '') }}</td>
                    <!-- Col K: Jumlah Masuk -->
                    <td class="data-cell text-right font-bold" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ $in ? \App\Models\Material::formatQty($in->amount) : '' }}</td>
                    <!-- Col L: Catatan Masuk -->
                    <td class="data-cell text-left">{{ $in ? str_replace([' (Barang Masuk)', ' [Import]'], '', $in->notes ?? '') : '' }}</td>

                    <!-- Col M: Spacer -->
                    <td style="border: none; background: transparent;"></td>

                    <!-- Col N: Tgl Keluar -->
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $out ? ($out->transaction_date ? $out->transaction_date->format('d/m/Y') : $out->created_at->format('d/m/Y')) : '' }}</td>
                    <!-- Col O: Nama Barang Keluar -->
                    <td class="data-cell text-left">{{ $out && $out->material ? $out->material->name : ($out ? '-' : '') }}</td>
                    <!-- Col P: Jumlah Keluar -->
                    <td class="data-cell text-right font-bold" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ $out ? \App\Models\Material::formatQty($out->amount) : '' }}</td>
                    <!-- Col Q: Catatan Keluar -->
                    <td class="data-cell text-left">{{ $out ? str_replace([' (Barang Keluar)', ' [Import]'], '', $out->notes ?? '') : '' }}</td>
                </tr>
            @endfor
            <!-- Total Baris Bawah (17 cols) -->
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="3" class="data-cell text-center">TOTAL</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ \App\Models\Material::formatQty($materials->sum('initial_stock')) }}</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ \App\Models\Material::formatQty($materials->sum('total_in')) }}</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ \App\Models\Material::formatQty($materials->sum('total_out')) }}</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ \App\Models\Material::formatQty($materials->sum('stock')) }}</td>
                <td style="border: none; background: transparent;"></td>
                <td colspan="2" class="data-cell text-center">TOTAL MASUK</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ \App\Models\Material::formatQty($incoming->sum('amount')) }}</td>
                <td class="data-cell"></td>
                <td style="border: none; background: transparent;"></td>
                <td colspan="2" class="data-cell text-center">TOTAL KELUAR</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ \App\Models\Material::formatQty($outgoing->sum('amount')) }}</td>
                <td class="data-cell"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
