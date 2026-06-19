@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3 class="text-center" style="margin-bottom: 15px">Atur Jadwal untuk {{ $user->cname }}</h3>

    <div class="d-flex justify-content-end">
        <a href="{{ url('/schedule/export?nuserid=' . $user->nid . '&start_date=' . request('start_date') . '&end_date=' . request('end_date')) }}"
            class="btn btn-success mb-3">
            Export Excel
        </a>
    </div>

    <form action="{{ route('schedule.assign') }}" method="POST">
        @csrf
        <input type="hidden" name="nuserid" value="{{ $user->nid }}">

        <table class="table table-bordered">
            <thead class="table-danger text-center">
                <tr>
                    <th>Tanggal</th>
                    <th>Shift</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($period as $date)
                @php $d = $date->format('Y-m-d'); @endphp
                <tr>
                    <td class="text-center">{{ $date->format('d/m/Y') }}</td>
                    <td>
                        <select name="dates[{{ $d }}]" class="form-select">
                            <option value="">-- none --</option>
                            @foreach ($masters as $m)
                            <option value="{{ $m->nid }}"
                                @if (isset($existingSchedules[$d]) && $existingSchedules[$d]==$m->nid) selected @endif>
                                {{ $m->cname }}
                                ({{ substr($m->dstart, 0, 5) }} - {{ substr($m->dend, 0, 5) }}
                                @if ($m->dstart2 && $m->dend2)
                                | {{ substr($m->dstart2, 0, 5) }} - {{ substr($m->dend2, 0, 5) }}
                                @endif)
                            </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row mt-3">
            <div class="col-6">
                <a href="{{ route('schedule.index') }}" class="btn btn-secondary w-100">
                    Kembali
                </a>
            </div>

            <div class="col-6">
                <button type="submit" class="btn btn-success w-100">
                    Simpan Jadwal
                </button>
            </div>
        </div>

    </form>
</div>
@endsection