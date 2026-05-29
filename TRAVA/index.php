<?php
session_start();
include 'config/koneksi.php';

$is_logged_in = isset($_SESSION['login']);

// =========================
// SEARCH & DATA WISATA
// =========================

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

$where = '';
if($search !== ''){
    $where = "WHERE nama LIKE '%$search%' OR lokasi LIKE '%$search%' OR kategori LIKE '%$search%'";
}

$query = mysqli_query($conn,"
    SELECT *
    FROM wisata
    $where
    ORDER BY created_at DESC
");


// Fetch user photo for navbar (only if logged in)
$user_id = $_SESSION['user_id'] ?? 0;
if($is_logged_in && $user_id) {
    $_user_nav = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$user_id'"));
    $_nav_foto = !empty($_user_nav['foto']) ? $_user_nav['foto'] : '';
    $_nav_initial = strtoupper(mb_substr($_user_nav['nama'] ?? $_SESSION['nama'] ?? 'U', 0, 1));
} else {
    $_nav_foto = '';
    $_nav_initial = 'U';
}

// Wishlist check: get all wisata_id in wishlist for current user
$wishlist_ids = [];
if($is_logged_in && $user_id) {
    $wl_res = mysqli_query($conn,"SELECT wisata_id FROM wishlist WHERE user_id='$user_id'");
    while($wl_row = mysqli_fetch_assoc($wl_res)) {
        $wishlist_ids[] = (int)$wl_row['wisata_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>TRAVA - Home</title>

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

/* NAVBAR */
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


.active{ color:#17375e; }

/* CONTAINER */
.container{
    width:88%;
    margin:auto;
    padding:40px 0;
}

/* ============================================
   HERO — HOME (EXPLORE CIREBON)
   ============================================ */
.hero{
    position:relative;
    overflow:visible;
    border-radius:28px;
    background:#0e2348;
    margin-bottom:40px;
    min-height:360px;
    display:flex;
    align-items:stretch;
    box-shadow:
        0 2px 0 rgba(255,255,255,0.07) inset,
        0 -1px 0 rgba(0,0,0,0.35) inset,
        0 32px 90px rgba(8,18,52,0.55),
        0 8px 24px rgba(8,18,52,0.35);
}

/* Clip the rounded corners of the whole hero */
.hero-inner{
    position:absolute;
    inset:0;
    border-radius:28px;
    overflow:hidden;
    display:flex;
    align-items:stretch;
}

/* === LEFT PHOTO PANEL === */
.hero-img-left{
    position:absolute;
    left:0; top:0; bottom:0;
    width:295px;
    z-index:1;
}

.hero-img-left img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    display:block;
    filter: brightness(0.9) saturate(1.1);
    clip-path: url(#waveLeft);
    transition: transform 0.6s ease;
}

.hero:hover .hero-img-left img,
.hero:hover .hero-img-right img{
    transform: scale(1.04);
}

/* Gold border SVG on left edge */
.hero-img-left-border{
    position:absolute;
    inset:0;
    z-index:3;
    pointer-events:none;
}

/* === RIGHT PHOTO PANEL === */
.hero-img-right{
    position:absolute;
    right:0; top:0; bottom:0;
    width:295px;
    z-index:1;
}

.hero-img-right img{
    width:100%;
    height:100%;
    object-fit:cover;
    object-position:center;
    display:block;
    filter: brightness(0.9) saturate(1.1);
    clip-path: url(#waveRight);
    transition: transform 0.6s ease;
}

.hero-img-right-border{
    position:absolute;
    inset:0;
    z-index:3;
    pointer-events:none;
}

/* Center background gradient */
.hero::before{
    content:'';
    position:absolute;
    inset:0;
    border-radius:28px;
    z-index:0;
    background:
        radial-gradient(ellipse 65% 70% at 50% 50%, #1c3d6e 0%, #0e2348 55%, #081730 100%),
        radial-gradient(ellipse 45% 40% at 50% 110%, rgba(255,140,40,0.1) 0%, transparent 60%);
}

/* Top glass rim */
.hero::after{
    content:'';
    position:absolute;
    top:0; left:0; right:0;
    height:1px;
    z-index:10;
    border-radius:28px 28px 0 0;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(255,255,255,0.12) 20%,
        rgba(255,255,255,0.28) 50%,
        rgba(255,255,255,0.12) 80%,
        transparent 100%
    );
}

/* === AIRPLANE DECORATION === */
.hero-airplane{
    position:absolute;
    top:44px;
    right:320px;
    z-index:4;
    pointer-events:none;
}

/* === CENTER CONTENT === */
.hero-content{
    position:relative;
    z-index:3;
    text-align:center;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    flex:1;
    padding:52px 310px;
}

.hero h1{
    font-family:'Cormorant Garamond', serif;
    font-size:46px;
    font-weight:700;
    color:white;
    margin-bottom:14px;
    letter-spacing:0.5px;
    text-shadow: 0 2px 30px rgba(0,0,0,0.4);
}

.hero p{
    color:rgba(255,255,255,0.75);
    font-size:15px;
    line-height:1.9;
    max-width:480px;
    margin-bottom:32px;
    text-shadow: 0 1px 10px rgba(0,0,0,0.3);
}

/* === SEARCH BAR === */
.search-form{
    display:flex;
    gap:0;
    max-width:560px;
    width:100%;
    background:rgba(255,255,255,0.12);
    border-radius:50px;
    padding:5px 5px 5px 22px;
    backdrop-filter:blur(12px);
    -webkit-backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,0.2);
    box-shadow: 0 4px 24px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.1);
}

.search-input{
    flex:1;
    padding:12px 12px 12px 0;
    border:none;
    outline:none;
    background:transparent;
    font-size:14px;
    font-family:'Manrope', sans-serif;
    color:white;
}

.search-input::placeholder{
    color:rgba(255,255,255,0.48);
}

.search-btn{
    padding:13px 26px;
    background:white;
    color:#0e2348;
    border:none;
    border-radius:50px;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:all 0.25s ease;
    display:flex;
    align-items:center;
    gap:8px;
    white-space:nowrap;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}

.search-btn:hover{
    background:#f0f4ff;
    transform:scale(1.02);
}

.search-clear{
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-top:12px;
    padding:8px 16px;
    background:rgba(255,255,255,0.13);
    color:white;
    border-radius:999px;
    text-decoration:none;
    font-size:12px;
    font-weight:700;
    border:1px solid rgba(255,255,255,0.22);
    transition:0.25s;
}

.search-clear:hover{
    background:rgba(255,255,255,0.22);
}

/* SECTION TITLE */
.section-head{
    margin-bottom: 28px;
}

.section-label{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(23,55,94,0.07);
    color: #17375e;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    padding: 6px 14px;
    border-radius: 50px;
    margin-bottom: 10px;
}

.section-title{
    font-family:'Cormorant Garamond', serif;
    font-size:34px;
    color:#17375e;
    margin-bottom:8px;
    font-weight: 700;
    line-height: 1.2;
}

.section-subtitle{
    color:#64748b;
    font-size:14px;
    margin-bottom:28px;
    line-height: 1.7;
    max-width: 520px;
}

/* GRID */
.wisata-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:26px;
}

/* CARD */
.wisata-card{
    background:white;
    border-radius:28px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(15,23,42,0.05);
    transition:0.3s;
}

.wisata-card:hover{
    transform:translateY(-6px);
}

/* IMAGE */
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

/* BODY */
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

/* BUTTON */
.detail-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    width:100%;
    padding:15px;
    border-radius:18px;
    background:#17375e;
    color:white;
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    transition:0.3s;
}

.detail-btn:hover{ background:#102845; }

/* EMPTY STATE */
.empty-state{
    grid-column:1/-1;
    text-align:center;
    padding:80px 20px;
    color:#94a3b8;
}

.empty-state i{
    font-size:48px;
    margin-bottom:18px;
    display:block;
    opacity:0.4;
}

.empty-state h3{
    font-family:'Cormorant Garamond', serif;
    font-size:24px;
    color:#64748b;
    margin-bottom:10px;
}

/* FOOTER */
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


/* ================================================
   WELCOME TO CIREBON SECTION
   ================================================ */
.wtc-section{
    position:relative;
    margin:52px 0 48px;
    border-radius:32px;
    background:linear-gradient(135deg,#07111f 0%,#0d1f3a 40%,#0e2348 70%,#112040 100%);
    padding:56px 60px;
    overflow:hidden;
    display:flex;
    align-items:center;
    gap:40px;
    box-shadow:0 32px 80px rgba(5,12,30,0.5),0 8px 20px rgba(5,12,30,0.3),0 2px 0 rgba(255,255,255,0.06) inset;
}

.wtc-bg-glow{
    position:absolute;
    inset:0;
    pointer-events:none;
    background:
        radial-gradient(ellipse 60% 80% at 80% 50%, rgba(201,168,76,0.08) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 20% 80%, rgba(30,80,160,0.18) 0%, transparent 55%),
        radial-gradient(ellipse 30% 40% at 50% 0%, rgba(255,255,255,0.03) 0%, transparent 50%);
}

.wtc-particles{
    position:absolute;
    inset:0;
    pointer-events:none;
    overflow:hidden;
}

.wtc-inner{
    position:relative;
    z-index:2;
    flex:1;
}

.wtc-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(201,168,76,0.12);
    border:1px solid rgba(201,168,76,0.3);
    color:rgba(245,216,112,0.9);
    font-size:12px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    padding:7px 16px;
    border-radius:999px;
    margin-bottom:20px;
}

.wtc-badge-dot{
    width:7px;
    height:7px;
    background:#f5d870;
    border-radius:50%;
    animation:wtcPulse 1.8s ease-in-out infinite;
}

@keyframes wtcPulse{
    0%,100%{opacity:1;transform:scale(1);}
    50%{opacity:0.5;transform:scale(1.3);}
}

.wtc-title-wrap{
    display:flex;
    align-items:center;
    gap:16px;
    margin-bottom:22px;
}

.wtc-line-deco{
    flex:1;
    max-width:120px;
    opacity:0.7;
}

.wtc-title{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:2px;
    white-space:nowrap;
}

.wtc-welcome{
    font-family:'Cormorant Garamond',serif;
    font-size:20px;
    font-weight:500;
    color:rgba(255,255,255,0.65);
    letter-spacing:4px;
    text-transform:uppercase;
    animation:wtcSlideDown 0.9s cubic-bezier(0.22,1,0.36,1) both;
}

.wtc-cirebon{
    font-family:'Cormorant Garamond',serif;
    font-size:62px;
    font-weight:700;
    line-height:0.95;
    letter-spacing:2px;
    background:linear-gradient(135deg,#f5d870 0%,#f9c84a 25%,#fff8e0 50%,#f9c84a 75%,#c9a84c 100%);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    background-clip:text;
    filter:drop-shadow(0 2px 16px rgba(201,168,76,0.35));
    animation:wtcSlideUp 0.9s cubic-bezier(0.22,1,0.36,1) 0.15s both;
}

@keyframes wtcSlideDown{
    from{opacity:0;transform:translateY(-18px);}
    to{opacity:1;transform:translateY(0);}
}

@keyframes wtcSlideUp{
    from{opacity:0;transform:translateY(18px);}
    to{opacity:1;transform:translateY(0);}
}

.wtc-desc{
    color:rgba(255,255,255,0.65);
    font-size:14.5px;
    line-height:1.85;
    max-width:520px;
    margin-bottom:32px;
    animation:wtcFadeIn 1s 0.4s ease both;
}

@keyframes wtcFadeIn{
    from{opacity:0;transform:translateY(10px);}
    to{opacity:1;transform:translateY(0);}
}

.wtc-stats{
    display:flex;
    align-items:center;
    gap:24px;
    margin-bottom:28px;
    animation:wtcFadeIn 1s 0.55s ease both;
}

.wtc-stat-div{
    width:1px;
    height:36px;
    background:rgba(201,168,76,0.25);
}

.wtc-stat-num{
    font-family:'Cormorant Garamond',serif;
    font-size:32px;
    font-weight:700;
    color:#f5d870;
    line-height:1;
}

.wtc-stat-num::after{
    content:'+';
    font-size:20px;
}

.wtc-stat-lbl{
    font-size:11.5px;
    color:rgba(255,255,255,0.45);
    font-weight:600;
    letter-spacing:0.5px;
    margin-top:4px;
}

.wtc-tags{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    animation:wtcFadeIn 1s 0.7s ease both;
}

.wtc-tag{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:9px 16px;
    border-radius:999px;
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.12);
    color:rgba(255,255,255,0.7);
    font-size:12.5px;
    font-weight:600;
    cursor:default;
    transition:all 0.3s;
}

.wtc-tag:hover{
    background:rgba(201,168,76,0.15);
    border-color:rgba(201,168,76,0.35);
    color:rgba(245,216,112,0.95);
    transform:translateY(-2px);
}

.wtc-tag i{
    color:rgba(245,216,112,0.7);
}

.wtc-compass{
    position:relative;
    z-index:2;
    width:140px;
    height:140px;
    flex-shrink:0;
    animation:wtcSpin 18s linear infinite;
    opacity:0.65;
}

@keyframes wtcSpin{
    from{transform:rotate(0deg);}
    to{transform:rotate(360deg);}
}

/* Floating particles */
.wtc-particle{
    position:absolute;
    border-radius:50%;
    pointer-events:none;
    animation:wtcFloat linear infinite;
}

@keyframes wtcFloat{
    0%{transform:translateY(0) scale(1);opacity:var(--op0);}
    50%{transform:translateY(-40px) scale(1.1);opacity:var(--op1);}
    100%{transform:translateY(-80px) scale(0.8);opacity:0;}
}

@media(max-width:900px){
    .wtc-section{flex-direction:column;padding:40px 32px;}
    .wtc-compass{width:90px;height:90px;}
    .wtc-cirebon{font-size:46px;}
}

@media(max-width:600px){
    .wtc-section{padding:32px 22px;}
    .wtc-cirebon{font-size:38px;}
    .wtc-stats{gap:16px;}
    .wtc-stat-num{font-size:26px;}
}


/* LIKE / WISHLIST BUTTON ON CARD */
.like-btn{
    position:absolute;
    top:14px;
    right:14px;
    background:white;
    border:none;
    width:38px;
    height:38px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:18px;
    color:#cbd5e1;
    box-shadow:0 2px 10px rgba(0,0,0,0.15);
    transition:all 0.25s;
    text-decoration:none;
    z-index:2;
}
.like-btn:hover{
    transform:scale(1.1);
    color:#ef4444;
}
.like-btn.liked{
    color:#ef4444;
}
.like-btn .fa-heart{
    transition:transform 0.2s;
}
.like-btn:active .fa-heart{
    transform:scale(1.3);
}

/* GUEST LOGIN PROMPT TOAST */
#guest-toast{
    position:fixed;
    bottom:28px;
    left:50%;
    transform:translateX(-50%) translateY(80px);
    background:#1e293b;
    color:white;
    padding:14px 24px;
    border-radius:999px;
    font-size:14px;
    font-weight:600;
    z-index:9999;
    opacity:0;
    transition:all 0.35s ease;
    white-space:nowrap;
    box-shadow:0 8px 30px rgba(0,0,0,0.25);
    pointer-events:none;
}
#guest-toast.show{
    opacity:1;
    transform:translateX(-50%) translateY(0);
    pointer-events:auto;
}
#guest-toast a{
    color:#f5d870;
    font-weight:700;
    text-decoration:none;
    margin-left:6px;
}
#guest-toast a:hover{
    text-decoration:underline;
}
@media(max-width:1100px){
    .hero-content{ padding:52px 210px; }
    .hero-img-left, .hero-img-right{ width:200px; }
    .hero-airplane{ right:215px; }
}

