@if (auth()->user()->fsuper == 1)
<div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-danger text-white">
        <span>Master Company</span>
        <button class="btn btn-warning text-white btn-sm" data-bs-toggle="modal" data-bs-target="#editCompanyModal">
            Edit Company
        </button>
    </div>
    <div class="card-body">
        @if ($company)
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr style="background-color: #d0e8ff;">
                    <th style="background-color: #ffd8e0ff !important;" class="text-center" style="width: 40%;">Nama Company</th>
                    <th style="background-color: #ffd8e0ff !important;" class="text-center">Domain Email Company</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center fw-semibold">{{ $company->cname }}</td>
                    <td class="text-center">
                        <code>{{ $company->cemail }}</code>
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- <p class="text-muted small mt-2 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Domain email digunakan sebagai format login karyawan: <strong>username@{{ $company->cemail }}</strong>
        </p> -->
        @else
        <p class="text-muted text-center mb-0">Data company tidak ditemukan.</p>
        @endif
    </div>
</div>

{{-- Modal Edit Company --}}
@include('backoffice.modal.modal_edit_company', ['company' => $company])
@endif