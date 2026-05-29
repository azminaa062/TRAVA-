<?php
session_start();

include '../config/koneksi.php';

$email = $_POST['email'];
$password = md5($_POST['password']);

$query = mysqli_query($conn,"
SELECT *
FROM users
WHERE email='$email'
AND password='$password'
");

if(mysqli_num_rows($query) > 0){

    $data = mysqli_fetch_assoc($query);

    $_SESSION['login'] = true;
    $_SESSION['id'] = $data['id'];
    $_SESSION['user_id'] = $data['id'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = $data['role'];


    // ======================
    // JIKA ADMIN
    // ======================

    if($data['role'] == 'admin'){

        header("Location: ../admin/index.php");
        exit;

    }


    // ======================
    // JIKA USER
    // ======================

    else{

        header("Location: ../index.php");
        exit;

    }

}else{

    header("Location: ../login.php?pesan=gagal");
    exit;

}
?>