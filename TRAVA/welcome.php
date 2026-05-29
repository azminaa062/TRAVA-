<?php
session_start();
if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}
$nama = $_SESSION['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Selamat Datang - TRAVA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap');

*{margin:0;padding:0;box-sizing:border-box;}

body{
    background: #1a0a00;
    font-family:'Manrope',sans-serif;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    position:relative;
}

/* Batik Megamendung SVG background */
body::before{
    content:'';
    position:absolute;
    inset:0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='120'%3E%3Cdefs%3E%3CradialGradient id='c1' cx='50%25' cy='50%25' r='50%25'%3E%3Cstop offset='0%25' stop-color='%23d4780a' stop-opacity='0.35'/%3E%3Cstop offset='100%25' stop-color='transparent'/%3E%3C/radialGradient%3E%3C/defs%3E%3Cg stroke='%23c9a84c' stroke-width='1.2' fill='none' opacity='0.3'%3E%3Cpath d='M20,60 Q35,30 60,35 Q85,40 90,60 Q85,80 60,85 Q35,90 20,60Z'/%3E%3Cpath d='M25,60 Q38,36 60,40 Q80,44 84,60 Q80,76 60,80 Q38,84 25,60Z' stroke-opacity='0.5'/%3E%3Cpath d='M30,60 Q42,42 60,46 Q76,50 79,60 Q76,70 60,74 Q42,78 30,60Z' stroke-opacity='0.3'/%3E%3Cpath d='M110,60 Q125,30 150,35 Q175,40 180,60 Q175,80 150,85 Q125,90 110,60Z'/%3E%3Cpath d='M115,60 Q128,36 150,40 Q170,44 174,60 Q170,76 150,80 Q128,84 115,60Z' stroke-opacity='0.5'/%3E%3Cpath d='M120,60 Q132,42 150,46 Q166,50 169,60 Q166,70 150,74 Q132,78 120,60Z' stroke-opacity='0.3'/%3E%3Cpath d='M55,15 Q65,5 80,8 Q95,11 98,25 Q92,38 75,36 Q58,34 55,15Z' stroke-opacity='0.6'/%3E%3Cpath d='M160,95 Q170,85 185,88 Q198,91 198,105 Q194,118 178,116 Q162,114 160,95Z' stroke-opacity='0.6'/%3E%3Ccircle cx='60' cy='60' r='4' stroke-opacity='0.4'/%3E%3Ccircle cx='150' cy='60' r='4' stroke-opacity='0.4'/%3E%3Cpath d='M60,56 Q68,50 75,54 Q70,58 64,62 Q58,66 60,56' stroke-opacity='0.5'/%3E%3Cpath d='M150,56 Q158,50 165,54 Q160,58 154,62 Q148,66 150,56' stroke-opacity='0.5'/%3E%3C/g%3E%3C/svg%3E");
    background-size: 200px 120px;
    opacity: 0.6;
    z-index: 0;
}

/* Deep warm color overlay gradient */
body::after{
    content:'';
    position:absolute;
    inset:0;
    background:
        radial-gradient(ellipse 80% 60% at 50% 50%, rgba(160,60,10,0.18) 0%, transparent 70%),
        radial-gradient(ellipse 50% 80% at 20% 20%, rgba(201,168,76,0.12) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 80% 80%, rgba(139,30,10,0.15) 0%, transparent 60%);
    z-index: 0;
}

.batik-corner{
    position: absolute;
    width: 60px;
    height: 60px;
    opacity: 0.35;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cg stroke='%23c9a84c' stroke-width='1.5' fill='none'%3E%3Cpath d='M5,30 Q20,5 45,10 Q55,15 50,30 Q45,45 20,50 Q5,55 5,30Z'/%3E%3Cpath d='M10,30 Q22,10 42,14 Q50,18 46,30 Q42,42 22,46 Q10,50 10,30Z' stroke-opacity='0.6'/%3E%3Ccircle cx='30' cy='30' r='5' stroke-opacity='0.4'/%3E%3Cpath d='M25,25 Q32,18 40,22 Q35,28 30,32 Q24,36 25,25' stroke-opacity='0.5'/%3E%3C/g%3E%3C/svg%3E");
}
.batik-tl{ top:12px; left:12px; }
.batik-br{ bottom:12px; right:12px; transform: rotate(180deg); }


