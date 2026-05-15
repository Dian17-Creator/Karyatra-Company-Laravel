<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #000;
        }

        h3 {
            text-align: center;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        img.face {
            width: 45px;
            height: 60px;
            object-fit: cover;
            border: 1px solid #777;
        }

        /* ===== LEBAR KOLOM ===== */
        .c-username {
            width: 13%;
        }

        .c-gmail {
            width: 15%;
        }

        .c-phone {
            width: 9%;
        }

        .c-ktp {
            width: 9%;
        }

        .c-rekening {
            width: 11%;
        }

        .c-bank {
            width: 7%;
        }

        .c-nama {
            width: 8%;
        }

        .c-namalngkp {
            width: 14%;
        }

        .c-finger {
            width: 5%;
        }

        .c-tanggal {
            width: 7%;
        }

        .c-cabang {
            width: 9%;
        }

        .c-role {
            width: 7%;
        }

        .c-status {
            width: 6%;
        }

        .c-foto {
            width: 6%;
        }

        .footer {
            margin-top: 8px;
            font-size: 7px;
            text-align: right;
            color: #555;
        }
    </style>
</head>

<body>

    <h3>Daftar User</h3>

    <table>
        <thead>
            <tr>
                <th class="c-username">Username</th>
                <th class="c-gmail">Gmail</th>
                <th class="c-phone">No Telp</th>
                <th class="c-ktp">No KTP</th>
                <th class="c-rekening">No Rekening</th>
                <th class="c-bank">Bank</th>
                <th class="c-nama">Nama</th>
                <th class="c-namalngkp">Nama Lengkap</th>
                <th class="c-finger">Finger ID</th>
                <th class="c-tanggal">Tgl Masuk</th>
                <th class="c-cabang">Cabang</th>
                <th class="c-role">Role</th>
                <th class="c-status">Status</th>
                <th class="c-foto">Foto</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($users as $user)
                @php
                    /* ===== ROLE ===== */
                    $role = $user->fhrd
                        ? 'HRD'
                        : ($user->fsuper
                            ? 'Supervisor'
                            : ($user->fadmin
                                ? 'Captain'
                                : ($user->fsenior
                                    ? 'Senior Crew'
                                    : 'Crew')));

                    /* ===== REKENING & BANK ===== */
                    $rekening = $user->caccnumber ?: $user->rekening->nomor_rekening ?? '-';
                    $bank = strtoupper($user->bank ?: $user->rekening->bank ?? '-');
                @endphp

                <tr>
                    <td>{{ $user->cemail }}</td>
                    <td>{{ $user->cmailaddress ?? '-' }}</td>
                    <td>{{ $user->cphone ?? '-' }}</td>
                    <td>{{ $user->cktp ?? '-' }}</td>
                    <td>{{ $rekening }}</td>
                    <td>{{ $bank }}</td>
                    <td>{{ $user->cname }}</td>
                    <td>{{ $user->cfullname ?? '-' }}</td>
                    <td>{{ $user->finger_id ?? '-' }}</td>
                    <td>
                        {{ $user->dtanggalmasuk ? \Carbon\Carbon::parse($user->dtanggalmasuk)->format('d M Y') : '-' }}
                    </td>
                    <td>{{ $user->department->cname ?? '-' }}</td>
                    <td>{{ $role }}</td>
                    <td>{{ $user->factive ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        @if (!empty($user->photos))
                            @foreach ($user->photos as $img)
                                <img src="{{ $img }}" class="face" style="margin-bottom:4px;">
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d M Y H:i') }}
    </div>

</body>

</html>
