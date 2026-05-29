<?php
$host     = "127.0.0.1"; // bisa juga "localhost"
$user     = "root";
$password = "";          // kosongkan jika root tidak punya password
$dbname   = "trava_db";
$port     = 3306;        // default port MySQL di XAMPP

$conn = mysqli_connect($host, $user, $password, $dbname, $port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