@media(max-width:768px){
    .navbar{ flex-direction:column; gap:18px; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    .hero-img-left, .hero-img-right{ display:none; }
    .hero-content{ padding:44px 28px; }
    .hero h1{ font-size:30px; }
    .search-form{ max-width:100%; }
    .hero-airplane{ display:none; }
    .footer-grid{ grid-template-columns:1fr; }
    .wisata-grid{ grid-template-columns:1fr; }
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


<!-- NAVBAR -->
<div class="navbar">

    <a href="index.php" class="nav-logo">
        <img src="assets/img/logo-trava.png" alt="TRAVA Logo">
    </a>

    <div class="nav-menu">
        <a href="index.php" class="active">Home</a>
        <a href="<?= $is_logged_in ? 'wishlist.php' : 'login.php'; ?>">Wishlist</a>
        <a href="<?= $is_logged_in ? 'trip.php' : 'login.php'; ?>">Trip</a>

        <!-- NOTIFIKASI BELL -->
        <?php if($is_logged_in): ?>
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
        <?php else: ?>
        <a href="login.php" style="padding:10px 20px;background:#17375e;color:white;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;transition:0.2s;" onmouseover="this.style.background='#102845'" onmouseout="this.style.background='#17375e'">Masuk</a>
        <a href="register.php" style="padding:10px 20px;background:#f5f7fb;color:#17375e;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;border:2px solid #17375e;transition:0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f5f7fb'">Daftar</a>
        <?php endif; ?>
    </div>

</div>




<!-- CONTAINER -->
<div class="container">


    <!-- HERO -->
    <!-- Hidden SVG defs for clip paths -->
    <svg width="0" height="0" style="position:absolute">
        <defs>
            <!-- Left photo wave clip: smooth S-curve on right edge -->
            <clipPath id="waveLeft" clipPathUnits="objectBoundingBox">
                <path d="
                    M0,0
                    L0.78,0
                    C0.78,0 0.98,0.08 0.98,0.18
                    C0.98,0.28 0.78,0.34 0.78,0.5
                    C0.78,0.66 0.98,0.72 0.98,0.82
                    C0.98,0.92 0.78,1 0.78,1
                    L0,1
                    Z
                "/>
            </clipPath>
            <!-- Right photo wave clip: mirror of left -->
            <clipPath id="waveRight" clipPathUnits="objectBoundingBox">
                <path d="
                    M1,0
                    L0.22,0
                    C0.22,0 0.02,0.08 0.02,0.18
                    C0.02,0.28 0.22,0.34 0.22,0.5
                    C0.22,0.66 0.02,0.72 0.02,0.82
                    C0.02,0.92 0.22,1 0.22,1
                    L1,1
                    Z
                "/>
            </clipPath>
        </defs>
    </svg>

    <div class="hero">

        <!-- LEFT IMAGE: Keraton Kasepuhan -->
        <div class="hero-img-left">
            <img src="assets/img/Kasepuhan.jpg" alt="Keraton Kasepuhan">
            <!-- Gold border SVG — traces the wave curve exactly -->
            <svg class="hero-img-left-border" viewBox="0 0 295 360" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="goldL" x1="0" y1="0" x2="0" y2="1" gradientUnits="objectBoundingBox">
                        <stop offset="0%"   stop-color="#c9a84c" stop-opacity="0.0"/>
                        <stop offset="15%"  stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="35%"  stop-color="#c9a84c" stop-opacity="0.8"/>
                        <stop offset="50%"  stop-color="#f5d870" stop-opacity="1.0"/>
                        <stop offset="65%"  stop-color="#c9a84c" stop-opacity="0.8"/>
                        <stop offset="85%"  stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="100%" stop-color="#c9a84c" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <path
                    d="M230,0 C230,0 289,29 289,65 C289,101 230,122 230,180 C230,238 289,259 289,295 C289,331 230,360 230,360"
                    stroke="url(#goldL)"
                    stroke-width="2.5"
                    fill="none"
                    stroke-linecap="round"
                />
            </svg>
        </div>

        <!-- RIGHT IMAGE: Goa Sunyaragi -->
        <div class="hero-img-right">
            <img src="assets/img/goa sunyaragi.jpeg" alt="Goa Sunyaragi">
            <!-- Gold border SVG — mirror wave -->
            <svg class="hero-img-right-border" viewBox="0 0 295 360" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="goldR" x1="0" y1="0" x2="0" y2="1" gradientUnits="objectBoundingBox">
                        <stop offset="0%"   stop-color="#c9a84c" stop-opacity="0.0"/>
                        <stop offset="15%"  stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="35%"  stop-color="#c9a84c" stop-opacity="0.8"/>
                        <stop offset="50%"  stop-color="#f5d870" stop-opacity="1.0"/>
                        <stop offset="65%"  stop-color="#c9a84c" stop-opacity="0.8"/>
                        <stop offset="85%"  stop-color="#f0d060" stop-opacity="1.0"/>
                        <stop offset="100%" stop-color="#c9a84c" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                <path
                    d="M65,0 C65,0 6,29 6,65 C6,101 65,122 65,180 C65,238 6,259 6,295 C6,331 65,360 65,360"
                    stroke="url(#goldR)"
                    stroke-width="2.5"
                    fill="none"
                    stroke-linecap="round"
                />
            </svg>
        </div>

        <!-- AIRPLANE DECORATION -->
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
            <h1>Explore Cirebon</h1>
            <p>
                Temukan destinasi wisata terbaik, mulai dari wisata sejarah, pantai,<br>
                hingga tempat healing paling populer di Cirebon.
            </p>
            <form method="GET" action="index.php" class="search-form">
                <input
                    type="text"
                    name="q"
                    class="search-input"
                    placeholder="Cari destinasi, lokasi, atau kategori..."
                    value="<?= htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Cari
                </button>
            </form>
            <?php if($search !== '') : ?>
            <a href="index.php" class="search-clear">
                <i class="fa-solid fa-xmark"></i>
                Hapus pencarian
            </a>
            <?php endif; ?>
        </div>

    </div>


    <!-- TITLE -->
    <div class="section-head">
        <div class="section-label">
            <i class="fa-solid fa-map-pin"></i>
            Destinasi Pilihan
        </div>
        <div class="section-title">Tempat Wisata Terbaik Cirebon</div>
        <?php if($search !== '') : ?>
        <div class="section-subtitle">
            Hasil pencarian untuk "<strong><?= htmlspecialchars($search); ?></strong>"
            — <?= mysqli_num_rows($query); ?> destinasi ditemukan
        </div>
        <?php else : ?>
        <div class="section-subtitle">
            Dari keraton bersejarah hingga pantai yang memukau — semua ada di sini.
        </div>
        <?php endif; ?>
    </div>


    <!-- GRID -->
    <div class="wisata-grid">

        <?php
        $count = 0;
        while($row = mysqli_fetch_assoc($query)) :
            $count++;
        ?>

        <div class="wisata-card">

            <!-- IMAGE -->
            <div class="wisata-image">

                <img src="assets/img/<?= $row['gambar']; ?>">

                <div class="category">
                    <?= $row['kategori']; ?>
                </div>



            </div>

            <!-- BODY -->
            <div class="wisata-body">

                <h3><?= $row['nama']; ?></h3>

                <div class="location">
                    <i class="fa-solid fa-location-dot"></i>
                    <?= $row['lokasi']; ?>
                </div>

                <div class="rating">
                    <i class="fa-solid fa-star"></i>
                    <?= number_format($row['rating_avg'],1); ?>
                    (<?= $row['rating_count']; ?> Review)
                </div>

                <div class="price">
                    Rp <?= number_format($row['harga']); ?>
                </div>

                <a href="detail.php?id=<?= $row['id']; ?>" class="detail-btn">
                    <i class="fa-solid fa-arrow-right"></i>
                    Lihat Detail
                </a>

            </div>

        </div>

        <?php endwhile; ?>

        <?php if($count === 0) : ?>
        <div class="empty-state">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>Destinasi tidak ditemukan</h3>
            <p>Coba kata kunci lain atau <a href="index.php" style="color:#17375e;font-weight:700;">lihat semua destinasi</a></p>
        </div>
        <?php endif; ?>

    </div>

</div>


<!-- FOOTER -->

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

<!-- GUEST TOAST -->
<div id="guest-toast">
    ❤️ Login dulu untuk menyimpan wishlist!
    <a href="login.php">Masuk Sekarang</a>
</div>

<script>
function showGuestToast(){
    var t = document.getElementById('guest-toast');
    t.classList.add('show');
    setTimeout(function(){ t.classList.remove('show'); }, 3500);
}
</script>

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
<!-- WELCOME TO CIREBON JS -->
<script>
// Counter animation
(function(){
    var nums = document.querySelectorAll('.wtc-stat-num');
    var animated = false;
    function animateCounters(){
        if(animated) return;
        animated = true;
        nums.forEach(function(el){
            var target = parseInt(el.getAttribute('data-target'));
            var start = 0;
            var duration = 1800;
            var startTime = null;
            function step(ts){
                if(!startTime) startTime = ts;
                var prog = Math.min((ts - startTime) / duration, 1);
                var ease = 1 - Math.pow(1 - prog, 3);
                el.textContent = Math.floor(ease * target);
                if(prog < 1) requestAnimationFrame(step);
                else el.textContent = target;
            }
            requestAnimationFrame(step);
        });
    }
    var wtcSection = document.querySelector('.wtc-section');
    if(wtcSection){
        var obs = new IntersectionObserver(function(entries){
            if(entries[0].isIntersecting) animateCounters();
        }, {threshold:0.3});
        obs.observe(wtcSection);
    }
})();
// Floating particles
(function(){
    var container = document.getElementById('wtcParticles');
    if(!container) return;
    var colors = ['rgba(245,216,112,', 'rgba(255,255,255,', 'rgba(150,200,255,'];
    for(var i=0;i<18;i++){
        var p = document.createElement('div');
        p.className = 'wtc-particle';
        var size = Math.random()*4+2;
        var left = Math.random()*100;
        var delay = Math.random()*8;
        var dur = Math.random()*6+6;
        var op0 = Math.random()*0.4+0.1;
        var op1 = Math.random()*0.6+0.2;
        var col = colors[Math.floor(Math.random()*colors.length)];
        p.style.cssText = 'width:'+size+'px;height:'+size+'px;left:'+left+'%;bottom:0;background:'+col+op0+');--op0:'+op0+';--op1:'+op1+';animation-duration:'+dur+'s;animation-delay:'+delay+'s;';
        container.appendChild(p);
    }
})();
</script>
</body>
</html>
