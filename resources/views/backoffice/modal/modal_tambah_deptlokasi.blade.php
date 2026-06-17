@if (auth()->user()->fhrd == 1)
@once('leaflet_css')
<!-- Leaflet & Geocoder CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
@endonce

<div class="modal fade" id="addDeptLokasiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('tdeptlokasi.store') }}" method="POST">
            @csrf
            <style>
                @media (min-width: 768px) {
                    .border-end-md {
                        border-right: 1px solid #dee2e6 !important;
                    }
                }
            </style>
            <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <div class="modal-header bg-danger text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold">Tambah Lokasi & WiFi Departemen</h5>
                </div>
                <div class="modal-body text-start p-4">
                    <div class="row g-3">
                        <!-- Kolom Kiri: Informasi Departemen & WiFi -->
                        <div class="col-md-6 border-end-md pe-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Departemen <span class="text-danger">*</span></label>
                                <select name="ndeptid" class="form-select border-2" required style="border-radius: 8px;">
                                    <option value="">-- Pilih Departemen --</option>
                                    @foreach ($departments as $dept)
                                    <option value="{{ $dept->nid }}">
                                        {{ $dept->cname }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Wifi</label>
                                <input type="text" name="cssid" class="form-control border-2" placeholder="Contoh: Matahati2027" style="border-radius: 8px;">
                                <small class="text-muted">Isi menggunakan nama wifi yang digunakan di lokasi.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Radius (Meter)</label>
                                <input type="text" step="any" id="add_nradius" name="nradius" class="form-control border-2" placeholder="Contoh: 100" style="border-radius: 8px;">
                                <small class="text-muted">Radius jangkauan lokasi yang diperbolehkan untuk absen.</small>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Peta & Koordinat -->
                        <div class="col-md-6 ps-md-4">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold mb-1">Latitude</label>
                                    <input type="number" step="any" id="add_nlat" name="nlat" class="form-control border-2" placeholder="Contoh: -8.077641" style="border-radius: 8px;">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold mb-1">Longitude</label>
                                    <input type="number" step="any" id="add_nlng" name="nlng" class="form-control border-2" placeholder="Contoh: 111.936132" style="border-radius: 8px;">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1">Peta Lokasi</label>
                                <div id="map-add" style="height: 220px; width: 100%; border: 2px solid #ccc; border-radius: 8px; margin-bottom: 5px;"></div>
                                <small class="text-muted d-block" style="font-size: 0.8rem; line-height: 1.2;">
                                    Klik pada peta atau geser penanda untuk menentukan koordinat secara otomatis.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between w-100 gap-2 bg-light p-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary flex-fill px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-success flex-fill px-4 py-2 fw-semibold" style="border-radius: 8px;">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
@once('leaflet_js')
<!-- Leaflet & Geocoder JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
@endonce

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let mapAdd;
        let markerAdd;
        let circleAdd;
        const fallbackLat = -7.801194;
        const fallbackLng = 110.364917;

        const radiusInput = document.getElementById("add_nradius");

        // Inisialisasi Map dengan koordinat fallback (Yogyakarta) terlebih dahulu
        mapAdd = L.map("map-add").setView([fallbackLat, fallbackLng], 13);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "© OpenStreetMap contributors",
        }).addTo(mapAdd);

        // Inisialisasi Marker
        markerAdd = L.marker([fallbackLat, fallbackLng], {
            draggable: true
        });

        function updateCircleAdd(latlng) {
            const radiusVal = radiusInput ? parseFloat(radiusInput.value) : 100;
            const finalRadius = isNaN(radiusVal) ? 0 : radiusVal;

            if (circleAdd) {
                circleAdd.setLatLng(latlng);
                circleAdd.setRadius(finalRadius);
            } else {
                circleAdd = L.circle(latlng, {
                    color: 'red',
                    fillColor: '#f03',
                    fillOpacity: 0.2,
                    radius: finalRadius
                }).addTo(mapAdd);
            }
        }

        // Tambahkan Search Geocoder ke peta (dibatasi hanya mencari di Indonesia agar lebih akurat)
        L.Control.geocoder({
                defaultMarkGeocode: false,
                geocoder: L.Control.Geocoder.nominatim({
                    geocodingQueryParams: {
                        countrycodes: 'id'
                    }
                })
            })
            .on("markgeocode", function(e) {
                const latlng = e.geocode.center;
                markerAdd.setLatLng(latlng).addTo(mapAdd);
                mapAdd.setView(latlng, 17);
                updateAddLocationFields(latlng);
            })
            .addTo(mapAdd);

        function updateAddLocationFields(latlng) {
            const lat = latlng.lat.toFixed(6);
            const lng = latlng.lng.toFixed(6);
            document.getElementById("add_nlat").value = lat;
            document.getElementById("add_nlng").value = lng;
            updateCircleAdd(latlng);
        }

        // Event klik pada peta
        mapAdd.on("click", function(e) {
            markerAdd.setLatLng(e.latlng).addTo(mapAdd);
            updateAddLocationFields(e.latlng);
        });

        // Event geser marker
        markerAdd.on("dragend", function(e) {
            updateAddLocationFields(e.target.getLatLng());
        });

        // Sinkronisasi manual jika user mengetik koordinat
        function syncInputsToMap() {
            const latVal = parseFloat(document.getElementById("add_nlat").value);
            const lngVal = parseFloat(document.getElementById("add_nlng").value);

            if (!isNaN(latVal) && !isNaN(lngVal)) {
                const latlng = L.latLng(latVal, lngVal);
                markerAdd.setLatLng(latlng).addTo(mapAdd);
                mapAdd.setView(latlng, 17);
                updateCircleAdd(latlng);
            }
        }

        document.getElementById("add_nlat").addEventListener("input", syncInputsToMap);
        document.getElementById("add_nlng").addEventListener("input", syncInputsToMap);

        if (radiusInput) {
            radiusInput.addEventListener("input", function() {
                const latVal = parseFloat(document.getElementById("add_nlat").value);
                const lngVal = parseFloat(document.getElementById("add_nlng").value);
                if (!isNaN(latVal) && !isNaN(lngVal)) {
                    updateCircleAdd(L.latLng(latVal, lngVal));
                }
            });
        }

        // Jalankan inisialisasi marker jika input latitude/longitude sudah berisi nilai
        const initLat = parseFloat(document.getElementById("add_nlat").value);
        const initLng = parseFloat(document.getElementById("add_nlng").value);
        if (!isNaN(initLat) && !isNaN(initLng)) {
            const latlng = L.latLng(initLat, initLng);
            markerAdd.setLatLng(latlng).addTo(mapAdd);
            mapAdd.setView(latlng, 17);
            updateCircleAdd(latlng);
        }

        // Fungsi untuk meminta lokasi terkini menggunakan Geolocation API
        function requestCurrentLocation() {
            const currentLat = parseFloat(document.getElementById("add_nlat").value);
            const currentLng = parseFloat(document.getElementById("add_nlng").value);

            // Hanya minta lokasi jika koordinat di input masih kosong
            if (isNaN(currentLat) || isNaN(currentLng)) {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const userLat = position.coords.latitude;
                            const userLng = position.coords.longitude;
                            const userLatLng = L.latLng(userLat, userLng);

                            markerAdd.setLatLng(userLatLng).addTo(mapAdd);
                            mapAdd.setView(userLatLng, 17);
                            updateAddLocationFields(userLatLng);
                        },
                        function(error) {
                            console.warn("Akses lokasi ditolak atau gagal:", error.message);
                        }, {
                            enableHighAccuracy: true,
                            timeout: 5000,
                            maximumAge: 0
                        }
                    );
                } else {
                    console.warn("Geolocation API tidak didukung oleh browser ini.");
                }
            }
        }

        // Panggil deteksi lokasi saat halaman dimuat
        requestCurrentLocation();

        // Pasang event bootstrap modal shown untuk me-refresh ukuran peta agar tidak bug render abu-abu (Vanilla JS)
        const addDeptLokasiModal = document.getElementById('addDeptLokasiModal');
        if (addDeptLokasiModal) {
            addDeptLokasiModal.addEventListener('shown.bs.modal', function() {
                if (mapAdd) {
                    setTimeout(() => {
                        mapAdd.invalidateSize();
                        requestCurrentLocation();
                    }, 200);
                }
            });
        }
    });
</script>
@endpush