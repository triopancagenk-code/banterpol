@extends('layouts.admin')
@section('title', 'Admin NOC Banterpool - Peta ODC, ODP & Jaringan Fiber Optik')
@section('page-title', 'Peta Geospasial Infrastruktur Threefive Network (GIS NOC)')

@push('styles')
<!-- CSS Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .map-container {
        width: 100%;
        height: 78vh;
        min-height: 650px;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        position: relative;
        background: #0b0e14;
    }
    #odcGisMap {
        width: 100%;
        height: 100%;
        background: #0b0e14;
    }

    /* Dark Mode Filter OpenStreetMap */
    .map-dark-theme {
        filter: brightness(0.6) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3) brightness(0.7);
    }

    /* Animasi Kabel Fiber Optik */
    @keyframes cableFlow {
        to { stroke-dashoffset: -28; }
    }
    .cable-flow {
        stroke-dasharray: 6 8 !important;
        animation: cableFlow 1.5s linear infinite !important;
    }

    /* Pewarnaan Jalur Kabel Sesuai Desa */
    .cable-kasegeran { stroke: #d946ef !important; filter: drop-shadow(0 0 5px #d946ef) drop-shadow(0 0 8px #a855f7); }
    .cable-jatisaba  { stroke: #a3e635 !important; filter: drop-shadow(0 0 5px #a3e635) drop-shadow(0 0 8px #84cc16); }
    .cable-pageraji  { stroke: #00f3ff !important; filter: drop-shadow(0 0 5px #00f3ff) drop-shadow(0 0 8px #0284c7); }
    .cable-karangendep { stroke: #fb923c !important; filter: drop-shadow(0 0 5px #fb923c) drop-shadow(0 0 8px #ea580c); }
    .cable-panusupan { stroke: #22c55e !important; filter: drop-shadow(0 0 5px #22c55e) drop-shadow(0 0 8px #16a34a); }
    .cable-cipete    { stroke: #f43f5e !important; filter: drop-shadow(0 0 5px #f43f5e); }
    .cable-cikidang  { stroke: #facc15 !important; filter: drop-shadow(0 0 5px #facc15); }
    .cable-default   { stroke: #ffffff !important; filter: drop-shadow(0 0 4px #ffffff); }

    /* Ikon Bendera Tiang */
    .flag-pole-icon {
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        filter: drop-shadow(0 0 4px #ffeb3b) drop-shadow(0 0 8px #f57f17);
    }
    .flag-pole-icon svg {
        width: 100%;
        height: 100%;
        fill: #ffeb3b;
    }

    /* Tooltip / Label Nama Klien */
    .client-label {
        background: rgba(10, 15, 24, 0.85) !important;
        border: 1px solid rgba(0, 243, 255, 0.4) !important;
        color: #e0f2fe !important;
        font-size: 10px !important;
        font-weight: 500 !important;
        padding: 1px 5px !important;
        border-radius: 4px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.5) !important;
        white-space: nowrap;
    }
    .client-label::before {
        display: none !important;
    }

    /* Banner Status Menggambar Kabel */
    #cableDrawingBanner {
        display: none;
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        background: rgba(124, 58, 237, 0.95);
        color: #fff;
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.5);
        border: 1px solid #c4b5fd;
    }

    /* Tombol Status Atas */
    .custom-overlay-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: rgba(13, 17, 23, 0.9);
        color: #00ffcc;
        border: 1px solid #00ffcc;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 12px;
        box-shadow: 0 0 10px rgba(0, 255, 204, 0.2);
    }

    /* Legenda Topologi */
    .legend-box {
        position: absolute;
        bottom: 20px;
        right: 20px;
        background: rgba(13, 17, 23, 0.94);
        border: 1px solid #1f2937;
        border-radius: 8px;
        padding: 10px 14px;
        color: #ffffff;
        font-family: inherit;
        font-size: 11px;
        max-height: 55vh;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.6);
    }
    .legend-row {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    .legend-row:last-child { margin-bottom: 0; }
    .legend-sym {
        width: 22px;
        margin-right: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .legend-sym img {
        max-width: 16px;
        max-height: 16px;
    }
    .line-badge {
        width: 16px;
        height: 3px;
        border-radius: 2px;
    }

    /* Popup Leaflet */
    .leaflet-popup-content-wrapper {
        background: #101726;
        color: #f3f4f6;
        border: 1px solid #1e293b;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7);
    }
    .leaflet-popup-tip {
        background: #101726;
    }
    .popup-actions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-top: 8px;
    }
    .btn-act-popup {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 6px 4px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-act-popup:hover {
        opacity: 0.85;
    }
    .btn-add-client { background: #2563eb; color: #ffffff !important; }
    .btn-add-odp { background: #059669; color: #ffffff !important; }
    .btn-add-pole { background: #d97706; color: #ffffff !important; }
    .btn-add-cable { background: #7c3aed; color: #ffffff !important; }
    .btn-copy-coord {
        grid-column: span 2;
        background: #1f2937;
        color: #38bdf8 !important;
        border: 1px solid #374151;
    }
    .btn-delete-marker {
        width: 100%;
        background: #dc2626;
        color: #ffffff !important;
        margin-top: 8px;
        padding: 5px 8px;
        border-radius: 4px;
        border: none;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-delete-marker:hover {
        background: #b91c1c;
    }
</style>
@endpush

@section('content')
<div class="card bg-dark border-0">
    <div class="card-body p-0">
        <div class="map-container">
            <!-- Indikator mode gambar kabel -->
            <div id="cableDrawingBanner">
                ⚡ Mode Tarik Kabel: Klik titik-titik rute, Klik 2x untuk Selesai
            </div>

            <button class="custom-overlay-btn">Mode Malam: AKTIF</button>
            
            <div id="odcGisMap"></div>

            <!-- UI Legenda -->
            <div class="legend-box">
                <div style="font-weight: bold; margin-bottom: 6px; color: #00ffff; font-size: 11px;">PERANGKAT</div>
                <div class="legend-row">
                    <div class="legend-sym"><img src="{{ asset('peta_jaringan/files/odp.png') }}" alt="ODP" onerror="this.style.display='none'"></div>
                    <span>ODP</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym">
                        <div class="flag-pole-icon">
                            <svg viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg>
                        </div>
                    </div>
                    <span>Tiang (Bendera)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><img src="{{ asset('peta_jaringan/files/location_4985966.png') }}" alt="Klien" onerror="this.style.display='none'"></div>
                    <span>Klien / Pelanggan</span>
                </div>

                <div style="font-weight: bold; margin: 8px 0 5px 0; color: #00ffff; font-size: 11px;">RUTE KABEL FO (DESA)</div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #d946ef; box-shadow: 0 0 4px #d946ef;"></div></div>
                    <span>Kasegeran (Ungu)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #a3e635; box-shadow: 0 0 4px #a3e635;"></div></div>
                    <span>Jatisaba (Lime)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #00f3ff; box-shadow: 0 0 4px #00f3ff;"></div></div>
                    <span>Pageraji (Cyan)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #fb923c; box-shadow: 0 0 4px #fb923c;"></div></div>
                    <span>Karangendep (Oranye)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #22c55e; box-shadow: 0 0 4px #22c55e;"></div></div>
                    <span>Panusupan (Hijau)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #f43f5e; box-shadow: 0 0 4px #f43f5e;"></div></div>
                    <span>Cipete (Pink)</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #facc15; box-shadow: 0 0 4px #facc15;"></div></div>
                    <span>Cikidang / Batuanten</span>
                </div>
                <div class="legend-row">
                    <div class="legend-sym"><div class="line-badge" style="background: #ffffff; box-shadow: 0 0 4px #ffffff;"></div></div>
                    <span>Backbone Lainnya</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- JS Leaflet & Omnivore -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-omnivore@0.3.4/leaflet-omnivore.min.js"></script>

<script>
    var map;
    var odpIcon, clientIcon, flagPoleIcon;
    var markersList = {};

    // State untuk menggambar kabel interaktif
    var isDrawingCable = false;
    var cablePoints = [];
    var tempCablePolyline = null;
    var currentCableName = '';
    var currentCableClass = 'cable-default';

    function copyCoordinate(lat, lng) {
        var text = lat + ', ' + lng;
        navigator.clipboard.writeText(text).then(function() {
            alert('Koordinat berhasil disalin:\n' + text);
        }).catch(function() {
            prompt('Salin manual koordinat ini:', text);
        });
    }

    function removeMarker(layerId) {
        if (confirm('Apakah Anda yakin ingin menghapus item ini dari peta?')) {
            if (markersList[layerId]) {
                map.removeLayer(markersList[layerId]);
                delete markersList[layerId];
            }
        }
    }

    function attachDeletablePopup(layer, title, subtitle) {
        var layerId = L.stamp(layer);
        markersList[layerId] = layer;

        var content = `
            <div style="font-size: 12px; min-width: 160px;">
                <div style="font-weight: bold; color: #00ffff; font-size: 13px;">${title}</div>
                <div style="color: #94a3b8; font-size: 11px; margin-top: 2px;">${subtitle}</div>
                <button type="button" onclick="removeMarker(${layerId})" class="btn-delete-marker">
                    🗑️ Hapus Titik Ini
                </button>
            </div>
        `;
        layer.bindPopup(content);
    }

    function placeDeviceOnMap(type, lat, lng) {
        var marker;
        if (type === 'client') {
            var name = prompt('Masukkan Nama Klien/Pelanggan:', 'Klien Baru');
            if (name === null) return;
            marker = L.marker([lat, lng], { icon: clientIcon }).addTo(map);
            marker.bindTooltip(name, {
                permanent: true,
                direction: 'bottom',
                offset: [0, 10],
                className: 'client-label'
            });
            attachDeletablePopup(marker, name, 'Klien Baru (' + lat + ', ' + lng + ')');
            marker.openPopup();
        } else if (type === 'odp') {
            var odpName = prompt('Masukkan Kode/Nama ODP:', 'ODP-NEW-01');
            if (odpName === null) return;
            marker = L.marker([lat, lng], { icon: odpIcon }).addTo(map);
            marker.bindTooltip(odpName, {
                permanent: true,
                direction: 'bottom',
                offset: [0, 12],
                className: 'client-label'
            });
            attachDeletablePopup(marker, odpName, 'ODP Baru (' + lat + ', ' + lng + ')');
            marker.openPopup();
        } else if (type === 'pole') {
            var poleName = prompt('Masukkan Kode Tiang (contoh: T-TF 15):', 'T-TF New');
            if (poleName === null) return;
            marker = L.marker([lat, lng], { icon: flagPoleIcon }).addTo(map);
            marker.bindTooltip(poleName, {
                permanent: true,
                direction: 'bottom',
                offset: [0, 8],
                className: 'client-label'
            });
            attachDeletablePopup(marker, poleName, 'Tiang Baru (' + lat + ', ' + lng + ')');
            marker.openPopup();
        } else if (type === 'cable') {
            var cableName = prompt('Masukkan Nama Rute Kabel (contoh: Backbone_Jatisaba / Distribusi_Kasegeran):', 'Backbone_Baru');
            if (!cableName) return;

            currentCableName = cableName;
            var cLower = cableName.toLowerCase();
            if (cLower.includes('kasegeran')) currentCableClass = 'cable-kasegeran';
            else if (cLower.includes('jatisaba')) currentCableClass = 'cable-jatisaba';
            else if (cLower.includes('pageraji')) currentCableClass = 'cable-pageraji';
            else if (cLower.includes('karangendep')) currentCableClass = 'cable-karangendep';
            else if (cLower.includes('panusupan')) currentCableClass = 'cable-panusupan';
            else if (cLower.includes('cipete')) currentCableClass = 'cable-cipete';
            else if (cLower.includes('cikidang') || cLower.includes('batuanten')) currentCableClass = 'cable-cikidang';
            else currentCableClass = 'cable-default';

            // Memulai penarikan kabel
            isDrawingCable = true;
            cablePoints = [[lat, lng]]; // Titik awal dari klik pertama
            document.getElementById('cableDrawingBanner').style.display = 'block';

            tempCablePolyline = L.polyline(cablePoints, {
                weight: 2.5,
                className: 'cable-flow ' + currentCableClass
            }).addTo(map);

            map.closePopup();
            return;
        }

        map.closePopup();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // 1. Inisialisasi Peta
        map = L.map('odcGisMap', {
            preferCanvas: false,
            doubleClickZoom: false // Dimatikan agar double click bisa dipakai untuk menyelesaikan kabel
        }).setView([-7.43261, 109.14656], 14);

        // 2. Basemap Dark Mode
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
            className: 'map-dark-theme'
        }).addTo(map);

        // 3. Definisi Ikon
        odpIcon = L.icon({
            iconUrl: "{{ asset('peta_jaringan/files/odp.png') }}",
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            popupAnchor: [0, -11]
        });

        clientIcon = L.icon({
            iconUrl: "{{ asset('peta_jaringan/files/location_4985966.png') }}",
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            popupAnchor: [0, -10]
        });

        flagPoleIcon = L.divIcon({
            className: '',
            html: '<div class="flag-pole-icon"><svg viewBox="0 0 24 24"><path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6z"/></svg></div>',
            iconSize: [18, 18],
            iconAnchor: [4, 18]
        });

        // 4. Custom Layer Pembaca Data KML
        var customLayer = L.geoJson(null, {
            pointToLayer: function (feature, latlng) {
                var props = feature.properties || {};
                var name = (props.name || '').trim();
                var nameLower = name.toLowerCase();
                var desc = (props.description || '').toLowerCase();
                var styleUrl = (props.styleUrl || '').toLowerCase();

                // Deteksi ODP
                if (nameLower.includes('odp') || desc.includes('odp') || styleUrl.includes('odp')) {
                    var marker = L.marker(latlng, { icon: odpIcon });
                    if (name) {
                        marker.bindTooltip(name, {
                            permanent: true,
                            direction: 'bottom',
                            offset: [0, 12],
                            className: 'client-label'
                        });
                    }
                    attachDeletablePopup(marker, name || 'ODP', 'Lat: ' + latlng.lat.toFixed(6) + ', Lng: ' + latlng.lng.toFixed(6));
                    return marker;
                }
                
                // Deteksi Tiang
                if (
                    nameLower.includes('untitled placemark') ||
                    nameLower.includes('t-tf') ||
                    nameLower.startsWith('ttf') ||
                    nameLower.includes('tiang') ||
                    nameLower.startsWith('t.') ||
                    nameLower.startsWith('t ') ||
                    nameLower.includes('pole') ||
                    desc.includes('tiang') ||
                    desc.includes('t-tf') ||
                    styleUrl.includes('flag')
                ) {
                    var marker = L.marker(latlng, { icon: flagPoleIcon });
                    if (name && !nameLower.includes('untitled placemark')) {
                        marker.bindTooltip(name, {
                            permanent: true,
                            direction: 'bottom',
                            offset: [0, 8],
                            className: 'client-label'
                        });
                    }
                    attachDeletablePopup(marker, name || 'Tiang', 'Lat: ' + latlng.lat.toFixed(6) + ', Lng: ' + latlng.lng.toFixed(6));
                    return marker;
                }

                // Default: Klien
                var marker = L.marker(latlng, { icon: clientIcon });
                if (name) {
                    marker.bindTooltip(name, {
                        permanent: true,
                        direction: 'bottom',
                        offset: [0, 10],
                        className: 'client-label'
                    });
                }
                attachDeletablePopup(marker, name || 'Klien', 'Lat: ' + latlng.lat.toFixed(6) + ', Lng: ' + latlng.lng.toFixed(6));
                return marker;
            },
            style: function (feature) {
                var props = feature.properties || {};
                var name = (props.name || '').toLowerCase();
                var desc = (props.description || '').toLowerCase();

                var colorClass = 'cable-default';

                if (name.includes('kasegeran') || desc.includes('kasegeran')) {
                    colorClass = 'cable-kasegeran';
                } else if (name.includes('jatisaba') || desc.includes('jatisaba')) {
                    colorClass = 'cable-jatisaba';
                } else if (name.includes('pageraji') || desc.includes('pageraji')) {
                    colorClass = 'cable-pageraji';
                } else if (name.includes('karangendep') || desc.includes('karangendep')) {
                    colorClass = 'cable-karangendep';
                } else if (name.includes('panusupan') || desc.includes('panusupan')) {
                    colorClass = 'cable-panusupan';
                } else if (name.includes('cipete') || desc.includes('cipete')) {
                    colorClass = 'cable-cipete';
                } else if (name.includes('cikidang') || name.includes('batuanten') || desc.includes('cikidang')) {
                    colorClass = 'cable-cikidang';
                }

                return {
                    weight: 2.5,
                    className: 'cable-flow ' + colorClass
                };
            },
            onEachFeature: function (feature, layer) {
                if (layer instanceof L.Polyline) {
                    var lineId = L.stamp(layer);
                    markersList[lineId] = layer;
                    layer.bindPopup(`
                        <div style="font-size: 12px; min-width: 140px;">
                            <b>${feature.properties.name || 'Jalur Kabel FO'}</b><br>
                            <button type="button" onclick="removeMarker(${lineId})" class="btn-delete-marker">
                                🗑️ Hapus Kabel Ini
                            </button>
                        </div>
                    `);
                }
            }
        });

        // 5. Muat file doc.kml
        var kmlLayer = omnivore.kml("{{ asset('peta_jaringan/doc.kml') }}", null, customLayer)
            .on('ready', function() {
                map.fitBounds(kmlLayer.getBounds());
            })
            .addTo(map);

        // 6. Event Klik Peta: Mode Normal vs Mode Tarik Kabel
        map.on('click', function(e) {
            if (isDrawingCable) {
                // Menambahkan titik berikutnya pada kabel yang sedang ditarik
                cablePoints.push([e.latlng.lat, e.latlng.lng]);
                tempCablePolyline.setLatLngs(cablePoints);
                return;
            }

            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);

            var popupContent = `
                <div style="font-size: 12px; min-width: 190px;">
                    <div style="font-weight: bold; color: #00ffff; margin-bottom: 2px;">Titik Koordinat Baru</div>
                    <div style="color: #94a3b8; font-size: 11px;">
                        Lat: <code>${lat}</code><br>
                        Lng: <code>${lng}</code>
                    </div>
                    <hr style="border: 0; border-top: 1px solid #1e293b; margin: 6px 0;">
                    
                    <div class="popup-actions-grid">
                        <button type="button" onclick="copyCoordinate('${lat}', '${lng}')" class="btn-act-popup btn-copy-coord">
                            📋 Salin Koordinat
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('client', ${lat}, ${lng})" class="btn-act-popup btn-add-client">
                            + Klien
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('odp', ${lat}, ${lng})" class="btn-act-popup btn-add-odp">
                            + ODP
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('pole', ${lat}, ${lng})" class="btn-act-popup btn-add-pole">
                            + Tiang
                        </button>
                        <button type="button" onclick="placeDeviceOnMap('cable', ${lat}, ${lng})" class="btn-act-popup btn-add-cable">
                            + Jalur FO
                        </button>
                    </div>
                </div>
            `;

            L.popup()
                .setLatLng(e.latlng)
                .setContent(popupContent)
                .openOn(map);
        });

        // 7. Selesaikan Penarikan Kabel saat Double-Click (Klik 2x)
        map.on('dblclick', function(e) {
            if (isDrawingCable && tempCablePolyline) {
                cablePoints.push([e.latlng.lat, e.latlng.lng]);
                tempCablePolyline.setLatLngs(cablePoints);

                var lineId = L.stamp(tempCablePolyline);
                markersList[lineId] = tempCablePolyline;

                tempCablePolyline.bindPopup(`
                    <div style="font-size: 12px; min-width: 140px;">
                        <b>${currentCableName}</b><br>
                        <button type="button" onclick="removeMarker(${lineId})" class="btn-delete-marker">
                            🗑️ Hapus Kabel Ini
                        </button>
                    </div>
                `);

                // Reset mode
                isDrawingCable = false;
                cablePoints = [];
                tempCablePolyline = null;
                document.getElementById('cableDrawingBanner').style.display = 'none';
            }
        });

        setTimeout(function() {
            map.invalidateSize();
        }, 300);
    });
</script>
@endpush