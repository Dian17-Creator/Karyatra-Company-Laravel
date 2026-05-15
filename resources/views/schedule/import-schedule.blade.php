<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Jadwal Kerja</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.1.0/css/font-awesome.min.css">

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/js/bootstrap.min.js"></script>

</head>

<body>
    <div class="container mt-3">
        <!-- Return button top-left -->
        <a href="{{ url('/schedule') }}" class="btn btn-secondary mb-3">
            &larr; Kembali
        </a>

        {{-- notifikasi form validasi --}}
        @if ($errors->has('file'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">x</button>
                <strong>{{ $errors->first('file') }}</strong>
            </div>
        @endif

        <h3 class="mb-2 text-center" style="background-color:palegreen;">
            Import Jadwal Kerja
        </h3>

        <div class="text-center">
            <h5 class="mb-2">File Jadwal Kerja</h5>
            <form id="importScheduleForm" action="{{ route('file-import-schedule') }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                @if ($sukses = Session::get('userschedulesuccess'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">x</button>
                        <strong>{{ $sukses }}</strong>
                    </div>
                @endif

                @if ($warnings = Session::get('userschedule_warning'))
                    <div class="alert alert-warning alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">x</button>
                        <ul class="mb-0">
                            @foreach ($warnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-group mb-2" style="max-width: 500px; margin: 0 auto;">
                    <div class="custom-file text-left">
                        <input type="file" name="file" class="custom-file-input" id="customFile" accept=".xlsx">
                        <label class="custom-file-label" for="customFile">...</label>
                    </div>
                </div>

                <button id="scheduleimport" class="btn btn-primary"
                    data-loading-text="<i class='fa fa-spinner fa-spin '></i> Proses Import...">Import data</button>

            </form>
        </div>
    </div>

    <script>
        // Add the following code if you want the name of the file appear on select
        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });

        $('button').click(function() {
            var $this = $(this);
            $this.button('loading');
        });

        (function() {
            window.onpageshow = function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            };
        })();
    </script>
</body>

</html>
