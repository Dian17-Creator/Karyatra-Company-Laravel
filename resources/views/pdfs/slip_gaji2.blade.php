<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Slip Gaji - {{ $nama ?? '-' }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 0px;

            background-image: url("{{ public_path('images/background1.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* ================= INFO BIODATA ================= */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .info-table td {
            padding: 3px 4px;
            border: none;
            font-size: 12px;
        }

        .info-label {
            width: 28%;
            font-weight: bold;
        }

        .info-colon {
            width: 2%;
            text-align: center;
        }

        .container {
            margin-top: 60px;
            padding: 100px;
        }

        .title {
            text-align: center;
            font-family: "Times New Roman", Georgia, serif;
            font-weight: 800;
            font-size: 22px;
            text-transform: uppercase;

            position: relative;
            display: inline-block;
            margin: 0 auto 18px auto;
            padding-bottom: 12px;
        }

        .title-wrap {
            text-align: center;
            margin-bottom: 6px;
        }

        .title {
            font-family: "Times New Roman", Georgia, serif;
            font-weight: 800;
            font-size: 22px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .line {
            width: 100%;
            height: 1px;
            background: #000;
            margin: 3px auto;
        }

        .line2 {
            width: 100%;
            height: 2px;
            background: #000;
            margin: 3px auto;
        }

        .subtitle {
            text-align: center;
            font-family: "Times New Roman", Georgia, serif;
            font-weight: 900;
            font-size: 16px;
            letter-spacing: 0px;
            text-transform: uppercase;
            margin-bottom: 14px;
            text-shadow: 0 0 1px #000;
        }

        .box {
            border: 2px solid #000;
            padding: 6px;
            margin-bottom: 12px;
        }

        .total-box {
            border: 3px solid #000;
            font-size: 14px;
            background: #f7f7f7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* hanya tabel GAJI saja */
        .box table th,
        .box table td {
            padding: 6px 8px;

            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            border-left: none;
            border-right: none;
        }

        /* garis kiri kanan hanya untuk tabel gaji */
        .box table tr td:first-child,
        .box table tr th:first-child {
            border-left: 1px solid #ddd;
        }

        .box table tr td:last-child,
        .box table tr th:last-child {
            border-right: 1px solid #ddd;
        }

        /* ====== KOLOM RP & ANGKA ====== */
        .rp {
            width: 30px;
            text-align: center;
            white-space: nowrap;
        }

        .nominal {
            width: 140px;
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }

        .bold {
            font-weight: 700;
        }

        .section-title {
            font-weight: 700;
            margin-top: 8px;
            margin-bottom: 4px;
        }

        .notes {
            margin-top: 8px;
            font-size: 11px;
            color: #333;
        }

        /* ===== PENGHASILAN (header merah) ===== */
        .penghasilan-box {
            border: 1px solid #000;
            padding: 0;
            margin-bottom: 12px;
        }

        .penghasilan-header {
            background: #cc3d51;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            padding: 8px 0;
            border: 1px solid #000;
        }

        .penghasilan-box table {
            border-collapse: collapse;
        }

        .penghasilan-box table td {
            padding: 6px 8px;
            border: 1px solid #000;
        }

        .penghasilan-box table {
            border: 1px solid #000;
        }

        .penghasilan-box td:first-child {
            width: 53%;
        }

        .penghasilan-box td.rp {
            width: 5%;
            border-right: none;
        }

        .penghasilan-box td.nominal {
            width: 42%;
            border-left: none;
        }

        /* HEADER 2 KOLOM PENGHASILAN */
        .penghasilan-head-row th {
            background: #cc3d51;
            color: #fff;
            font-weight: 700;
            text-align: center;
            padding: 8px 0;
            border: 1px solid #000;
        }

        .penghasilan-head-row th:first-child {
            width: 53%;
        }

        .penghasilan-head-row th:last-child {
            width: 47%;
        }


        /* ===== POTONGAN (header merah) ===== */
        .potongan-box {
            border: 1px solid #000;
            padding: 0;
            margin-bottom: 12px;
        }

        .potongan-header {
            background: #cc3d51;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            padding: 8px 0;
            border: 1px solid #000;
        }

        .potongan-box table {
            border-collapse: collapse;
        }

        .potongan-box table td {
            padding: 6px 8px;
            border: 1px solid #000;
        }

        .potongan-box table {
            border: 1px solid #000;
        }

        .potongan-box td:first-child {
            width: 53%;
        }

        .potongan-box td.rp {
            width: 5%;
            border-right: none;
        }

        .potongan-box td.nominal {
            width: 42%;
            border-left: none;
        }

        /* ===== TOTAL GAJI (highlight) ===== */
        .total-box {
            border: 1px solid #000;
            padding: 0;
            background: transparent;
        }

        .total-header {
            background: #cc3d51;
            color: #fff;
            font-weight: 700;
            text-align: center;
            padding: 10px 0;
            font-size: 14px;
            letter-spacing: 0.5px;
            border: 1px solid #000;
        }

        .total-amount {
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 0;
            border: 1px solid #000;
        }

        /* Shape bawah */
        .shape-bottom {
            position: fixed;
            right: -2px;
            bottom: -10px;
            width: 650px;
            height: auto;
            z-index: -1;
        }

        .shape-top1 {
            position: fixed;
            left: -40px;
            top: 0;
            width: 400px;
            height: auto;
            z-index: -1;
            margin-top: 30px;
        }

        .shape-top2 {
            position: fixed;
            right: 85px;
            top: 0;
            width: 350px;
            height: auto;
            z-index: -1;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <img src="{{ public_path('images/MataHati2.png') }}" class="shape-top1">
    <img src="{{ public_path('images/SlipGaji.png') }}" class="shape-top2">

    <div class="container">

        <div class="title-wrap">
            <div class="line"></div>
            <div class="line2"></div>
        </div>
        <div class="subtitle">INFORMASI KARYAWAN :</div>
        {{-- <div class="subtitle">BULAN {{ strtoupper($bulan ?? date('F Y')) }}</div> --}}

        <!-- INFO -->
        <table class="info-table">
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td class="value">{{ $nama ?? '-' }}</td>

                <td class="label">Jumlah Hari Masuk</td>
                <td class="colon">:</td>
                <td class="value">{{ $hari_masuk ?? 0 }}</td>
            </tr>

            <tr>
                <td class="label">Jabatan</td>
                <td class="colon">:</td>
                <td class="value">{{ $jabatan ?? '-' }}</td>

                <td class="label">Tanggal Cetak</td>
                <td class="colon">:</td>
                <td class="value">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</td>
            </tr>
        </table>


        <div class="penghasilan-box">

            <!-- ================= PENGHASILAN ================= -->
            <table>
                <thead>
                    <tr class="penghasilan-head-row">
                        <th>KETERANGAN</th>
                        <th colspan="2">NOMINAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($gaji_pokok ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Makan</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($tunjangan_makan ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Jabatan</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($tunjangan_jabatan ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Transportasi</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($tunjangan_transport ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Tunjangan Backup</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($tunjangan_backup ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Gaji Lembur</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($lembur ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Bonus Kehadiran</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($bonus_kehadiran ?? 0, 2, ',', '.') }}</td>
                    <tr>
                        <td>Tabungan Diambil</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($tabungan_diambil ?? 0, 2, ',', '.') }}</td>
                    </tr>

                    <tr class="bold">
                        <td>Jumlah Penghasilan</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($jumlah_penghasilan ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="potongan-box">

            <div class="potongan-header">
                POTONGAN
            </div>

            <table>
                <tbody>
                    <tr>
                        <td>Potongan Keterlambatan</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($potongan_keterlambatan ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Potongan Lain-Lain</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($potongan_lain ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Potongan Tabungan</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($potongan_tabungan ?? 0, 2, ',', '.') }}</td>
                    </tr>

                    <tr class="bold">
                        <td>Jumlah Potongan</td>
                        <td class="rp">Rp</td>
                        <td class="nominal">{{ number_format($jumlah_potongan ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="box total-box">

            <div class="total-header">
                GAJI YANG DITERIMA
            </div>

            <div class="total-amount">
                Rp {{ number_format($gaji_diterima ?? 0, 2, ',', '.') }}
            </div>

            @if (!empty($catatan))
                <div class="notes" style="padding:8px 10px;">
                    <strong>Catatan :</strong> {!! nl2br(e($catatan)) !!}
                </div>
            @endif

        </div>

    </div>

    <img src="{{ public_path('images/shape1.png') }}" class="shape-bottom">

</body>

</html>
