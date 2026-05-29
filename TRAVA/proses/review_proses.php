<?php
session_start();

include '../config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// =========================
// AMBIL DATA
// =========================

$wisata_id = $_POST['wisata_id'];
$rating = $_POST['rating'];
$komentar = htmlspecialchars($_POST['komentar']);


// =========================
// INSERT REVIEW
// =========================

mysqli_query($conn,"
    INSERT INTO review
    VALUES(
        NULL,
        '$user_id',
        '$wisata_id',
        '$komentar',
        '$rating',
        NOW()
    )
");


// =========================
// UPDATE RATING
// =========================

$avg = mysqli_query($conn,"
    SELECT
    AVG(rating) as rata,
    COUNT(id) as total
    FROM review
    WHERE wisata_id='$wisata_id'
");

$data = mysqli_fetch_assoc($avg);

mysqli_query($conn,"
    UPDATE wisata
    SET
    rating_avg='".$data['rata']."',
    rating_count='".$data['total']."'
    WHERE id='$wisata_id'
");


// =========================
// NOTIFIKASI - simpan ke DB
// =========================

// Ambil nama wisata
$wisata_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM wisata WHERE id='$wisata_id'"));
$wisata_nama = $wisata_row ? $wisata_row['nama'] : 'destinasi ini';

// Pastikan tabel ada
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL DEFAULT 0,
  `trip_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'review',
  `message` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `type` varchar(50) DEFAULT 'akun'");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `message` varchar(255) DEFAULT ''");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `from_user_id` int(11) NOT NULL DEFAULT 0");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `trip_id` int(11) DEFAULT NULL");

$notif_msg = mysqli_real_escape_string($conn, "Review-mu di ".$wisata_nama." berhasil dikirim. Rating: ".$rating." bintang.");
mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message)
    VALUES('$user_id', '$user_id', 'review', '$notif_msg')");


// =========================
// KEMBALI
// =========================

header("Location: ../detail.php?id=".$wisata_id);

?>