<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $redirect = $_SESSION['redirect_url'] ?? 'dashboard.php';
            unset($_SESSION['redirect_url']);
            header("Location: $redirect");
            exit;
        } else {
            $error = "Password verification failed";
        }
    } else {
        $error = "User not found";
    }
    $error = $error ?: "Invalid username or password.";
    sleep(1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #5c6bc0;
            --primary-hover: #3949ab;
            --secondary-color: #74ebd5;
            --gradient-start: #74ebd5;
            --gradient-end: #acb6e5;
        }
        
        body {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: var(--primary-color);
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .logo {
            max-height: 150px;
            width: auto;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 10px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(92, 107, 192, 0.25);
        }

        .input-group-text {
            background-color: white;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .input-group-text:hover {
            color: var(--primary-color);
        }

        .alert {
            border-radius: 8px;
        }

        .forgot-password {
            text-align: right;
            margin-top: 5px;
        }

        .forgot-password a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary-color);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .divider-text {
            padding: 0 10px;
            color: #777;
            font-size: 0.9rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                Inventory System
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="assets/terralogix2.png" alt="Inventory System Logo" class="img-fluid logo">
                    
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger shake"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Enter your username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" name="password" id="loginPassword" required placeholder="Enter your password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Show/Hide Password">
                                <span id="togglePasswordIcon" class="bi bi-eye"></span>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                </form> 
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            togglePassword.addEventListener('click', function() {
                const pwd = document.getElementById('loginPassword');
                const icon = document.getElementById('togglePasswordIcon');
                if (pwd.type === "password") {
                    pwd.type = "text";
                    icon.classList.remove("bi-eye");
                    icon.classList.add("bi-eye-slash");
                } else {
                    pwd.type = "password";
                    icon.classList.remove("bi-eye-slash");
                    icon.classList.add("bi-eye");
                }
            });

            // Form submission animation
            const form = document.getElementById('loginForm');
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
                submitBtn.disabled = true;
            });

            // Add focus effects
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('.input-group-text').style.color = 'var(--primary-color)';
                });
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('.input-group-text').style.color = '';
                });
            });
        });
    </script>
</body>

</html>