<?php
session_start();

include '../../config/koneksi.php';
include '../auth/cek_login.php';


// =========================
// CEK ID
// =========================

if(!isset($_GET['id'])){

    header("Location: data.php");
    exit;
}

$id = $_GET['id'];


// =========================
// CEK REVIEW
// =========================

$cek = mysqli_query($conn,"
SELECT *
FROM review
WHERE id='$id'
");

if(mysqli_num_rows($cek) == 0){

    header("Location: data.php");
    exit;
}


// =========================
// AMBIL DATA REVIEW
// =========================

$data = mysqli_fetch_assoc($cek);

$wisata_id = $data['wisata_id'];
$rating = $data['rating'];


// =========================
// HAPUS REVIEW
// =========================

mysqli_query($conn,"
DELETE FROM review
WHERE id='$id'
");


// =========================
// UPDATE RATING WISATA
// =========================

$avg = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT 
AVG(rating) AS rata_rating,
COUNT(id) AS total_review

FROM review

WHERE wisata_id='$wisata_id'
"));

$rating_avg = $avg['rata_rating'] ? $avg['rata_rating'] : 0;
$rating_count = $avg['total_review'];

mysqli_query($conn,"
UPDATE wisata
SET 
rating_avg='$rating_avg',
rating_count='$rating_count'

WHERE id='$wisata_id'
");


// =========================
// KEMBALI
// =========================

header("Location: data.php");
exit;
?>