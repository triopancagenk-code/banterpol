<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @php
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data Pesanan</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
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
        .badge-status { font-weight: bold; }
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
            <td colspan="15" class="company-name">BANTERPOOL INTERNET SERVICE PROVIDER</td>
        </tr>
        <tr>
            <td colspan="15" class="report-title">LAPORAN REKAPITULASI PEMESANAN PELANGGAN BARU</td>
        </tr>
        <tr>
            <td colspan="15" class="meta-info">
                Waktu Unduh: {{ now()->translatedFormat('d F Y, H:i:s') }} WIB | 
                Filter Status: {{ $statusFilter === 'all' ? 'Semua Status' : $statusFilter }} | 
                Pencarian: {{ $search ? '"'.$search.'"' : 'Semua Data' }} | 
                Total Data: {{ count($orders) }} Pesanan
            </td>
        </tr>
        <tr><td colspan="15"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 130px;">No. Pesanan</th>
                <th style="width: 140px;">Tanggal Pesan</th>
                <th style="width: 180px;">Nama Pelanggan</th>
                <th style="width: 130px;">No. WhatsApp</th>
                <th style="width: 180px;">Email</th>
                <th style="width: 250px;">Alamat Pemasangan</th>
                <th style="width: 120px;">Paket Internet</th>
                <th style="width: 80px;">Kecepatan</th>
                <th style="width: 110px;">Biaya Paket (Rp)</th>
                <th style="width: 110px;">Biaya Pasang (Rp)</th>
                <th style="width: 100px;">PPN 11% (Rp)</th>
                <th style="width: 120px;">Total Bayar (Rp)</th>
                <th style="width: 120px;">Jadwal Pasang</th>
                <th style="width: 130px;">Metode Bayar</th>
                <th style="width: 120px;">Status Bayar</th>
                <th style="width: 140px;">Status Pesanan</th>
                <th style="width: 150px;">Teknisi Ditugaskan</th>
                <th style="width: 110px;">ODP Terhubung</th>
                <th style="width: 200px;">Catatan Admin</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalNilai = 0;
                $totalPaket = 0;
                $totalPasang = 0;
            @endphp
            @forelse($orders as $index => $order)
                @php
                    $totalNilai += (float) ($order->total ?? 0);
                    $totalPaket += (float) ($order->price ?? 0);
                    $totalPasang += (float) ($order->installation_fee ?? 0);
                    $isAlt = ($index % 2 === 1);
                @endphp
                <tr class="{{ $isAlt ? 'row-alt' : '' }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="txt-fmt text-bold" style="color: #0284C7;">{{ $order->order_number }}</td>
                    <td class="text-center">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-bold">{{ $order->customer_name }}</td>
                    <td class="txt-fmt text-center">{{ $order->customer_phone }}</td>
                    <td>{{ $order->customer_email ?? '-' }}</td>
                    <td>{{ $order->address }}</td>
                    <td class="text-center">{{ $order->package_name }}</td>
                    <td class="text-center">{{ $order->speed ?? '-' }}</td>
                    <td class="num-fmt">{{ (float) ($order->price ?? 0) }}</td>
                    <td class="num-fmt">{{ (float) ($order->installation_fee ?? 0) }}</td>
                    <td class="num-fmt">{{ (float) ($order->tax ?? 0) }}</td>
                    <td class="num-fmt text-bold" style="color: #16A34A;">{{ (float) ($order->total ?? 0) }}</td>
                    <td class="text-center">
                        @if($order->installation_date)
                            {{ \Carbon\Carbon::parse($order->installation_date)->format('d/m/Y') }}
                            @if($order->installation_time) ({{ $order->installation_time }}) @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ $order->payment_method ?? 'Transfer Bank' }}</td>
                    <td class="text-center badge-status" style="{{ $order->payment_status === 'Sudah Bayar' ? 'color: #16A34A;' : 'color: #D97706;' }}">
                        {{ $order->payment_status ?? 'Belum Bayar' }}
                    </td>
                    <td class="text-center text-bold" style="
                        @if($order->status === 'Selesai') color: #16A34A;
                        @elseif($order->status === 'Sedang Dipasang') color: #4F46E5;
                        @elseif($order->status === 'Jadwal Teknisi') color: #0284C7;
                        @elseif($order->status === 'Dibatalkan') color: #DC2626;
                        @else color: #D97706;
                        @endif">
                        {{ $order->status }}
                    </td>
                    <td>{{ $order->technician ?? 'Belum Ditugaskan' }}</td>
                    <td class="text-center">{{ $order->assigned_odp ?? '-' }}</td>
                    <td>{{ $order->admin_notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="20" class="text-center" style="padding: 20px; color: #64748B;">
                        Tidak ada data pesanan yang sesuai dengan kriteria filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right text-bold">TOTAL KESELURUHAN ({{ count($orders) }} Pesanan):</td>
                <td class="num-fmt text-bold">{{ $totalPaket }}</td>
                <td class="num-fmt text-bold">{{ $totalPasang }}</td>
                <td></td>
                <td class="num-fmt text-bold" style="color: #16A34A;">{{ $totalNilai }}</td>
                <td colspan="7"></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
