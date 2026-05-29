<?php
session_start();
// If already logged in, redirect
if(isset($_SESSION['login'])){
    if($_SESSION['role'] == 'admin'){
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<?php include 'admin/partials/header.php'; ?>

<body class="login-page">

    <!-- LOGO -->
    <div class="login-navbar">

        <div class="logo-area">

            <img src="assets/img/logo-trava.png"
            class="logo-img">

        </div>

    </div>

    <!-- BOX -->
    <div class="login-box">

        <div class="login-card">

            <h2>SIGN UP!</h2>

            <p>
                Buat akun dan mulai jelajahi
                wisata terbaik Cirebon
            </p>

            <form action="proses/register_proses.php"
            method="POST">

                <!-- NAMA -->
                <div class="input-box">

                    <input type="text"
                    name="nama"
                    placeholder="Nama Lengkap"
                    required>

                </div>

                <!-- EMAIL -->
                <div class="input-box">

                    <input type="email"
                    name="email"
                    placeholder="Email"
                    required>

                </div>

                <!-- PASSWORD -->
                <div class="input-box pw-wrap">

                    <input type="password"
                    name="password"
                    id="regPassword"
                    placeholder="Password"
                    required>

                    <span class="eye-icon"
                    onclick="togglePw('regPassword','eyeReg')">
                        <i class="fa-solid fa-eye" id="eyeReg"></i>
                    </span>

                </div>

                <!-- BUTTON -->
                <button type="submit"
                class="btn-login">

                    Daftar

                </button>

            </form>

            <!-- LINK -->
            <div class="register-link">

                Sudah punya akun?

                <a href="login.php">
                    Login
                </a>

            </div>

        </div>

    </div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

.pw-wrap{
    position:relative;
}

.pw-wrap input{
    padding-right:50px !important;
}

.eye-icon{
    position:absolute;
    right:18px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    color:rgba(255,255,255,0.7);
    font-size:18px;
    user-select:none;
}

.eye-icon:hover{
    color:white;
}

.input-box{
    position:relative;
}

</style>

<script>
function togglePw(inputId, iconId){
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if(input.type === 'password'){
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}
</script>

</body>
</html>
