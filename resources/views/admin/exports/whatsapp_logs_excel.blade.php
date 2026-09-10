<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>WhatsApp Broadcast Logs</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    @endphp
    <style>
        body { font-family: 'Calibri', 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1E293B; }
        .title-table { margin-bottom: 12px; }
        .company-name { font-size: 16pt; font-weight: bold; color: #16A34A; }
        .report-title { font-size: 13pt; font-weight: bold; color: #0F172A; }
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
        .row-alt { background-color: #F8FAFC; }
        .txt-fmt { mso-number-format: "\@"; }
    </style>
</head>
<body>

    <table class="title-table" style="width: 100%;">
        <tr>
            <td colspan="8" class="company-name">BANTERPOOL NOC &bull; WHATSAPP GATEWAY ENGINE</td>
        </tr>
        <tr>
            <td colspan="8" class="report-title">REKAPITULASI LOG BROADCAST &amp; PENGIRIMAN PESAN WHATSAPP</td>
        </tr>
        <tr>
            <td colspan="8" class="meta-info">
                Waktu Unduh: {{ now()->translatedFormat('d F Y, H:i:s') }} WIB | 
                Total Pesan: {{ count($logs) }} Pesan | 
                Sender Bot: 0812-3456-7890 (Banterpool NOC)
            </td>
        </tr>
        <tr><td colspan="8"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 130px;">Waktu Kirim</th>
                <th style="width: 160px;">Nama Pelanggan</th>
                <th style="width: 130px;">No. WhatsApp</th>
                <th style="width: 110px;">Tipe Pesan</th>
                <th style="width: 140px;">Batch ID</th>
                <th style="width: 380px;">Isi Pesan</th>
                <th style="width: 90px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $index => $log)
                <tr class="{{ $index % 2 === 1 ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center txt-fmt">{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-' }}</td>
                    <td class="text-bold">{{ $log->recipient_name }}</td>
                    <td class="text-center txt-fmt">{{ $log->recipient_phone }}</td>
                    <td class="text-center text-bold">{{ strtoupper($log->message_type) }}</td>
                    <td class="text-center txt-fmt" style="font-size: 9pt; color: #475569;">{{ $log->batch_id ?? '-' }}</td>
                    <td style="white-space: pre-wrap; font-size: 9pt;">{{ $log->message }}</td>
                    <td class="text-center text-bold" style="color: {{ $log->status === 'sent' ? '#16A34A' : '#DC2626' }};">
                        {{ strtoupper($log->status) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #64748B;">
                        Belum ada riwayat pengiriman pesan WhatsApp.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
