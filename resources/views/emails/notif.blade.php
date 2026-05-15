<!DOCTYPE html>
<html>

<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">
    <p>Dear HRD,</p>

    <p>
        Ada <b>{{ strtoupper($data->tipe) }}</b> menunggu approval dari karyawan berikut:
    </p>

    <table style="border-collapse: collapse; margin: 10px 0;">
        <tr>
            <td style="padding: 4px 12px 4px 0; font-weight: bold;">Nama</td>
            <td>: {{ $data->nama }}</td>
        </tr>

        <tr>
            <td style="padding: 4px 12px 4px 0; font-weight: bold;">Tanggal</td>
            <td>:
                @if($data->tipe == 'izin')
                {{ \Carbon\Carbon::parse($data->start_date)->format('d M Y') }}
                -
                {{ \Carbon\Carbon::parse($data->end_date)->format('d M Y') }}
                @else
                {{ \Carbon\Carbon::parse($data->tanggal)->format('d M Y H:i') }}
                @endif
            </td>
        </tr>

        <tr>
            <td style="padding: 4px 12px 4px 0; font-weight: bold;">Alasan</td>
            <td>: {{ $data->creason }}</td>
        </tr>
    </table>

    <p>Silakan periksa dan lakukan approval pada sistem.</p>

    <p>Terima kasih.</p>

    <br>

    <hr style="border: none; border-top: 1px solid #ddd;">

    <p style="font-style: italic; font-size: 12px; color: #777;">
        Email dikirim otomatis. Mohon tidak membalas email ini.
    </p>
</body>

</html>