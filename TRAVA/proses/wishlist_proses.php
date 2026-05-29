<?php
session_start();
include '../config/koneksi.php';


// =========================
// CEK LOGIN
// =========================

if(!isset($_SESSION['login'])){

    header("Location: ../login.php");
    exit;
}


// =========================
// AMBIL DATA
// =========================

$user_id   = $_SESSION['user_id'];
$wisata_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


// =========================
// MODE HAPUS
// =========================

if(isset($_GET['hapus'])){

    mysqli_query($conn,"
        DELETE FROM wishlist
        WHERE user_id='$user_id'
        AND wisata_id='$wisata_id'
    ");

    echo "
    <script>

        alert('Wishlist berhasil dihapus');

        window.location='../wishlist.php';

    </script>
    ";

    exit;
}


// =========================
// CEK WISHLIST
// =========================

$cek = mysqli_query($conn,"
    SELECT *
    FROM wishlist
    WHERE user_id='$user_id'
    AND wisata_id='$wisata_id'
");


// =========================
// JIKA SUDAH ADA
// =========================

if(mysqli_num_rows($cek) > 0){

    echo "
    <script>

        alert('Wisata sudah ada di wishlist ❤️');

        window.location='../detail.php?id=$wisata_id';

    </script>
    ";

    exit;
}


// =========================
// INSERT WISHLIST
// =========================

mysqli_query($conn,"
    INSERT INTO wishlist(user_id, wisata_id)
    VALUES('$user_id','$wisata_id')
");


// =========================
// NOTIFIKASI - simpan ke DB
// =========================

// Ambil nama wisata
$wisata_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM wisata WHERE id='$wisata_id'"));
$wisata_nama = $wisata_row ? $wisata_row['nama'] : 'wisata ini';

// Pastikan tabel ada
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL DEFAULT 0,
  `trip_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'wishlist',
  `message` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `type` varchar(50) DEFAULT 'akun'");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `message` varchar(255) DEFAULT ''");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `from_user_id` int(11) NOT NULL DEFAULT 0");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `trip_id` int(11) DEFAULT NULL");

$notif_msg = mysqli_real_escape_string($conn, "Kamu menambahkan ".$wisata_nama." ke wishlist.");
mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message)
    VALUES('$user_id', '$user_id', 'wishlist', '$notif_msg')");

echo "
<script>

    alert('Wishlist berhasil ditambahkan ❤️');

    window.location='../detail.php?id=$wisata_id';

</script>
";
?>