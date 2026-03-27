<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="width: 400px;">
        <h3 class="text-center mb-3">Login</h3>

        <form action="../actions/login_action.php" method="POST">
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
            
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Remember Me</label>
                </div>
                <div>
                    <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                </div>
            </div>
        
            <button class="btn btn-success w-100">Login</button>
        </form>

        <p class="text-center mt-3">
            Don't have an account? <a href="register.php">Register</a>
        </p>
    </div>
</div>

</body>
</html>