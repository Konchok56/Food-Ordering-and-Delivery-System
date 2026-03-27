<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="width: 400px;">
        <h3 class="text-center mb-3">Forgot Password</h3>
        <p class="text-muted text-center">Enter your email to reset your password</p>

        <form action="../actions/forgot_password_action.php" method="POST">
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <button class="btn btn-primary w-100">Send Reset Link</button>
        </form>

        <p class="text-center mt-3">
            <a href="login.php">Back to Login</a>
        </p>
    </div>
</div>

</body>
</html>
