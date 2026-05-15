@extends('layouts.app')

@section('title', 'Halaman Penggajian')

@section('content')

    @if (auth()->user()->isPayrollAccess())

        <link rel="stylesheet" href="{{ asset('css/penggajian.css') }}?v=1">

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-gaji btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahRekening">
                + Tambah Rekening
            </button>
        </div>

        @include('penggajian.components.table_rekening')

        @include('penggajian.modals.modal_tambah_rekening')

        @include('penggajian.modals.modal_edit_rekening')

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-gaji btn-success" data-bs-toggle="modal" data-bs-target="#modalTambahGaji">
                + Tambah Gaji
            </button>
        </div>

        @include('penggajian.components.table_tunjangan')

        @include('penggajian.modals.modal_tambah_gaji')

        @php
            // get current department query parameter (validated in controller ideally)
            $curDepRaw = request()->query('department_id', '');

            // build list of valid department ids as strings
            $validDepIds = $departments
                ->pluck('id')
                ->map(function ($v) {
                    return (string) $v;
                })
                ->toArray();

            // if query param not in valid list, treat as empty (All)
            $curDep = in_array((string) $curDepRaw, $validDepIds, true) ? (string) $curDepRaw : '';
        @endphp

        <div class="d-flex justify-content-between align-items-start mb-3">
            {{-- LEFT: filter departemen --}}
            <form id="formDepartmentFilter" method="GET" action="{{ route('penggajian.index') }}"
                class="d-flex align-items-center gap-2" autocomplete="off">
                <input type="hidden" name="year" value="{{ $selYear }}">
                <input type="hidden" name="month" value="{{ $selMonth }}">

                <select id="departmentFilter" name="department_id" class="form-select" style="width:220px;"
                    autocomplete="off">
                    <option value="" {{ $curDep === '' ? 'selected' : '' }}>Semua Departemen</option>
                    @foreach ($departments as $dep)
                        <option value="{{ $dep->nid }}" {{ (string) $curDep === (string) $dep->nid ? 'selected' : '' }}>
                            {{ $dep->cname }}
                        </option>
                    @endforeach
                </select>

                <select name="year" class="form-select" style="width:110px;">
                    @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}" {{ $selYear == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                {{-- MONTH --}}
                <select name="month" class="form-select" style="width:130px;">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selMonth == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>


                @if ($curDep !== '')
                    <a href="{{ route('penggajian.index', ['year' => $selYear, 'month' => $selMonth]) }}"
                        class="btn btn-secondary btn-sm" id="btnResetDepartment">Reset</a>
                @endif
            </form>

            {{-- RIGHT: tombol aksi --}}
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-gaji btn-success" data-bs-toggle="modal" data-bs-target="#modalPayrollBank">
                    Payroll Bank
                </button>

                <button class="btn btn-gaji btn-dark" data-bs-toggle="modal" data-bs-target="#modalReportKehadiran">
                    Report Kehadiran
                </button>

                <button class="btn btn-gaji btn-secondary" data-bs-toggle="modal" data-bs-target="#modalReportGaji">
                    Report Gaji
                </button>

                <form id="formExportExcel" action="{{ route('gaji.export') }}" method="POST" style="display:none;">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selMonth }}">
                    <input type="hidden" name="year" value="{{ $selYear }}">
                    <input type="hidden" name="selected_ids" id="selected_ids">
                    <input type="hidden" name="source_table" id="source_table" value="{{ $exportSource ?? 'csalary' }}">
                    {{-- PASANG department_id supaya export mengikuti filter --}}
                    <input type="hidden" name="department_id" id="export_department_id"
                        value="{{ request('department_id') ?? '' }}">
                </form>

                {{-- <button class="btn btn-gaji btn-warning" data-bs-toggle="modal" data-bs-target="#modalKirimSlip">
                    Kirim SLip Gaji
                </button> --}}

                <button class="btn btn-gaji btn-primary" data-bs-toggle="modal" data-bs-target="#modalHitungGaji">
                    Hitung Gaji
                </button>
            </div>
        </div>

        @include('penggajian.components.table_payroll')

        @include('penggajian.modals.modal_hitung')

        @include('penggajian.modals.modal_kirim_slip')

        @include('penggajian.modals.modal_payroll_bank')

        @include('penggajian.modals.modal_edit')

        @include('penggajian.modals.modal_report_gaji')

        @include('penggajian.modals.modal_report_kehadiran')

        <script src="{{ asset('js/penggajian.js') }}"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const deptSelect = document.getElementById('departmentFilter');
                const yearSelect = document.querySelector('select[name="year"]');
                const monthSelect = document.querySelector('select[name="month"]');

                const tableBody = document.getElementById('tablePayrollBody');
                const exportDeptInput = document.getElementById('export_department_id');

                const bankSelect = document.getElementById('bankSelect');
                const rekeningBox = document.getElementById('rekeningMandiriBox');
                const mrekeningSelect = document.getElementById('mrekeningSelect');

                const form = document.getElementById('formExportBank');

                if (!tableBody) return;

                // ===============================
                // 🔥 STATE CONTROL
                // ===============================
                let isReady = false;

                function setLoading(state) {
                    isReady = !state;

                    let overlay = document.getElementById('loadingOverlay');

                    // auto create overlay kalau belum ada
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.id = 'loadingOverlay';
                        overlay.style.position = 'fixed';
                        overlay.style.top = 0;
                        overlay.style.left = 0;
                        overlay.style.width = '100%';
                        overlay.style.height = '100%';
                        overlay.style.background = 'rgba(0,0,0,0.4)';
                        overlay.style.display = 'none';
                        overlay.style.zIndex = 9999;
                        overlay.style.color = 'white';
                        overlay.style.fontSize = '20px';
                        overlay.style.justifyContent = 'center';
                        overlay.style.alignItems = 'center';
                        overlay.innerText = '⏳ Memuat data payroll...';
                        document.body.appendChild(overlay);
                    }

                    overlay.style.display = state ? 'flex' : 'none';
                }

                // ===============================
                // 🔥 FETCH PAYROLL
                // ===============================
                function fetchPayroll() {

                    setLoading(true); // 🔥 block UI

                    const department_id = deptSelect?.value || '';
                    const year = yearSelect?.value || '';
                    const month = monthSelect?.value || '';
                    const bulanInput = document.querySelector('#formExportBank input[name="bulan"]');

                    console.log('Filter ->', {
                        department_id,
                        year,
                        month
                    });

                    const url = "{{ route('penggajian.filter.department') }}" +
                        "?department_id=" + encodeURIComponent(department_id) +
                        "&year=" + encodeURIComponent(year) +
                        "&month=" + encodeURIComponent(month);

                    tableBody.innerHTML = '<tr><td colspan="999">Loading...</td></tr>';

                    fetch(url, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(json => {

                            const html = json.html || '';
                            tableBody.innerHTML = html ||
                                '<tr><td colspan="999" class="text-muted">Data belum tersedia</td></tr>';

                            if (exportDeptInput) exportDeptInput.value = department_id;

                            if (bulanInput) {
                                bulanInput.value = year + '-' + String(month).padStart(2, '0');
                                console.log("🗓️ BULAN UPDATED:", bulanInput.value);
                            }

                            // update URL
                            const newUrl = new URL(window.location.href);
                            newUrl.searchParams.set('year', year);
                            newUrl.searchParams.set('month', month);

                            if (department_id)
                                newUrl.searchParams.set('department_id', department_id);
                            else
                                newUrl.searchParams.delete('department_id');

                            window.history.replaceState({}, '', newUrl.toString());

                            // 🔥 kasih waktu render benar-benar selesai
                            setTimeout(() => {
                                setLoading(false);
                                console.log("✅ DATA READY");
                            }, 100);

                        })
                        .catch(err => {
                            console.error(err);
                            tableBody.innerHTML =
                                '<tr><td colspan="999">Gagal memuat data</td></tr>';
                            setLoading(false);
                        });
                }

                // ===============================
                // 🔥 LISTENER FILTER
                // ===============================
                deptSelect?.addEventListener('change', fetchPayroll);
                yearSelect?.addEventListener('change', fetchPayroll);
                monthSelect?.addEventListener('change', fetchPayroll);

                // ===============================
                // 🔥 FIRST LOAD
                // ===============================
                fetchPayroll();

                // ===============================
                // 🔥 TOGGLE MANDIRI
                // ===============================
                function toggleMandiriOptions() {
                    if (!bankSelect) return;

                    const isMandiri = String(bankSelect.value || '').toLowerCase() === 'mandiri';

                    if (rekeningBox)
                        rekeningBox.style.display = isMandiri ? 'block' : 'none';

                    if (mrekeningSelect) {
                        Array.from(mrekeningSelect.options).forEach(o => {
                            const b = (o.dataset.bank || '').toLowerCase();
                            o.style.display = (!isMandiri || b.includes('mandiri')) ? '' : 'none';
                        });

                        if (!isMandiri) mrekeningSelect.value = '';
                    }
                }

                toggleMandiriOptions();
                if (bankSelect) bankSelect.addEventListener('change', toggleMandiriOptions);

                // ===============================
                // 🔥 SUBMIT CONTROL (FINAL FIX)
                // ===============================
                if (form) {
                    form.addEventListener('submit', function(event) {

                        // 🔥 BLOCK kalau data belum ready
                        if (!isReady) {
                            alert('⏳ Data masih diproses, tunggu sebentar...');
                            event.preventDefault();
                            return false;
                        }

                        const bank = (bankSelect && bankSelect.value) ? bankSelect.value : '';

                        if (!bank) {
                            alert('Pilih format bank terlebih dahulu.');
                            event.preventDefault();
                            return false;
                        }

                        if (String(bank).toLowerCase() === 'mandiri' && mrekeningSelect && !mrekeningSelect
                            .value) {
                            alert('Silakan pilih Rekening Sumber untuk Mandiri.');
                            event.preventDefault();
                            return false;
                        }

                        return true;
                    });
                }

            });
        </script>
    @else
        <div class="container mt-5">
            <div class="alert alert-warning text-center shadow-sm">
                <h5 class="mb-2">Menu Tidak Tersedia</h5>
            </div>
        </div>
    @endif

@endsection
