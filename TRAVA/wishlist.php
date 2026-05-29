<?php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$wishlist = mysqli_query($conn,"
    SELECT 
    wishlist.*,
    wisata.nama,
    wisata.gambar,
    wisata.kategori,
    wisata.lokasi,
    wisata.rating_avg,
    wisata.rating_count,
    wisata.harga

    FROM wishlist

    JOIN wisata
    ON wishlist.wisata_id = wisata.id

    WHERE wishlist.user_id = '$user_id'

    ORDER BY wishlist.id DESC
");


$user_id_nav = $_SESSION['user_id'] ?? 0;
$_user_nav = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$user_id_nav'"));
$_nav_foto = !empty($_user_nav['foto']) ? $_user_nav['foto'] : '';
$_nav_initial = strtoupper(mb_substr($_user_nav['nama'] ?? $_SESSION['nama'] ?? 'U', 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Wishlist - TRAVA</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#f5f7fb;
    font-family:'Manrope', sans-serif;
    color:#1e293b;
}



/* =========================
NAVBAR
========================= */

.navbar{
    width:100%;
    padding:18px 6%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:white;
    box-shadow:0 4px 20px rgba(15,23,42,0.04);
    position:sticky;
    top:0;
    z-index:999;
}

.nav-logo{
    display:flex;
    align-items:center;
    gap:0;
    text-decoration:none;
}

.nav-logo img{
    height:52px;
    width:auto;
    object-fit:contain;
}

.nav-menu{
    display:flex;
    align-items:center;
    gap:28px;
}

.nav-menu a{
    text-decoration:none;
    color:#64748b;
    font-size:14px;
    font-weight:700;
    transition:0.3s;
    line-height:1;
}

.nav-menu a:hover{ color:#17375e; }
.nav-menu .active{ color:#17375e; }

.notif-wrapper{position:relative;display:inline-flex;align-items:center;}
.notif-btn{background:none;border:none;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#17375e;font-size:18px;transition:0.2s;position:relative;border-radius:50%;}
.notif-btn:hover{background:#f1f5f9;}
.notif-badge{position:absolute;top:2px;right:2px;background:#ef4444;color:white;border-radius:999px;font-size:10px;font-weight:700;min-width:18px;height:18px;display:none;align-items:center;justify-content:center;padding:0 4px;}
.notif-dropdown{position:absolute;top:52px;right:0;width:380px;background:white;border-radius:16px;box-shadow:0 8px 32px rgba(15,23,42,0.18);z-index:9999;display:none;overflow:hidden;}
.notif-dropdown.open{display:block;}
.notif-header{padding:16px 20px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;}
.notif-header h4{font-size:15px;font-weight:700;color:#17375e;margin:0;}
.notif-readall{font-size:11px;color:#2563eb;background:none;border:none;cursor:pointer;font-weight:600;}
.notif-list{max-height:360px;overflow-y:auto;}
.notif-item{padding:12px 18px;border-bottom:1px solid #f8fafc;cursor:pointer;transition:0.15s;display:flex;gap:12px;align-items:flex-start;}
.notif-item:hover{background:#f8fafc;}
.notif-item.unread{background:#eff6ff;}
.notif-icon-wrap{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.notif-icon-wrap.review{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.wishlist{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.trip{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.wacana{background:#fee2e2;color:#dc2626;}
.notif-icon-wrap.akun{background:#dcfce7;color:#16a34a;}
.notif-icon-wrap.invite{background:#dbeafe;color:#1d4ed8;}
.notif-body{flex:1;min-width:0;}
.notif-title{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;}
.notif-msg{font-size:12px;color:#64748b;line-height:1.5;}
.notif-time{font-size:11px;color:#9ca3af;margin-top:3px;}
.notif-dot{width:8px;height:8px;background:#2563eb;border-radius:50%;flex-shrink:0;margin-top:8px;}
.notif-footer{padding:12px;text-align:center;border-top:1px solid #f1f5f9;}
.notif-footer a{color:#2563eb;font-size:13px;font-weight:600;text-decoration:none;}
.notif-empty{padding:32px;text-align:center;color:#94a3b8;font-size:14px;}

.profile-wrapper{position:relative;display:inline-flex;align-items:center;}
.profile-avatar-btn{background:none;border:none;cursor:pointer;padding:0;width:40px;height:40px;border-radius:50%;overflow:visible;display:flex;align-items:center;justify-content:center;}
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}
.profile-dropdown{position:absolute;top:52px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}





/* =========================
CONTAINER
========================= */

.container{
    width:88%;
    margin:auto;
    padding:40px 0;
}


/* =========================
HERO — WISHLIST
========================= */
.hero{
    border-radius:28px;
    padding:52px;
    margin-bottom:35px;
    position:relative;
    overflow:hidden;
    min-height:190px;
    box-shadow:
        0 2px 0 rgba(255,255,255,0.06) inset,
        0 32px 80px rgba(8,18,50,0.5),
        0 8px 20px rgba(8,18,50,0.3);
    background: #09183a;
}

/* Photo layer — Gronggong sunset photo used as atmospheric bg */
.hero-photo{
    position:absolute;
    inset:0;
    background: url('assets/img/Gronggong.jpg') center 40% / cover no-repeat;
    z-index:0;
    transform: scale(1.06);
    filter: saturate(1.3) brightness(0.55);
}

/* Deep gradient overlay — left side dark for text, right reveals photo */
.hero::before{
    content:'';
    position:absolute;
    inset:0;
    z-index:1;
    background:
        linear-gradient(100deg,
            #09183a 0%,
            #0d2050 22%,
            rgba(10,22,58,0.82) 42%,
            rgba(8,16,45,0.38) 62%,
            rgba(5,10,28,0.12) 100%
        ),
        radial-gradient(ellipse 60% 80% at 50% 110%,
            rgba(255,120,40,0.28) 0%,
            transparent 65%
        );
}

/* Rim light glow at bottom edge */
.hero::after{
    content:'';
    position:absolute;
    bottom:0; left:0; right:0;
    height:2px;
    background: linear-gradient(90deg,
        rgba(255,160,60,0.0) 0%,
        rgba(255,160,60,0.5) 35%,
        rgba(255,200,80,0.8) 55%,
        rgba(255,160,60,0.4) 75%,
        rgba(255,160,60,0.0) 100%
    );
    z-index:6;
}

/* Top edge rim — glass feel */
.hero-glass-rim{
    position:absolute;
    top:0; left:0; right:0;
    height:1px;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(255,255,255,0.18) 25%,
        rgba(255,255,255,0.32) 50%,
        rgba(255,255,255,0.18) 75%,
        transparent 100%
    );
    z-index:6;
    border-radius:28px 28px 0 0;
}

.hero-content{
    position:relative;
    z-index:4;
}

.hero h1{
    font-family:'Cormorant Garamond', serif;
    font-size:40px;
    font-weight:700;
    color:white;
    margin-bottom:12px;
    text-shadow: 0 2px 24px rgba(0,0,0,0.6), 0 0 60px rgba(255,160,60,0.15);
    letter-spacing:0.3px;
}

.hero p{
    color:rgba(255,255,255,0.82);
    font-size:15px;
    line-height:1.85;
    max-width:480px;
    text-shadow: 0 1px 10px rgba(0,0,0,0.5);
}

/* Unused old deco classes — keep empty so PHP doesn't break */
.hero-deco-left{ display:none; }
.hero-deco{ display:none; }


/* =========================
TITLE
========================= */

.section-title{
    font-family:'Cormorant Garamond', serif;
    font-size:28px;
    color:#17375e;
    margin-bottom:28px;
}


/* =========================
GRID — sama dengan home
========================= */

.wisata-grid{
    display:grid;
    grid-template-columns:
    repeat(3,minmax(0,360px));

    gap:26px;
    justify-content:start;
}


/* =========================
CARD — identik dengan home
========================= */

.wisata-card{
    background:white;

    border-radius:28px;
    overflow:hidden;

    box-shadow:
    0 10px 25px rgba(15,23,42,0.05);

    transition:0.3s;
    width:100%;
    max-width:360px;
}

.wisata-card:hover{
    transform:translateY(-6px);
}


/* =========================
IMAGE
========================= */

.wisata-image{
    position:relative;
}

.wisata-image img{
    width:100%;
    height:240px;
    object-fit:cover;
}

.category{
    position:absolute;
    top:18px;
    left:18px;

    background:white;
    color:#17375e;

    padding:8px 14px;

    border-radius:50px;

    font-size:12px;
    font-weight:700;
}

.wish-love{
    position:absolute;
    top:14px;
    right:14px;
    color:#ef4444;
    font-size:22px;
    filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    line-height:1;
}


/* =========================
BODY
========================= */

.wisata-body{
    padding:26px;
}

.wisata-body h3{
    font-family:'Cormorant Garamond', serif;
    font-size:22px;
    color:#1e293b;

    margin-bottom:12px;
}

.location{
    color:#64748b;
    font-size:14px;

    margin-bottom:14px;
}

.rating{
    display:flex;
    align-items:center;
    gap:8px;

    color:#f59e0b;
    font-size:14px;
    font-weight:700;

    margin-bottom:16px;
}

.price{
    color:#16a34a;
    font-size:18px;
    font-weight:700;

    margin-bottom:22px;
}


/* =========================
BUTTON ROW
========================= */

.card-action{
    display:flex;
    gap:12px;
}

.detail-btn{
    flex:1;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;

    padding:15px;

    border-radius:18px;

    background:#17375e;
    color:white;

    text-decoration:none;

    font-size:14px;
    font-weight:700;

    transition:0.3s;
}

.detail-btn:hover{
    background:#102845;
}

.delete-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;

    padding:15px 18px;

    border-radius:18px;

    background:#fff1f2;
    color:#e11d48;

    text-decoration:none;

    font-size:14px;
    font-weight:700;

    transition:0.3s;
}

.delete-btn:hover{
    background:#ffe4e6;
}


/* =========================
EMPTY
========================= */

.empty{
    background:white;

    padding:70px 30px;

    text-align:center;

    border-radius:28px;

    border:1px solid #eef2f7;
}

.empty i{
    font-size:60px;
    color:#cbd5e1;
    margin-bottom:20px;
}

.empty h2{
    font-family:'Cormorant Garamond',serif;
    font-size:28px;
    margin-bottom:10px;
    color:#1e293b;
}

.empty p{
    color:#94a3b8;
    font-size:14px;
}



/* =====================================================
FOOTER TRAVA
===================================================== */

.trava-footer{
    background: #1a1a1a;
    margin-top: 80px;
    padding: 0;
}

.trava-footer-inner{
    max-width: 1200px;
    margin: 0 auto;
    padding: 56px 5% 0;
}

.trava-footer-grid{
    display: grid;
    grid-template-columns: 2fr 1.2fr 1.2fr 1.2fr;
    gap: 48px;
    padding-bottom: 48px;
}

.trava-footer-logo{
    font-family: 'Cormorant Garamond', serif;
    font-size: 38px;
    font-weight: 700;
    color: #f9844a;
    letter-spacing: 2px;
    margin-bottom: 16px;
    line-height: 1;
}

.trava-footer-desc{
    color: rgba(255,255,255,0.55);
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    line-height: 1.9;
}

.trava-footer-heading{
    font-family: 'Manrope', sans-serif;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #f9844a;
    margin-bottom: 20px;
}

.trava-footer-list{
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.trava-footer-list a{
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.25s;
}

.trava-footer-list a:hover{
    color: #f9844a;
}

.trava-footer-social{
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.trava-social-link{
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.25s;
}

.trava-social-link:hover{
    color: #f9844a;
}

.trava-social-icon{
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    opacity: 0.8;
}

.trava-social-link:hover .trava-social-icon{
    opacity: 1;
}

.trava-footer-divider{
    border: none;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin: 0;
}

.trava-footer-bottom{
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 22px 0 24px;
    font-family: 'Manrope', sans-serif;
    font-size: 13px;
    color: rgba(255,255,255,0.45);
}

.trava-footer-sep{
    color: #f9844a;
    font-size: 14px;
    opacity: 0.7;
}

@media (max-width: 900px){
    .trava-footer-grid{
        grid-template-columns: 1fr 1fr;
        gap: 36px;
    }
}

@media (max-width: 560px){
    .trava-footer-grid{
        grid-template-columns: 1fr;
        gap: 32px;
    }
}





/* =========================
RESPONSIVE
========================= */

@media(max-width:768px){

    .navbar{
        flex-direction:column;
        gap:18px;
    }

    .nav-menu{
        flex-wrap:wrap;
        justify-content:center;
    }

    .hero{
        padding:35px 28px;
    }

    .hero h1{
        font-size:32px;
    }

    .wisata-grid{
        grid-template-columns:1fr;
    }

    .footer-grid{
        grid-template-columns:1fr;
    }

}


/* NOTIFIKASI BELL */
.notif-wrapper{position:relative;display:inline-flex;align-items:center;}
.notif-btn{background:none;border:none;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#17375e;font-size:18px;transition:0.2s;position:relative;border-radius:50%;}
.notif-btn:hover{background:#f1f5f9;}
.notif-badge{position:absolute;top:2px;right:2px;background:#ef4444;color:white;border-radius:999px;font-size:10px;font-weight:700;min-width:18px;height:18px;display:none;align-items:center;justify-content:center;padding:0 4px;}
.notif-dropdown{position:absolute;top:50px;right:0;width:380px;background:white;border-radius:16px;box-shadow:0 8px 32px rgba(15,23,42,0.18);z-index:9999;display:none;overflow:hidden;}
.notif-dropdown.open{display:block;}
.notif-header{padding:16px 20px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f1f5f9;}
.notif-header h4{font-size:15px;font-weight:700;color:#17375e;margin:0;}
.notif-readall{font-size:11px;color:#2563eb;background:none;border:none;cursor:pointer;font-weight:600;}
.notif-list{max-height:360px;overflow-y:auto;}
.notif-item{padding:12px 18px;border-bottom:1px solid #f8fafc;cursor:pointer;transition:0.15s;display:flex;gap:12px;align-items:flex-start;}
.notif-item:hover{background:#f8fafc;}
.notif-item.unread{background:#eff6ff;}
.notif-icon-wrap{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.notif-icon-wrap.review{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.wishlist{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.trip{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.wacana{background:#fee2e2;color:#dc2626;}
.notif-icon-wrap.akun{background:#dcfce7;color:#16a34a;}
.notif-icon-wrap.invite{background:#dbeafe;color:#1d4ed8;}
.notif-body{flex:1;min-width:0;}
.notif-title{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;}
.notif-msg{font-size:12px;color:#64748b;line-height:1.5;}
.notif-time{font-size:11px;color:#9ca3af;margin-top:3px;}
.notif-dot{width:8px;height:8px;background:#2563eb;border-radius:50%;flex-shrink:0;margin-top:8px;}
.notif-footer{padding:12px;text-align:center;border-top:1px solid #f1f5f9;}
.notif-footer a{color:#2563eb;font-size:13px;font-weight:600;text-decoration:none;}
.notif-empty{padding:24px;text-align:center;color:#94a3b8;font-size:13px;}
/* PROFILE DROPDOWN */
.profile-wrapper{position:relative;display:inline-flex;align-items:center;}
.profile-avatar-btn{background:none;border:none;cursor:pointer;padding:0;width:40px;height:40px;border-radius:50%;overflow:visible;display:flex;align-items:center;justify-content:center;}
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}
.profile-dropdown{position:absolute;top:50px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}
/* PROFILE DROPDOWN */
.profile-wrapper{position:relative;display:inline-flex;align-items:center;}
.profile-avatar-btn{background:none;border:none;cursor:pointer;padding:0;width:40px;height:40px;border-radius:50%;overflow:visible;display:flex;align-items:center;justify-content:center;}
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}.profile-avatar-initial{width:40px;height:40px;border-radius:50%;background:#17375e;color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;border:2px solid #c8a84b;cursor:pointer;text-transform:uppercase;flex-shrink:0;}
.profile-dropdown{position:absolute;top:50px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}
</style>
</head>
<body>



<!-- =========================
NAVBAR
========================= -->

<div class="navbar">

    <a href="index.php" class="nav-logo">
        <img src="assets/img/logo-trava.png" alt="TRAVA Logo">
    </a>

    <div class="nav-menu">
        <a href="index.php" >Home</a>
        <a href="wishlist.php" class="active">Wishlist</a>
        <a href="trip.php" >Trip</a>

        <!-- NOTIFIKASI BELL -->
        <div class="notif-wrapper" id="notifWrapper">
            <button class="notif-btn" id="notifBtn" onclick="toggleNotif(event)" title="Notifikasi">
                <i class="fa-solid fa-bell"></i>
                <span class="notif-badge" id="notifBadge"></span>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <h4>Notifikasi</h4>
                    <button class="notif-readall" onclick="readAll()">Tandai semua dibaca</button>
                </div>
                <div class="notif-list" id="notifList"><div class="notif-empty">Belum ada notifikasi</div></div>
                <div class="notif-footer">
                    <a href="notifikasi.php">Lihat semua notifikasi</a>
                </div>
            </div>
        </div>

        <!-- PROFIL AVATAR -->
        <div class="profile-wrapper" id="profileWrapper">
            <button class="profile-avatar-btn" onclick="toggleProfile(event)">
                <?php if(!empty($_nav_foto)): ?>
                <img src="assets/img/profil/<?= htmlspecialchars($_nav_foto); ?>" alt="Profil" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;">
                <?php else: ?>
                <div class="profile-avatar-initial"><?= htmlspecialchars($_nav_initial ?? 'U'); ?></div>
                <?php endif; ?>
            </button>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="profil.php" class="profile-dd-item"><i class="fa-regular fa-user"></i> Lihat Profil Saya</a>
                <div class="profile-dd-divider"></div>
                <a href="logout.php" class="profile-dd-item"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>

</div>




<!-- =========================
CONTAINER
========================= -->

<div class="container">



    <!-- HERO -->
    <div class="hero">
        <div class="hero-photo"></div>
        <div class="hero-glass-rim"></div>
        <div class="hero-content">
            <h1>My Wishlist</h1>
            <p>
                Simpan destinasi impianmu di sini dan wujudkan<br>
                petualangan terbaik bersama TRAVA.
            </p>
        </div>
    </div>



    <!-- TITLE -->

    <?php if(mysqli_num_rows($wishlist) > 0) : ?>

    <!-- GRID -->

    <div class="wisata-grid">

        <?php while($w = mysqli_fetch_assoc($wishlist)) : ?>

        <div class="wisata-card">


            <!-- IMAGE -->

            <div class="wisata-image">

                <img
                src="assets/img/<?= $w['gambar']; ?>">

                <div class="category">

                    <?= $w['kategori']; ?>

                </div>

                <div class="wish-love">
                    <i class="fa-solid fa-heart"></i>
                </div>

            </div>



            <!-- BODY -->

            <div class="wisata-body">

                <h3><?= $w['nama']; ?></h3>

                <div class="location">

                    <i class="fa-solid fa-location-dot"></i>

                    <?= $w['lokasi']; ?>

                </div>

                <div class="rating">

                    <i class="fa-solid fa-star"></i>

                    <?= number_format($w['rating_avg'],1); ?>

                    (<?= $w['rating_count']; ?> Review)

                </div>

                <div class="price">

                    Rp <?= number_format($w['harga']); ?>

                </div>

                <div class="card-action">

                    <a href="detail.php?id=<?= $w['wisata_id']; ?>"
                    class="detail-btn">

                        <i class="fa-solid fa-arrow-right"></i>

                        Lihat Detail

                    </a>

                    <a href="proses/wishlist_proses.php?id=<?= $w['wisata_id']; ?>&hapus=1"
                    class="delete-btn"
                    onclick="return confirm('Hapus wishlist ini?')">

                        <i class="fa-solid fa-trash"></i>

                    </a>

                </div>

            </div>

        </div>

        <?php endwhile; ?>

    </div>


    <?php else : ?>


    <!-- EMPTY -->

    <div class="empty">

        <i class="fa-regular fa-heart"></i>

        <h2>Wishlist Masih Kosong</h2>

        <p>
            Tambahkan destinasi favoritmu sekarang juga.
        </p>

    </div>

    <?php endif; ?>


</div>





<!-- =====================================================
FOOTER TRAVA
===================================================== -->

<footer class="trava-footer">

    <div class="trava-footer-inner">

        <div class="trava-footer-grid">

            <!-- COL 1 — Brand -->
            <div class="trava-footer-brand">
                <div class="trava-footer-logo">TRAVA</div>
                <p class="trava-footer-desc">
                    Platform wisata modern untuk membantu
                    traveler menemukan destinasi terbaik
                    di Cirebon dan sekitarnya.
                </p>
            </div>

            <!-- COL 2 — Explore -->
            <div>
                <div class="trava-footer-heading">Explore</div>
                <ul class="trava-footer-list">
                    <li><a href="index.php">Destinations</a></li>
                    <li><a href="index.php">Nearby Places</a></li>
                    <li><a href="index.php">Trending</a></li>
                    <li><a href="index.php">Explore Maps</a></li>
                </ul>
            </div>

            <!-- COL 3 — Community -->
            <div>
                <div class="trava-footer-heading">Community</div>
                <ul class="trava-footer-list">
                    <li><a href="trip_group.php">Group Trips</a></li>
                    <li><a href="trip.php">Shared Trip</a></li>
                    <li><a href="#">Stories</a></li>
                    <li><a href="#">Partners</a></li>
                </ul>
            </div>

            <!-- COL 4 — Follow Us -->
            <div>
                <div class="trava-footer-heading">Follow Us</div>
                <ul class="trava-footer-social">
                    <li>
                        <a href="#" class="trava-social-link">
                            <svg class="trava-social-icon" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                            Instagram
                        </a>
                    </li>
                    <li>
                        <a href="#" class="trava-social-link">
                            <svg class="trava-social-icon" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.27 8.27 0 004.84 1.55V6.79a4.85 4.85 0 01-1.07-.1z"/>
                            </svg>
                            TikTok
                        </a>
                    </li>
                    <li>
                        <a href="#" class="trava-social-link">
                            <svg class="trava-social-icon" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                            YouTube
                        </a>
                    </li>
                    <li>
                        <a href="#" class="trava-social-link">
                            <svg class="trava-social-icon" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Divider -->
        <div class="trava-footer-divider">
            <span></span>
        </div>

        <!-- Bottom bar -->
        <div class="trava-footer-bottom">
            <span>© 2026 TRAVA</span>
            <span class="trava-footer-sep">|</span>
            <span>Explore Smarter, Travel Better</span>
        </div>

    </div>

</footer>


<script>
const _NI={
  review:'<i class="fa-solid fa-star"></i>',
  wishlist:'<i class="fa-solid fa-heart"></i>',
  trip:'<i class="fa-solid fa-plane-departure"></i>',
  wacana:'<i class="fa-solid fa-triangle-exclamation"></i>',
  akun:'<i class="fa-solid fa-user"></i>',
  invite:'<i class="fa-solid fa-user-plus"></i>',
  chat_personal:'<i class="fa-solid fa-message"></i>',
  chat_group:'<i class="fa-solid fa-comments"></i>'
};
if(typeof NOTIF_BASE==='undefined') var NOTIF_BASE='';
function toggleNotif(e){
  e.stopPropagation();
  var dd=document.getElementById("notifDropdown");
  var pd=document.getElementById("profileDropdown");
  if(pd) pd.classList.remove("open");
  dd.classList.toggle("open");
  if(dd.classList.contains("open")) _loadNL();
}
function toggleProfile(e){
  e.stopPropagation();
  var pd=document.getElementById("profileDropdown");
  var nd=document.getElementById("notifDropdown");
  if(nd) nd.classList.remove("open");
  pd.classList.toggle("open");
}
document.addEventListener("click",function(){
  var dd=document.getElementById("notifDropdown");
  var pd=document.getElementById("profileDropdown");
  if(dd) dd.classList.remove("open");
  if(pd) pd.classList.remove("open");
});
document.addEventListener("DOMContentLoaded",function(){
  var dd=document.getElementById("notifDropdown");
  var pd=document.getElementById("profileDropdown");
  if(dd) dd.addEventListener("click",function(e){e.stopPropagation();});
  if(pd) pd.addEventListener("click",function(e){e.stopPropagation();});
});
function _loadNL(){
  fetch(NOTIF_BASE+"proses/notif_proses.php?action=list").then(function(r){return r.json();}).then(function(list){
    var el=document.getElementById("notifList");
    if(!list||!list.length){el.innerHTML='<div class="notif-empty">Belum ada notifikasi</div>';return;}
    var html=list.slice(0,5).map(function(n){
      var ic=_NI[n.type]||'<i class="fa-solid fa-bell"></i>';
      var lk=n.link_url?NOTIF_BASE+n.link_url:(n.trip_id?NOTIF_BASE+"trip_group.php?id="+n.trip_id:NOTIF_BASE+"trip.php");
      var parts=n.message.split('.');
      var title=parts[0]||n.message;
      return '<div class="notif-item '+(n.is_read==0?"unread":"")+'" onclick="_goN('+n.id+',\''+lk+'\')">'+
        '<div class="notif-icon-wrap '+(n.type||"invite")+'">'+ic+'</div>'+
        '<div class="notif-body">'+
          '<div class="notif-title">'+title+'</div>'+
          '<div class="notif-msg">'+n.message+'</div>'+
          '<div class="notif-time">'+n.time+'</div>'+
        '</div>'+
        (n.is_read==0?'<div class="notif-dot"></div>':'')+
      '</div>';
    }).join("");
    el.innerHTML=html;
  }).catch(function(){});
}
function _goN(id,link){
  fetch(NOTIF_BASE+"proses/notif_proses.php?action=read_one&id="+id).then(function(){window.location.href=link;});
}
function readAll(){
  fetch(NOTIF_BASE+"proses/notif_proses.php?action=read_all").then(function(){
    document.getElementById("notifBadge").style.display="none";
    _loadNL();
  });
}
function _chkN(){
  fetch(NOTIF_BASE+"proses/notif_proses.php?action=count").then(function(r){return r.json();}).then(function(d){
    var b=document.getElementById("notifBadge");
    if(!b) return;
    if(d.count>0){b.textContent=d.count>99?"99+":d.count;b.style.display="flex";}
    else{b.style.display="none";}
  }).catch(function(){});
}
_chkN();setInterval(_chkN,8000);
</script>
</body>
</html>
