<?php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

/* =========================
AMBIL DATA TRIP — 1 trip = 1 baris
Destinasi di-GROUP_CONCAT
========================= */

$user_id = $_SESSION['user_id'];

$trip = mysqli_query($conn,"
SELECT 
    trip.*,
    GROUP_CONCAT(wisata.nama ORDER BY wisata.nama SEPARATOR ', ') AS nama_destinasi,
    COUNT(trip_detail.id) AS jumlah_destinasi,
    CASE WHEN trip.creator_id='$user_id' THEN 1 ELSE 0 END AS is_creator

FROM trip

LEFT JOIN trip_detail
ON trip.id = trip_detail.trip_id

LEFT JOIN wisata
ON trip_detail.wisata_id = wisata.id

WHERE (trip.creator_id='$user_id' OR trip.id IN (SELECT trip_id FROM trip_members WHERE user_id='$user_id'))

GROUP BY trip.id

ORDER BY trip.id DESC
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trip Planner - TRAVA</title>
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>

@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap');

*{ margin:0; padding:0; box-sizing:border-box; }

body{
    background:#f5f7fb;
    font-family:'Manrope', sans-serif;
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

/* HERO */
.hero{
    padding:45px;
    border-radius:28px;
    color:white;
    margin-bottom:35px;
    position:relative;
    overflow:hidden;
    min-height:190px;
    background: #07111f;
    box-shadow:
        0 2px 0 rgba(255,255,255,0.07) inset,
        0 32px 80px rgba(5,12,30,0.55),
        0 8px 20px rgba(5,12,30,0.35);
}

/* Photo layer — Kasepuhan used as atmospheric texture */
.hero-photo{
    position:absolute;
    inset:0;
    background: url('assets/img/Kasepuhan.jpg') center 30% / cover no-repeat;
    z-index:0;
    transform: scale(1.08);
    filter: saturate(0.7) brightness(0.38) hue-rotate(200deg);
}

/* Deep left-dark overlay protecting text */
.hero::before{
    content:'';
    position:absolute;
    inset:0;
    z-index:1;
    background:
        linear-gradient(100deg,
            #07111f 0%,
            #0a1a32 20%,
            rgba(7,17,31,0.88) 40%,
            rgba(5,12,28,0.45) 62%,
            rgba(5,10,22,0.15) 100%
        ),
        radial-gradient(ellipse 55% 70% at 75% 115%,
            rgba(255,140,30,0.2) 0%,
            transparent 60%
        );
}

/* Rim light bottom */
.hero::after{
    content:'';
    position:absolute;
    bottom:0; left:0; right:0;
    height:2px;
    background: linear-gradient(90deg,
        rgba(100,180,255,0.0) 0%,
        rgba(100,180,255,0.4) 30%,
        rgba(150,210,255,0.7) 55%,
        rgba(100,180,255,0.35) 75%,
        rgba(100,180,255,0.0) 100%
    );
    z-index:6;
}

.hero-glass-rim{
    position:absolute;
    top:0; left:0; right:0;
    height:1px;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(255,255,255,0.15) 25%,
        rgba(255,255,255,0.28) 50%,
        rgba(255,255,255,0.15) 75%,
        transparent 100%
    );
    z-index:6;
    border-radius:28px 28px 0 0;
}

.hero h1{
    font-family:'Cormorant Garamond', serif;
    font-size:40px;
    margin-bottom:12px;
    position:relative;
    z-index:3;
    text-shadow: 0 2px 24px rgba(0,0,0,0.7), 0 0 50px rgba(100,180,255,0.12);
    letter-spacing:0.3px;
}

.hero p{
    color:rgba(255,255,255,0.82);
    line-height:1.8;
    position:relative;
    z-index:3;
    text-shadow: 0 1px 10px rgba(0,0,0,0.6);
    max-width:460px;
}

/* Unused old deco class */
.hero-trip-deco{ display:none; }

/* ====================================================
   HERO RENCANA — PREMIUM ANIMATED
   ==================================================== */

.hero-rencana{
    min-height:340px;
    padding:52px 52px 48px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

/* Animated grid background */
.hero-grid-bg{
    position:absolute;
    inset:0;
    z-index:1;
    background-image:
        linear-gradient(rgba(100,180,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(100,180,255,0.04) 1px, transparent 1px);
    background-size:40px 40px;
    animation:gridMove 20s linear infinite;
}

@keyframes gridMove{
    from{background-position:0 0;}
    to{background-position:40px 40px;}
}

/* Floating orbs */
.hero-orb{
    position:absolute;
    border-radius:50%;
    filter:blur(60px);
    pointer-events:none;
    z-index:1;
}

.hero-orb-1{
    width:280px;height:280px;
    background:radial-gradient(circle, rgba(37,99,235,0.22) 0%, transparent 70%);
    top:-60px;right:80px;
    animation:orbFloat1 8s ease-in-out infinite;
}

.hero-orb-2{
    width:180px;height:180px;
    background:radial-gradient(circle, rgba(245,216,112,0.14) 0%, transparent 70%);
    bottom:-30px;right:220px;
    animation:orbFloat2 11s ease-in-out infinite;
}

.hero-orb-3{
    width:200px;height:200px;
    background:radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 70%);
    top:40px;left:40%;
    animation:orbFloat3 9s ease-in-out infinite;
}

@keyframes orbFloat1{0%,100%{transform:translate(0,0);}50%{transform:translate(-20px,15px);}}
@keyframes orbFloat2{0%,100%{transform:translate(0,0);}50%{transform:translate(15px,-20px);}}
@keyframes orbFloat3{0%,100%{transform:translate(0,0);}50%{transform:translate(-10px,10px);}}

/* Plane trail */
.hero-plane-trail{
    position:absolute;
    top:0;right:0;
    width:60%;height:100%;
    z-index:2;
    pointer-events:none;
    overflow:hidden;
}

.hero-trail-svg{
    position:absolute;
    inset:0;
    width:100%;height:100%;
}

.hero-plane-icon{
    position:absolute;
    font-size:22px;
    top:16%;
    left:0%;
    color:rgba(255,255,255,0.6);
    animation:planeFly 7s ease-in-out infinite;
    filter:drop-shadow(0 0 8px rgba(150,200,255,0.4));
}

@keyframes planeFly{
    0%{left:-5%;top:75%;opacity:0;}
    10%{opacity:1;}
    85%{opacity:1;}
    100%{left:102%;top:15%;opacity:0;}
}

/* Content */
.hero-rencana-content{
    position:relative;
    z-index:4;
}

.hero-pretitle{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(37,99,235,0.2);
    border:1px solid rgba(100,160,255,0.3);
    color:rgba(150,200,255,0.9);
    font-size:11.5px;
    font-weight:700;
    letter-spacing:1.5px;
    text-transform:uppercase;
    padding:6px 14px;
    border-radius:999px;
    margin-bottom:18px;
    animation:rFadeUp 0.7s ease both;
}

.hero-pretitle-dot{
    width:6px;height:6px;
    background:#60a5fa;
    border-radius:50%;
    animation:rPulse 1.6s ease-in-out infinite;
}

@keyframes rPulse{0%,100%{box-shadow:0 0 0 0 rgba(96,165,250,0.5);}50%{box-shadow:0 0 0 5px rgba(96,165,250,0);}}

.hero-rencana-h1{
    font-family:'Cormorant Garamond',serif!important;
    font-size:48px!important;
    font-weight:700!important;
    line-height:1.05!important;
    margin-bottom:16px!important;
    display:flex!important;
    flex-direction:column!important;
    gap:4px!important;
}

.hero-h1-line1{
    color:white;
    display:block;
}

.hero-h1-line2{
    color:white;
    display:block;
}

@keyframes shimmer{
    0%{background-position:0% center;}
    100%{background-position:200% center;}
}

.hero-rencana-p{
    color:rgba(255,255,255,0.68)!important;
    font-size:14.5px!important;
    line-height:1.8!important;
    max-width:440px!important;
    margin-bottom:28px!important;
    animation:rFadeUp 0.8s 0.35s ease both;
}

/* Avatars */
.hero-avatars{
    display:flex;
    align-items:center;
    gap:0;
    margin-bottom:22px;
    animation:rFadeUp 0.8s 0.5s ease both;
}

.hero-av{
    width:38px;height:38px;
    border-radius:50%;
    border:2.5px solid #07111f;
    display:flex;align-items:center;justify-content:center;
    font-size:14px;
    margin-left:-8px;
    position:relative;
    transition:transform 0.25s;
}

.hero-av:first-child{margin-left:0;}
.hero-av:hover{transform:translateY(-4px) scale(1.1);z-index:10;}

.hero-av-1{background:linear-gradient(135deg,#1d4ed8,#60a5fa);color:white;}
.hero-av-2{background:linear-gradient(135deg,#7c3aed,#a78bfa);color:white;}
.hero-av-3{background:linear-gradient(135deg,#059669,#34d399);color:white;}
.hero-av-4{background:linear-gradient(135deg,#b45309,#fbbf24);color:white;}
.hero-av-plus{
    background:rgba(255,255,255,0.12);
    color:rgba(255,255,255,0.8);
    font-size:11px;
    font-weight:700;
    border-color:rgba(255,255,255,0.2);
}

.hero-avatars-label{
    margin-left:14px;
    font-size:12.5px;
    color:rgba(255,255,255,0.5);
    font-weight:500;
}

/* Pills */
.hero-pills{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    animation:rFadeUp 0.8s 0.65s ease both;
}

.hero-pill{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:9px 16px;
    border-radius:999px;
    background:rgba(255,255,255,0.07);
    border:1px solid rgba(255,255,255,0.13);
    color:rgba(255,255,255,0.72);
    font-size:12.5px;
    font-weight:600;
    cursor:default;
    transition:all 0.3s;
    position:relative;
    overflow:hidden;
}

.hero-pill::before{
    content:'';
    position:absolute;
    inset:0;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.06),transparent);
    transform:translateX(-100%);
    transition:transform 0.5s;
}

.hero-pill:hover::before{transform:translateX(100%);}
.hero-pill:hover{background:rgba(96,165,250,0.2);border-color:rgba(96,165,250,0.4);color:rgba(180,220,255,0.95);transform:translateY(-2px);}

.hero-pill i{color:rgba(96,165,250,0.8);}

@keyframes rFadeUp{
    from{opacity:0;transform:translateY(16px);}
    to{opacity:1;transform:translateY(0);}
}

/* Override old hero h1/p for this context */
.hero-rencana h1,
.hero-rencana p{
    text-shadow:none!important;
}

/* BUTTON */
.add-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    margin-bottom:30px;
    padding:16px 24px;
    border-radius:20px;
    background:#17375e;
    color:white;
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    transition:0.3s;
}

.add-btn:hover{ transform:translateY(-2px); }

/* GRID */
.trip-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:26px;
    align-items:stretch;
}

/* ── TRIP CARD ── */
.trip-card{
    background:white;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 6px 24px rgba(15,23,42,0.10);
    transition:transform 0.3s, box-shadow 0.3s;
    display:flex;
    flex-direction:column;
    height:100%;
}

.trip-card:hover{
    transform:translateY(-5px);
    box-shadow:0 16px 36px rgba(15,23,42,0.14);
}

/* HEADER — navy gelap, nama di atas, badge di bawah */
.trip-header{
    position:relative;
    height:180px;
    flex-shrink:0;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    padding:20px 20px 18px;
    overflow:hidden;
    border-radius:20px 20px 0 0;
    background:#17375e;
}

.trip-header-bg{ display:none; }
.trip-header-overlay{ display:none; }

.trip-header-content{
    width:100%;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    height:100%;
}

.trip-header-content h3{
    font-family:'Cormorant Garamond',serif;
    font-size:24px;
    font-weight:700;
    color:#fff;
    margin:0;
    line-height:1.2;
}

.trip-header-meta{
    color:rgba(255,255,255,0.65);
    font-size:13px;
    font-weight:500;
    display:none; /* tanggal dipindah ke body */
}

/* STATUS badge — bawah kiri */
.trip-badges{
    display:flex;
    justify-content:flex-start;
    align-items:center;
    gap:6px;
    margin-top:auto;
}

.trip-status{
    display:inline-block;
    padding:6px 16px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    color:white;
    letter-spacing:0.3px;
}

.planning{ background:#4a7fa5; }
.ongoing { background:#0ea5e9; }
.selesai { background:#22c55e; }
.batal   { background:#ef4444; }

.trip-member-pill{
    display:inline-flex;
    align-items:center;
    gap:4px;
    background:rgba(255,255,255,0.18);
    border:1px solid rgba(255,255,255,0.30);
    color:white;
    font-size:11px;
    font-weight:700;
    padding:5px 11px;
    border-radius:999px;
}

/* BODY */
.trip-body{
    padding:20px 20px 20px;
    display:flex;
    flex-direction:column;
    flex:1;
    gap:10px;
}

.trip-info{
    color:#64748b;
    font-size:13px;
    display:flex;
    align-items:center;
    gap:8px;
    flex-shrink:0;
}

.trip-info i{
    color:#17375e;
    width:14px;
    font-size:13px;
    flex-shrink:0;
}

.dest-tags{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    min-height:58px;
    align-content:flex-start;
    flex-shrink:0;
}

.dest-tag{
    padding:5px 13px;
    background:#f1f5f9;
    color:#17375e;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    border:1px solid #e2e8f0;
}

/* BUTTON AREA */
.trip-btn-area{
    margin-top:auto;
    padding-top:10px;
    display:flex;
    flex-direction:column;
    gap:8px;
    flex-shrink:0;
}

/* DETAIL BUTTON */
.detail-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    text-decoration:none;
    padding:14px 20px;
    border-radius:14px;
    background:#17375e;
    color:white;
    font-size:14px;
    font-weight:700;
    transition:0.25s;
    width:100%;
    box-sizing:border-box;
}

.detail-btn:hover{
    background:#102845;
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(23,55,94,0.25);
}

/* COLLABORATOR BUTTON */
.collab-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    text-decoration:none;
    padding:13px 20px;
    border-radius:14px;
    background:linear-gradient(135deg,#1e4a7a,#2563a8);
    color:white;
    font-size:13px;
    font-weight:700;
    transition:0.25s;
    width:100%;
    box-sizing:border-box;
    border:none;
    cursor:pointer;
    box-shadow:0 3px 10px rgba(23,55,94,0.15);
}
.collab-btn:hover{
    background:linear-gradient(135deg,#234d7d,#2d72c8);
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(23,55,94,0.22);
}
.collab-btn i{ font-size:14px; }

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
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}.profile-avatar-initial{width:40px;height:40px;border-radius:50%;background:#17375e;color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;border:2px solid #c8a84b;cursor:pointer;text-transform:uppercase;flex-shrink:0;}
.profile-dropdown{position:absolute;top:50px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}

/* RESPONSIVE */
@media(max-width:900px){
    .trip-grid{ grid-template-columns:repeat(2,1fr); }
}
@media(max-width:768px){
    .navbar{ flex-direction:column; gap:18px; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    .footer-grid{ grid-template-columns:1fr; }
}
@media(max-width:480px){
    .trip-grid{ grid-template-columns:1fr; }
}

</style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">

    <a href="index.php" class="nav-logo">
        <img src="assets/img/logo-trava.png" alt="TRAVA Logo">
    </a>

    <div class="nav-menu">
        <a href="index.php" >Home</a>
        <a href="wishlist.php" >Wishlist</a>
        <a href="trip.php" class="active">Trip</a>

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

<script>
const NOTIF_ICONS = {
    invite: '<i class="fa-solid fa-user-plus"></i>',
    chat_personal: '<i class="fa-solid fa-message"></i>',
    chat_group: '<i class="fa-solid fa-comments"></i>'
};

function toggleNotif(e){
    e.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    const pd = document.getElementById('profileDropdown');
    if(pd) pd.classList.remove('open');
    dd.classList.toggle('open');
    if(dd.classList.contains('open')) loadNotifs();
}
function toggleProfile(e){
    e.stopPropagation();
    const pd = document.getElementById('profileDropdown');
    const nd = document.getElementById('notifDropdown');
    if(nd) nd.classList.remove('open');
    pd.classList.toggle('open');
}
document.addEventListener('click', ()=>{
    document.getElementById('notifDropdown').classList.remove('open');
    const pd = document.getElementById('profileDropdown');
    if(pd) pd.classList.remove('open');
});
document.getElementById('notifDropdown').addEventListener('click', e=>e.stopPropagation());
document.addEventListener('DOMContentLoaded', ()=>{
    const pd = document.getElementById('profileDropdown');
    if(pd) pd.addEventListener('click', e=>e.stopPropagation());
});

function loadNotifs(){
    fetch('proses/notif_proses.php?action=list')
    .then(r=>r.json()).then(list=>{
        const el = document.getElementById('notifList');
        if(!list.length){
            el.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-bell-slash" style="font-size:24px;display:block;margin-bottom:8px;"></i>Belum ada notifikasi</div>';
            return;
        }
        el.innerHTML = list.map(n=>{
            const icon = NOTIF_ICONS[n.type] || '<i class="fa-solid fa-bell"></i>';
            const link = n.link_url ? n.link_url : (n.trip_id ? `trip_group.php?id=${n.trip_id}` : 'trip.php');
            const parts = n.message.split('.');
            const title = parts[0] || n.message;
            return `<div class="notif-item ${n.is_read==0?'unread':''}" onclick="goNotif(${n.id},'${link}')">
                <div class="notif-icon-wrap ${n.type||'invite'}">${icon}</div>
                <div class="notif-body">
                    <div class="notif-title">${title}</div>
                    <div class="notif-msg">${n.message}</div>
                    <div class="notif-time">${n.time}</div>
                </div>
                ${n.is_read==0?'<div class="notif-dot"></div>':''}
            </div>`;
        }).join('');
    }).catch(()=>{});
}

function goNotif(id, link){
    fetch('proses/notif_proses.php?action=read_one&id='+id).then(()=>{
        window.location.href = link;
    });
}

function readAll(){
    fetch('proses/notif_proses.php?action=read_all').then(()=>{
        document.getElementById('notifBadge').style.display='none';
        loadNotifs();
    });
}

function checkNotifCount(){
    fetch('proses/notif_proses.php?action=count')
    .then(r=>r.json()).then(d=>{
        const b = document.getElementById('notifBadge');
        if(d.count > 0){
            b.textContent = d.count > 99 ? '99+' : d.count;
            b.style.display='flex';
        } else {
            b.style.display='none';
        }
    }).catch(()=>{});
}
checkNotifCount();
setInterval(checkNotifCount, 10000);
</script>

</div>


<!-- CONTAINER -->
<div class="container">

<!-- HERO PREMIUM -->
<div class="hero hero-rencana">
    <div class="hero-photo"></div>
    <div class="hero-glass-rim"></div>
    <!-- Animated grid lines -->
    <div class="hero-grid-bg"></div>
    <!-- Floating orbs -->
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
    <!-- Animated plane path -->
    <div class="hero-plane-trail">
        <svg class="hero-trail-svg" viewBox="0 0 600 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path class="trail-path" d="M20,90 C120,60 200,40 300,50 C400,60 480,30 580,20" stroke="rgba(255,255,255,0.18)" stroke-width="1.5" stroke-dasharray="6 4" fill="none"/>
        </svg>
        <div class="hero-plane-icon">✈</div>
    </div>
    <!-- Content -->
    <div class="hero-rencana-content">
        <h1 class="hero-rencana-h1">
            <span class="hero-h1-line1">Rencanakan Perjalanan</span>
            <span class="hero-h1-line2">Bersama Temanmu</span>
        </h1>
        <p class="hero-rencana-p">
            Buat trip bersama, susun destinasi favorit, dan track<br>
            semua petualanganmu bersama TRAVA.
        </p>
    </div>
</div>

<!-- BUTTON -->
<a href="proses/trip_proses.php" class="add-btn">
    <i class="fa-solid fa-plus"></i>
    Buat Trip
</a>


<!-- GRID -->
<div class="trip-grid">

<?php while($t = mysqli_fetch_assoc($trip)) : ?>

<?php
    $dest_list = $t['nama_destinasi'] ? explode(', ', $t['nama_destinasi']) : [];

    // Ambil gambar destinasi pertama untuk background header
    $first_dest_img = null;
    if(!empty($dest_list)) {
        $dest_name_esc = mysqli_real_escape_string($conn, trim($dest_list[0]));
        $img_q = mysqli_query($conn,"SELECT gambar FROM wisata WHERE nama='$dest_name_esc' LIMIT 1");
        if($img_q && $img_row = mysqli_fetch_assoc($img_q)) {
            $first_dest_img = $img_row['gambar'];
        }
    }

    $bg_style = $first_dest_img
        ? "background-image:url('assets/img/" . htmlspecialchars($first_dest_img) . "')"
        : "background:linear-gradient(135deg,#0e2348,#17375e)";
?>

<div class="trip-card">

    <!-- HEADER dengan background foto gelap -->
    <div class="trip-header">
        <div class="trip-header-bg" style="<?= $bg_style ?>"></div>
        <div class="trip-header-overlay"></div>
        <div class="trip-header-content">
            <h3><?= htmlspecialchars($t['nama_trip']); ?></h3>
            <!-- Badge status & anggota — bawah kiri -->
            <div class="trip-badges">
                <span class="trip-status <?= $t['status']; ?>"><?= strtoupper($t['status']); ?></span>
                <?php if(!$t['is_creator']): ?>
                <span class="trip-member-pill"><i class="fa-solid fa-user-group"></i> Anggota</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- BODY -->
    <div class="trip-body">

        <div class="trip-info">
            <i class="fa-solid fa-calendar"></i>
            <?= htmlspecialchars($t['tanggal']); ?>
        </div>

        <div class="trip-info">
            <i class="fa-solid fa-map-location-dot"></i>
            <?= (int)$t['jumlah_destinasi']; ?> Destinasi
        </div>

        <?php if(!empty($dest_list)) : ?>
        <div class="dest-tags">
            <?php foreach($dest_list as $d) : ?>
            <span class="dest-tag"><?= htmlspecialchars(trim($d)); ?></span>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="dest-tags"></div>
        <?php endif; ?>

        <div class="trip-btn-area">
        <a href="trip_detail.php?id=<?= $t['id']; ?>" class="detail-btn">
            <i class="fa-solid fa-arrow-right"></i>
            Detail Trip
        </a>
        <a href="trip_group.php?id=<?= $t['id']; ?>" class="collab-btn">
            <i class="fa-solid fa-users"></i>
            Trip Group &amp; Collaborator
        </a>
        </div>

    </div>

</div>

<?php endwhile; ?>

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



</body>
</html>