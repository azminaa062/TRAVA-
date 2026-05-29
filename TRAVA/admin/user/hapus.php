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
// CEK USER ADA / TIDAK
// =========================

$cek = mysqli_query($conn,"
SELECT *
FROM users
WHERE id='$id'
");

if(mysqli_num_rows($cek) == 0){

    header("Location: data.php");
    exit;
}


// =========================
// HAPUS DATA TERKAIT
// =========================


// hapus wishlist user

mysqli_query($conn,"
DELETE FROM wishlist
WHERE user_id='$id'
");


// hapus review user

mysqli_query($conn,"
DELETE FROM review
WHERE user_id='$id'
");


// hapus trip detail dari trip milik user

mysqli_query($conn,"
DELETE trip_detail
FROM trip_detail

JOIN trip
ON trip_detail.trip_id = trip.id

WHERE trip.creator_id='$id'
");


// hapus member trip user

mysqli_query($conn,"
DELETE FROM trip_member
WHERE user_id='$id'
");


// hapus trip user

mysqli_query($conn,"
DELETE FROM trip
WHERE creator_id='$id'
");


// =========================
// HAPUS USER
// =========================

mysqli_query($conn,"
DELETE FROM users
WHERE id='$id'
");


// =========================
// KEMBALI
// =========================

header("Location: data.php");
exit;
?>