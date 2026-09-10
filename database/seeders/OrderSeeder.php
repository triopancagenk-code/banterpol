<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = [
            [
                'order_number' => 'ORD-202609-00101',
                'customer_name' => 'Bambang Sudarsono',
                'customer_phone' => '081298765432',
                'customer_email' => 'bambang.sudar@gmail.com',
                'address' => 'Jl. Raya Pernasidi No. 12, RT 01/RW 02, Kec. Cilongok, Kab. Banyumas',
                'latitude' => '-7.413500',
                'longitude' => '109.139200',
                'package_name' => 'Paket 50 Mbps',
                'speed' => '50 Mbps',
                'price' => 220000,
                'installation_fee' => 0,
                'tax' => 0,
                'total' => 220000,
                'installation_date' => now()->addDays(1)->format('Y-m-d'),
                'installation_time' => 'pagi',
                'payment_method' => 'BCA Virtual Account',
                'payment_status' => 'Lunas',
                'status' => 'Menunggu Konfirmasi',
                'technician' => null,
                'assigned_odp' => 'ODP-CLK-01',
                'admin_notes' => 'Pesanan baru masuk dari website. Menunggu verifikasi jadwal instalasi.',
                'created_at' => now()->subMinutes(15),
            ],
            [
                'order_number' => 'ORD-202609-00098',
                'customer_name' => 'Dewi Anggraini',
                'customer_phone' => '085711223344',
                'customer_email' => 'dewi.ang@yahoo.com',
                'address' => 'Desa Panembangan RT 03/02, Dekat Masjid Baiturrahim, Kec. Cilongok, Banyumas',
                'latitude' => '-7.401200',
                'longitude' => '109.146200',
                'package_name' => 'Paket 20 Mbps',
                'speed' => '20 Mbps',
                'price' => 110000,
                'installation_fee' => 0,
                'tax' => 0,
                'total' => 110000,
                'installation_date' => now()->addDays(1)->format('Y-m-d'),
                'installation_time' => 'siang',
                'payment_method' => 'Transfer Bank (Mandiri)',
                'payment_status' => 'Lunas',
                'status' => 'Jadwal Teknisi',
                'technician' => 'Randi Pratama (Tim Fiber)',
                'assigned_odp' => 'ODP-CLK-02',
                'admin_notes' => 'Teknisi sudah dijadwalkan pasang drop core 45m ke ODP-CLK-02.',
                'created_at' => now()->subHours(2),
            ],
            [
                'order_number' => 'ORD-202609-00095',
                'customer_name' => 'Hendro Prasetyo',
                'customer_phone' => '082199887766',
                'customer_email' => 'hendro.pr@gmail.com',
                'address' => 'Perum Jatisaba Indah Blok D-11, RT 04/05, Kec. Cilongok, Banyumas',
                'latitude' => '-7.432800',
                'longitude' => '109.149500',
                'package_name' => 'Paket 30 Mbps',
                'speed' => '30 Mbps',
                'price' => 165000,
                'installation_fee' => 0,
                'tax' => 0,
                'total' => 165000,
                'installation_date' => now()->format('Y-m-d'),
                'installation_time' => 'pagi',
                'payment_method' => 'GoPay',
                'payment_status' => 'Lunas',
                'status' => 'Sedang Dipasang',
                'technician' => 'Fajar & Tim Lapangan',
                'assigned_odp' => 'ODP-CLK-03',
                'admin_notes' => 'Teknisi sedang di lokasi melakukan penarikan kabel drop core dan splicing ONT.',
                'created_at' => now()->subHours(5),
            ],
            [
                'order_number' => 'ORD-202609-00089',
                'customer_name' => 'Kurniawan Santika',
                'customer_phone' => '087812341234',
                'customer_email' => 'kurnia.stk@gmail.com',
                'address' => 'Desa Karanglo No. 44, RT 02/01, Kec. Cilongok, Banyumas',
                'latitude' => '-7.391500',
                'longitude' => '109.151800',
                'package_name' => 'Paket 20 Mbps',
                'speed' => '20 Mbps',
                'price' => 110000,
                'installation_fee' => 0,
                'tax' => 0,
                'total' => 110000,
                'installation_date' => now()->subDays(1)->format('Y-m-d'),
                'installation_time' => 'siang',
                'payment_method' => 'BRI Virtual Account',
                'payment_status' => 'Lunas',
                'status' => 'Selesai',
                'technician' => 'Bambang Irawan',
                'assigned_odp' => 'ODP-CLK-04',
                'admin_notes' => 'Instalasi selesai, ONT aktif dengan redaman optik -18.4 dBm. Internet sudah online lancar.',
                'created_at' => now()->subDays(1),
            ],
            [
                'order_number' => 'ORD-202609-00085',
                'customer_name' => 'Slamet Riyadi',
                'customer_phone' => '089655443322',
                'customer_email' => 'slamet.r@gmail.com',
                'address' => 'Desa Cikidang Kidul No. 19, Kec. Cilongok, Banyumas',
                'latitude' => '-7.405500',
                'longitude' => '109.156500',
                'package_name' => 'Paket 20 Mbps',
                'speed' => '20 Mbps',
                'price' => 110000,
                'installation_fee' => 0,
                'tax' => 0,
                'total' => 110000,
                'installation_date' => now()->subDays(2)->format('Y-m-d'),
                'installation_time' => 'pagi',
                'payment_method' => 'Alfamart',
                'payment_status' => 'Gagal',
                'status' => 'Dibatalkan',
                'technician' => null,
                'assigned_odp' => null,
                'admin_notes' => 'Batas waktu pembayaran kasir Alfamart kadaluarsa (24 jam).',
                'created_at' => now()->subDays(2),
            ],
        ];

        foreach ($orders as $orderData) {
            Order::updateOrCreate(
                ['order_number' => $orderData['order_number']],
                $orderData
            );
        }
    }
}
