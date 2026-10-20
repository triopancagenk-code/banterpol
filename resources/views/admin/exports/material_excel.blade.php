<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Stok Barang</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        .title-main {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
        }
        .header-laporan {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            font-size: 11pt;
            border: 0.5pt solid #cbd5e1;
            padding: 6px;
        }
        .header-in {
            background-color: #dcfce7;
            color: #166534;
            font-weight: bold;
            font-size: 11pt;
            border: 0.5pt solid #86efac;
            padding: 6px;
        }
        .header-out {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            font-size: 11pt;
            border: 0.5pt solid #fca5a5;
            padding: 6px;
        }
        .th-cell {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #cbd5e1;
            padding: 6px;
            font-size: 10pt;
        }
        .th-in {
            background-color: #15803d;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #cbd5e1;
            padding: 6px;
            font-size: 10pt;
        }
        .th-out {
            background-color: #b91c1c;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 0.5pt solid #cbd5e1;
            padding: 6px;
            font-size: 10pt;
        }
        .data-cell {
            border: 0.5pt solid #e2e8f0;
            padding: 5px;
            font-size: 9.5pt;
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
        <!-- Header Atas Sesuai CSV -->
        <tr>
            <td colspan="7" class="header-laporan text-center">LAPORAN STOK BARANG ({{ strtoupper($monthName ?? 'OKTOBER') }} {{ $selectedYear ?? 2026 }})</td>
            <td style="width: 20px;"></td>
            <td colspan="4" class="header-in text-center">BARANG MASUK</td>
            <td style="width: 20px;"></td>
            <td colspan="4" class="header-out text-center">Barang Keluar</td>
        </tr>
        <tr>
            <td colspan="18"></td>
        </tr>
        <tr>
            <td class="font-bold" colspan="2">Tgl Stok Awal</td>
            <td colspan="5">{{ $tglAwal ?? '01/10/2026' }}</td>
            <td></td>
            <td colspan="4"></td>
            <td></td>
            <td colspan="4"></td>
        </tr>
        <tr>
            <td class="font-bold" colspan="2">Tgl Closing</td>
            <td colspan="5">{{ $tglClosing ?? date('d/m/Y') }}</td>
            <td></td>
            <td colspan="4"></td>
            <td></td>
            <td colspan="4"></td>
        </tr>
        <tr>
            <td colspan="18"></td>
        </tr>

        <!-- Table Headers Sesuai Kolom CSV -->
        <thead>
            <tr>
                <!-- Master Laporan Stok -->
                <th class="th-cell" style="width: 90px;">Kode Barang</th>
                <th class="th-cell" style="width: 85px;">Bulan</th>
                <th class="th-cell" style="width: 250px;">Nama Barang</th>
                <th class="th-cell" style="width: 85px;">Stok Awal</th>
                <th class="th-cell" style="width: 90px;">Barang Masuk</th>
                <th class="th-cell" style="width: 90px;">Barang Keluar</th>
                <th class="th-cell" style="width: 85px;">Stok Akhir</th>

                <!-- Pembatas -->
                <th style="width: 20px; background-color: #f8fafc; border: none;"></th>

                <!-- Kolom Barang Masuk -->
                <th class="th-in" style="width: 85px;">Tgl</th>
                <th class="th-in" style="width: 180px;">Nama Barang</th>
                <th class="th-in" style="width: 95px;">Jumlah Masuk</th>
                <th class="th-in" style="width: 140px;">Catatan</th>

                <!-- Pembatas -->
                <th style="width: 20px; background-color: #f8fafc; border: none;"></th>

                <!-- Kolom Barang Keluar -->
                <th class="th-out" style="width: 85px;">Tgl</th>
                <th class="th-out" style="width: 180px;">Nama Barang</th>
                <th class="th-out" style="width: 95px;">Jumlah Keluar</th>
                <th class="th-out" style="width: 140px;">Catatan</th>
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
                    <!-- Master Material Data -->
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $m ? $m->code : '' }}</td>
                    <td class="data-cell text-center">{{ $m ? ($m->display_month ?? $m->month ?? ($monthName ?? 'Oktober')) : '' }}</td>
                    <td class="data-cell text-left">{{ $m ? $m->name : '' }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ $m ? \App\Models\Material::formatQty($m->computed_initial_stock ?? $m->initial_stock) : '' }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ $m ? \App\Models\Material::formatQty($m->computed_total_in ?? $m->total_in) : '' }}</td>
                    <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ $m ? \App\Models\Material::formatQty($m->computed_total_out ?? $m->total_out) : '' }}</td>
                    <td class="data-cell text-right font-bold {{ $stockClass }}" style="mso-number-format:'\#\,\#\#0\.0';">{{ $m ? \App\Models\Material::formatQty($m->computed_stock ?? $m->stock) : '' }}</td>

                    <!-- Pembatas -->
                    <td style="border: none; background-color: #f8fafc;"></td>

                    <!-- Barang Masuk Data -->
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $in ? ($in->transaction_date ? $in->transaction_date->format('d/m/Y') : $in->created_at->format('d/m/Y')) : '' }}</td>
                    <td class="data-cell text-left">{{ $in && $in->material ? $in->material->name : ($in ? '-' : '') }}</td>
                    <td class="data-cell text-right font-bold" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ $in ? \App\Models\Material::formatQty($in->amount) : '' }}</td>
                    <td class="data-cell text-left">{{ $in ? str_replace([' (Barang Masuk)', ' [Import]'], '', $in->notes ?? '') : '' }}</td>

                    <!-- Pembatas -->
                    <td style="border: none; background-color: #f8fafc;"></td>

                    <!-- Barang Keluar Data -->
                    <td class="data-cell text-center" style="mso-number-format:'\@';">{{ $out ? ($out->transaction_date ? $out->transaction_date->format('d/m/Y') : $out->created_at->format('d/m/Y')) : '' }}</td>
                    <td class="data-cell text-left">{{ $out && $out->material ? $out->material->name : ($out ? '-' : '') }}</td>
                    <td class="data-cell text-right font-bold" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ $out ? \App\Models\Material::formatQty($out->amount) : '' }}</td>
                    <td class="data-cell text-left">{{ $out ? str_replace([' (Barang Keluar)', ' [Import]'], '', $out->notes ?? '') : '' }}</td>
                </tr>
            @endfor
            <!-- Total Baris Bawah -->
            <tr style="font-weight: bold; background-color: #f1f5f9;">
                <td colspan="3" class="data-cell text-center">TOTAL</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ \App\Models\Material::formatQty($materials->sum('initial_stock')) }}</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ \App\Models\Material::formatQty($materials->sum('total_in')) }}</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ \App\Models\Material::formatQty($materials->sum('total_out')) }}</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ \App\Models\Material::formatQty($materials->sum('stock')) }}</td>
                <td style="border: none;"></td>
                <td colspan="2" class="data-cell text-center">TOTAL MASUK</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #166534;">{{ \App\Models\Material::formatQty($incoming->sum('amount')) }}</td>
                <td class="data-cell"></td>
                <td style="border: none;"></td>
                <td colspan="2" class="data-cell text-center">TOTAL KELUAR</td>
                <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0'; color: #991b1b;">{{ \App\Models\Material::formatQty($outgoing->sum('amount')) }}</td>
                <td class="data-cell"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
