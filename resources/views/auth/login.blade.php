<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Matahati Backoffice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #ff6a00, #ee0979);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }

        .login-card {
            background: #fff;
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            padding: 35px;
            text-align: center;
            overflow: hidden;
        }

        .login-card h3 {
            margin-bottom: 25px;
            color: #333;
            font-weight: 600;
        }

        .form-control {
            border-radius: 8px;
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            top: 75%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1.2rem;
        }

        .toggle-password:hover {
            color: #333;
        }

        .btn-login {
            width: 100%;
            background-color: #ff6a00;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 10px;
            transition: 0.3s;
        }

        .btn-login:hover {
            background-color: #e65c00;
        }

        .alert {
            border-radius: 8px;
        }

        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-header {
            background: #1b1b1b;
            color: #ffffff;
            padding: 20px 32px;
            text-align: center;
        }

        .login-header h2 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin: 0;
            color: #ffffff;
        }

        .login-body {
            padding: 30px 35px 35px;
        }
    </style>
</head>

<body>

    <div class="login-card" style="padding: 0 !important;">

        <div class="login-header">
            <h2>LOGIN</h2>
        </div>

        <div class="login-body">

            {{-- Tampilkan pesan error jika ada --}}
            @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
            @endif

            {{-- Form Login --}}
            <form action="{{ url('/login') }}" method="POST">
                @csrf
                <div class="mb-3 text-start">
                    <label for="cemail" class="form-label">Email</label>
                    <input type="email" name="cemail" id="cemail" class="form-control" required autofocus>
                </div>

                <div class="mb-4 text-start password-wrapper">
                    <label for="cpassword" class="form-label">Password</label>
                    <input type="password" name="cpassword" id="cpassword" class="form-control" required>
                    <i class="bi bi-eye toggle-password" id="togglePassword"></i>
                </div>

                <button type="submit" class="btn btn-login">Masuk</button>
            </form>

            <div class="footer mt-2">
                <span>Belum Punya Akun? <a href="{{ url('/register') }}" style="color: #ff6a00; text-decoration: none; font-weight: 600;">Daftar</a></span>
                <br>
                <small class="d-block mt-2">© {{ date('Y') }} Karyatra Backoffice</small>
            </div>

        </div>

    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('cpassword');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>

</body>

</html>