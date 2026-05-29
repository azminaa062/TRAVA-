<?php
session_start();
include '../../config/koneksi.php';

// CEK SESSION ADMIN MANUAL (avoid absolute path issue in cek_login)
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../../login.php");
    exit;
}

if(isset($_GET['id']) && is_numeric($_GET['id'])){
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM wisata WHERE id=$id");
}
header("Location: data.php");
exit;
