<?php 
session_start();
include 'config/koneksi.php';

// Redirect if already logged in
if(isset($_SESSION['login'])){
    if($_SESSION['role'] == 'admin'){
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$pesan = isset($_GET['pesan']) ? $_GET['pesan'] : '';
?>
<?php include 'admin/partials/header.php'; ?>

<body class="login-page">

    <!-- LOGO -->
    <div class="login-navbar">
        <div class="logo-area">
            <img src="assets/img/logo-trava.png" class="logo-img">
        </div>
    </div>

    <!-- BOX -->
    <div class="login-box">

        <div class="login-card">

            <!-- TABS -->
            <div class="auth-tabs">
                <button class="tab-btn active" id="tabLogin" onclick="switchTab('login')">Login</button>
                <button class="tab-btn" id="tabRegister" onclick="switchTab('register')">Daftar</button>
            </div>


            <!-- ===== LOGIN FORM ===== -->
            <div id="formLogin">

                <p>Login untuk melanjutkan perjalananmu</p>

                <?php if($pesan == 'gagal'): ?>
                    <div class="auth-alert error">Email atau password salah!</div>
                <?php endif; ?>

                <form action="proses/login_proses.php" method="POST">

                    <div class="input-box">
                        <input type="email" name="email" placeholder="Email" autocomplete="username" required>
                    </div>

                    <div class="input-box pw-wrap">
                        <input type="password" name="password" id="loginPassword" placeholder="Password" autocomplete="current-password" required>
                        <span class="eye-icon" onclick="togglePw('loginPassword','eyeLogin')">
                            <i class="fa-solid fa-eye" id="eyeLogin"></i>
                        </span>
                    </div>

                    <!-- CAPTCHA -->
                    <div class="input-box">
                        <div class="captcha-box" id="captchaDisplay" onclick="refreshCaptcha()" title="Klik untuk refresh"></div>
                        <input type="text" id="captchaInput" placeholder="Masukkan kode captcha" autocomplete="off" required>
                    </div>

                    <button type="submit" class="btn-login" onclick="return validateCaptcha()">Login</button>

                </form>

            </div>


            <!-- ===== REGISTER FORM ===== -->
            <div id="formRegister" style="display:none;">

                <p>Buat akun dan mulai jelajahi Cirebon</p>

                <form action="proses/register_proses.php" method="POST">

                    <div class="input-box">
                        <input type="text" name="nama" placeholder="Nama Lengkap" required>
                    </div>

                    <div class="input-box">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>

                    <div class="input-box pw-wrap">
                        <input type="password" name="password" id="regPassword" placeholder="Password" required>
                        <span class="eye-icon" onclick="togglePw('regPassword','eyeReg')">
                            <i class="fa-solid fa-eye" id="eyeReg"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn-login">Daftar</button>

                </form>

            </div>

        </div>

    </div>

</body>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

/* ========== TABS ========== */

.auth-tabs{
    display:flex;
    gap:0;
    margin-bottom:22px;
    border-radius:14px;
    overflow:hidden;
    border:1px solid rgba(255,255,255,0.25);
}

.tab-btn{
    flex:1;
    padding:11px;
    border:none;
    background:rgba(255,255,255,0.08);
    color:rgba(255,255,255,0.7);
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
}

.tab-btn.active{
    background:rgba(255,255,255,0.22);
    color:white;
}

/* ========== PASSWORD WRAP ========== */

.pw-wrap{
    position:relative;
}

.pw-wrap input{
    padding-right:48px !important;
}

.eye-icon{
    position:absolute;
    right:16px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    color:rgba(255,255,255,0.7);
    font-size:16px;
    user-select:none;
}

.eye-icon:hover{
    color:white;
}

/* ========== CAPTCHA ========== */

.captcha-box{
    background:rgba(255,255,255,0.18);
    border-radius:10px;
    padding:10px 16px;
    font-size:20px;
    font-weight:bold;
    letter-spacing:8px;
    color:white;
    text-align:center;
    margin-bottom:10px;
    cursor:pointer;
    user-select:none;
}

/* ========== ALERT ========== */

.auth-alert{
    padding:10px 14px;
    border-radius:10px;
    font-size:13px;
    margin-bottom:16px;
}

.auth-alert.error{
    background:rgba(239,68,68,0.25);
    color:#fca5a5;
}

.auth-alert.success{
    background:rgba(34,197,94,0.25);
    color:#86efac;
}

/* ========== RESIZE CARD ========== */

.login-card{
    width:380px !important;
    padding:36px 34px !important;
}

.login-card p{
    font-size:13px !important;
    margin-bottom:20px !important;
}

.input-box{
    margin-bottom:14px !important;
}

.input-box input{
    height:46px !important;
    font-size:14px !important;
}

.btn-login{
    height:46px !important;
    font-size:15px !important;
    margin-top:4px;
}

</style>

<script>

function switchTab(tab){
    document.getElementById('formLogin').style.display    = tab==='login'    ? 'block' : 'none';
    document.getElementById('formRegister').style.display = tab==='register' ? 'block' : 'none';
    document.getElementById('tabLogin').classList.toggle('active', tab==='login');
    document.getElementById('tabRegister').classList.toggle('active', tab==='register');
    if(tab==='login') refreshCaptcha();
}

function togglePw(inputId, iconId){
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if(inp.type==='password'){
        inp.type='text';
        icon.className='fa-solid fa-eye-slash';
    } else {
        inp.type='password';
        icon.className='fa-solid fa-eye';
    }
}

let captchaCode = '';

function generateCaptcha(){
    const chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let c='';
    for(let i=0;i<6;i++) c+=chars[Math.floor(Math.random()*chars.length)];
    captchaCode=c;
    const el = document.getElementById('captchaDisplay');
    if(el) el.innerText=c;
}

function refreshCaptcha(){
    generateCaptcha();
    const inp = document.getElementById('captchaInput');
    if(inp) inp.value='';
}

function validateCaptcha(){
    const inp = document.getElementById('captchaInput');
    if(!inp) return true;
    if(inp.value.toUpperCase().trim() !== captchaCode){
        alert('Kode captcha salah! Klik kode untuk refresh.');
        refreshCaptcha();
        return false;
    }
    return true;
}

window.onload = function(){ generateCaptcha(); };

</script>

</html>
