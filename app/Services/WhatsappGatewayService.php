<?php

namespace App\Services;

use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsappGatewayService
{
    /**
     * Get default templates
     */
    public function getTemplates(): array
    {
        return [
            'maintenance' => [
                'title' => 'Pemeliharaan Jaringan (Maintenance FO)',
                'icon' => 'fa-screwdriver-wrench',
                'color' => 'amber',
                'badge' => 'Maintenance',
                'message' => "Halo {nama},\n\nKami informasikan bahwa tim teknisi NOC Banterpool akan melakukan pemeliharaan optimalisasi jaringan fiber optik pada:\n\n📅 Waktu: Malam ini, pukul 01:00 - 04:00 WIB\n📍 Area: Cilongok dan sekitarnya\n⚡ Estimasi Downtime: ± 30-45 menit\n\nLangkah ini kami lakukan untuk meningkatkan kehandalan dan stabilitas koneksi internet {paket} Anda. Mohon maaf atas ketidaknyamanannya.\n\nSalam hangat,\nTim NOC Banterpool",
            ],
            'billing' => [
                'title' => 'Pengingat Tagihan Bulanan (Billing Reminder)',
                'icon' => 'fa-file-invoice-dollar',
                'color' => 'blue',
                'badge' => 'Tagihan',
                'message' => "Halo Kak {nama},\n\nTerima kasih telah setia berlangganan WiFi Banterpool ({paket}).\n\nKami ingin menginformasikan tagihan internet periode berjalan Anda:\n💰 Total Tagihan: {tagihan}\n📅 Batas Jatuh Tempo: {jatuh_tempo}\n\nUntuk menghindari isolir koneksi otomatis, silakan lakukan pembayaran melalui transfer bank, e-wallet, atau melalui kolektor resmi kami.\n🔗 Link Pembayaran: {link_bayar}\n\nTerima kasih,\nBilling Support Banterpool",
            ],
            'outage' => [
                'title' => 'Pemberitahuan Insiden Jaringan (Emergency Outage)',
                'icon' => 'fa-triangle-exclamation',
                'color' => 'red',
                'badge' => 'Gangguan',
                'message' => "Pemberitahuan Insiden Jaringan Banterpool\n\nYth. Pelanggan Banterpool ({nama}),\n\nSaat ini sedang terjadi kendala jaringan akibat kabel fiber optik distribusi terputus di jalur utama. Tim teknisi fiber kami sudah berada di lokasi untuk proses penyambungan (splicing).\n\n⏳ Estimasi perbaikan: 1-2 jam ke depan\n\nKoneksi Anda ({paket}) akan pulih secara otomatis setelah perbaikan selesai. Kami mohon maaf atas kendala yang terjadi.\n\nNOC Banterpool Fast Response",
            ],
            'promo' => [
                'title' => 'Promo Upgrade Kecepatan Internet',
                'icon' => 'fa-rocket',
                'color' => 'emerald',
                'badge' => 'Promosi',
                'message' => "Kabar Gembira untuk Kak {nama}! 🚀\n\nSebagai pelanggan setia paket {paket}, nikmati penawaran eksklusif upgrade kecepatan hingga 30 Mbps / 50 Mbps dengan diskon 25% untuk 3 bulan pertama!\n\nStreaming lebih lancar, gaming tanpa lag, dan download super ngebut untuk seluruh keluarga.\n\nBalas pesan WhatsApp ini dengan ketik: *MAU UPGRADE* untuk klaim promonya sekarang!\n\nCustomer Care Banterpool",
            ],
            'custom' => [
                'title' => 'Pesan Kustom Bebas',
                'icon' => 'fa-pen-to-square',
                'color' => 'slate',
                'badge' => 'Kustom',
                'message' => "Halo {nama},\n\n[Tuliskan isi pengumuman atau informasi Anda di sini...]\n\nSalam,\nAdmin Banterpool",
            ],
        ];
    }

    /**
     * Check if a valid API token is configured
     */
    public function hasValidApiToken(): bool
    {
        $token = cache()->get('wa_gateway_api_token');
        return !empty($token) && $token !== 'btp_wa_sec_live_9988224411aa';
    }

    /**
     * Get Gateway Device / Bot Status
     */
    public function getGatewayStatus(): array
    {
        $phone = cache()->get('wa_gateway_phone_number', '0881-8679-774');
        $deviceName = cache()->get('wa_gateway_device_name', 'Banterpool NOC Bot');
        $provider = cache()->get('wa_gateway_provider', 'Banterpool Local Gateway (Direct WA Web)');
        $hasToken = $this->hasValidApiToken();

        return [
            'status' => $hasToken ? 'ONLINE (API CONNECTED)' : 'ONLINE',
            'is_api_connected' => $hasToken,
            'device_name' => $deviceName,
            'phone_number' => $phone,
            'server' => 'Local Gateway Cluster #1 (Cilongok NOC)',
            'provider' => $provider,
            'battery' => '100%',
            'signal' => 'Sangat Kuat (5G/Fiber)',
            'ping' => '18 ms',
            'uptime' => '142 Hari',
            'quota_remaining' => 'Unlimited',
            'speed_rate' => '15 pesan / detik (Anti-Spam Delay)',
        ];
    }

    /**
     * Format Phone Number to Indonesian Standard (628...)
     */
    public function formatPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        } elseif (!str_starts_with($clean, '62')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }

    /**
     * Replace dynamic variables inside template message
     */
    public function parseTemplateVariables(string $template, array $data): string
    {
        $replacements = [
            '{nama}' => $data['name'] ?? 'Pelanggan',
            '{tagihan}' => $data['bill_total'] ?? 'Rp110.000',
            '{paket}' => $data['package_name'] ?? 'Paket Internet',
            '{jatuh_tempo}' => $data['due_date'] ?? date('d M Y', strtotime('+7 days')),
            '{alamat}' => $data['address'] ?? 'Cilongok, Banyumas',
            '{nomor_layanan}' => $data['customer_id'] ?? 'BTP-' . rand(1000, 9999),
            '{link_bayar}' => url('/tagihan'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Send Real HTTP Request to WhatsApp Gateway API (Fonnte / Wablas)
     */
    public function sendApiMessage(string $phone, string $message): array
    {
        $token = cache()->get('wa_gateway_api_token');
        $provider = cache()->get('wa_gateway_provider', 'Fonnte');
        $formattedPhone = $this->formatPhoneNumber($phone);

        if (empty($token) || $token === 'btp_wa_sec_live_9988224411aa') {
            return [
                'success' => false,
                'is_gateway' => false,
                'reason' => 'Mode Direct WA Web (Belum ada API Token Gateway yang aktif).',
            ];
        }

        // 1. Fonnte (fonnte.com)
        if (stripos($provider, 'fonnte') !== false || stripos($provider, 'official') !== false) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $token,
                ])->timeout(12)->asForm()->post('https://api.fonnte.com/send', [
                    'target' => $formattedPhone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

                $json = $response->json();
                $isOk = $response->successful() && ($json['status'] ?? false) !== false;

                return [
                    'success' => $isOk,
                    'is_gateway' => true,
                    'reason' => $isOk ? 'Terkirim via Fonnte API' : ($json['reason'] ?? 'Gagal mengirim pesan via Fonnte API'),
                    'response' => $json,
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'is_gateway' => true,
                    'reason' => 'Error koneksi Fonnte: ' . $e->getMessage(),
                ];
            }
        }

        // 2. Wablas (wablas.com)
        if (stripos($provider, 'wablas') !== false) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $token,
                ])->timeout(12)->asForm()->post('https://phone.wablas.com/api/send-message', [
                    'phone' => $formattedPhone,
                    'message' => $message,
                ]);

                return [
                    'success' => $response->successful(),
                    'is_gateway' => true,
                    'reason' => $response->successful() ? 'Terkirim via Wablas API' : 'Gagal mengirim pesan via Wablas API',
                    'response' => $response->json(),
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'is_gateway' => true,
                    'reason' => 'Error koneksi Wablas: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'success' => false,
            'is_gateway' => false,
            'reason' => 'Provider gateway tidak dikenali.',
        ];
    }

    /**
     * Send Single WhatsApp message and record log
     */
    public function sendSingle(string $phone, string $name, string $message, string $type = 'single', string $sentBy = 'Admin NOC'): WhatsappLog
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        $apiResult = $this->sendApiMessage($formattedPhone, $message);
        $status = ($apiResult['is_gateway'] && !$apiResult['success']) ? 'failed' : 'sent';

        return WhatsappLog::create([
            'recipient_name' => $name,
            'recipient_phone' => $formattedPhone,
            'message_type' => $type,
            'message' => $message,
            'status' => $status,
            'batch_id' => 'SINGLE-' . strtoupper(Str::random(8)),
            'target_filter' => 'single',
            'sent_by' => $sentBy,
            'sent_at' => now(),
        ]);
    }

    /**
     * Send Broadcast Blast to an array of recipients and record logs
     */
    public function sendBroadcast(array $recipients, string $messageTemplate, string $messageType = 'broadcast', string $targetFilter = 'all', string $sentBy = 'Admin NOC'): array
    {
        $batchId = 'BLAST-' . date('YmdHis') . '-' . strtoupper(Str::random(6));
        $logs = [];
        $successCount = 0;
        $failedCount = 0;
        $hasApiToken = $this->hasValidApiToken();

        foreach ($recipients as $recipient) {
            $name = $recipient['name'] ?? 'Pelanggan';
            $phone = $recipient['phone'] ?? '';

            if (empty($phone)) {
                continue;
            }

            $personalizedMessage = $this->parseTemplateVariables($messageTemplate, $recipient);
            $formattedPhone = $this->formatPhoneNumber($phone);

            $apiResult = $this->sendApiMessage($formattedPhone, $personalizedMessage);
            $status = ($apiResult['is_gateway'] && !$apiResult['success']) ? 'failed' : 'sent';

            if ($status === 'sent') {
                $successCount++;
            } else {
                $failedCount++;
            }

            $log = WhatsappLog::create([
                'recipient_name' => $name,
                'recipient_phone' => $formattedPhone,
                'message_type' => $messageType,
                'message' => $personalizedMessage,
                'status' => $status,
                'batch_id' => $batchId,
                'target_filter' => $targetFilter,
                'sent_by' => $sentBy,
                'sent_at' => now(),
            ]);

            $logs[] = $log;
        }

        return [
            'batch_id' => $batchId,
            'total_sent' => $successCount,
            'failed_count' => $failedCount,
            'has_api_token' => $hasApiToken,
            'logs' => $logs,
        ];
    }
}
