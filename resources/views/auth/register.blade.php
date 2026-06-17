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
                <input type="text" name="ccompany" id="ccompany" class="form-control" placeholder="Contoh: Matahati" required autofocus>
            </div>

            <div class="mb-3 text-start">
                <label for="cname" class="form-label">Nama Lengkap</label>
                <input type="text" name="cname" id="cname" class="form-control" placeholder="Nama Anda" required>
            </div>

            <div class="mb-3 text-start">
                <label for="cemail" class="form-label">Email</label>
                <input type="email" name="cemail" id="cemail" class="form-control" placeholder="email@perusahaan" required>
            </div>

            <div class="mb-4 text-start">
                <label for="cpassword" class="form-label">Password</label>
                <input type="password" name="cpassword" id="cpassword" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-register">Daftar Sekarang</button>
        </form>

        <div class="footer mt-2">
            <span>Sudah Punya Akun? <a href="{{ url('/login') }}">Login</a></span>
            <br>
            <small class="d-block mt-2">© {{ date('Y') }} Karyatra Backoffice</small>
        </div>
    </div>

</body>

</html>