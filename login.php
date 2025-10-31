<?php
session_start();
include __DIR__ . '/config/db.php'; // file koneksi ke database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $passw = $_POST['passw'];

    $sql = "SELECT * FROM user WHERE id='$id' AND passw='$passw' AND aktif=1 LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['id'] = $row['id'];
        $_SESSION['code'] = $row['code'];
        $_SESSION['level'] = $row['level'];

        header("Location: index.php");
        exit();
    } else {
        $error = "ID atau Password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login KSP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1c2534, #2d3b52);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            background: #fff;
        }
        .login-header {
            background: #1c2534;
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .login-body {
            padding: 30px;
        }
        .form-control:focus {
            border-color: #1c2534;
            box-shadow: 0 0 5px rgba(28,37,52,0.5);
        }
        .btn-login {
            background: #1c2534;
            color: #fff;
            font-weight: bold;
        }
        .btn-login:hover {
            background: #2d3b52;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h3>🔐 KSP Panel</h3>
        <p>Silakan login untuk melanjutkan</p>
    </div>
    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="mb-3">
                <label for="id" class="form-label">ID</label>
                <input type="text" class="form-control" id="id" name="id" required autofocus>
            </div>
            <div class="mb-3">
                <label for="passw" class="form-label">Password</label>
                <input type="password" class="form-control" id="passw" name="passw" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Login</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
