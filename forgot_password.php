<?php
include 'includes/db.php';

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $token = bin2hex(random_bytes(16));

  $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email='$email'"));
  if ($user) {
    // Save token
    mysqli_query($conn, "UPDATE users SET reset_token='$token' WHERE email='$email'");

    // Send reset link (replace with PHPMailer in production)
    $reset_link = "http://yourdomain.com/reset_password.php?token=$token";
    mail($email, "Password Reset", "Click here to reset your password: $reset_link");
  }

  $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="height:100vh;">

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow">
          <div class="card-body">
            <h3 class="text-center mb-4">Forgot Password</h3>

            <?php if ($success): ?>
              <div class="alert alert-info">
                If your email is registered, a reset link has been sent.
              </div>
            <?php else: ?>
              <form method="POST">
                <div class="mb-3">
                  <label class="form-label">Email address</label>
                  <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
              </form>
            <?php endif; ?>

            <div class="mt-3 text-center">
              <a href="login.php">Back to login</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
