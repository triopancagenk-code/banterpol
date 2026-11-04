<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once app_path('Services/routeros_api.class.php');

class MikrotikController extends Controller
{
    private function getHost(): string
    {
        return env('MIKROTIK_HOST', '172.14.10.3');
    }

    private function getUser(): string
    {
        return env('MIKROTIK_USER', 'api_map');
    }

    private function getPass(): string
    {
        return env('MIKROTIK_PASS', 'pklbsi');
    }

    private function getPort(): int
    {
        return (int) env('MIKROTIK_PORT', 23500);
    }

    /**
     * Helper koneksi ke RouterOS API
     */
    private function connectApi(int $timeout = 4): ?\RouterosAPI
    {
        $API = new \RouterosAPI();
        $API->timeout = $timeout;
        $API->debug = false;

        if ($API->connect($this->getHost(), $this->getUser(), $this->getPass(), $this->getPort())) {
            return $API;
        }

        return null;
    }

    /**
     * Endpoint Data Peta Lengkap MikroTik Winbox Live + ODP + Status PPPoE + Isolir
     */
    public function getPureMikrotikMap(?Request $request = null)
    {
        $request = $request ?: request();
        set_time_limit(180);
        ini_set('memory_limit', '512M');

        $forceRefresh = $request->has('refresh') || $request->input('refresh') == '1';
        $cacheKey = 'mikrotik_pure_map_data_cache';

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (!empty($cached) && is_array($cached) && ($cached['status'] ?? '') === 'success') {
                return response()->json($cached);
            }
        }

