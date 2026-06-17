@if (auth()->user()->fhrd == 1)
@once('leaflet_css')
<!-- Leaflet & Geocoder CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
@endonce

<div class="modal fade" id="editDeptLokasiModal{{ $loc->nid }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('tdeptlokasi.update', $loc->nid) }}" method="POST">
            @csrf
            @method('PUT')
            <style>
                @media (min-width: 768px) {
                    .border-end-md {
                        border-right: 1px solid #dee2e6 !important;
                    }
                }
            </style>
            <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
                <div class="modal-header bg-warning text-white py-3" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold">Edit Lokasi & WiFi Departemen</h5>
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
                                    <option value="{{ $dept->nid }}" {{ $loc->ndeptid == $dept->nid ? 'selected' : '' }}>
                                        {{ $dept->cname }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Wifi</label>
                                <input type="text" name="cssid" class="form-control border-2" value="{{ $loc->cssid }}" placeholder="Contoh: Matahati2027" style="border-radius: 8px;">
                                <small class="text-muted">Isi menggunakan nama wifi yang digunakan di lokasi.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Radius (Meter)</label>
                                <input type="text" step="any" id="edit_nradius_{{ $loc->nid }}" name="nradius" class="form-control border-2" value="{{ $loc->nradius }}" placeholder="Contoh: 100" style="border-radius: 8px;">
                                <small class="text-muted">Radius jangkauan GPS yang diperbolehkan untuk absen.</small>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Peta & Koordinat -->
                        <div class="col-md-6 ps-md-4">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold mb-1">Latitude</label>
                                    <input type="number" step="any" id="edit_nlat_{{ $loc->nid }}" name="nlat" class="form-control border-2" value="{{ $loc->nlat }}" placeholder="Contoh: -8.077641" style="border-radius: 8px;">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold mb-1">Longitude</label>
                                    <input type="number" step="any" id="edit_nlng_{{ $loc->nid }}" name="nlng" class="form-control border-2" value="{{ $loc->nlng }}" placeholder="Contoh: 111.936132" style="border-radius: 8px;">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1">Peta Lokasi</label>
                                <div id="map-edit-{{ $loc->nid }}" style="height: 220px; width: 100%; border: 2px solid #ccc; border-radius: 8px; margin-bottom: 5px;"></div>
                                <small class="text-muted d-block" style="font-size: 0.8rem; line-height: 1.2;">
                                    Klik pada peta atau geser penanda untuk menentukan koordinat secara otomatis.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between w-100 gap-2 bg-light p-3" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary flex-fill px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-warning flex-fill px-4 py-2 fw-semibold text-dark" style="border-radius: 8px;">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif