@if ($company)
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('backoffice.updateCompany') }}">
                @csrf
                @method('PUT')
                <div class="bg-danger modal-header text-white">
                    <h5 class="modal-title" id="editCompanyModalLabel">
                        Edit Data Company
                    </h5>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Company</label>

                        <input
                            type="text"
                            id="companyName"
                            name="cname"
                            class="form-control"
                            value="{{ old('cname', $company->cname) }}"
                            data-original="{{ $company->cname }}"
                            required
                            maxlength="255"
                            placeholder="">

                        <div id="companyNameFeedback"></div>

                        <!-- <div class="form-text">
                            Perubahan nama company akan sinkronisasi ke semua data user terkait.
                        </div> -->
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Domain Email Company</label>

                        <div class="input-group">
                            <span class="input-group-text">@</span>

                            <input
                                type="text"
                                id="companyDomain"
                                name="cemail"
                                class="form-control"
                                value="{{ old('cemail', $company->cemail) }}"
                                data-original="{{ $company->cemail }}"
                                required
                                maxlength="255"
                                placeholder="Contoh: matahati">
                        </div>

                        <div id="companyDomainFeedback"></div>

                        <div class="form-text">
                            Domain ini dipakai untuk format login karyawan:
                            <strong>username@domain</strong>
                        </div>
                    </div>

                </div>
                <div class="modal-footer d-flex justify-content-between w-100 gap-2">
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Batal</button>

                    <button
                        type="submit"
                        id="btnSaveCompany"
                        class="btn btn-success text-white flex-fill">
                        Simpan
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const nameInput = document.getElementById('companyName');
        const domainInput = document.getElementById('companyDomain');

        if (!nameInput || !domainInput) {
            return;
        }

        const nameFeedback = document.getElementById('companyNameFeedback');
        const domainFeedback = document.getElementById('companyDomainFeedback');

        const btnSave = document.getElementById('btnSaveCompany');

        let timer;

        function clearValidation(input, feedback) {
            input.classList.remove('is-valid', 'is-invalid');
            feedback.innerHTML = '';
        }

        function validateCompany() {

            clearTimeout(timer);

            timer = setTimeout(() => {

                const cname = nameInput.value.trim();
                const cemail = domainInput.value.trim();

                const originalName = nameInput.dataset.original.trim();
                const originalDomain = domainInput.dataset.original.trim();

                // Kalau tidak ada perubahan sama sekali
                if (cname === originalName && cemail === originalDomain) {

                    clearValidation(nameInput, nameFeedback);
                    clearValidation(domainInput, domainFeedback);

                    btnSave.disabled = false;
                    return;
                }

                fetch("{{ route('backoffice.checkCompany') }}", {

                        method: "POST",

                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },

                        body: JSON.stringify({
                            cname: cname,
                            cemail: cemail
                        })

                    })
                    .then(res => res.json())
                    .then(data => {

                        let invalid = false;

                        // ===================
                        // Nama Company
                        // ===================

                        if (cname !== originalName) {

                            if (data.name_exists) {

                                nameInput.classList.add("is-invalid");
                                nameInput.classList.remove("is-valid");

                                nameFeedback.innerHTML =
                                    '<div class="invalid-feedback d-block">Nama company sudah digunakan.</div>';

                                invalid = true;

                            } else {

                                nameInput.classList.add("is-valid");
                                nameInput.classList.remove("is-invalid");

                                nameFeedback.innerHTML =
                                    '<div class="valid-feedback d-block">Nama company tersedia.</div>';
                            }

                        } else {

                            clearValidation(nameInput, nameFeedback);

                        }

                        // ===================
                        // Domain
                        // ===================

                        if (cemail !== originalDomain) {

                            if (data.domain_exists) {

                                domainInput.classList.add("is-invalid");
                                domainInput.classList.remove("is-valid");

                                domainFeedback.innerHTML =
                                    '<div class="invalid-feedback d-block">Domain email sudah digunakan.</div>';

                                invalid = true;

                            } else {

                                domainInput.classList.add("is-valid");
                                domainInput.classList.remove("is-invalid");

                                domainFeedback.innerHTML =
                                    '<div class="valid-feedback d-block">Domain email tersedia.</div>';
                            }

                        } else {

                            clearValidation(domainInput, domainFeedback);

                        }

                        btnSave.disabled = invalid;

                    });

            }, 400);

        }

        nameInput.addEventListener('input', validateCompany);
        domainInput.addEventListener('input', validateCompany);

    });
</script>