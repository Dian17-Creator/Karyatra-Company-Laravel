<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Matahati Backoffice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }

        .register-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 35px;
            text-align: center;
        }

        .register-card h3 {
            margin-bottom: 25px;
            color: #333;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
        }

        .btn-register {
            width: 100%;
            background-color: #ff6a00;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 10px;
            transition: 0.3s;
        }

        .btn-register:hover {
            background-color: #e65c00;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .footer a {
            color: #ff6a00;
            text-decoration: none;
            font-weight: 600;
        }

        /* Email field – read-only style */
        #cemail {
            background-color: #f8f9fa;
            color: #555;
        }

        /* Password toggle button */
        .input-group .btn-toggle-password {
            border: 1px solid #ced4da;
            border-left: none;
            border-radius: 0 8px 8px 0;
            background: #fff;
            color: #888;
            transition: color 0.2s;
        }

        .input-group .btn-toggle-password:hover {
            color: #ff6a00;
        }

        .input-group .form-control {
            border-radius: 8px 0 0 8px;
        }

        .icon-password {
            cursor: pointer;
        }

        /* Sembunyikan icon mata bawaan browser (Edge & Chrome) */
        #cpassword::-ms-reveal,
        #cpassword::-ms-clear {
            display: none;
        }

        #cpassword::-webkit-credentials-auto-fill-button,
        #cpassword::-webkit-textfield-decoration-container {
            display: none !important;
        }
    </style>
</head>

<body>

    <div class="register-card">
        <h3>REGISTRASI</h3>

        @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ url('/register') }}" method="POST">
            @csrf

            <div class="mb-3 text-start">
                <label for="ccompany" class="form-label">Nama Perusahaan</label>
                <input
                    type="text"
                    name="ccompany"
                    id="ccompany"
                    class="form-control"
                    placeholder="Contoh: Matahati"
                    value="{{ old('ccompany') }}"
                    required
                    autofocus>

                <small id="companyStatus" class="mt-1 d-block"></small>

            </div>

            <div class="mb-3 text-start">
                <label for="cname" class="form-label">Nama</label>
                <input type="text" name="cname" id="cname" class="form-control"
                    placeholder="Nama Anda" value="{{ old('cname') }}" required>
            </div>

            <div class="mb-3 text-start">
                <label for="cemail" class="form-label">Email Perusahaan</label>
                <input type="text" name="cemail" id="cemail" class="form-control"
                    placeholder="" value="{{ old('cemail') }}" readonly>
                <!-- <small class="text-muted">Dibuat otomatis dari nama &amp; nama perusahaan</small> -->
            </div>

            <div class="mb-4 text-start">
                <label for="cpassword" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" name="cpassword" id="cpassword" class="form-control" required>
                    <button type="button" class="btn btn-toggle-password" id="togglePassword" tabindex="-1">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
                <small class="text-muted mt-1 d-block">
                    <i class="bi bi-info-circle"></i> Minimal 6 karakter
                </small>
            </div>

            <button id="registerBtn" type="submit" class="btn btn-register">Daftar Sekarang</button>
        </form>

        <div class="footer mt-2">
            <span>Sudah Punya Akun? <a href="{{ url('/login') }}">Login</a></span>
            <br>
            <small class="d-block mt-2">© {{ date('Y') }} Karyatra Backoffice</small>
        </div>
    </div>

    <script>
        /**
         * AUTO-GENERATE EMAIL
         * Mirrors the server-side logic in LoginController@register:
         *   domain   = ccompany → lowercase, strip leading "PT " / "CV ", keep [a-z0-9]
         *   username = cname    → lowercase, keep [a-z0-9]
         *   email    = username@domain
         */
        function toDomain(companyName) {
            return companyName
                .toLowerCase()
                .replace(/^(pt|cv)\s+/i, '') // strip PT / CV prefix
                .replace(/[^a-z0-9]/g, ''); // keep only alphanumeric
        }

        function toUsername(name) {
            return name.toLowerCase().replace(/[^a-z0-9]/g, '');
        }

        function updateEmail() {
            const domain = toDomain(document.getElementById('ccompany').value);
            const username = toUsername(document.getElementById('cname').value);

            document.getElementById('cemail').value =
                (username || domain) ? username + '@' + domain : '';
        }

        document.getElementById('ccompany').addEventListener('input', updateEmail);
        document.getElementById('cname').addEventListener('input', updateEmail);

        // Run once on load to restore old() values after validation error
        updateEmail();

        /**
         * PASSWORD SHOW / HIDE TOGGLE
         */
        const toggleBtn = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        const passInput = document.getElementById('cpassword');

        toggleBtn.addEventListener('click', function() {
            const isHidden = passInput.type === 'password';
            passInput.type = isHidden ? 'text' : 'password';
            toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        let debounceTimer;

        const companyInput = document.getElementById('ccompany');
        const companyStatus = document.getElementById('companyStatus');
        const registerBtn = document.getElementById('registerBtn');

        companyInput.addEventListener('input', function() {

            updateEmail();

            clearTimeout(debounceTimer);

            companyStatus.textContent = "";
            companyInput.classList.remove("is-valid", "is-invalid");

            const company = companyInput.value.trim();

            if (company.length < 2) {
                registerBtn.disabled = false;
                return;
            }

            debounceTimer = setTimeout(() => {

                fetch(`/register/check-company?ccompany=${encodeURIComponent(company)}`)
                    .then(response => response.json())
                    .then(data => {

                        if (data.exists) {

                            companyInput.classList.add("is-invalid");
                            companyInput.classList.remove("is-valid");

                            companyStatus.className =
                                "text-danger mt-1 d-block";

                            companyStatus.innerHTML =
                                '<i class="bi bi-x-circle"></i> Nama perusahaan sudah terdaftar';

                            registerBtn.disabled = true;

                        } else {

                            companyInput.classList.add("is-valid");
                            companyInput.classList.remove("is-invalid");

                            companyStatus.className =
                                "text-success mt-1 d-block";

                            companyStatus.innerHTML =
                                '<i class="bi bi-check-circle"></i> Nama perusahaan tersedia';

                            registerBtn.disabled = false;
                        }

                    });

            }, 500);

        });
    </script>

</body>

</html>