.welcome-box{
    position:relative;
    z-index:2;
    text-align:center;
    padding:60px 50px;
    animation: fadeInUp 0.8s ease both;
    background: rgba(26,8,0,0.55);
    border-radius: 28px;
    border: 1px solid rgba(201,168,76,0.25);
    box-shadow:
        0 20px 60px rgba(0,0,0,0.5),
        0 0 0 1px rgba(201,168,76,0.1),
        inset 0 1px 0 rgba(255,220,80,0.08);
    backdrop-filter: blur(8px);
    max-width: 460px;
    width: 90%;
}

@keyframes fadeInUp{
    from{opacity:0;transform:translateY(40px);}
    to{opacity:1;transform:translateY(0);}
}

.checkmark{
    width:90px;
    height:90px;
    background: linear-gradient(135deg, rgba(201,168,76,0.2), rgba(160,60,10,0.2));
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 30px;
    font-size:40px;
    color: #f0d060;
    animation: popIn 0.6s 0.3s ease both;
    border: 3px solid rgba(201,168,76,0.5);
    box-shadow: 0 0 30px rgba(201,168,76,0.25);
}

@keyframes popIn{
    0%{transform:scale(0);opacity:0;}
    70%{transform:scale(1.15);}
    100%{transform:scale(1);opacity:1;}
}

.welcome-logo{
    font-family:'Cormorant Garamond',serif;
    font-size:52px;
    font-weight:700;
    background: linear-gradient(135deg, #c9a84c 0%, #f0d060 40%, #e8c86a 60%, #c9a84c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing:4px;
    margin-bottom:20px;
    animation: fadeInUp 0.8s 0.2s ease both;
}

h1{
    font-family:'Cormorant Garamond',serif;
    font-size:32px;
    color:white;
    margin-bottom:14px;
    animation: fadeInUp 0.8s 0.4s ease both;
}

p{
    color:rgba(255,255,255,0.8);
    font-size:16px;
    line-height:1.7;
    margin-bottom:10px;
    animation: fadeInUp 0.8s 0.5s ease both;
}

.sub{
    color:rgba(255,255,255,0.55);
    font-size:14px;
    animation: fadeInUp 0.8s 0.6s ease both;
}

.progress-bar{
    width:220px;
    height:4px;
    background:rgba(255,255,255,0.2);
    border-radius:999px;
    margin:30px auto 0;
    overflow:hidden;
    animation: fadeInUp 0.8s 0.7s ease both;
}

.progress-fill{
    height:100%;
    background: linear-gradient(90deg, #c9a84c, #f0d060, #c9a84c);
    border-radius:999px;
    width:0%;
    animation: fillBar 3s 1s linear forwards;
}

@keyframes fillBar{
    from{width:0%;}
    to{width:100%;}
}
</style>
</head>
<body>

<div class="welcome-box">

    <!-- Batik corner decoration -->
    <div class="batik-corner batik-tl"></div>
    <div class="batik-corner batik-br"></div>

    <div class="checkmark">
        <i class="fa-solid fa-check"></i>
    </div>

    <div class="welcome-logo">TRAVA</div>

    <h1>Akun Berhasil Dibuat!</h1>

    <p>Selamat datang di TRAVA, <strong style="color:white;"><?= htmlspecialchars($nama); ?></strong>!</p>

    <p class="sub">Mengarahkan ke halaman utama...</p>

    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>

</div>

<script>
// Redirect ke homepage setelah 3.5 detik
setTimeout(function(){
    window.location.href = 'index.php';
}, 3500);
</script>

</body>
</html>
