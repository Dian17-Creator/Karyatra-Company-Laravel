@if (auth()->user()->fhrd == 1)
<div class="card mb-4 shadow-sm" id="masterDeptLokasiContainer">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span>Manajemen Lokasi & WiFi Departemen</span>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addDeptLokasiModal">
            + Tambah Lokasi
        </button>
    </div>
    <div class="card-body">
        <table class="table table-bordered text-center align-middle">
            <thead class="text-center" style="background:#d7ffe2">
                <tr>
                    <th style="background-color: #ffd8e0ff !important;">Departemen</th>
                    <th style="background-color: #ffd8e0ff !important;">SSID WiFi</th>
                    <th style="background-color: #ffd8e0ff !important;">Latitude</th>
                    <th style="background-color: #ffd8e0ff !important;">Longitude</th>
                    <th style="background-color: #ffd8e0ff !important;">Radius (Meter)</th>
                    <th style="background-color: #ffd8e0ff !important;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @isset($deptLocations)
                @forelse ($deptLocations as $loc)
                <tr>
                    <td>
                        <span class="badge bg-primary">
                            {{ $loc->department->cname ?? 'Tidak Diketahui' }}
                        </span>
                    </td>
                    <td>
                        @if($loc->cssid)
                        <code>{{ $loc->cssid }}</code>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $loc->nlat ?? '-' }}</td>
                    <td>{{ $loc->nlng ?? '-' }}</td>
                    <td>
                        @if($loc->nradius)
                        {{ number_format($loc->nradius, 0) }} m
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#editDeptLokasiModal{{ $loc->nid }}">
                                Edit
                            </button>
                            <form action="{{ route('tdeptlokasi.destroy', $loc->nid) }}" method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-muted">Belum ada data lokasi departemen</td>
                </tr>
                @endforelse
                @else
                <tr>
                    <td colspan="7" class="text-muted">Data lokasi tidak tersedia</td>
                </tr>
                @endisset
            </tbody>
        </table>

        {{-- Modals --}}
        @isset($deptLocations)
        @foreach ($deptLocations as $loc)
        @include('backoffice.modal.modal_edit_deptlokasi', ['loc' => $loc])
        @endforeach
        @endisset

        @if ($deptLocations instanceof \Illuminate\Pagination\LengthAwarePaginator && $deptLocations->hasPages())
        <div class="mt-3 px-2">
            {{ $deptLocations->links('penggajian.components.custom_pagination') }}
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.deptLokasiPaginationBound) return;
        window.deptLokasiPaginationBound = true;

        document.addEventListener('click', async function(e) {
            const paginationLink = e.target.closest('#masterDeptLokasiContainer .custom-page-link');

            if (paginationLink && paginationLink.tagName.toLowerCase() === 'a') {
                e.preventDefault();

                const container = document.getElementById('masterDeptLokasiContainer');
                if (!container) return;

                // Efek loading transparan
                container.style.transition = 'opacity 0.2s';
                container.style.opacity = '0.5';
                container.style.pointerEvents = 'none';

                try {
                    const response = await fetch(paginationLink.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Component': 'master_deptlokasi'
                        }
                    });
                    const html = await response.text();

                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContent = doc.getElementById('masterDeptLokasiContainer');

                    if (newContent) {
                        container.innerHTML = newContent.innerHTML;
                    }

                    // Update URL browser tanpa refresh
                    window.history.pushState({}, '', paginationLink.href);

                } catch (error) {
                    console.error('Error fetching pagination:', error);
                } finally {
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                }
            }
        });
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const mapEditInstances = {};

        // Gunakan event delegation untuk mendeteksi kapan modal edit dibuka
        document.addEventListener('shown.bs.modal', function(event) {
            const modal = event.target;
            if (modal.id && modal.id.startsWith('editDeptLokasiModal')) {
                const locId = modal.id.replace('editDeptLokasiModal', '');

                setTimeout(() => {
                    initEditMap(locId);
                }, 200);
            }
        });

        function initEditMap(locId) {
            const mapContainer = document.getElementById(`map-edit-${locId}`);
            if (!mapContainer) return;

            const latInput = document.getElementById(`edit_nlat_${locId}`);
            const lngInput = document.getElementById(`edit_nlng_${locId}`);
            if (!latInput || !lngInput) return;

            const fallbackLat = -7.801194;
            const fallbackLng = 110.364917;

            let initLat = parseFloat(latInput.value);
            let initLng = parseFloat(lngInput.value);

            const hasInitCoord = !isNaN(initLat) && !isNaN(initLng);
            const startLat = hasInitCoord ? initLat : fallbackLat;
            const startLng = hasInitCoord ? initLng : fallbackLng;

            // Jika peta belum dibuat untuk ID ini, buat sekarang
            if (!mapEditInstances[locId]) {
                const map = L.map(`map-edit-${locId}`).setView([startLat, startLng], 13);
                L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                    attribution: "© OpenStreetMap contributors",
                }).addTo(map);

                const marker = L.marker([startLat, startLng], {
                    draggable: true
                });

                const radiusInput = document.getElementById(`edit_nradius_${locId}`);
                let circle;

                function updateCircle(latlng) {
                    const radiusVal = radiusInput ? parseFloat(radiusInput.value) : 100;
                    const finalRadius = isNaN(radiusVal) ? 0 : radiusVal;

                    if (circle) {
                        circle.setLatLng(latlng);
                        circle.setRadius(finalRadius);
                    } else {
                        circle = L.circle(latlng, {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.2,
                            radius: finalRadius
                        }).addTo(map);
                    }
                }

                if (hasInitCoord) {
                    marker.addTo(map);
                    map.setView([startLat, startLng], 17);
                    updateCircle([startLat, startLng]);
                }

                // Tambahkan Search Geocoder ke peta (dibatasi hanya mencari di Indonesia agar lebih akurat)
                const geocoder = L.Control.geocoder({
                        defaultMarkGeocode: false,
                        geocoder: L.Control.Geocoder.nominatim({
                            geocodingQueryParams: {
                                countrycodes: 'id'
                            }
                        })
                    })
                    .on("markgeocode", function(e) {
                        const latlng = e.geocode.center;
                        marker.setLatLng(latlng).addTo(map);
                        map.setView(latlng, 17);
                        updateFields(latlng);
                    })
                    .addTo(map);

                function updateFields(latlng) {
                    const lat = latlng.lat.toFixed(6);
                    const lng = latlng.lng.toFixed(6);
                    latInput.value = lat;
                    lngInput.value = lng;
                    updateCircle(latlng);
                }

                // Event klik pada peta
                map.on("click", function(e) {
                    marker.setLatLng(e.latlng).addTo(map);
                    updateFields(e.latlng);
                });

                // Event geser marker
                marker.on("dragend", function(e) {
                    updateFields(e.target.getLatLng());
                });

                // Sinkronisasi manual jika user mengetik koordinat
                function syncInputsToMap() {
                    const latVal = parseFloat(latInput.value);
                    const lngVal = parseFloat(lngInput.value);

                    if (!isNaN(latVal) && !isNaN(lngVal)) {
                        const latlng = L.latLng(latVal, lngVal);
                        marker.setLatLng(latlng).addTo(map);
                        map.setView(latlng, 17);
                        updateCircle(latlng);
                    }
                }

                latInput.addEventListener("input", syncInputsToMap);
                lngInput.addEventListener("input", syncInputsToMap);

                if (radiusInput) {
                    radiusInput.addEventListener("input", function() {
                        const latVal = parseFloat(latInput.value);
                        const lngVal = parseFloat(lngInput.value);
                        if (!isNaN(latVal) && !isNaN(lngVal)) {
                            updateCircle(L.latLng(latVal, lngVal));
                        }
                    });
                }

                // Fungsi minta lokasi jika koordinat kosong
                function requestCurrentLocation() {
                    const currentLat = parseFloat(latInput.value);
                    const currentLng = parseFloat(lngInput.value);

                    if (isNaN(currentLat) || isNaN(currentLng)) {
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(
                                function(position) {
                                    const userLat = position.coords.latitude;
                                    const userLng = position.coords.longitude;
                                    const userLatLng = L.latLng(userLat, userLng);

                                    marker.setLatLng(userLatLng).addTo(map);
                                    map.setView(userLatLng, 17);
                                    updateFields(userLatLng);
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

                mapEditInstances[locId] = {
                    map,
                    marker,
                    updateFields,
                    requestCurrentLocation
                };

                // Minta lokasi terkini jika koordinat awal kosong
                requestCurrentLocation();
            } else {
                // Jika peta sudah ada, panggil invalidateSize agar render lancar
                mapEditInstances[locId].map.invalidateSize();

                // Minta lokasi terkini jika koordinat kosong
                mapEditInstances[locId].requestCurrentLocation();
            }
        }
    });
</script>
@endpush
@endif