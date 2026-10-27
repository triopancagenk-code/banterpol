<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data Pelanggan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        body { font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1E293B; }
        .title-table { margin-bottom: 12px; }
        .company-name { font-size: 16pt; font-weight: bold; color: #DC2626; }
        .report-title { font-size: 13pt; font-weight: bold; color: #0F172A; }
        .meta-info { font-size: 9pt; color: #64748B; }
        
        table.data-table { border-collapse: collapse; width: 100%; }
        table.data-table th {
            background-color: #FFFF00;
            color: #000000;
            font-weight: bold;
            font-size: 11pt;
            border: 1px solid #000000;
            padding: 8px 10px;
            text-align: center;
            vertical-align: middle;
        }
        table.data-table td {
            border: 1px solid #CBD5E1;
            padding: 6px 8px;
            font-size: 10pt;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .num-fmt { mso-number-format: "\#\,\#\#0"; text-align: right; }
        .txt-fmt { mso-number-format: "\@"; }
    </style>
</head>
<body>

    <table class="title-table" style="width: 100%;">
        <tr>
            <td colspan="9" class="company-name">BANTERPOOL INTERNET SERVICE PROVIDER</td>
        </tr>
        <tr>
            <td colspan="9" class="report-title">MASTER DATA PELANGGAN TERDAFTAR (ARSIP PENDAFTARAN FIBER BROADBAND)</td>
        </tr>
        <tr>
            <td colspan="9" class="meta-info">
                Waktu Unduh: {{ $now }} WIB | Filter Wilayah: Semua Wilayah | Filter Layanan: Semua Layanan | Filter Status: Semua Status | Total Data: {{ count($sampleData) }} Pelanggan
            </td>
        </tr>
        <tr><td colspan="9"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 200px;">Nama Pemohon</th>
                <th style="width: 180px;">PPOE</th>
                <th style="width: 180px;">No KTP</th>
                <th style="width: 150px;">No Handphone</th>
                <th style="width: 220px;">Email</th>
                <th style="width: 150px;">Jenis Layanan</th>
                <th style="width: 120px;">Harga</th>
                <th style="width: 320px;">Alamat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sampleData as $index => $s)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-bold">{{ $s['customer_name'] }}</td>
                    <td class="txt-fmt text-center">{{ $s['pppoe'] ?? '-' }}</td>
                    <td class="txt-fmt text-center">{{ $s['id_card_number'] ?? '-' }}</td>
                    <td class="txt-fmt text-center">{{ $s['customer_phone'] ?? '-' }}</td>
                    <td>{{ $s['customer_email'] ?? '-' }}</td>
                    <td class="text-center">{{ $s['package_name'] ?? '-' }}</td>
                    <td class="num-fmt">{{ (float) ($s['price'] ?? 0) }}</td>
                    <td>{{ $s['address'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
