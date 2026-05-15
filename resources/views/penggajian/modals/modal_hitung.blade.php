<div class="modal fade" id="modalHitungGaji" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ url('penggajian/recalc-all') }}" id="formHitungGaji">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Hitung Penggajian</h5>
                </div>
                <div class="modal-body">
                    <div class="row gx-2">

                        {{-- TAHUN --}}
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label">Tahun</label>
                            <select name="year" id="year" class="form-control">
                                @for ($y = date('Y') + 1; $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}"
                                        {{ ($year ?? date('Y')) == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- BULAN --}}
                        <div class="col-md-6 mb-3">
                            <label for="month" class="form-label">Bulan</label>
                            <select name="month" id="month" class="form-control">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}"
                                        {{ ($month ?? date('n')) == $m ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    {{-- Filter Department --}}
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Departemen</label>
                        <select name="department_id" id="department_id" class="form-control">
                            <option value="" {{ request('department_id') == '' ? 'selected' : '' }}>
                                -- Semua Departemen --
                            </option>

                            @foreach ($departments as $dept)
                                <option value="{{ $dept->nid }}"
                                    {{ request('department_id') == $dept->nid ? 'selected' : '' }}>
                                    {{ $dept->cname }}
                                </option>
                            @endforeach


                        </select>
                    </div>

                    <!-- opsi split by change -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="split_by_change" name="split_by_change"
                            value="1" {{ old('split_by_change') ? 'checked' : '' }}>
                        <label class="form-check-label" for="split_by_change">
                            Hitung sesuai perubahan dalam bulan (split by change)
                        </label>
                    </div>

                    <!-- NEW: opsi recalculate / force -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="recalculate" name="recalculate"
                            value="1">
                        <label class="form-check-label" for="recalculate">
                            Hitung Ulang
                        </label>
                        <small class="form-text text-muted">(Jika tidak dipilih, maka akan ditampilkan hasil perhitungan
                            sebelumnya)</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Proses</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formHitungGaji');
        if (!form) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : 'Proses';

        const metaCsrf = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : (form.querySelector(
            'input[name="_token"]') ? form.querySelector('input[name="_token"]').value : '');

        // gunakan route() Laravel agar path valid di semua environment
        const recalcAjaxUrl =
            @json(route('payroll.recalc.ajax')); // => "/penggajian/recalc-ajax" atau sesuai environment
        const payrollIndexUrl =
            @json(route('penggajian.index')); // base URL untuk reload (relatif/absolute sesuai env)

        function collectUserIdsFromTable() {
            const rows = Array.from(document.querySelectorAll('table.table-payroll tbody tr[data-user-id]'));
            const ids = rows.map(r => {
                const v = r.getAttribute('data-user-id');
                return v ? parseInt(v, 10) : null;
            }).filter(Boolean);
            return ids;
        }

        function applyResultsToTable(results) {
            if (!Array.isArray(results)) return;
            results.forEach(item => {
                const userId = item.user_id ?? item.userId ?? item.nuserid;
                const ket = item.ket ?? item.keterangan_absensi ?? '';
                if (!userId) return;
                const row = document.querySelector('tr[data-user-id="' + userId + '"]');
                if (!row) return;
                const cell = row.querySelector('.ket-absensi-cell');
                if (!cell) return;
                cell.textContent = ket && ket.toString().trim().length ? ket : 'A = 0, I = 0, S = 0';
            });
        }

        async function postRecalcAjax(userIds, month, year) {
            try {
                const resp = await fetch(recalcAjaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        user_ids: userIds,
                        period_month: String(month),
                        period_year: String(year)
                    })
                });

                if (!resp.ok) {
                    const body = await resp.text().catch(() => null);
                    console.error('recalcAjax failed', resp.status, body);
                    return {
                        ok: false,
                        status: resp.status,
                        body
                    };
                }
                const data = await resp.json();
                return {
                    ok: true,
                    data
                };
            } catch (err) {
                console.error('recalcAjax error', err);
                return {
                    ok: false,
                    err
                };
            }
        }

        form.addEventListener('submit', async function(ev) {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
            }

            const monthInput = form.querySelector('select[name="month"], input[name="month"]');
            const yearInput = form.querySelector('select[name="year"], input[name="year"]');

            const periodMonth = monthInput ? monthInput.value : null;
            const periodYear = yearInput ? yearInput.value : null;

            // build reloadUrl dengan benar menggunakan route() dari server
            const urlObj = new URL(payrollIndexUrl, window.location.origin);
            urlObj.searchParams.set('year', String(periodYear));
            urlObj.searchParams.set('month', String(periodMonth));
            const reloadUrl = urlObj.toString();

            const userIds = collectUserIdsFromTable();

            // ❗ JIKA KOSONG → TETAP LANJUT
            if (userIds.length === 0) {
                console.warn(
                    'Tabel kosong, backend akan menghitung berdasarkan mtunjangan'
                );
            }


            if (userIds.length > 0) {
                const ajaxResult = await postRecalcAjax(
                    userIds,
                    periodMonth,
                    periodYear
                );
                if (ajaxResult.ok) {
                    const results = ajaxResult.data.results ?? [];
                    applyResultsToTable(results);
                }
            }

            window.location.href = reloadUrl;
        });
    });
</script>
