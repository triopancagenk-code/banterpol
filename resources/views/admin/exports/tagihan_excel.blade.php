<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data Tagihan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        body { font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1E293B; }
        .title-table { margin-bottom: 12px; }
        .company-name { font-size: 16pt; font-weight: bold; color: #DC2626; }
        .report-title { font-size: 13pt; font-weight: bold; color: #0F172A; }
        .meta-info { font-size: 9pt; color: #64748B; }
        
        table.data-table { border-collapse: collapse; width: 100%; }
        table.data-table th {
            background-color: #1E293B;
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
        .row-alt { background-color: #F8FAFC; }
        .total-row {
            background-color: #F1F5F9;
            font-weight: bold;
            border-top: 2px solid #0F172A;
            border-bottom: 2px solid #0F172A;
        }
        .num-fmt { mso-number-format: "\#\,\#\#0"; text-align: right; }
        .txt-fmt { mso-number-format: "\@"; }
    </style>
</head>
<body>

    <table class="title-table" style="width: 100%;">
        <tr>
            <td colspan="12" class="company-name">BANTERPOOL INTERNET SERVICE PROVIDER</td>
        </tr>
        <tr>
            <td colspan="12" class="report-title">LAPORAN REKAPITULASI MONITORING TAGIHAN & INVOICE PELANGGAN</td>
        </tr>
        <tr>
            <td colspan="12" class="meta-info">
                Waktu Unduh: {{ now()->translatedFormat('d F Y, H:i:s') }} WIB | 
                Filter Status: {{ $statusFilter === 'all' ? 'Semua Status' : $statusFilter }} | 
                Pencarian: {{ $search ? '"'.$search.'"' : 'Semua Data' }} | 
                Total Data: {{ count($bills) }} Invoice
            </td>
        </tr>
        <tr><td colspan="12"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 140px;">No. Invoice</th>
                <th style="width: 130px;">Tanggal Invoice</th>
                <th style="width: 180px;">Nama Pelanggan</th>
                <th style="width: 130px;">No. WhatsApp</th>
                <th style="width: 250px;">Alamat Pelanggan</th>
                <th style="width: 130px;">Paket Langganan</th>
                <th style="width: 160px;">Periode Pemakaian</th>
                <th style="width: 120px;">Jatuh Tempo</th>
                <th style="width: 120px;">Total Tagihan (Rp)</th>
                <th style="width: 140px;">Metode Pembayaran</th>
                <th style="width: 130px;">Status Pembayaran</th>
                <th style="width: 110px;">ODP / Area</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotal = 0;
                $totalLunas = 0;
                $totalBelumBayar = 0;
            @endphp
            @forelse($bills as $index => $bill)
                @php
                    $nominal = (float) ($bill['total_raw'] ?? str_replace('.', '', $bill['total']));
                    $grandTotal += $nominal;
                    if ($bill['status'] === 'Lunas') {
                        $totalLunas += $nominal;
                    } else {
                        $totalBelumBayar += $nominal;
                    }
                    $isAlt = ($index % 2 === 1);
                @endphp
                <tr class="{{ $isAlt ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="txt-fmt text-bold" style="color: #0284C7;">{{ $bill['id'] }}</td>
                    <td class="text-center">{{ $bill['bill_date'] ?? $bill['created_at'] ?? '-' }}</td>
                    <td class="text-bold">{{ $bill['customer_name'] }}</td>
                    <td class="txt-fmt text-center">{{ $bill['customer_phone'] }}</td>
                    <td>{{ $bill['address'] ?? '-' }}</td>
                    <td class="text-center">{{ $bill['package_name'] }}</td>
                    <td class="text-center">{{ $bill['period'] ?? '-' }}</td>
                    <td class="text-center" style="{{ $bill['status'] === 'Jatuh Tempo' ? 'color: #DC2626; font-weight: bold;' : '' }}">
                        {{ $bill['due_date'] }}
                    </td>
                    <td class="num-fmt text-bold" style="color: #16A34A;">{{ $nominal }}</td>
                    <td class="text-center">{{ $bill['payment_method'] ?? '-' }}</td>
                    <td class="text-center text-bold" style="
                        @if($bill['status'] === 'Lunas') color: #16A34A;
                        @elseif($bill['status'] === 'Menunggu Verifikasi') color: #D97706;
                        @elseif($bill['status'] === 'Jatuh Tempo') color: #DC2626;
                        @else color: #475569;
                        @endif">
                        {{ $bill['status'] }}
                    </td>
                    <td class="text-center">{{ $bill['odp'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center" style="padding: 20px; color: #64748B;">
                        Tidak ada data tagihan yang sesuai dengan kriteria filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right text-bold">TOTAL TAGIHAN ({{ count($bills) }} Tagihan):</td>
                <td class="num-fmt text-bold" style="color: #16A34A;">{{ $grandTotal }}</td>
                <td colspan="3"></td>
            </tr>
            <tr style="background-color: #F8FAFC; font-size: 9pt;">
                <td colspan="9" class="text-right">Total Sudah Lunas:</td>
                <td class="num-fmt text-bold" style="color: #16A34A;">{{ $totalLunas }}</td>
                <td colspan="3"></td>
            </tr>
            <tr style="background-color: #F8FAFC; font-size: 9pt;">
                <td colspan="9" class="text-right">Total Belum Terbayar / Piutang:</td>
                <td class="num-fmt text-bold" style="color: #DC2626;">{{ $totalBelumBayar }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
