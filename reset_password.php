<?php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token'];
  $newpass = password_hash($_POST['password'], PASSWORD_DEFAULT);
  mysqli_query($conn, "UPDATE users SET password='$newpass', reset_token=NULL WHERE reset_token='$token'");
  echo "<div class='alert alert-success'>Password updated. <a href='login.php'>Login</a></div>";
} else if (isset($_GET['token'])) {
  $token = $_GET['token'];
  $result = mysqli_query($conn, "SELECT * FROM users WHERE reset_token='$token'");
  if (mysqli_num_rows($result)) {
?>
<h2>Reset Password</h2>
<form method="POST" class="w-50">
  <input type="hidden" name="token" value="<?= $token ?>">
  <input class="form-control mb-3" type="password" name="password" placeholder="New Password" required>
  <button class="btn btn-success" type="submit">Reset Password</button>
</form>
<?php
  } else {
    echo "<div class='alert alert-danger'>Invalid or expired token.</div>";
  }
}
?>