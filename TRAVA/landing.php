<?php
session_start();
include 'config/koneksi.php';

// If already logged in, redirect to dashboard
if(isset($_SESSION['login'])){
    if($_SESSION['role'] == 'admin'){
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Search
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

// Fetch wisata semua (dengan search)
$where = $search !== '' ? "WHERE nama LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%'" : '';
$show_all = isset($_GET['show']) && $_GET['show'] === 'all';
$limit = ($search !== '' || $show_all) ? "" : "LIMIT 12";
$query_preview = mysqli_query($conn, "SELECT * FROM wisata $where ORDER BY rating_avg DESC $limit");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TRAVA - Explore Cirebon</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
*{ margin:0; padding:0; box-sizing:border-box; }

:root{
    --navy: #0e2348;
    --navy-mid: #17375e;
    --navy-light: #1c3d6e;
    --gold: #c9a84c;
    --gold-light: #f0d060;
    --cream: #fdf8f0;
    --text: #1e293b;
    --muted: #64748b;
    --bg: #f5f7fb;
}

html{ scroll-behavior: smooth; }

body{
    background: var(--bg);
    font-family: 'Manrope', sans-serif;
    color: var(--text);
    overflow-x: hidden;
}

/* ============================
   NAVBAR GUEST
   ============================ */
.navbar{
    width: 100%;
    padding: 16px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    box-shadow: 0 4px 20px rgba(15,23,42,0.04);
    position: sticky;
    top: 0;
    z-index: 999;
}

.nav-logo{
    display: flex;
    align-items: center;
    text-decoration: none;
}

.nav-logo img{
    height: 50px;
    width: auto;
    object-fit: contain;
}

.nav-menu{
    display: flex;
    align-items: center;
    gap: 4px;
}

.nav-link{
    text-decoration: none;
    color: var(--muted);
    font-size: 14px;
    font-weight: 700;
    padding: 8px 14px;
    border-radius: 10px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.nav-link:hover{ color: var(--navy-mid); background: #f1f5f9; }
.nav-link.active{ color: var(--navy-mid); }

/* Guest feature links — show tooltip */
.nav-link-guest{
    color: var(--muted);
    cursor: pointer;
    position: relative;
}

.nav-link-guest::after{
    content: 'Login diperlukan';
    position: absolute;
    bottom: -32px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--navy);
    color: white;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    padding: 4px 10px;
    border-radius: 6px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
}

.nav-link-guest:hover::after{ opacity: 1; }

.nav-cta-group{
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: 12px;
}

.btn-login{
    text-decoration: none;
    color: var(--navy-mid);
    font-size: 14px;
    font-weight: 700;
    padding: 9px 18px;
    border-radius: 10px;
    border: 2px solid var(--navy-mid);
    transition: all 0.2s;
}

.btn-login:hover{
    background: var(--navy-mid);
    color: white;
}

.btn-daftar{
    text-decoration: none;
    color: white;
    font-size: 14px;
    font-weight: 700;
    padding: 9px 18px;
    border-radius: 10px;
    background: var(--navy-mid);
    border: 2px solid var(--navy-mid);
    transition: all 0.2s;
}

.btn-daftar:hover{
    background: var(--navy);
    border-color: var(--navy);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(23,55,94,0.3);
}


/* ============================
   CONTAINER
   ============================ */
.container{
    width: 88%;
    margin: auto;
    padding: 40px 0;
}


/* ============================
   HERO SECTION
   ============================ */
.hero{
    position: relative;
    overflow: visible;
    border-radius: 28px;
    background: var(--navy);
    margin-bottom: 56px;
    min-height: 380px;
    display: flex;
    align-items: stretch;
    box-shadow:
        0 2px 0 rgba(255,255,255,0.07) inset,
        0 -1px 0 rgba(0,0,0,0.35) inset,
        0 32px 90px rgba(8,18,52,0.55),
        0 8px 24px rgba(8,18,52,0.35);
}

.hero-inner{
    position: absolute;
    inset: 0;
    border-radius: 28px;
    overflow: hidden;
    display: flex;
    align-items: stretch;
}

.hero-img-left{
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 295px;
    z-index: 1;
}

.hero-img-left img{
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    filter: brightness(0.9) saturate(1.1);
    clip-path: url(#waveLeft);
    transition: transform 0.6s ease;
}

.hero:hover .hero-img-left img,
.hero:hover .hero-img-right img{
    transform: scale(1.04);
}

.hero-img-left-border{
    position: absolute;
    inset: 0;
    z-index: 3;
    pointer-events: none;
}

.hero-img-right{
    position: absolute;
    right: 0; top: 0; bottom: 0;
    width: 295px;
    z-index: 1;
}

.hero-img-right img{
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    filter: brightness(0.9) saturate(1.1);
    clip-path: url(#waveRight);
    transition: transform 0.6s ease;
}

.hero-img-right-border{
    position: absolute;
    inset: 0;
    z-index: 3;
    pointer-events: none;
}

.hero::before{
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 28px;
    z-index: 0;
    background:
        radial-gradient(ellipse 65% 70% at 50% 50%, #1c3d6e 0%, #0e2348 55%, #081730 100%),
        radial-gradient(ellipse 45% 40% at 50% 110%, rgba(255,140,40,0.1) 0%, transparent 60%);
}

.hero::after{
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    z-index: 10;
    border-radius: 28px 28px 0 0;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(255,255,255,0.12) 20%,
        rgba(255,255,255,0.28) 50%,
        rgba(255,255,255,0.12) 80%,
        transparent 100%
    );
}

.hero-airplane{
    position: absolute;
    top: 44px;
    right: 320px;
    z-index: 4;
    pointer-events: none;
}

.hero-content{
    position: relative;
    z-index: 3;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 52px 330px;
}

.hero h1{
    font-family: 'Cormorant Garamond', serif;
    font-size: 48px;
    font-weight: 700;
    color: white;
    margin-bottom: 14px;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 30px rgba(0,0,0,0.4);
    line-height: 1.15;
}

.hero p{
    color: rgba(255,255,255,0.75);
    font-size: 15px;
    line-height: 1.9;
    max-width: 480px;
    margin-bottom: 28px;
    text-shadow: 0 1px 10px rgba(0,0,0,0.3);
}

.search-form{
    display: flex;
    gap: 0;
    max-width: 560px;
    width: 100%;
    background: rgba(255,255,255,0.12);
    border-radius: 50px;
    padding: 5px 5px 5px 22px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.2);
    box-shadow: 0 4px 24px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.1);
    margin-bottom: 20px;
}

.search-input{
    flex: 1;
    padding: 12px 12px 12px 0;
    border: none;
    outline: none;
    background: transparent;
    font-size: 14px;
    font-family: 'Manrope', sans-serif;
    color: white;
}

.search-input::placeholder{ color: rgba(255,255,255,0.48); }

.search-btn{
    padding: 13px 26px;
    background: white;
    color: var(--navy);
    border: none;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    font-family: 'Manrope', sans-serif;
}

.search-btn:hover{
    background: #f0f4ff;
    transform: scale(1.02);
}

.hero-cta-row{
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}

.hero-cta-primary{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 26px;
    background: var(--gold);
    color: var(--navy);
    text-decoration: none;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 800;
    transition: all 0.25s;
    box-shadow: 0 4px 20px rgba(201,168,76,0.4);
}

.hero-cta-primary:hover{
    background: var(--gold-light);
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(201,168,76,0.5);
}

.hero-cta-secondary{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 22px;
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    border: 1px solid rgba(255,255,255,0.22);
    transition: all 0.25s;
}

.hero-cta-secondary:hover{
    background: rgba(255,255,255,0.22);
}


/* ============================
   STATS BAR
   ============================ */
.stats-bar{
    display: flex;
    justify-content: center;
    gap: 0;
    background: white;
    border-radius: 20px;
    padding: 0;
    box-shadow: 0 8px 32px rgba(15,23,42,0.06);
    margin-bottom: 60px;
    overflow: hidden;
}

.stat-item{
    flex: 1;
    text-align: center;
    padding: 28px 20px;
    border-right: 1px solid #f1f5f9;
    transition: background 0.2s;
}

.stat-item:last-child{ border-right: none; }
.stat-item:hover{ background: #fafbff; }

.stat-num{
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 700;
    color: var(--navy-mid);
    line-height: 1;
    margin-bottom: 6px;
}

.stat-label{
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}


/* ============================
   SECTION TITLES
   ============================ */
.section-head{
    margin-bottom: 32px;
}

.section-label{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(23,55,94,0.07);
    color: var(--navy-mid);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 6px 14px;
    border-radius: 50px;
    margin-bottom: 12px;
}

.section-title{
    font-family: 'Cormorant Garamond', serif;
    font-size: 34px;
    font-weight: 700;
    color: var(--navy-mid);
    margin-bottom: 8px;
    line-height: 1.2;
}

.section-subtitle{
    color: var(--muted);
    font-size: 15px;
    line-height: 1.7;
    max-width: 520px;
}


/* ============================
   WISATA PREVIEW GRID
   ============================ */
/* ============================
   CAROUSEL (Destinasi)
   ============================ */
.carousel-outer{
    position: relative;
    display: flex;
    align-items: center;
    gap: 0;
    margin-bottom: 20px;
}

.carousel-track-wrap{
    overflow: hidden;
    border-radius: 24px;
    flex: 1;
}

.carousel-track{
    display: flex;
    gap: 26px;
    transition: transform 0.45s cubic-bezier(0.25,0.46,0.45,0.94);
    will-change: transform;
}

.carousel-item{
    flex: 0 0 calc(33.333% - 18px);
    min-width: 0;
}

.carousel-btn{
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    border: none;
    background: white;
    color: var(--navy-mid);
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(15,23,42,0.14);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.carousel-btn:hover{
    background: var(--navy-mid);
    color: white;
    transform: translateY(-50%) scale(1.08);
    box-shadow: 0 8px 28px rgba(23,55,94,0.25);
}
.carousel-btn:disabled{
    opacity: 0.3;
    cursor: default;
    pointer-events: none;
}
.carousel-prev{ left: -22px; }
.carousel-next{ right: -22px; }

.carousel-dots{
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 36px;
    flex-wrap: wrap;
}
.carousel-dot{
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    border: none;
    cursor: pointer;
    transition: all 0.25s;
    padding: 0;
}
.carousel-dot.active{
    background: var(--navy-mid);
    width: 24px;
    border-radius: 4px;
}

@media(max-width:900px){
    .carousel-item{ flex: 0 0 calc(50% - 13px); }
    .carousel-prev{ left: -18px; }
    .carousel-next{ right: -18px; }
}
@media(max-width:600px){
    .carousel-item{ flex: 0 0 calc(100% - 0px); }
    .carousel-prev{ left: -16px; }
    .carousel-next{ right: -16px; }
}

/* ============================
   COLLAB INLINE CAR ANIMATION
   ============================ */
.collab-anim-wrap{
    position: relative;
    width: 100%;
    height: 300px;
    background: linear-gradient(180deg, #87ceeb 0%, #b8e4f7 38%, #d4f0c0 65%, #90d070 100%);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(15,23,42,0.1);
}
/* Sky */
.canim-sky{
    position:absolute; top:0; left:0; right:0; height:55%;
}
/* Sun */
.canim-sun{
    position:absolute; top:18px; right:24px;
    width:48px; height:48px;
    background: radial-gradient(circle, #ffe066 0%, #ffd000 60%, #ffb300 100%);
    border-radius:50%;
    box-shadow: 0 0 0 8px rgba(255,224,102,0.3), 0 0 0 16px rgba(255,200,0,0.12);
    animation: sunPulse 4s ease-in-out infinite;
}
/* Clouds */
.canim-cloud{
    position:absolute; background:white; border-radius:50px; opacity:0.88;
}
.canim-cloud::before,.canim-cloud::after{
    content:''; position:absolute; background:white; border-radius:50%;
}
.canim-c1{ width:80px;height:24px;top:20px; animation:cloudMove 18s linear infinite; }
.canim-c1::before{ width:36px;height:36px;top:-18px;left:12px; }
.canim-c1::after{ width:28px;height:28px;top:-12px;left:42px; }
.canim-c2{ width:56px;height:18px;top:44px;opacity:0.65; animation:cloudMove 26s linear infinite;animation-delay:-10s; }
.canim-c2::before{ width:25px;height:25px;top:-12px;left:9px; }
.canim-c2::after{ width:20px;height:20px;top:-9px;left:30px; }
.canim-c3{ width:66px;height:22px;top:14px;opacity:0.72; animation:cloudMove 22s linear infinite;animation-delay:-5s; }
.canim-c3::before{ width:29px;height:29px;top:-16px;left:11px; }
.canim-c3::after{ width:23px;height:23px;top:-10px;left:36px; }
/* Birds */
.canim-birds{
    position:absolute;top:28px;left:18%;
    animation:birdsMove 12s linear infinite;
}
.canim-bird{
    display:inline-block;width:14px;height:5px;
    border-top:2px solid #e85d04;border-radius:50% 50% 0 0;
    position:relative;margin:0 4px;
    animation:birdFlap 0.5s ease-in-out infinite alternate;
}
.canim-bird::before{
    content:'';position:absolute;right:-7px;top:0;width:7px;height:5px;
    border-top:2px solid #e85d04;border-radius:50% 50% 0 0;
    animation:birdFlap 0.5s ease-in-out infinite alternate-reverse;
}
.canim-bird:nth-child(2){ animation-delay:0.1s;margin-top:-5px; }
.canim-bird:nth-child(3){ animation-delay:0.25s;margin-top:3px; }
/* Ground */
.canim-ground{
    position:absolute;bottom:0;left:0;right:0;height:68px;
    background:linear-gradient(180deg,#7ec850 0%,#5aaa2a 100%);
}
/* Flowers */
.canim-flowers{
    position:absolute;bottom:30px;left:0;right:0;height:28px;pointer-events:none;overflow:hidden;
}
/* Road */
.canim-road{
    position:absolute;bottom:26px;left:0;right:0;height:34px;background:#444;
}
.canim-road::before{
    content:'';position:absolute;top:50%;left:0;right:0;height:3px;
    background:repeating-linear-gradient(90deg,#f9c74f 0px,#f9c74f 30px,transparent 30px,transparent 60px);
    transform:translateY(-50%);
    animation:roadMove 1.2s linear infinite;
}
/* Car in collab */
.canim-car-wrap{
    position:absolute;bottom:28px;left:-210px;
    animation:carDrive 9s linear infinite;z-index:10;
}
/* Exhaust in collab */
.canim-puff{
    position:absolute;bottom:20px;left:-8px;width:14px;height:14px;
    background:rgba(255,255,255,0.7);border-radius:50%;
    animation:puff 0.7s ease-out infinite;
}
/* Car bounce in collab */
.canim-bounce{ animation:carBounce 0.35s ease-in-out infinite alternate; }
/* Caption */
.canim-caption{
    position:absolute;top:16px;left:50%;transform:translateX(-50%);
    background:rgba(255,255,255,0.9);backdrop-filter:blur(6px);
    padding:6px 18px;border-radius:50px;
    font-size:13px;font-weight:700;color:var(--navy-mid);
    box-shadow:0 2px 12px rgba(0,0,0,0.1);
    white-space:nowrap;z-index:5;
}

/* ============================
   CAR ANIMATION KEYFRAMES
   ============================ */
@keyframes sunPulse{
    0%,100%{ box-shadow:0 0 0 8px rgba(255,224,102,0.3),0 0 0 16px rgba(255,200,0,0.12); }
    50%{ box-shadow:0 0 0 14px rgba(255,224,102,0.25),0 0 0 28px rgba(255,200,0,0.08); }
}
@keyframes cloudMove{
    0%{ left:110%; }
    100%{ left:-120px; }
}
@keyframes birdsMove{
    0%{ left:110%; }
    100%{ left:-60px; }
}
@keyframes birdFlap{
    0%{ transform:scaleY(1); }
    100%{ transform:scaleY(-0.6); }
}
@keyframes roadMove{
    0%{ background-position:0 0; }
    100%{ background-position:-120px 0; }
}
@keyframes carDrive{
    0%{ left:-210px; }
    100%{ left:110%; }
}
@keyframes puff{
    0%{ opacity:0.7; transform:scale(1); }
    100%{ opacity:0; transform:scale(2.2) translateX(-12px); }
}
@keyframes carBounce{
    0%{ transform:translateY(0); }
    100%{ transform:translateY(-3px); }
}

.wisata-card{
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(15,23,42,0.06);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}

.wisata-card:hover{
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(15,23,42,0.12);
}

.wisata-image{
    position: relative;
    overflow: hidden;
}

.wisata-image img{
    width: 100%;
    height: 230px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.wisata-card:hover .wisata-image img{
    transform: scale(1.06);
}

.category{
    position: absolute;
    top: 16px;
    left: 16px;
    background: white;
    color: var(--navy-mid);
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.card-overlay{
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(14,35,72,0.35) 0%, transparent 60%);
    opacity: 0;
    transition: opacity 0.3s;
}

.wisata-card:hover .card-overlay{ opacity: 1; }

.card-lock{
    position: absolute;
    bottom: 14px;
    right: 14px;
    background: rgba(255,255,255,0.93);
    color: var(--navy-mid);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    opacity: 0;
    transition: opacity 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.wisata-card:hover .card-lock{ opacity: 1; }

.wisata-body{
    padding: 22px 24px 24px;
}

.wisata-body h3{
    font-family: 'Cormorant Garamond', serif;
    font-size: 21px;
    color: var(--text);
    margin-bottom: 8px;
    line-height: 1.3;
}

.location{
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-meta{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.rating{
    display: flex;
    align-items: center;
    gap: 5px;
    color: #f59e0b;
    font-size: 13px;
    font-weight: 700;
}

.price{
    color: #16a34a;
    font-size: 15px;
    font-weight: 800;
}

.detail-btn{
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 13px;
    border-radius: 14px;
    background: var(--navy-mid);
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.25s;
    cursor: pointer;
    border: none;
    font-family: 'Manrope', sans-serif;
}

.detail-btn:hover{
    background: var(--navy);
    transform: translateY(-1px);
}

.see-all-wrap{
    text-align: center;
    margin-bottom: 70px;
}

.btn-see-all{
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 32px;
    border-radius: 50px;
    border: 2px solid var(--navy-mid);
    color: var(--navy-mid);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s;
    cursor: pointer;
    background: transparent;
    font-family: 'Manrope', sans-serif;
}

.btn-see-all:hover{
    background: var(--navy-mid);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(23,55,94,0.2);
}


/* ============================
   TRIP COLLABORATOR SECTION - SOFT BLUE
   ============================ */
.collab-section{
    background: linear-gradient(135deg, #e8f4fd 0%, #dbeeff 40%, #e4f2ff 70%, #f0f8ff 100%);
    border-radius: 28px;
    padding: 60px 0;
    margin-bottom: 60px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 40px rgba(59,130,246,0.1), 0 0 0 1px rgba(147,197,253,0.3);
}

.collab-section::before{
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
    border-radius: 50%;
}

.collab-section::after{
    content: '';
    position: absolute;
    bottom: -40px;
    left: -40px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(99,179,237,0.1) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.collab-inner{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    padding: 0 60px;
}

.collab-text .section-label{ margin-bottom: 14px; }

.collab-text .section-title{ font-size: 36px; margin-bottom: 14px; }

.collab-text .section-subtitle{ margin-bottom: 28px; }

.collab-features{
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 32px;
}

.collab-feat{
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px;
    border-radius: 14px;
    background: var(--bg);
    border: 1px solid #e8edf4;
    transition: all 0.2s;
}

.collab-feat:hover{
    background: #f0f4ff;
    border-color: #c8d5e8;
    transform: translateX(4px);
}

.collab-feat-icon{
    width: 42px;
    height: 42px;
    background: var(--navy-mid);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.collab-feat-text h4{
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.collab-feat-text p{
    font-size: 13px;
    color: var(--muted);
    line-height: 1.6;
}

.btn-collab{
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    background: var(--navy-mid);
    color: white;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s;
    cursor: pointer;
    border: none;
    font-family: 'Manrope', sans-serif;
}

.btn-collab:hover{
    background: var(--navy);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(23,55,94,0.3);
}

/* MOCK UI */
.collab-mockup{
    position: relative;
}

.mock-card{
    background: var(--bg);
    border-radius: 20px;
    padding: 24px;
    border: 1px solid #e8edf4;
    box-shadow: 0 8px 30px rgba(15,23,42,0.08);
}

.mock-title{
    font-weight: 700;
    font-size: 14px;
    color: var(--navy-mid);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Chat bubbles */
.mock-chats{
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 18px;
}

.chat-bubble{
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.chat-bubble.right{
    flex-direction: row-reverse;
}

.chat-avatar{
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: white;
    flex-shrink: 0;
}

.chat-avatar.a{ background: #3b82f6; }
.chat-avatar.b{ background: #8b5cf6; }
.chat-avatar.c{ background: #ec4899; }

.chat-msg{
    max-width: 70%;
    padding: 9px 13px;
    border-radius: 14px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--text);
    background: white;
    box-shadow: 0 2px 8px rgba(15,23,42,0.07);
}

.chat-bubble.right .chat-msg{
    background: var(--navy-mid);
    color: white;
}

/* Members */
.mock-members{
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.mock-members-label{
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    display: block;
}

.member-chip{
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: white;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
    box-shadow: 0 2px 6px rgba(15,23,42,0.08);
    border: 1px solid #e8edf4;
}

.member-dot{
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.member-dot.online{ background: #22c55e; }
.member-dot.away{ background: #f59e0b; }

/* Voting */
.mock-vote{
    background: white;
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 2px 8px rgba(15,23,42,0.06);
}

.vote-title{
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}

.vote-item{
    margin-bottom: 10px;
}

.vote-item:last-child{ margin-bottom: 0; }

.vote-top{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.vote-name{
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
}

.vote-pct{
    font-size: 11px;
    font-weight: 700;
    color: var(--navy-mid);
}

.vote-bar-bg{
    height: 7px;
    background: #f1f5f9;
    border-radius: 50px;
    overflow: hidden;
}

.vote-bar-fill{
    height: 100%;
    background: linear-gradient(90deg, var(--navy-mid), #3b82f6);
    border-radius: 50px;
    transition: width 1.2s ease;
}


/* ============================
   HOW IT WORKS
   ============================ */
.how-section{
    margin-bottom: 70px;
}

.steps-grid{
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    position: relative;
}

.steps-grid::before{
    content: '';
    position: absolute;
    top: 36px;
    left: calc(12.5% + 20px);
    right: calc(12.5% + 20px);
    height: 2px;
    background: repeating-linear-gradient(90deg, var(--navy-mid) 0, var(--navy-mid) 8px, transparent 8px, transparent 16px);
    opacity: 0.2;
}

.step-card{
    text-align: center;
    padding: 32px 24px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(15,23,42,0.05);
    transition: all 0.3s;
    position: relative;
}

.step-card:hover{
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(15,23,42,0.1);
}

.step-num{
    width: 52px;
    height: 52px;
    background: var(--navy-mid);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Cormorant Garamond', serif;
    font-size: 22px;
    font-weight: 700;
    margin: 0 auto 18px;
    position: relative;
    z-index: 1;
}

.step-card:nth-child(2) .step-num{ background: #3b82f6; }
.step-card:nth-child(3) .step-num{ background: #8b5cf6; }
.step-card:nth-child(4) .step-num{ background: var(--gold); color: var(--navy); }

.step-card h4{
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 8px;
}

.step-card p{
    font-size: 13px;
    color: var(--muted);
    line-height: 1.7;
}


/* ============================
   CTA BOTTOM BANNER
   ============================ */
.cta-banner{
    background: linear-gradient(135deg, var(--navy) 0%, #1c3d6e 50%, #0a1f3a 100%);
    border-radius: 28px;
    padding: 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 70px;
    box-shadow: 0 20px 60px rgba(8,18,52,0.35);
}

.cta-banner::before{
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(201,168,76,0.15) 0%, transparent 70%);
}

.cta-banner::after{
    content: '';
    position: absolute;
    bottom: -80px; left: -80px;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, transparent 70%);
}

.cta-banner h2{
    font-family: 'Cormorant Garamond', serif;
    font-size: 44px;
    font-weight: 700;
    color: white;
    margin-bottom: 14px;
    position: relative;
    z-index: 1;
}

.cta-banner p{
    color: rgba(255,255,255,0.7);
    font-size: 16px;
    margin-bottom: 36px;
    position: relative;
    z-index: 1;
}

.cta-btn-row{
    display: flex;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.cta-btn-primary{
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    background: var(--gold);
    color: var(--navy);
    text-decoration: none;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 800;
    transition: all 0.25s;
    box-shadow: 0 6px 24px rgba(201,168,76,0.4);
}

.cta-btn-primary:hover{
    background: var(--gold-light);
    transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(201,168,76,0.5);
}

.cta-btn-secondary{
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 700;
    border: 1.5px solid rgba(255,255,255,0.25);
    transition: all 0.25s;
}

.cta-btn-secondary:hover{
    background: rgba(255,255,255,0.18);
}


/* ============================
   FOOTER
   ============================ */
.trava-footer{
    background: #1a1a1a;
    margin-top: 0;
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

.trava-footer-list a:hover{ color: #f9844a; }

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

.trava-social-link:hover{ color: #f9844a; }

.trava-social-icon{
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    opacity: 0.8;
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

.trava-footer-sep{ color: #f9844a; font-size: 14px; opacity: 0.7; }


/* ============================
   LOGIN PROMPT MODAL
   ============================ */
.modal-overlay{
    position: fixed;
    inset: 0;
    background: rgba(8,18,52,0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal-overlay.open{ display: flex; }

.modal-box{
    background: white;
    border-radius: 24px;
    padding: 40px 40px 48px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 24px 80px rgba(8,18,52,0.3);
    animation: modalIn 0.3s ease;
    position: relative;
}

@keyframes modalIn{
    from{ opacity:0; transform:translateY(16px) scale(0.96); }
    to{ opacity:1; transform:translateY(0) scale(1); }
}

.modal-icon{
    width: 72px;
    height: 72px;
    background: #eff6ff;
    color: var(--navy-mid);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto 20px;
}

.modal-box h3{
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px;
    font-weight: 700;
    color: var(--navy-mid);
    margin-bottom: 10px;
}

.modal-box p{
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
    margin-bottom: 28px;
}

.modal-btns{
    display: flex;
    gap: 10px;
    justify-content: center;
}

.modal-btn-primary{
    flex: 1;
    padding: 13px;
    background: var(--navy-mid);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.2s;
}

.modal-btn-primary:hover{ background: var(--navy); }

.modal-btn-secondary{
    flex: 1;
    padding: 13px;
    background: #f1f5f9;
    color: var(--navy-mid);
    border-radius: 12px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    transition: all 0.2s;
}

.modal-btn-secondary:hover{ background: #e2e8f0; }

.modal-close{
    position: absolute;
    top: 14px;
    right: 16px;
    background: none;
    border: none;
    font-size: 18px;
    color: var(--muted);
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    transition: background 0.2s;
}

.modal-close:hover{ background: #f1f5f9; }

.modal-box-wrapper{
    position: relative;
}




@keyframes fadeUp{
    from{ opacity:0; transform:translateY(24px); }
    to{ opacity:1; transform:translateY(0); }
}

.fade-up{
    animation: fadeUp 0.6s ease forwards;
}

.delay-1{ animation-delay: 0.1s; opacity: 0; }
.delay-2{ animation-delay: 0.2s; opacity: 0; }
.delay-3{ animation-delay: 0.3s; opacity: 0; }
.delay-4{ animation-delay: 0.4s; opacity: 0; }
.delay-5{ animation-delay: 0.5s; opacity: 0; }


/* ============================
   RESPONSIVE
   ============================ */
@media(max-width:1100px){
    .hero-content{ padding:52px 220px; }
    .hero-img-left, .hero-img-right{ width:210px; }
    .hero-airplane{ right:225px; }
    .collab-inner{ gap:36px; padding:0 40px; }
}

@media(max-width:900px){
    .welcome-cirebon{ flex-direction:column; }
    .welcome-cirebon-img{ width:100%; min-height:220px; }
    .welcome-cirebon-img img{ min-height:220px; }
    .welcome-cirebon-text{ padding:30px 28px; }
    .wisata-grid{ grid-template-columns:repeat(2,1fr); }
    .steps-grid{ grid-template-columns:repeat(2,1fr); }
    .steps-grid::before{ display:none; }
    .collab-inner{ grid-template-columns:1fr; gap:40px; }
    .trava-footer-grid{ grid-template-columns:1fr 1fr; gap:36px; }
}

/* ============================
   WELCOME CIREBON - GOLD PREMIUM
   ============================ */
.welcome-cirebon{
    display: flex;
    align-items: stretch;
    background: linear-gradient(135deg, #1a1200 0%, #2a1e00 40%, #1e1600 100%);
    border-radius: 28px;
    overflow: hidden;
    margin-bottom: 40px;
    box-shadow:
        0 8px 40px rgba(0,0,0,0.25),
        0 0 0 1px rgba(201,168,76,0.18),
        inset 0 1px 0 rgba(255,220,80,0.08);
    position: relative;
}

.welcome-cirebon::before{
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 70% 50%, rgba(201,168,76,0.07) 0%, transparent 70%),
        radial-gradient(ellipse 30% 40% at 10% 20%, rgba(240,208,96,0.06) 0%, transparent 60%);
    pointer-events: none;
    z-index: 0;
}

.welcome-cirebon-img{
    position: relative;
    flex-shrink: 0;
    width: 45%;
    z-index: 1;
    padding: 20px 0 20px 20px;
}

.welcome-cirebon-img img{
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    min-height: 320px;
    filter: brightness(0.92) saturate(1.15);
    border-radius: 20px;
}

.welcome-cirebon-img::after{
    content: '';
    position: absolute;
    inset: 20px 0 20px 20px;
    background: linear-gradient(90deg, transparent 60%, rgba(26,18,0,0.7) 100%);
    pointer-events: none;
    border-radius: 20px;
}

.welcome-cirebon-badge{
    position: absolute;
    bottom: 24px;
    left: 24px;
    background: linear-gradient(135deg, #c9a84c 0%, #e8c86a 50%, #c9a84c 100%);
    color: #1a1200;
    padding: 12px 22px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    font-family: 'Manrope', sans-serif;
    box-shadow: 0 4px 20px rgba(201,168,76,0.5), 0 0 0 1px rgba(255,220,80,0.3);
    z-index: 2;
    letter-spacing: 0.5px;
}

.welcome-cirebon-badge strong{
    font-weight: 800;
}

.welcome-cirebon-text{
    display: flex;
    gap: 20px;
    align-items: stretch;
    flex: 1;
    padding: 44px 44px 44px 40px;
    position: relative;
    z-index: 1;
}

.welcome-cirebon-bar{
    width: 4px;
    min-height: 100%;
    background: linear-gradient(180deg, transparent 0%, #c9a84c 15%, #f0d060 50%, #c9a84c 85%, transparent 100%);
    border-radius: 4px;
    flex-shrink: 0;
}

.welcome-cirebon-content{
    flex: 1;
}

.welcome-cirebon-title{
    font-family: 'Cormorant Garamond', serif;
    font-size: 30px;
    font-weight: 700;
    background: linear-gradient(135deg, #c9a84c 0%, #f0d060 40%, #e8c86a 60%, #c9a84c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 20px;
    letter-spacing: 2px;
    text-shadow: none;
}

.welcome-cirebon-content p{
    color: rgba(255,245,220,0.82);
    font-size: 15px;
    line-height: 1.9;
    text-align: justify;
    margin-bottom: 14px;
}

.welcome-cirebon-content p:last-child{ margin-bottom: 0; }
.welcome-cirebon-content em{ color: #f0d060; font-style: italic; }
.welcome-cirebon-content strong{ color: #f0d060; }

@media(max-width:768px){
    .navbar{ flex-direction:column; gap:14px; padding:14px 5%; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; gap:4px; }
    .hero-img-left, .hero-img-right{ display:none; }
    .hero-content{ padding:48px 24px; }
    .hero h1{ font-size:32px; }
    .search-form{ max-width:100%; }
    .hero-airplane{ display:none; }
    .stats-bar{ flex-direction:column; }
    .stat-item{ border-right:none; border-bottom:1px solid #f1f5f9; }
    .wisata-grid{ grid-template-columns:1fr; }
    .steps-grid{ grid-template-columns:1fr; }
    .cta-banner{ padding:40px 24px; }
    .cta-banner h2{ font-size:30px; }
    .trava-footer-grid{ grid-template-columns:1fr; }
    .collab-inner{ padding:0 24px; }
}
</style>
</head>
<body>


<!-- ========================
     NAVBAR GUEST
     ======================== -->
<div class="navbar">
    <a href="landing.php" class="nav-logo">
        <img src="assets/img/logo-trava.png" alt="TRAVA Logo">
    </a>

    <div class="nav-menu">
        <a href="landing.php" class="nav-link active">Home</a>

        <!-- Wishlist - butuh login -->
        <a class="nav-link nav-link-guest" onclick="showLoginPrompt('wishlist')">Wishlist</a>

        <!-- Trip - butuh login -->
        <a class="nav-link nav-link-guest" onclick="showLoginPrompt('trip')">Trip</a>

        <div class="nav-cta-group">
            <a href="login.php" class="btn-login">Masuk</a>
        </div>
    </div>
</div>


<!-- ========================
     CONTAINER
     ======================== -->
<div class="container">

    <!-- SVG defs untuk hero clip -->
    <svg width="0" height="0" style="position:absolute">
        <defs>
            <clipPath id="waveLeft" clipPathUnits="objectBoundingBox">
                <path d="M0,0 L0.78,0 C0.78,0 0.98,0.08 0.98,0.18 C0.98,0.28 0.78,0.34 0.78,0.5 C0.78,0.66 0.98,0.72 0.98,0.82 C0.98,0.92 0.78,1 0.78,1 L0,1 Z"/>
            </clipPath>
            <clipPath id="waveRight" clipPathUnits="objectBoundingBox">
                <path d="M1,0 L0.22,0 C0.22,0 0.02,0.08 0.02,0.18 C0.02,0.28 0.22,0.34 0.22,0.5 C0.22,0.66 0.02,0.72 0.02,0.82 C0.02,0.92 0.22,1 0.22,1 L1,1 Z"/>
            </clipPath>
        </defs>
    </svg>


    <!-- ========================
         HERO SECTION
         ======================== -->
    <div class="hero">

        <!-- LEFT IMAGE -->
        <div class="hero-img-left">
            <img src="assets/img/Kasepuhan.jpg" alt="Keraton Kasepuhan">
            <svg class="hero-img-left-border" viewBox="0 0 295 380" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="goldL" x1="0" y1="0" x2="0" y2="1" gradientUnits="objectBoundingBox">
                        <stop offset="0%" stop-color="#c9a84c" stop-opacity="0.0"/>
                        <stop offset="15%" stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="50%" stop-color="#f5d870" stop-opacity="1.0"/>
                        <stop offset="85%" stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="100%" stop-color="#c9a84c" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <path d="M230,0 C230,0 289,30 289,68 C289,106 230,126 230,190 C230,254 289,274 289,312 C289,350 230,380 230,380" stroke="url(#goldL)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- RIGHT IMAGE -->
        <div class="hero-img-right">
            <img src="assets/img/goa sunyaragi.jpeg" alt="Goa Sunyaragi">
            <svg class="hero-img-right-border" viewBox="0 0 295 380" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="goldR" x1="0" y1="0" x2="0" y2="1" gradientUnits="objectBoundingBox">
                        <stop offset="0%" stop-color="#c9a84c" stop-opacity="0.0"/>
                        <stop offset="15%" stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="50%" stop-color="#f5d870" stop-opacity="1.0"/>
                        <stop offset="85%" stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="100%" stop-color="#c9a84c" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <path d="M65,0 C65,0 6,30 6,68 C6,106 65,126 65,190 C65,254 6,274 6,312 C6,350 65,380 65,380" stroke="url(#goldR)" stroke-width="2.5" fill="none" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- AIRPLANE DECO -->
        <div class="hero-airplane">
            <svg width="180" height="70" viewBox="0 0 180 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8,58 C40,40 90,18 158,14" stroke="rgba(255,255,255,0.38)" stroke-width="1.4" stroke-dasharray="5,5" fill="none" stroke-linecap="round"/>
                <g transform="translate(148,10) rotate(-8)">
                    <ellipse cx="0" cy="0" rx="16" ry="3.5" fill="rgba(240,245,255,0.88)"/>
                    <path d="M-4,-2.5 L-16,-11 L-9,-2.5 L-16,6 L-4,2.5Z" fill="rgba(225,235,255,0.82)"/>
                    <path d="M-14,-1.5 L-20,-6 L-16,-1.5 L-20,3 L-14,1.5Z" fill="rgba(210,225,250,0.78)"/>
                    <ellipse cx="16" cy="0" rx="3" ry="2" fill="rgba(210,225,255,0.85)"/>
                    <rect x="-11" y="2" width="8" height="3" rx="1.5" fill="rgba(190,210,240,0.75)"/>
                    <rect x="-11" y="-5" width="8" height="3" rx="1.5" fill="rgba(190,210,240,0.75)"/>
                </g>
            </svg>
        </div>

        <!-- CENTER CONTENT -->
        <div class="hero-content">
            <h1 class="fade-up">Explore Cirebon</h1>
            <p class="fade-up delay-1">
                Temukan destinasi wisata terbaik, mulai dari wisata sejarah, pantai, hingga tempat healing paling populer di Cirebon.
            </p>

            <!-- Search bar — kalau guest search, arahkan ke login dulu -->
            <form method="GET" action="landing.php" class="search-form fade-up delay-2" id="guestSearchForm">
                <input
                    type="text"
                    name="q"
                    id="heroSearchInput"
                    class="search-input"
                    placeholder="Cari destinasi, lokasi, atau kategori..."
                    value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Cari
                </button>
            </form>

        </div>

    </div>





    <!-- ========================
         DESTINASI PREVIEW
         ======================== -->
    <div class="section-head fade-up">
        <div class="section-label">
            <i class="fa-solid fa-map-pin"></i>
            Destinasi Pilihan
        </div>
        <div class="section-title">Tempat Wisata Terbaik Cirebon</div>
        <div class="section-subtitle">
            Dari keraton bersejarah hingga pantai yang memukau — semua ada di sini.
        </div>
    </div>

    <!-- SEARCH RESULT INFO -->
    <?php if($search !== '') : ?>
    <p style="color:var(--muted); font-size:14px; margin-bottom:20px;">
        Menampilkan hasil untuk <strong>"<?= htmlspecialchars($search); ?>"</strong>
        — <a href="landing.php" style="color:var(--navy-mid); font-weight:700;">Lihat semua</a>
    </p>
    <?php endif; ?>

    <!-- CAROUSEL WRAPPER -->
    <div class="carousel-outer">
        <button class="carousel-btn carousel-prev" id="carouselPrev" aria-label="Sebelumnya">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="carousel-track-wrap" id="carouselWrap">
            <div class="carousel-track" id="carouselTrack">
                <?php
                $count = 0;
                while($row = mysqli_fetch_assoc($query_preview)) :
                    $count++;
                ?>
                <div class="wisata-card carousel-item">
                    <a href="detail.php?id=<?= $row['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
                    <div class="wisata-image">
                        <img src="assets/img/<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['nama']); ?>">
                        <div class="category"><?= htmlspecialchars($row['kategori']); ?></div>
                        <div class="card-overlay"></div>
                    </div>
                    <div class="wisata-body">
                        <h3><?= htmlspecialchars($row['nama']); ?></h3>
                        <div class="location">
                            <i class="fa-solid fa-location-dot"></i>
                            <?= htmlspecialchars($row['lokasi']); ?>
                        </div>
                        <div class="card-meta">
                            <div class="rating">
                                <i class="fa-solid fa-star"></i>
                                <?= number_format($row['rating_avg'], 1); ?>
                                <span style="color:var(--muted); font-weight:500;">(<?= $row['rating_count']; ?>)</span>
                            </div>
                            <div class="price">Rp <?= number_format($row['harga']); ?></div>
                        </div>
                        <div class="detail-btn">
                            <i class="fa-solid fa-arrow-right"></i>
                            Lihat Detail
                        </div>
                    </div>
                    </a>
                </div>
                <?php endwhile; ?>

                <?php if($count === 0) : ?>
                <div style="min-width:100%; text-align:center; padding:80px 20px; color:#94a3b8;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size:48px; opacity:0.3; display:block; margin-bottom:16px;"></i>
                    <h3 style="font-family:'Cormorant Garamond',serif; font-size:24px; color:#64748b; margin-bottom:10px;">Destinasi tidak ditemukan</h3>
                    <p>Coba kata kunci lain atau <a href="landing.php" style="color:var(--navy-mid); font-weight:700;">lihat semua destinasi</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <button class="carousel-btn carousel-next" id="carouselNext" aria-label="Berikutnya">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <!-- Dots indicator -->
    <div class="carousel-dots" id="carouselDots"></div>

    <div style="margin-bottom:60px;"></div>


    <!-- ========================
         WELCOME CIREBON
         ======================== -->
    <div class="welcome-cirebon fade-up">
        <div class="welcome-cirebon-img">
            <img src="assets/img/Kejawan.jpg" alt="Wisata Cirebon">
        </div>
        <div class="welcome-cirebon-text">
            <div class="welcome-cirebon-bar"></div>
            <div class="welcome-cirebon-content">
                <h2 class="welcome-cirebon-title">WELCOME TO CIREBON</h2>
                <p>Cirebon adalah kota pesisir yang kaya akan warisan budaya, kuliner legendaris, dan pesona sejarah yang tak ternilai. Kota yang dikenal sebagai <em>Kota Udang</em> ini memadukan tradisi Jawa, Sunda, dan Cina dalam harmoni yang unik.</p>
                <p>Dari keraton megah dan situs bersejarah hingga pantai indah, wisata kuliner, dan pengalaman tak terlupakan &mdash; Cirebon siap menyambut Anda. Temukan ceritamu sendiri di Cirebon, <strong>Explore Cirebon!</strong></p>
            </div>
        </div>
    </div>


    <!-- ========================
         STATS BAR
         ======================== -->
    <div class="stats-bar fade-up" style="margin-bottom:60px;">
        <div class="stat-item">
            <div class="stat-num">50+</div>
            <div class="stat-label">Destinasi Wisata</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">1K+</div>
            <div class="stat-label">Pengguna Aktif</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">4.8★</div>
            <div class="stat-label">Rating Platform</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">100%</div>
            <div class="stat-label">Gratis Digunakan</div>
        </div>
    </div>

    <!-- ========================
         TRIP COLLABORATOR SECTION
         ======================== -->
    <div class="collab-section">
        <div class="collab-inner">

            <!-- TEXT -->
            <div class="collab-text">
                <div class="section-title">Rencanakan Perjalanan Bersama Temanmu</div>
                <div class="section-subtitle">
                    TRAVA punya fitur Trip Collaborator yang bikin planning liburan bareng jadi
                    lebih seru dan terorganisir. Chat, vote destinasi, dan atur jadwal bareng!
                </div>

                <div class="collab-features">
                    <div class="collab-feat">
                        <div class="collab-feat-icon">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div class="collab-feat-text">
                            <h4>Chat Grup Real-time</h4>
                            <p>Diskusikan rencana perjalanan langsung bareng teman-teman dalam satu grup trip.</p>
                        </div>
                    </div>
                    <div class="collab-feat">
                        <div class="collab-feat-icon">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <div class="collab-feat-text">
                            <h4>Tambah Anggota</h4>
                            <p>Undang teman ke trip planningmu dengan mudah. Semua bisa lihat dan kontribusi.</p>
                        </div>
                    </div>
                    <div class="collab-feat">
                        <div class="collab-feat-icon">
                            <i class="fa-solid fa-vote-yea"></i>
                        </div>
                        <div class="collab-feat-text">
                            <h4>Voting Destinasi</h4>
                            <p>Bingung mau ke mana? Vote bareng dan destinasi favorit langsung ketahuan.</p>
                        </div>
                    </div>
                </div>

                <button class="btn-collab" onclick="showLoginPrompt('collab')">
                    <i class="fa-solid fa-rocket"></i>
                    Mulai Collaborate Sekarang
                </button>
            </div>

            <!-- ANIMASI MOBIL KELUARGA -->
            <div class="collab-anim-wrap">
                <!-- Sky layer -->
                <div class="canim-sky">
                    <div class="canim-sun"></div>
                    <div class="canim-cloud canim-c1"></div>
                    <div class="canim-cloud canim-c2"></div>
                    <div class="canim-cloud canim-c3"></div>
                </div>
                <!-- Birds -->
                <div class="canim-birds">
                    <div class="canim-bird"></div>
                    <div class="canim-bird"></div>
                    <div class="canim-bird"></div>
                </div>
                <!-- Ground -->
                <div class="canim-ground"></div>
                <!-- Flowers on ground -->
                <div class="canim-flowers">
                    <svg style="position:absolute;left:4%;bottom:2px;" width="18" height="20" viewBox="0 0 18 20"><circle cx="9" cy="8" r="4" fill="#e85d04" opacity="0.9"/><circle cx="5" cy="5" r="3" fill="#f97316" opacity="0.7"/><circle cx="13" cy="5" r="3" fill="#f97316" opacity="0.7"/><circle cx="9" cy="3" r="3" fill="#fb923c" opacity="0.7"/><circle cx="9" cy="8" r="2.2" fill="#fde68a"/><rect x="8" y="11" width="2" height="9" rx="1" fill="#4ade80"/></svg>
                    <svg style="position:absolute;left:18%;bottom:2px;" width="15" height="18" viewBox="0 0 15 18"><circle cx="7.5" cy="6.5" r="3.5" fill="#c084fc" opacity="0.9"/><circle cx="4" cy="4" r="2.8" fill="#a855f7" opacity="0.7"/><circle cx="11" cy="4" r="2.8" fill="#a855f7" opacity="0.7"/><circle cx="7.5" cy="2" r="2.8" fill="#d8b4fe" opacity="0.7"/><circle cx="7.5" cy="6.5" r="2" fill="#fef3c7"/><rect x="6.5" y="10" width="2" height="8" rx="1" fill="#4ade80"/></svg>
                    <svg style="position:absolute;left:34%;bottom:2px;" width="18" height="20" viewBox="0 0 18 20"><circle cx="9" cy="8" r="4" fill="#f43f5e" opacity="0.9"/><circle cx="5" cy="5" r="3" fill="#fb7185" opacity="0.7"/><circle cx="13" cy="5" r="3" fill="#fb7185" opacity="0.7"/><circle cx="9" cy="3" r="3" fill="#fda4af" opacity="0.7"/><circle cx="9" cy="8" r="2.2" fill="#fef9c3"/><rect x="8" y="11" width="2" height="9" rx="1" fill="#4ade80"/></svg>
                    <svg style="position:absolute;left:52%;bottom:2px;" width="15" height="18" viewBox="0 0 15 18"><circle cx="7.5" cy="6.5" r="3.5" fill="#facc15" opacity="0.9"/><circle cx="4" cy="4" r="2.8" fill="#fbbf24" opacity="0.7"/><circle cx="11" cy="4" r="2.8" fill="#fbbf24" opacity="0.7"/><circle cx="7.5" cy="2" r="2.8" fill="#fde68a" opacity="0.7"/><circle cx="7.5" cy="6.5" r="2" fill="#fff"/><rect x="6.5" y="10" width="2" height="8" rx="1" fill="#4ade80"/></svg>
                    <svg style="position:absolute;left:68%;bottom:2px;" width="18" height="20" viewBox="0 0 18 20"><circle cx="9" cy="8" r="4" fill="#e85d04" opacity="0.9"/><circle cx="5" cy="5" r="3" fill="#f97316" opacity="0.7"/><circle cx="13" cy="5" r="3" fill="#f97316" opacity="0.7"/><circle cx="9" cy="3" r="3" fill="#fdba74" opacity="0.7"/><circle cx="9" cy="8" r="2.2" fill="#fef3c7"/><rect x="8" y="11" width="2" height="9" rx="1" fill="#4ade80"/></svg>
                    <svg style="position:absolute;left:84%;bottom:2px;" width="15" height="18" viewBox="0 0 15 18"><circle cx="7.5" cy="6.5" r="3.5" fill="#c084fc" opacity="0.9"/><circle cx="4" cy="4" r="2.8" fill="#a855f7" opacity="0.7"/><circle cx="11" cy="4" r="2.8" fill="#a855f7" opacity="0.7"/><circle cx="7.5" cy="2" r="2.8" fill="#d8b4fe" opacity="0.7"/><circle cx="7.5" cy="6.5" r="2" fill="#fef3c7"/><rect x="6.5" y="10" width="2" height="8" rx="1" fill="#4ade80"/></svg>
                </div>
                <!-- Road -->
                <div class="canim-road"></div>
                <!-- Trees -->
                <div style="position:absolute;bottom:52px;left:8%;z-index:3;pointer-events:none;">
                    <div style="width:0;height:0;border-left:16px solid transparent;border-right:16px solid transparent;border-bottom:28px solid #2e7d32;margin:0 auto -3px;"></div>
                    <div style="width:7px;height:18px;background:#7b4f2e;margin:0 auto;border-radius:2px;"></div>
                </div>
                <div style="position:absolute;bottom:52px;left:82%;z-index:3;pointer-events:none;">
                    <div style="width:0;height:0;border-left:14px solid transparent;border-right:14px solid transparent;border-bottom:24px solid #388e3c;margin:0 auto -3px;"></div>
                    <div style="width:6px;height:16px;background:#6d4c2a;margin:0 auto;border-radius:2px;"></div>
                </div>
                <!-- Car -->
                <div class="canim-car-wrap">
                    <div class="canim-bounce">
                        <div class="canim-puff"></div>
                        <div class="canim-puff" style="animation-delay:0.25s;left:-18px;width:10px;height:10px;"></div>
                        <div class="canim-puff" style="animation-delay:0.5s;left:-26px;width:8px;height:8px;"></div>
                        <svg width="200" height="110" viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg">
                            <rect x="15" y="55" width="170" height="42" rx="10" fill="#f97316"/>
                            <path d="M50,55 L65,20 L145,20 L162,55 Z" fill="#fb923c"/>
                            <path d="M72,55 L82,28 L130,28 L140,55 Z" fill="#bae6fd" opacity="0.85"/>
                            <rect x="52" y="32" width="22" height="20" rx="4" fill="#bae6fd" opacity="0.8"/>
                            <rect x="130" y="32" width="22" height="20" rx="4" fill="#bae6fd" opacity="0.8"/>
                            <ellipse cx="178" cy="72" rx="10" ry="7" fill="#fef3c7"/>
                            <ellipse cx="178" cy="72" rx="6" ry="4" fill="#fde68a"/>
                            <ellipse cx="22" cy="72" rx="8" ry="6" fill="#fca5a5"/>
                            <ellipse cx="22" cy="72" rx="5" ry="4" fill="#f87171"/>
                            <rect x="165" y="85" width="22" height="8" rx="4" fill="#fdba74"/>
                            <rect x="13" y="85" width="22" height="8" rx="4" fill="#fdba74"/>
                            <line x1="95" y1="56" x2="95" y2="95" stroke="#ea580c" stroke-width="2"/>
                            <rect x="110" y="72" width="14" height="4" rx="2" fill="#ea580c"/>
                            <rect x="76" y="72" width="14" height="4" rx="2" fill="#ea580c"/>
                            <!-- Wheels -->
                            <circle cx="52" cy="97" r="16" fill="#374151"/>
                            <circle cx="52" cy="97" r="10" fill="#6b7280"/>
                            <circle cx="52" cy="97" r="5" fill="#d1d5db"/>
                            <line x1="52" y1="87" x2="52" y2="107" stroke="#9ca3af" stroke-width="2"/>
                            <line x1="42" y1="97" x2="62" y2="97" stroke="#9ca3af" stroke-width="2"/>
                            <line x1="45" y1="90" x2="59" y2="104" stroke="#9ca3af" stroke-width="1.5"/>
                            <line x1="59" y1="90" x2="45" y2="104" stroke="#9ca3af" stroke-width="1.5"/>
                            <circle cx="152" cy="97" r="16" fill="#374151"/>
                            <circle cx="152" cy="97" r="10" fill="#6b7280"/>
                            <circle cx="152" cy="97" r="5" fill="#d1d5db"/>
                            <line x1="152" y1="87" x2="152" y2="107" stroke="#9ca3af" stroke-width="2"/>
                            <line x1="142" y1="97" x2="162" y2="97" stroke="#9ca3af" stroke-width="2"/>
                            <line x1="145" y1="90" x2="159" y2="104" stroke="#9ca3af" stroke-width="1.5"/>
                            <line x1="159" y1="90" x2="145" y2="104" stroke="#9ca3af" stroke-width="1.5"/>
                            <!-- Dad -->
                            <circle cx="140" cy="28" r="11" fill="#fcd9b1"/>
                            <rect x="130" y="36" width="20" height="10" rx="4" fill="#1d4ed8"/>
                            <path d="M130,22 Q140,14 150,22" fill="#4b3621"/>
                            <path d="M136,30 Q140,34 144,30" stroke="#c2410c" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            <rect x="133" y="25" width="7" height="4" rx="2" fill="#1e40af" opacity="0.8"/>
                            <rect x="141" y="25" width="7" height="4" rx="2" fill="#1e40af" opacity="0.8"/>
                            <line x1="140" y1="27" x2="141" y2="27" stroke="#1e40af" stroke-width="1.5"/>
                            <!-- Mom -->
                            <circle cx="70" cy="28" r="10" fill="#fcd9b1"/>
                            <rect x="61" y="36" width="18" height="8" rx="4" fill="#be185d"/>
                            <path d="M60,28 Q70,14 80,28 L78,32 Q70,20 62,32 Z" fill="#92400e"/>
                            <path d="M66,31 Q70,35 74,31" stroke="#c2410c" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            <circle cx="67" cy="27" r="1.5" fill="#374151"/>
                            <circle cx="73" cy="27" r="1.5" fill="#374151"/>
                            <!-- Child waving -->
                            <circle cx="100" cy="16" r="9" fill="#fcd9b1"/>
                            <rect x="93" y="23" width="14" height="8" rx="3" fill="#16a34a"/>
                            <ellipse cx="100" cy="12" rx="9" ry="4" fill="#0891b2"/>
                            <rect x="95" y="8" width="10" height="5" rx="2" fill="#0891b2"/>
                            <path d="M96,19 Q100,23 104,19" stroke="#c2410c" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                            <line x1="109" y1="20" x2="120" y2="8" stroke="#fcd9b1" stroke-width="4" stroke-linecap="round"/>
                            <circle cx="120" cy="7" r="4" fill="#fcd9b1"/>
                            <!-- Child 2 -->
                            <circle cx="58" cy="32" r="7" fill="#fcd9b1"/>
                            <path d="M51,30 Q58,23 65,30" fill="#7c3aed" opacity="0.9"/>
                            <circle cx="55" cy="32" r="1.2" fill="#374151"/>
                            <circle cx="61" cy="32" r="1.2" fill="#374151"/>
                            <!-- Plate -->
                            <rect x="75" y="90" width="50" height="14" rx="3" fill="white" stroke="#d1d5db" stroke-width="1"/>
                            <text x="100" y="101" font-size="8" font-weight="bold" fill="#1e293b" text-anchor="middle" font-family="monospace">RMF 11 UE</text>
                            <path d="M110,35 L130,35 L125,48 L105,48 Z" fill="white" opacity="0.12"/>
                        </svg>
                    </div>
                </div>

            </div>

        </div>
    </div>


    <!-- ========================
    <!-- ========================
         CTA BOTTOM BANNER
         ======================== -->
    <div class="cta-banner">
        <h2>Siap Explore Cirebon?</h2>
        <p>Bergabung dengan ribuan traveler yang sudah merencanakan perjalanan mereka di TRAVA.</p>
        <div class="cta-btn-row">
            <a href="login.php?tab=register" class="cta-btn-primary">
                <i class="fa-solid fa-user-plus"></i>
                Daftar Gratis
            </a>
            <a href="login.php" class="cta-btn-secondary">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                Sudah Punya Akun? Login
            </a>
        </div>
    </div>

</div><!-- end .container -->


<!-- ========================
     FOOTER
     ======================== -->
<footer class="trava-footer">
    <div class="trava-footer-inner">
        <div class="trava-footer-grid">

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


<!-- ========================
     LOGIN PROMPT MODAL
     ======================== -->
<div class="modal-overlay" id="loginModal" onclick="closeModalOutside(event)">
    <div class="modal-box-wrapper">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="modal-icon" id="modalIcon">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h3 id="modalTitle">Login Diperlukan</h3>
            <p id="modalMsg">Login dulu untuk mengakses fitur ini.</p>
            <div class="modal-btns">
                <a href="login.php" class="modal-btn-primary">Masuk</a>
            </div>
        </div>
    </div>
</div>


<script>
// Login prompt modal
const modalMessages = {
    wishlist: {
        icon: '<i class="fa-solid fa-heart"></i>',
        title: 'Simpan ke Wishlist',
        msg: 'Login dulu untuk menyimpan destinasi favoritmu ke wishlist. Gratis dan mudah!'
    },
    trip: {
        icon: '<i class="fa-solid fa-plane-departure"></i>',
        title: 'Fitur Trip',
        msg: 'Buat dan kelola rencana perjalananmu dengan fitur Trip. Login dulu ya!'
    },
    detail: {
        icon: '<i class="fa-solid fa-map-location-dot"></i>',
        title: 'Lihat Detail Lengkap',
        msg: 'Login untuk melihat detail wisata, review dari traveler lain, dan simpan ke wishlistmu.'
    },
    collab: {
        icon: '<i class="fa-solid fa-users"></i>',
        title: 'Trip Collaborator',
        msg: 'Rencanakan perjalanan bareng teman dengan fitur kolaborasi TRAVA. Login dulu!'
    },
    explore: {
        icon: '<i class="fa-solid fa-compass"></i>',
        title: 'Eksplorasi Penuh',
        msg: 'Login untuk melihat semua destinasi wisata Cirebon, simpan favorit, dan rencanakan tripmu.'
    }
};

function showLoginPrompt(type){
    const data = modalMessages[type] || modalMessages['explore'];
    document.getElementById('modalIcon').innerHTML = data.icon;
    document.getElementById('modalTitle').textContent = data.title;
    document.getElementById('modalMsg').textContent = data.msg;
    document.getElementById('loginModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(){
    document.getElementById('loginModal').classList.remove('open');
    document.body.style.overflow = '';
}

function closeModalOutside(e){
    if(e.target.id === 'loginModal') closeModal();
}

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
});

// Animate vote bars on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting){
            entry.target.querySelectorAll('.vote-bar-fill').forEach(bar => {
                bar.style.width = bar.style.width;
            });
        }
    });
}, { threshold: 0.3 });
document.querySelectorAll('.mock-vote').forEach(el => observer.observe(el));

// ===== CAROUSEL =====
(function(){
    const track = document.getElementById('carouselTrack');
    const wrap  = document.getElementById('carouselWrap');
    const prev  = document.getElementById('carouselPrev');
    const next  = document.getElementById('carouselNext');
    const dotsC = document.getElementById('carouselDots');
    if(!track) return;

    const items = track.querySelectorAll('.carousel-item');
    const total = items.length;
    if(total === 0) return;

    let perView = 3;
    let current = 0;

    function getPerView(){
        const w = window.innerWidth;
        if(w <= 600) return 1;
        if(w <= 900) return 2;
        return 3;
    }

    function maxIndex(){ return Math.max(0, total - perView); }

    function buildDots(){
        dotsC.innerHTML = '';
        const pages = maxIndex() + 1;
        for(let i = 0; i < pages; i++){
            const d = document.createElement('button');
            d.className = 'carousel-dot' + (i === current ? ' active' : '');
            d.addEventListener('click', () => goTo(i));
            dotsC.appendChild(d);
        }
    }

    function updateDots(){
        dotsC.querySelectorAll('.carousel-dot').forEach((d,i) => {
            d.className = 'carousel-dot' + (i === current ? ' active' : '');
        });
    }

    function goTo(idx){
        current = Math.max(0, Math.min(idx, maxIndex()));
        const itemW = items[0].getBoundingClientRect().width;
        const gap = 26;
        track.style.transform = `translateX(-${current * (itemW + gap)}px)`;
        prev.disabled = current === 0;
        next.disabled = current >= maxIndex();
        updateDots();
    }

    function init(){
        perView = getPerView();
        current = Math.min(current, maxIndex());
        // Set item widths via JS for precise control
        const wrapW = wrap.clientWidth;
        const gap = 26;
        const iw = (wrapW - gap * (perView - 1)) / perView;
        items.forEach(el => { el.style.minWidth = iw + 'px'; el.style.flexBasis = iw + 'px'; });
        goTo(current);
        buildDots();
    }

    prev.addEventListener('click', () => goTo(current - 1));
    next.addEventListener('click', () => goTo(current + 1));

    // Touch/swipe support
    let startX = 0;
    track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, {passive:true});
    track.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - startX;
        if(dx < -40) goTo(current + 1);
        else if(dx > 40) goTo(current - 1);
    }, {passive:true});

    window.addEventListener('resize', init);
    init();
})();

</script>
</body>
</html>