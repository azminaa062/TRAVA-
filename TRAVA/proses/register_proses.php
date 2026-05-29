<?php

include '../config/koneksi.php';

$nama = $_POST['nama'];
$email = $_POST['email'];
$password = md5($_POST['password']);

$cek = mysqli_query($conn,
"SELECT * FROM users
WHERE email='$email'");

if(mysqli_num_rows($cek) > 0){

    echo "
    <script>
        alert('Email sudah digunakan!');
        window.location='../register.php';
    </script>
    ";

}else{

    $result = mysqli_query($conn,
    "INSERT INTO users
    (nama,email,password)
    VALUES
    ('$nama','$email','$password')");

    if($result){
        // Ambil data user yang baru dibuat untuk auto-login
        $new_user = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        $user_data = mysqli_fetch_assoc($new_user);
        
        session_start();
        $_SESSION['login'] = true;
        $_SESSION['id'] = $user_data['id'];
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['nama'] = $user_data['nama'];
        $_SESSION['role'] = $user_data['role'];
        
        // Redirect ke halaman welcome
        header("Location: ../welcome.php");
        exit;
    } else {
        echo "
        <script>
            alert('Terjadi kesalahan, coba lagi!');
            window.location='../register.php';
        </script>
        ";
    }

}
