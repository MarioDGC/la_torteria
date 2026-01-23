<?php
// /public/generate-hash.php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: {$password}<br>";
echo "Hash: {$hash}<br><br>";

echo "SQL para actualizar:<br>";
echo "<textarea rows='3' cols='80' style='font-family:monospace'>";
echo "UPDATE users SET password_hash = '{$hash}' WHERE username = 'admin';";
echo "</textarea>";