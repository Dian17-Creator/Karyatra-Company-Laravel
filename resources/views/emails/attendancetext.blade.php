<!DOCTYPE html>
<html>

<body style="font-family: Arial, sans-serif; font-size: 14px; color:#333;">

    <p>Halo HR,</p>

    <p>Berikut laporan absensi user hari ini :</p>

    <div
        style="
        background:#f6f6f6;
        border:1px solid #ddd;
        padding:12px;
        font-family: monospace;
        white-space: pre-line;
        line-height:1.5;
    ">
        {{ $report }}
    </div>

    <br>

    <p>Terima kasih.</p>

    <hr style="border:none;border-top:1px solid #ddd">

    <p style="font-style: italic; font-size: 12px; color: #777;">
        Email dikirim otomatis. Mohon tidak membalas email ini.
    </p>

</body>

</html>
