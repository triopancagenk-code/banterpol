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
            <td colspan="20" class="company-name">BANTERPOOL INTERNET SERVICE PROVIDER</td>
        </tr>
        <tr>
            <td colspan="20" class="report-title">MASTER DATA PELANGGAN TERDAFTAR (ARSIP PENDAFTARAN FIBER BROADBAND)</td>
        </tr>
        <tr>
            <td colspan="20" class="meta-info">
                Waktu Unduh: {{ now()->translatedFormat('d F Y, H:i:s') }} WIB | 
                Filter Wilayah: {{ $wilayahFilter === 'all' ? 'Semua Wilayah' : $wilayahFilter }} | 
                Filter Layanan: {{ $layananFilter === 'all' ? 'Semua Layanan' : $layananFilter }} | 
                Filter Status: {{ $statusFilter === 'all' ? 'Semua Status' : $statusFilter }} | 
                Total Data: {{ count($customers) }} Pelanggan
            </td>
        </tr>
        <tr><td colspan="20"></td></tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 130px;">ID / No. Pelanggan</th>
                <th style="width: 180px;">No. KTP / NIK</th>
                <th style="width: 200px;">Nama Lengkap Pelanggan</th>
                <th style="width: 120px;">Tempat Lahir</th>
                <th style="width: 110px;">Tanggal Lahir</th>
                <th style="width: 60px;">Usia</th>
                <th style="width: 140px;">No. Handphone (WA)</th>
                <th style="width: 220px;">Alamat Email</th>
                <th style="width: 150px;">Jenis Layanan (Paket)</th>
                <th style="width: 90px;">Kecepatan</th>
                <th style="width: 130px;">Harga (Rp)</th>
                <th style="width: 300px;">Alamat</th>
                <th style="width: 140px;">Wilayah / Desa</th>
                <th style="width: 140px;">Teknisi</th>
                <th style="width: 120px;">ODP</th>
                <th style="width: 130px;">Tanggal Pemasangan</th>
                <th style="width: 120px;">Status Berlangganan</th>
                <th style="width: 130px;">Tanggal Terdaftar</th>
                <th style="width: 200px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalMrr = 0;
            @endphp
            @forelse($customers as $index => $c)
                @php
                    $totalMrr += (float) ($c->price ?? 0);
                    $rowClass = ($index % 2 === 1) ? 'row-alt' : '';
                    
                    // Hitung usia jika tanggal lahir ada
                    $age = '-';
                    if (!empty($c->birth_date)) {
                        try {
                            $age = \Carbon\Carbon::parse($c->birth_date)->age . ' th';
                        } catch (\Exception $e) {}
                    }

                    // Wilayah / Desa pelanggan
                    $wilayah = $c->village ?: (\App\Services\CustomerImportService::resolveVillage(null, null, $c->address, $c->admin_notes) ?: '-');
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="txt-fmt text-center">{{ $c->order_number }}</td>
                    <td class="txt-fmt" style="mso-number-format: '\@'; font-weight: bold;">{{ $c->id_card_number ?: '-' }}</td>
                    <td><b>{{ $c->customer_name }}</b></td>
                    <td>{{ $c->birth_place ?: '-' }}</td>
                    <td class="text-center">{{ $c->birth_date ? \Carbon\Carbon::parse($c->birth_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $age }}</td>
                    <td class="txt-fmt" style="mso-number-format: '\@';">{{ $c->customer_phone ?: '-' }}</td>
                    <td>{{ $c->customer_email ?: '-' }}</td>
                    <td>{{ $c->package_name ?: '-' }}</td>
                    <td class="text-center">{{ $c->speed ?: '-' }}</td>
                    <td class="num-fmt">{{ (float) ($c->price ?? 0) }}</td>
                    <td>{{ $c->address ?: '-' }}</td>
                    <td>{{ $wilayah }}</td>
                    <td>{{ $c->technician ?: '-' }}</td>
                    <td class="text-center">{{ $c->assigned_odp ?: '-' }}</td>
                    <td class="text-center">{{ $c->installation_date ? \Carbon\Carbon::parse($c->installation_date)->format('d/m/Y') : '-' }}</td>
                    <td class="text-center"><b>{{ $c->status ?: '-' }}</b></td>
                    <td class="text-center">{{ $c->created_at ? $c->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $c->admin_notes ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="20" class="text-center" style="padding: 20px; color: #64748B;">
                        Tidak ada data pelanggan yang sesuai dengan kriteria filter.
                    </td>
                </tr>
            @endforelse

            @if(count($customers) > 0)
                <tr class="total-row">
                    <td colspan="11" class="text-right text-bold">TOTAL ESTIMASI PENDAPATAN BULANAN (MRR):</td>
                    <td class="num-fmt text-bold">{{ $totalMrr }}</td>
                    <td colspan="8" class="text-bold"> (Total {{ count($customers) }} Pelanggan)</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
