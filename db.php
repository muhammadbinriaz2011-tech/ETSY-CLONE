<?php
// db.php
$host = 'localhost';
$dbname = 'rsoa_rsoa000142_2';
$username = 'rsoa_rsoa000142_2';
$password = '654321#';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database Connection Failed!<br>Username: <b>rsoa_rsoa000142_2</b><br>Password: <b>654321#</b><br>Error: " . htmlspecialchars($e->getMessage()));
}
?>