        $API = $this->connectApi(5);
        if (!$API) {
            // Jika router sedang tidak terjangkau tapi ada cache lama, gunakan cache lama
            if (Cache::has($cacheKey)) {
                $stale = Cache::get($cacheKey);
                $stale['is_stale'] = true;
                $stale['message'] = 'Router sedang sibuk, menampilkan data sesi terakhir.';
                return response()->json($stale);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal terhubung ke router MikroTik Winbox (' . $this->getHost() . ':' . $this->getPort() . '). Pastikan koneksi VPN / lokal aktif.'
            ], 500);
        }

        // Ambil Identity Router
        $identityArr = $API->comm('/system/identity/print');
        $routerIdentity = $identityArr[0]['name'] ?? 'ThreeFive_Router1';

        // Ambil Data PPP Secrets & Actives
        $secrets = $API->comm('/ppp/secret/print');
        $actives = $API->comm('/ppp/active/print');
        $API->disconnect();

        // 1. Ekstrak koordinat dari KML jika ada
        $kmlCoords = [];
        $kmlOdpCoords = [];
        $kmlPath = public_path('peta_jaringan/doc.kml');
        if (!file_exists($kmlPath)) {
            $kmlPath = public_path('gis/doc.kml');
        }

        if (file_exists($kmlPath)) {
            $xml = @simplexml_load_file($kmlPath);
            if ($xml) {
                $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');
                $placemarks = $xml->xpath('//kml:Placemark');
                foreach ($placemarks as $pm) {
                    $name = trim((string)$pm->name);
                    $coordStr = '';
                    if (isset($pm->Point->coordinates)) {
                        $coordStr = trim((string)$pm->Point->coordinates);
                    } elseif (isset($pm->coordinates)) {
                        $coordStr = trim((string)$pm->coordinates);
                    }

                    if ($name && $coordStr) {
                        $parts = explode(',', $coordStr);
                        if (count($parts) >= 2) {
                            $lng = (float) trim($parts[0]);
                            $lat = (float) trim($parts[1]);
                            $clean = strtolower(preg_replace('/[^a-z0-9]/', '', $name));

                            if (str_contains(strtolower($name), 'odp') || str_starts_with(strtolower($name), 'p')) {
                                $kmlOdpCoords[$clean] = ['lat' => $lat, 'lng' => $lng];
                            } else {
                                $kmlCoords[$clean] = ['lat' => $lat, 'lng' => $lng];
                            }
                        }
                    }
                }
            }
        }

        // Koordinat Titik Pusat Wilayah/Desa (Banyumas / Cilongok)
        $villageCenters = [
            'sulis'       => [-7.435926, 109.153747],
            'kasegeran'   => [-7.436200, 109.154100], 
            'jatisaba'    => [-7.438500, 109.165200], 
            'panusupan'   => [-7.443500, 109.168500], 
            'karangendep' => [-7.449800, 109.162000], 
            'sawangan'    => [-7.438820, 109.142180], 
            'batuanten'   => [-7.428500, 109.138200], 
            'cikidang'    => [-7.418500, 109.129000], 
            'sudimara'    => [-7.418500, 109.146500], 
            'pageraji'    => [-7.412500, 109.142000], 
            'cipete'      => [-7.408500, 109.158500], 
            'dawuan'      => [-7.424500, 109.141200], 
            'notog'       => [-7.465000, 109.185000]  
        ];

        $villageRules = [
            'panusupan'   => ['panusupan', 'penusupan', 'pansup', 'pan-sup', 'pns'],
            'karangendep' => ['karangendep', 'krngedep', 'krendep', 'krngdp', 'kargin', 'krgndp', 'karangdep'],
            'notog'       => ['notog', 'ntg'],
            'jatisaba'    => ['jatisaba', 'jtsb', 'jts'],
            'sawangan'    => ['sawangan', 'sawet', 'swg', 'snwg', 'sawngan', 'sawanganwetan'],
            'cipete'      => ['cipete', 'cpt'],
            'batuanten'   => ['batuanten', 'btnstn', 'btn', 'batuan'],
            'cikidang'    => ['cikidang', 'ckd'],
            'pageraji'    => ['pageraji', 'pgrj', 'pgr'],
            'sudimara'    => ['sudimara', 'sdm'],
            'dawuan'      => ['dawuan', 'dwn'],
            'kasegeran'   => ['kasegeran', 'ksgrt', 'ksg', 'ksgr']
        ];

        // Hash Map Sesi Aktif O(1)
        $activeMap = [];
        foreach ($actives as $act) {
            $actName = $act['name'] ?? '';
            $activeMap[$actName] = $act;
            $activeMap[strtolower($actName)] = $act;
            
            $prefix = strtolower(explode('_', explode('@', explode('/', $actName)[0])[0])[0]);
            if (!empty($prefix) && !isset($activeMap[$prefix])) {
                $activeMap[$prefix] = $act;
            }
        }

        $clients = [];
        $odpList = [];
        $isolirCount = 0;
        $onlineCount = 0;

        foreach ($secrets as $index => $sec) {
            $rawSecretName  = $sec['name'] ?? '';
            $rawComment     = $sec['comment'] ?? '';
            $currentProfile = $sec['profile'] ?? 'default';

            if (empty($rawSecretName)) continue;

            // Bersihkan nama display pelanggan
            $namaPelanggan = $rawSecretName;
            if (!empty($rawComment) && str_contains($rawComment, '/')) {
                $commentParts = explode('/', $rawComment);
                $firstPart = trim($commentParts[0]);
                if (!empty($firstPart) && !str_contains(strtolower($firstPart), 'active')) {
                    $namaPelanggan = str_replace(['_', '-'], ' ', $firstPart);
                }
            } elseif (str_contains($rawSecretName, '@')) {
                $atParts = explode('@', $rawSecretName);
                $namaPelanggan = str_replace(['_', '-'], ' ', $atParts[0]);
            }
            $namaPelanggan = ucwords(trim($namaPelanggan));

            // Deteksi ODP
            $kodeOdp = 'ODP-DIST';
            if (str_contains($rawComment, '/')) {
                $cParts = explode('/', $rawComment);
                if (count($cParts) >= 2) {
                    $secondPart = trim($cParts[1]);
                    // Contoh: V90_10 atau P3_V90_29
                    if (!empty($secondPart)) {
                        $kodeOdp = 'ODP-' . strtoupper($secondPart);
                    }
                }
            }

            // Deteksi Wilayah / Desa
            $searchTarget = strtolower($rawSecretName . ' ' . $rawComment);
            $detectedVillage = 'kasegeran';
            foreach ($villageRules as $vKey => $aliases) {
                foreach ($aliases as $al) {
                    if (str_contains($searchTarget, $al)) {
                        $detectedVillage = $vKey;
                        break 2;
                    }
                }
            }

            $center = $villageCenters[$detectedVillage] ?? $villageCenters['kasegeran'];

            // Inisialisasi ODP
            $odpKey = $detectedVillage . '_' . $kodeOdp;
            if (!isset($odpList[$odpKey])) {
                $cleanOdp = strtolower(preg_replace('/[^a-z0-9]/', '', $kodeOdp));
                $odpLat = null;
                $odpLng = null;

                if (isset($kmlOdpCoords[$cleanOdp])) {
                    $odpLat = $kmlOdpCoords[$cleanOdp]['lat'];
                    $odpLng = $kmlOdpCoords[$cleanOdp]['lng'];
                } else {
                    $odpHash = crc32($kodeOdp . $detectedVillage);
                    $odpLat = $center[0] + ((($odpHash % 80) - 40) * 0.00018);
                    $odpLng = $center[1] + ((((($odpHash >> 8) % 80) - 40)) * 0.00018);
                }

                $odpList[$odpKey] = [
                    'id'           => $odpKey,
                    'kode'         => strtoupper($kodeOdp) . ' (' . ucfirst($detectedVillage) . ')',
                    'raw_kode'     => $kodeOdp,
                    'lat'          => $odpLat,
                    'lng'          => $odpLng,
                    'desa'         => ucfirst($detectedVillage),
                    'total_client' => 0,
                    'online_count' => 0,
                    'offline_count'=> 0,
                    'isolir_count' => 0
                ];
            }

            $odpList[$odpKey]['total_client']++;

            // Cek Sesi Aktif di MikroTik
            $cleanUserKey = strtolower(explode('_', explode('@', explode('/', $rawSecretName)[0])[0])[0]);
            $session = $activeMap[$rawSecretName] 
                       ?? ($activeMap[strtolower($rawSecretName)] 
                       ?? ($activeMap[$cleanUserKey] ?? null));

            $isOnline = ($session !== null);
            if ($isOnline) {
                $odpList[$odpKey]['online_count']++;
                $onlineCount++;
            } else {
                $odpList[$odpKey]['offline_count']++;
            }

            $ipAddress = $session['address'] ?? ($sec['remote-address'] ?? '-');
            
            // Cek Status Isolir di MikroTik
            $isIsolir = in_array(strtolower($currentProfile), ['isolir', 'profile-isolir', 'isolir-profile'])
                        || str_starts_with($ipAddress, '10.100.100.')
                        || ($sec['disabled'] ?? 'false') === 'true';

            if ($isIsolir) {
                $odpList[$odpKey]['isolir_count']++;
                $isolirCount++;
            }

            $odpLat = $odpList[$odpKey]['lat'];
            $odpLng = $odpList[$odpKey]['lng'];

            $cLat = null;
            $cLng = null;

            // 1. Koordinat jika ada di comment
            if (str_contains($rawComment, '|')) {
                $cParts = explode('|', $rawComment);
                $cCoords = explode(',', trim($cParts[1] ?? ''));
                if (count($cCoords) === 2 && is_numeric(trim($cCoords[0]))) {
                    $cLat = (float) trim($cCoords[0]);
                    $cLng = (float) trim($cCoords[1]);
                }
            }

            // 2. Koordinat dari KML
            if (!$cLat) {
                $cleanSearch = strtolower(preg_replace('/[^a-z0-9]/', '', $namaPelanggan));
                if (isset($kmlCoords[$cleanSearch])) {
                    $cLat = $kmlCoords[$cleanSearch]['lat'];
                    $cLng = $kmlCoords[$cleanSearch]['lng'];
                }
            }

            // 3. Sebaran Spiral Fibonacci (Anti-Menumpuk untuk ratusan klien per ODP)
            if (!$cLat) {
                $clientNum = $odpList[$odpKey]['total_client'];
                $angle = ($clientNum * 137.5) * (pi() / 180);
                // Radius diperketat 10-30m di sekitar ODP agar rapi dan tidak meluber ke sawah/desa lain
                $radius = 0.00008 + (sqrt($clientNum) * 0.00005);
                $cLat = $odpLat + ($radius * cos($angle));
                $cLng = $odpLng + ($radius * sin($angle) * 1.25);
            }

            $clients[] = [
                'name'         => $namaPelanggan,
                'secret_id'    => $sec['.id'] ?? '',
                'raw_name'     => $rawSecretName,
                'username'     => $rawSecretName,
                'profile'      => $currentProfile,
                'is_isolir'    => $isIsolir,
                'odp'          => $odpList[$odpKey]['kode'],
                'odp_id'       => $odpKey,
                'desa'         => ucfirst($detectedVillage),
                'odp_lat'      => $odpLat,
                'odp_lng'      => $odpLng,
                'lat'          => $cLat,
                'lng'          => $cLng,
                'online'       => $isOnline,
                'ip'           => $ipAddress,
                'uptime'       => $session['uptime'] ?? '-',
                'mac'          => $session['caller-id'] ?? '-',
                'comment'      => $rawComment,
                'disabled'     => ($sec['disabled'] ?? 'false') === 'true',
            ];
        }

        // 10 Jalur Backbone Utama Distribusi Fiber Optik ThreeFive Network (Elegan & Anti-Semrawut)
        $backbones = [
            ['from' => 'Sulis POP', 'to' => 'Kasegeran', 'name' => 'Trunk Sulis - Kasegeran', 'coords' => [[-7.435926, 109.153747], [-7.436200, 109.154100]]],
            ['from' => 'Sulis POP', 'to' => 'Sawangan', 'name' => 'Trunk Sulis - Sawangan', 'coords' => [[-7.435926, 109.153747], [-7.438820, 109.142180]]],
            ['from' => 'Sulis POP', 'to' => 'Jatisaba', 'name' => 'Trunk Sulis - Jatisaba', 'coords' => [[-7.435926, 109.153747], [-7.438500, 109.165200]]],
            ['from' => 'Jatisaba', 'to' => 'Panusupan', 'name' => 'Trunk Jatisaba - Panusupan', 'coords' => [[-7.438500, 109.165200], [-7.443500, 109.168500]]],
            ['from' => 'Panusupan', 'to' => 'Karangendep', 'name' => 'Trunk Panusupan - Karangendep', 'coords' => [[-7.443500, 109.168500], [-7.449800, 109.162000]]],
            ['from' => 'Sulis POP', 'to' => 'Sudimara', 'name' => 'Trunk Sulis - Sudimara', 'coords' => [[-7.435926, 109.153747], [-7.418500, 109.146500]]],
            ['from' => 'Sudimara', 'to' => 'Pageraji', 'name' => 'Trunk Sudimara - Pageraji', 'coords' => [[-7.418500, 109.146500], [-7.412500, 109.142000]]],
            ['from' => 'Sudimara', 'to' => 'Cipete', 'name' => 'Trunk Sudimara - Cipete', 'coords' => [[-7.418500, 109.146500], [-7.408500, 109.158500]]],
            ['from' => 'Sawangan', 'to' => 'Batuanten', 'name' => 'Trunk Sawangan - Batuanten', 'coords' => [[-7.438820, 109.142180], [-7.428500, 109.138200]]],
            ['from' => 'Batuanten', 'to' => 'Cikidang', 'name' => 'Trunk Batuanten - Cikidang', 'coords' => [[-7.428500, 109.138200], [-7.418500, 109.129000]]]
        ];

        $result = [
            'status'         => 'success',
            'router_identity'=> $routerIdentity,
            'router_host'    => $this->getHost() . ':' . $this->getPort(),
            'total_secret'   => count($secrets),
            'total_online'   => $onlineCount,
            'total_offline'  => count($secrets) - $onlineCount,
            'total_isolir'   => $isolirCount,
            'total_odp'      => count($odpList),
            'server_node'    => [
                'name' => 'SERVER UTAMA (Sulis Tower)',
                'lat'  => $villageCenters['sulis'][0],
                'lng'  => $villageCenters['sulis'][1]
            ],
            'backbones'      => $backbones,
            'villages'       => array_values(array_unique(array_column($clients, 'desa'))),
            'odps'           => array_values($odpList),
            'clients'        => $clients,
            'updated_at'     => now('Asia/Jakarta')->translatedFormat('H:i:s') . ' WIB'
        ];

        // Simpan di cache selama 20 detik agar responsif dan tidak membebani CPU MikroTik
        Cache::put($cacheKey, $result, 20);

        return response()->json($result);
    }

    /**
     * Endpoint API Isolir / Buka Isolir Langsung ke MikroTik
     */
    public function toggleIsolir(Request $request)
    {
        $rawName       = trim($request->input('raw_name') ?? $request->input('username') ?? '');
        $secretIdInput = trim($request->input('secret_id') ?? '');
        $action        = $request->input('action'); // 'isolir' atau 'unisolir'
        $normalProfile = $request->input('normal_profile');

        $API = $this->connectApi(5);
        if (!$API) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal terhubung ke router MikroTik Winbox (' . $this->getHost() . ').'
            ], 500);
        }

        $allSecrets = $API->comm('/ppp/secret/print');
        $targetSecret = null;

        // 1. Cari berdasarkan .id MikroTik
        if (!empty($secretIdInput)) {
            foreach ($allSecrets as $sec) {
                if (isset($sec['.id']) && $sec['.id'] === $secretIdInput) {
                    $targetSecret = $sec;
                    break;
                }
            }
        }

        // 2. Cari berdasarkan raw_name persis
        if (!$targetSecret && !empty($rawName)) {
            foreach ($allSecrets as $sec) {
                if (isset($sec['name']) && strtolower(trim($sec['name'])) === strtolower($rawName)) {
                    $targetSecret = $sec;
                    break;
                }
            }
        }

        // 3. Fallback prefix
        if (!$targetSecret && !empty($rawName)) {
            $prefix = strtolower(explode('/', explode('@', explode('_', $rawName)[0])[0])[0]);
            foreach ($allSecrets as $sec) {
                $sName = strtolower(trim($sec['name'] ?? ''));
                if (str_starts_with($sName, $prefix)) {
                    $targetSecret = $sec;
                    break;
                }
            }
        }

        if (!$targetSecret || empty($targetSecret['.id'])) {
            $API->disconnect();
            return response()->json([
                'status'  => 'error',
                'message' => "Tidak ditemukan baris secret di MikroTik untuk [{$rawName}]."
            ], 404);
        }

        $secretId       = $targetSecret['.id'];
        $secretName     = $targetSecret['name'];
        $currentProfile = $targetSecret['profile'] ?? 'default';

        if ($action === 'isolir') {
            $targetProfile = 'profile-isolir';
        } else {
            $targetProfile = (!empty($normalProfile) && $normalProfile !== 'profile-isolir' && $normalProfile !== 'ISOLIR')
                             ? $normalProfile
                             : ($currentProfile !== 'profile-isolir' && $currentProfile !== 'ISOLIR' ? $currentProfile : 'PPPoE-Under-Vlan-90');
        }

        // Set Profile Baru di MikroTik
        $API->comm('/ppp/secret/set', [
            '.id'     => $secretId,
            'profile' => $targetProfile
        ]);

        // Putus sesi aktif agar dial ulang dengan profile baru
        $allActives = $API->comm('/ppp/active/print');
        $prefix = strtolower(explode('/', explode('@', explode('_', $secretName)[0])[0])[0]);

        foreach ($allActives as $act) {
            $aName = strtolower(trim($act['name'] ?? ''));
            if ($aName === strtolower($secretName) || str_starts_with($aName, $prefix)) {
                if (isset($act['.id'])) {
                    $API->comm('/ppp/active/remove', [
                        '.id' => $act['.id']
                    ]);
                }
            }
        }

        $API->disconnect();

        // Hapus cache agar pembaruan langsung terlihat
        Cache::forget('mikrotik_pure_map_data_cache');

        $pesan = ($action === 'isolir') ? 'DIISOLIR' : 'DIBUKA KEMBALI';
        return response()->json([
            'status'          => 'success',
            'message'         => "Pelanggan [{$secretName}] berhasil {$pesan} di router MikroTik!",
            'current_profile' => $targetProfile,
            'is_isolir'       => ($action === 'isolir')
        ]);
    }

    /**
     * Endpoint API Kick / Restart Sesi PPPoE di MikroTik
     */
    public function kickSession(Request $request)
    {
        $username = trim($request->input('username') ?? '');
        if (empty($username)) {
            return response()->json(['status' => 'error', 'message' => 'Username tidak boleh kosong'], 400);
        }

        $API = $this->connectApi(5);
        if (!$API) {
            return response()->json(['status' => 'error', 'message' => 'Gagal koneksi ke MikroTik.'], 500);
        }

        $allActives = $API->comm('/ppp/active/print');
        $kicked = false;
        foreach ($allActives as $act) {
            if (isset($act['name']) && strtolower(trim($act['name'])) === strtolower($username)) {
                if (isset($act['.id'])) {
                    $API->comm('/ppp/active/remove', ['.id' => $act['.id']]);
                    $kicked = true;
                }
            }
        }
        $API->disconnect();
        Cache::forget('mikrotik_pure_map_data_cache');

        if ($kicked) {
            return response()->json(['status' => 'success', 'message' => "Sesi PPPoE [{$username}] berhasil di-restart."]);
        }

        return response()->json(['status' => 'error', 'message' => "Sesi aktif [{$username}] tidak ditemukan (sedang offline)."]);
    }

    /**
     * Endpoint API Active Users MikroTik
     */
    public function getActiveUsers(Request $request)
    {
        $API = $this->connectApi(4);
        if (!$API) {
            return response()->json(['status' => 'error', 'message' => 'Gagal koneksi ke MikroTik'], 500);
        }

        $actives = $API->comm('/ppp/active/print');
        $API->disconnect();

        return response()->json([
            'status' => 'success',
            'count'  => count($actives),
            'data'   => $actives
        ]);
    }

    /**
     * Sinkronisasi data ke database lokal pelanggan
     */
    public function syncFromMikrotik()
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $API = $this->connectApi(5);
        if (!$API) {
            return back()->with('error', 'Gagal terhubung ke router MikroTik.');
        }

        $secrets = $API->comm('/ppp/secret/print');
        $actives = $API->comm('/ppp/active/print');
        $API->disconnect();

        $activeMap = [];
        foreach ($actives as $act) {
            $activeMap[$act['name']] = true;
        }

        $now = now();
        $targetTable = Schema::hasTable('customers') ? 'customers' : (Schema::hasTable('pelanggans') ? 'pelanggans' : 'customers');

        $totalDiproses = 0;
        foreach ($secrets as $idx => $sec) {
            $username = $sec['name'] ?? '';
            if (empty($username)) continue;

            $parts = explode('/', $username);
            $namaPemohon = !empty($parts[0]) ? ucwords(str_replace('_', ' ', trim($parts[0]))) : $username;
            $profile = $sec['profile'] ?? 'default';

            DB::table($targetTable)->updateOrInsert(
                ['pppoe' => $username],
                [
                    'customer_name'  => $namaPemohon,
                    'order_number'   => 'ORD-' . str_pad(($idx + 1), 5, '0', STR_PAD_LEFT),
                    'id_card_number' => '3302' . str_pad(($idx + 100), 12, '0', STR_PAD_LEFT),
                    'customer_phone' => '08' . rand(1111111111, 9999999999),
                    'package_name'   => 'Paket ' . ($profile === 'default' ? '20 Mbps' : $profile),
                    'price'          => 110000,
                    'address'        => 'Desa ' . ($parts[1] ?? 'Banyumas') . ' RT 01/RW 01',
                    'status'         => ($profile === 'profile-isolir' || $profile === 'ISOLIR') ? 'Non-Aktif' : 'Selesai',
                    'updated_at'     => $now,
                    'created_at'     => $now
                ]
            );
            $totalDiproses++;
        }

        return redirect('/admin/pelanggan')->with('success', "Berhasil menyinkronkan {$totalDiproses} pelanggan dari MikroTik!");
    }
}
