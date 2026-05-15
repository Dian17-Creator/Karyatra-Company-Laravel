<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Notifikasi Sistem Absensi</title>
</head>

<body>
    <h3>{{ $subjectLine ?? 'Notifikasi Baru dari Sistem Absensi' }}</h3>

    @php
        // support beberapa nama variabel yang mungkin dikirim dari Mailable:
        $items = $bodyLines ?? ($notifications ?? ($notifications ?? []));
        // jika masih null, pastikan set ke array kosong
        if (!is_iterable($items)) {
            $items = [];
        }
    @endphp

    <ul>
        @foreach ($items as $i)
            @php
                // jika $i adalah array/objek, coba ambil message / property
                if (is_array($i)) {
                    // preferensi field 'message' atau '0' atau gabungkan semua
                    $text = $i['message'] ?? ($i[0] ?? json_encode($i));
                } elseif (is_object($i)) {
                    $text = $i->message ?? (property_exists($i, 'message') ? $i->message : json_encode($i));
                } else {
                    // jika string langsung, tampilkan
                    $text = (string) $i;
                }
            @endphp

            <li>{!! nl2br(e($text)) !!}</li>
        @endforeach
    </ul>
</body>

</html>
