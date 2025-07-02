<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/googleauthenticator.php';
session_start();

$user_id = $_SESSION['user_id']; // must be logged in

$ga = new PHPGangsta_GoogleAuthenticator();
$secret = $ga->createSecret();

$stmt = $conn->prepare("UPDATE users SET ga_secret=? WHERE id=?");
$stmt->bind_param("si", $secret, $user_id);
$stmt->execute();

// Generate QR for Google Authenticator
$qrCodeUrl = $ga->getQRCodeGoogleUrl('InventorySystem (' . $user_id . ')', $secret);

echo "<h2>Set up Google Authenticator</h2>";
echo "<img src='$qrCodeUrl' alt='QR Code'><br>";
echo "Scan with your Google Authenticator app.<br>";
echo "Manual secret: <b>" . htmlspecialchars($secret) . "</b>";
echo "<br><a href='dashboard.php'>Back to dashboard</a>";
?>
<?
$qrCodeUrl = $ga->getQRCodeGoogleUrl('InventorySystem(' . $user_id . ')', $secret);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Enable 2FA</title>
</head>
<body>
    <h2>Set up Google Authenticator</h2>
    <img src="<?= $qrCodeUrl ?>" alt="Scan this QR code with Google Authenticator">
    <p>Or manually enter this secret: <b><?= htmlspecialchars($secret) ?></b></p>
    <form method="post">
        <button type="submit" name="confirm_enable">Enable 2FA</button>
    </form>
</body>
</html>