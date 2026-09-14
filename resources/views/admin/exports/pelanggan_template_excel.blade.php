<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Template Pelanggan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        body { font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1E293B; }
        .title-table { margin-bottom: 12px; }
        .company-name { font-size: 15pt; font-weight: bold; color: #DC2626; }
        .report-title { font-size: 12pt; font-weight: bold; color: #0F172A; }
        .meta-info { font-size: 9pt; color: #64748B; }
        
        table.data-table { border-collapse: collapse; width: 100%; }
        table.data-table th {
            background-color: #0F172A;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 10pt;
            border: 1px solid #334155;
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
        .date-fmt { mso-number-format: "yyyy\-mm\-dd"; text-align: center; }
        .note-box {
            background-color: #FEF3C7;
            border: 1px solid #F59E0B;
            padding: 8px;
            font-size: 9pt;
            color: #92400E;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <table class="title-table" style="width: 100%;">
        <tr>
            <td colspan="12" class="company-name">BANTERPOOL INTERNET SERVICE PROVIDER</td>
        </tr>
        <tr>
            <td colspan="12" class="report-title">TEMPLATE IMPORT DATA PELANGGAN FIBER BROADBAND</td>
        </tr>
        <tr>
            <td colspan="12" class="meta-info">
                Waktu Unduh: {{ $now }} WIB | 
                Petunjuk: Kolom dengan tanda (*) wajib diisi. Baris contoh di bawah ini dapat dihapus atau diganti dengan data Anda.
            </td>
        </tr>
        <tr><td colspan="12"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 130px;">ID Pelanggan<br><small style="font-weight: normal; color: #94A3B8;">(Opsional/Kosongkan)</small></th>
                <th style="width: 180px;">No. KTP / NIK *<br><small style="font-weight: normal; color: #94A3B8;">(16 Digit Angka)</small></th>
                <th style="width: 200px;">Nama Lengkap Pelanggan *</th>
                <th style="width: 120px;">Tempat Lahir</th>
                <th style="width: 110px;">Tanggal Lahir<br><small style="font-weight: normal; color: #94A3B8;">(YYYY-MM-DD)</small></th>
                <th style="width: 140px;">No. Handphone (WA) *</th>
                <th style="width: 220px;">Alamat Email</th>
                <th style="width: 150px;">Jenis Layanan (Paket)<br><small style="font-weight: normal; color: #94A3B8;">(20/30/50 Mbps)</small></th>
                <th style="width: 90px;">Kecepatan</th>
                <th style="width: 120px;">Harga / Bulan (Rp)</th>
                <th style="width: 320px;">Alamat Lengkap Pemasangan *</th>
                <th style="width: 120px;">Status Berlangganan<br><small style="font-weight: normal; color: #94A3B8;">(Selesai)</small></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sampleData as $s)
                <tr>
                    <td class="txt-fmt text-center">{{ $s['order_number'] }}</td>
                    <td class="txt-fmt text-center">{{ $s['id_card_number'] }}</td>
                    <td class="text-bold">{{ $s['customer_name'] }}</td>
                    <td class="text-center">{{ $s['birth_place'] }}</td>
                    <td class="date-fmt">{{ $s['birth_date'] }}</td>
                    <td class="txt-fmt text-center">{{ $s['customer_phone'] }}</td>
                    <td>{{ $s['customer_email'] }}</td>
                    <td class="text-center">{{ $s['package_name'] }}</td>
                    <td class="text-center">{{ $s['speed'] }}</td>
                    <td class="num-fmt">{{ $s['price'] }}</td>
                    <td>{{ $s['address'] }}</td>
                    <td class="text-center">{{ $s['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
