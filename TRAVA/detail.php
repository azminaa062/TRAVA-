<?php
session_start();
include 'config/koneksi.php';

// Auto-create tables untuk fitur baru
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `review_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`review_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `review_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `wisata_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wisata_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

@mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL DEFAULT 0,
  `trip_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'akun',
  `message` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
@mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `link_url` varchar(255) DEFAULT NULL");


// =========================
// AMBIL ID
// =========================

$id = $_GET['id'];

// RECORD KUNJUNGAN (untuk notif popularitas)
$visit_uid = isset($_SESSION['user_id']) ? "'".(int)$_SESSION['user_id']."'" : "NULL";
mysqli_query($conn,"INSERT INTO wisata_visits(wisata_id, user_id) VALUES('$id', $visit_uid)");

// CEK POPULARITAS & NOTIFIKASI WISHLIST (wisata dikunjungi banyak minggu ini)
$visit_week = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) as c FROM wisata_visits
    WHERE wisata_id='$id' AND visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
"))['c'] ?? 0;

// Threshold popularitas = 10 kunjungan dalam 7 hari
if($visit_week >= 10 && isset($_SESSION['user_id'])){
    $wisata_info = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM wisata WHERE id='$id'"));
    if($wisata_info){
        // Notif ke semua user yang wishlist wisata ini (kecuali diri sendiri), max 1x per hari
        $wl_pop = mysqli_query($conn,"SELECT user_id FROM wishlist WHERE wisata_id='$id'");
        while($wu = mysqli_fetch_assoc($wl_pop)){
            $wuid = (int)$wu['user_id'];
            $cek_today = mysqli_fetch_assoc(mysqli_query($conn,"
                SELECT id FROM notifications WHERE user_id='$wuid' AND type='wishlist'
                AND message LIKE '%populer%' AND message LIKE '%".mysqli_real_escape_string($conn,$wisata_info['nama'])."%'
                AND DATE(created_at) = CURDATE()
            "));
            if(!$cek_today){
                $nm = mysqli_real_escape_string($conn, $wisata_info['nama']);
                $msg = mysqli_real_escape_string($conn,
                    "🔥 Destinasi wishlist-mu sedang populer! $nm dikunjungi $visit_week kali minggu ini. Jangan sampai kehabisan tempat!");
                $link = "detail.php?id=$id";
                mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message, link_url)
                    VALUES('$wuid', 0, 'wishlist', '$msg', '$link')");
            }
        }
    }
}


// =========================
// DATA WISATA
// =========================

$query = mysqli_query($conn,"
    SELECT *
    FROM wisata
    WHERE id='$id'
");

$data = mysqli_fetch_assoc($query);


// =========================
// REVIEW
// =========================

$review = mysqli_query($conn,"
    SELECT review.*, users.nama
    FROM review
    JOIN users
    ON review.user_id = users.id
    WHERE wisata_id='$id'
    ORDER BY created_at DESC
");


$user_id_nav = $_SESSION['user_id'] ?? 0;
$_user_nav_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$user_id_nav'"));
$_nav_foto = !empty($_user_nav_row['foto']) ? $_user_nav_row['foto'] : '';
$_nav_initial = strtoupper(mb_substr($_user_nav_row['nama'] ?? $_SESSION['nama'] ?? 'U', 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title><?= $data['nama']; ?> - TRAVA</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

/* =========================
GOOGLE FONT
========================= */

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

/* Guest navbar styles */
.nav-link-guest{color:#64748b;cursor:pointer;position:relative;text-decoration:none;font-size:14px;font-weight:700;transition:0.3s;}
.nav-link-guest::after{content:'Login diperlukan';position:absolute;bottom:-32px;left:50%;transform:translateX(-50%);background:#17375e;color:white;font-size:11px;font-weight:600;white-space:nowrap;padding:4px 10px;border-radius:6px;opacity:0;pointer-events:none;transition:opacity 0.2s;}
.nav-link-guest:hover::after{opacity:1;}
.nav-cta-group{display:flex;align-items:center;gap:10px;margin-left:12px;}
.btn-login{text-decoration:none;color:#17375e;font-size:14px;font-weight:700;padding:9px 18px;border-radius:10px;border:2px solid #17375e;transition:all 0.2s;}
.btn-login:hover{background:#17375e;color:white;}





/* =========================
CONTAINER
========================= */

.container{
    width:88%;
    margin:auto;
    padding:40px 0;
}


/* =========================
HERO IMAGE
========================= */

.hero-image{
    position:relative;
    overflow:hidden;

    border-radius:32px;

    margin-bottom:35px;

    box-shadow:
    0 20px 50px rgba(15,23,42,0.08);
}

.hero-image img{
    width:100%;
    height:520px;
    object-fit:cover;
}

.overlay{
    position:absolute;
    inset:0;

    background:
    linear-gradient(
    to top,
    rgba(0,0,0,0.55),
    transparent
    );
}

.hero-content{
    position:absolute;
    left:40px;
    bottom:40px;

    color:white;
}

.hero-content h1{
    font-family:'Cormorant Garamond', serif;
    font-size:64px;
    margin-bottom:10px;
}

.hero-content p{
    font-size:15px;
    opacity:0.85;
}


/* =========================
GRID
========================= */

.detail-grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:30px;
}


/* =========================
CARD
========================= */

.content-box{
    background:white;

    padding:30px;
    border-radius:28px;

    margin-bottom:25px;

    box-shadow:
    0 10px 25px rgba(15,23,42,0.04);
}

.content-title{
    font-family:'Cormorant Garamond', serif;
    font-size:36px;

    color:#17375e;

    margin-bottom:18px;
}

.content-text{
    color:#64748b;
    line-height:1.9;
}


/* =========================
SIDEBAR
========================= */

.sidebar{
    position:sticky;
    top:110px;
}

.price{
    font-size:38px;
    font-weight:700;
    color:#16a34a;

    margin-bottom:20px;
}

.rating-box{
    display:flex;
    align-items:center;
    gap:10px;

    margin-bottom:25px;

    color:#f59e0b;
    font-weight:700;
}


/* =========================
BUTTON
========================= */

.action-btn{
    width:100%;

    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;

    padding:16px;

    border-radius:20px;

    text-decoration:none;

    font-size:14px;
    font-weight:700;

    margin-bottom:14px;

    transition:0.3s;
}

.wishlist-btn{
    background:#ef4444;
    color:white;
}

.trip-btn{
    background:#17375e;
    color:white;
}

.action-btn:hover{
    transform:translateY(-2px);
}


/* =========================
MAPS
========================= */

.maps-box iframe{
    width:100%;
    border:none;
    border-radius:20px;
}


/* =========================
REVIEW
========================= */

.review-card{
    background:white;

    padding:24px;
    border-radius:24px;

    margin-bottom:18px;

    box-shadow:
    0 10px 25px rgba(15,23,42,0.04);
}

.review-top{
    display:flex;
    justify-content:space-between;
    align-items:center;

    margin-bottom:12px;
}

.review-name{
    font-weight:700;
}

.review-date{
    color:#94a3b8;
    font-size:13px;
}

.review-rating{
    color:#f59e0b;
    margin-bottom:12px;
}

.review-text{
    color:#64748b;
    line-height:1.8;
}


/* =========================
FORM
========================= */

.form-box{
    background:white;

    padding:30px;
    border-radius:28px;

    margin-bottom:30px;

    box-shadow:
    0 10px 25px rgba(15,23,42,0.04);
}

.form-group{
    margin-bottom:20px;
}

.form-group label{
    display:block;
    margin-bottom:10px;

    font-weight:700;
    color:#334155;
}

.form-group textarea{
    width:100%;
    height:140px;

    border:none;
    outline:none;

    padding:18px;

    border-radius:20px;

    background:#f8fafc;

    resize:none;

    font-family:'Manrope', sans-serif;
}

.submit-btn{
    padding:16px 24px;

    border:none;
    border-radius:18px;

    background:#17375e;
    color:white;

    font-size:14px;
    font-weight:700;

    cursor:pointer;
}


/* =========================
STAR RATING
========================= */

.rating{
    display:flex;
    flex-direction:row-reverse;
    justify-content:flex-end;
    gap:5px;
}

.rating input{
    display:none;
}

.rating label{
    font-size:40px;
    color:#d1d5db;
    cursor:pointer;
    transition:0.2s;
}

.rating input:checked ~ label{
    color:#f59e0b;
}

.rating label:hover,
.rating label:hover ~ label{
    color:#f59e0b;
}


/* =========================
REVIEW LIKE & REPLY
========================= */

.review-actions{
    display:flex;
    align-items:center;
    gap:12px;
    margin-top:14px;
    padding-top:12px;
    border-top:1px solid #f1f5f9;
}

.like-btn{
    display:flex;
    align-items:center;
    gap:6px;
    background:none;
    border:1.5px solid #e2e8f0;
    border-radius:20px;
    padding:6px 14px;
    font-size:13px;
    font-weight:700;
    color:#64748b;
    cursor:pointer;
    transition:0.2s;
    font-family:'Manrope',sans-serif;
}

.like-btn:hover{
    border-color:#ef4444;
    color:#ef4444;
    background:#fff5f5;
}

.like-btn.liked{
    border-color:#ef4444;
    color:#ef4444;
    background:#fff5f5;
}

.like-static{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:#94a3b8;
}

.reply-toggle-btn{
    display:flex;
    align-items:center;
    gap:6px;
    background:none;
    border:1.5px solid #e2e8f0;
    border-radius:20px;
    padding:6px 14px;
    font-size:13px;
    font-weight:700;
    color:#64748b;
    cursor:pointer;
    transition:0.2s;
    font-family:'Manrope',sans-serif;
}

.reply-toggle-btn:hover{
    border-color:#17375e;
    color:#17375e;
    background:#f0f4ff;
}

.replies-list{
    margin-top:12px;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.reply-item{
    display:flex;
    gap:10px;
    align-items:flex-start;
}

.reply-item img{
    width:32px;
    height:32px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #e2e8f0;
    flex-shrink:0;
}

.reply-bubble{
    background:#f8fafc;
    border-radius:14px;
    padding:10px 14px;
    flex:1;
}

.reply-name{
    font-size:12px;
    font-weight:700;
    color:#17375e;
    margin-bottom:3px;
}

.reply-text{
    font-size:13px;
    color:#475569;
    line-height:1.6;
}

.reply-time{
    font-size:11px;
    color:#94a3b8;
    margin-top:4px;
}

.reply-form-box{
    margin-top:12px;
    display:flex;
    flex-direction:column;
    gap:8px;
}

.reply-input{
    width:100%;
    border:1.5px solid #e2e8f0;
    border-radius:14px;
    padding:10px 14px;
    font-family:'Manrope',sans-serif;
    font-size:13px;
    color:#1e293b;
    resize:none;
    outline:none;
    background:#f8fafc;
    transition:0.2s;
}

.reply-input:focus{
    border-color:#17375e;
    background:white;
}

.reply-submit-btn{
    align-self:flex-end;
    padding:8px 18px;
    background:#17375e;
    color:white;
    border:none;
    border-radius:12px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:0.2s;
    font-family:'Manrope',sans-serif;
}

.reply-submit-btn:hover{
    background:#234d7d;
    transform:translateY(-1px);
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

@media(max-width:900px){

    .detail-grid{
        grid-template-columns:1fr;
    }

    .sidebar{
        position:static;
    }

}

@media(max-width:768px){

    .navbar{
    width:100%;
    padding:22px 6%;

    display:flex;
    justify-content:space-between;
    align-items:center;

    background:white;

    box-shadow:
    0 4px 20px rgba(15,23,42,0.04);

    position:sticky;
    top:0;
    z-index:999;
}

    .nav-menu{
    display:flex;
    gap:28px;
}

    .hero-content h1{
        font-size:44px;
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

    <a href="<?= isset($_SESSION['login']) ? 'index.php' : 'landing.php'; ?>" class="nav-logo">
        <img src="assets/img/logo-trava.png" alt="TRAVA Logo">
    </a>

    <div class="nav-menu">
        <a href="<?= isset($_SESSION['login']) ? 'index.php' : 'landing.php'; ?>">Home</a>

        <?php if(isset($_SESSION['login'])): ?>
        <a href="wishlist.php">Wishlist</a>
        <a href="trip.php">Trip</a>

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

        <?php else: ?>
        <!-- Guest: Wishlist & Trip butuh login -->
        <a class="nav-link-guest" onclick="alert('Silakan login terlebih dahulu.')">Wishlist</a>
        <a class="nav-link-guest" onclick="alert('Silakan login terlebih dahulu.')">Trip</a>

        <div class="nav-cta-group">
            <a href="login.php" class="btn-login">Masuk</a>
        </div>
        <?php endif; ?>
    </div>

</div>




<!-- =========================
CONTAINER
========================= -->

<div class="container">


    <!-- HERO IMAGE -->

    <div class="hero-image">

        <img src="assets/img/<?= $data['gambar']; ?>">

        <div class="overlay"></div>

        <div class="hero-content">

            <h1><?= $data['nama']; ?></h1>

            <p>

                <i class="fa-solid fa-location-dot"></i>

                <?= $data['alamat_detail']; ?>

            </p>

        </div>

    </div>



    <!-- GRID -->

    <div class="detail-grid">


        <!-- LEFT -->

        <div>


            <!-- DESKRIPSI -->

            <div class="content-box">

                <div class="content-title">
                    Deskripsi
                </div>

                <div class="content-text">
                    <?= $data['deskripsi']; ?>
                </div>

            </div>



            <!-- FASILITAS -->

            <div class="content-box">

                <div class="content-title">
                    Fasilitas
                </div>

                <div class="content-text">
                    <?= $data['fasilitas']; ?>
                </div>

            </div>



            <!-- AKTIVITAS -->

            <div class="content-box">

                <div class="content-title">
                    Aktivitas
                </div>

                <div class="content-text">
                    <?= $data['aktivitas']; ?>
                </div>

            </div>



            <!-- MAPS -->

            <div class="content-box maps-box">

                <div class="content-title">
                    Lokasi Wisata
                </div>

                <?= $data['maps']; ?>

            </div>



            <!-- REVIEW -->

            <div class="content-title"
            style="margin-top:50px;">

                Review Pengunjung

            </div>



            <!-- FORM REVIEW -->

            <?php if(isset($_SESSION['login'])) : ?>

            <div class="form-box">
                <form action="proses/review_proses.php"
                method="POST">

                    <input
                    type="hidden"
                    name="wisata_id"
                    value="<?= $id; ?>">


                    <!-- RATING -->

                    <div class="form-group">

                        <label>Rating</label>

                        <div class="rating">

                            <input type="radio" name="rating" value="5" id="star5" required>
                            <label for="star5">★</label>

                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4">★</label>

                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3">★</label>

                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2">★</label>

                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1">★</label>

                        </div>

                    </div>



                    <!-- KOMENTAR -->

                    <div class="form-group">

                        <label>Komentar</label>

                        <textarea
                        name="komentar"
                        placeholder="Bagikan pengalamanmu..."
                        required></textarea>

                    </div>



                    <!-- BUTTON -->

                    <button class="submit-btn">

                        <i class="fa-solid fa-paper-plane"></i>

                        Kirim Review

                    </button>

                </form>

            </div>

            <?php endif; ?>

            <?php if(!isset($_SESSION['login'])) : ?>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px 24px; margin-bottom:20px; display:flex; align-items:center; gap:14px;">
                <i class="fa-solid fa-circle-info" style="color:#17375e; font-size:20px; flex-shrink:0;"></i>
                <div>
                    <strong style="color:#17375e; font-size:14px;">Mau kirim review?</strong>
                    <span style="color:#64748b; font-size:13px; margin-left:6px;"><a href="login.php" style="color:#17375e; font-weight:700;">Login</a> dulu ya!</span>
                </div>
            </div>
            <?php endif; ?>


            <!-- LIST REVIEW -->

            <?php if(mysqli_num_rows($review) > 0) : ?>

                <?php while($r = mysqli_fetch_assoc($review)) :
                    // Hitung like
                    $like_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM review_likes WHERE review_id='".$r['id']."'"))['c'] ?? 0;
                    // Cek apakah user ini sudah like
                    $is_liked = false;
                    if(isset($_SESSION['user_id'])){
                        $cek_like = mysqli_query($conn,"SELECT id FROM review_likes WHERE review_id='".$r['id']."' AND user_id='".$_SESSION['user_id']."'");
                        $is_liked = mysqli_num_rows($cek_like) > 0;
                    }
                    // Ambil replies
                    $replies_q = mysqli_query($conn,"SELECT review_replies.*, users.nama AS nama_user, users.foto FROM review_replies JOIN users ON users.id=review_replies.user_id WHERE review_id='".$r['id']."' ORDER BY created_at ASC");
                ?>

                <div class="review-card" id="review-<?= $r['id']; ?>">

                    <div class="review-top">

                        <div class="review-name">

                            <?= htmlspecialchars($r['nama']); ?>

                        </div>

                        <div class="review-date">

                            <?= date('d M Y',
                            strtotime($r['created_at'])); ?>

                        </div>

                    </div>

                    <div class="review-rating">

                        <?php
                        for($i=1; $i<=5; $i++){
                            if($i <= $r['rating']){ echo "⭐"; }else{ echo "☆"; }
                        }
                        ?>

                    </div>

                    <div class="review-text">

                        <?= htmlspecialchars($r['komentar']); ?>

                    </div>

                    <!-- LIKE & REPLY ACTIONS -->
                    <div class="review-actions">
                        <?php if(isset($_SESSION['login'])) : ?>
                        <!-- LOGGED IN: full like + reply -->
                        <button class="like-btn <?= $is_liked ? 'liked' : ''; ?>"
                            onclick="toggleLike(<?= $r['id']; ?>, this)"
                            data-review="<?= $r['id']; ?>">
                            <i class="fa-<?= $is_liked ? 'solid' : 'regular'; ?> fa-heart"></i>
                            <span class="like-count"><?= $like_count; ?></span>
                        </button>
                        <button class="reply-toggle-btn" onclick="toggleReplyBox(<?= $r['id']; ?>)">
                            <i class="fa-regular fa-comment"></i> Balas
                        </button>
                        <?php else : ?>
                        <!-- GUEST: like shows prompt, no reply button shown -->
                        <button class="like-btn" onclick="showGuestLoginPrompt('like')">
                            <i class="fa-regular fa-heart"></i>
                            <span class="like-count"><?= $like_count; ?></span>
                        </button>
                        <button class="reply-toggle-btn" onclick="showGuestLoginPrompt('reply')" style="opacity:0.6;">
                            <i class="fa-regular fa-comment"></i> Balas
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- REPLIES LIST -->
                    <div class="replies-list" id="replies-<?= $r['id']; ?>">
                        <?php while($rp = mysqli_fetch_assoc($replies_q)) : ?>
                        <div class="reply-item">
                            <img src="assets/img/profil/<?= htmlspecialchars($rp['foto'] ?? 'kimi.jpg'); ?>"
                                 alt="<?= htmlspecialchars($rp['nama_user']); ?>"
                                 onerror="this.src='assets/img/profil/kimi.jpg'">
                            <div class="reply-bubble">
                                <div class="reply-name"><?= htmlspecialchars($rp['nama_user']); ?></div>
                                <div class="reply-text"><?= htmlspecialchars($rp['komentar']); ?></div>
                                <div class="reply-time"><?= date('d M Y H:i', strtotime($rp['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- REPLY FORM -->
                    <?php if(isset($_SESSION['login'])) : ?>
                    <div class="reply-form-box" id="reply-box-<?= $r['id']; ?>" style="display:none;">
                        <textarea class="reply-input" id="reply-input-<?= $r['id']; ?>"
                            placeholder="Tulis balasanmu..." rows="2"></textarea>
                        <button class="reply-submit-btn"
                            onclick="submitReply(<?= $r['id']; ?>)">
                            <i class="fa-solid fa-paper-plane"></i> Kirim
                        </button>
                    </div>
                    <?php endif; ?>

                </div>

                <?php endwhile; ?>

            <?php else : ?>

            <div class="content-box">

                Belum ada review.

            </div>

            <?php endif; ?>

        </div>



        <!-- RIGHT -->

        <div class="sidebar">

            <div class="content-box">

                <div class="price">

                    Rp <?= number_format($data['harga']); ?>

                </div>

                <div class="rating-box">

                    <i class="fa-solid fa-star"></i>

                    <?= number_format($data['rating_avg'],1); ?>

                    (<?= $data['rating_count']; ?> Review)

                </div>



                <!-- BUTTON -->

                <?php if(isset($_SESSION['login'])) : ?>
                <a
                href="proses/wishlist_proses.php?id=<?= $data['id']; ?>"
                class="action-btn wishlist-btn">

                    <i class="fa-solid fa-heart"></i>

                    Tambah Wishlist

                </a>

                <a
                href="proses/trip_proses.php"
                class="action-btn trip-btn">

                    <i class="fa-solid fa-plane"></i>

                    Buat Trip

                </a>
                <?php else : ?>
                <button
                onclick="showGuestLoginPrompt('wishlist')"
                class="action-btn wishlist-btn" style="border:none; cursor:pointer; font-family:inherit;">

                    <i class="fa-solid fa-heart"></i>

                    Tambah Wishlist

                </button>

                <button
                onclick="showGuestLoginPrompt('trip')"
                class="action-btn trip-btn" style="border:none; cursor:pointer; font-family:inherit;">

                    <i class="fa-solid fa-plane"></i>

                    Buat Trip

                </button>
                <?php endif; ?>

            </div>

        </div>

    </div>

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
var NOTIF_BASE="";
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

// ==============================
// LIKE & REPLY FUNCTIONS
// ==============================
function toggleLike(reviewId, btn){
    fetch('proses/review_like_proses.php?action=toggle_like&review_id='+reviewId)
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.status==='ok'){
            var icon = btn.querySelector('i');
            var count = btn.querySelector('.like-count');
            if(d.liked){
                btn.classList.add('liked');
                icon.className='fa-solid fa-heart';
            } else {
                btn.classList.remove('liked');
                icon.className='fa-regular fa-heart';
            }
            count.textContent = d.count;
        }
    }).catch(function(){});
}

function toggleReplyBox(reviewId){
    var box = document.getElementById('reply-box-'+reviewId);
    if(!box) return;
    box.style.display = box.style.display==='none' ? 'block' : 'none';
    if(box.style.display==='block'){
        var inp = document.getElementById('reply-input-'+reviewId);
        if(inp) inp.focus();
    }
}

function submitReply(reviewId){
    var inp = document.getElementById('reply-input-'+reviewId);
    if(!inp || !inp.value.trim()) return;
    var komentar = inp.value.trim();

    var fd = new FormData();
    fd.append('action','add_reply');
    fd.append('review_id', reviewId);
    fd.append('komentar', komentar);

    fetch('proses/review_like_proses.php', {method:'POST', body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        if(d.status==='ok'){
            inp.value='';
            // Sembunyikan form
            document.getElementById('reply-box-'+reviewId).style.display='none';
            // Tambahkan reply ke DOM
            var list = document.getElementById('replies-'+reviewId);
            var div = document.createElement('div');
            div.className='reply-item';
            div.innerHTML='<img src="assets/img/profil/'+d.foto+'" onerror="this.src=\'assets/img/profil/kimi.jpg\'" alt="'+d.nama+'">' +
                '<div class="reply-bubble">' +
                '<div class="reply-name">'+_esc(d.nama)+'</div>' +
                '<div class="reply-text">'+_esc(d.komentar)+'</div>' +
                '<div class="reply-time">'+d.created_at+'</div>' +
                '</div>';
            list.appendChild(div);
        } else {
            alert(d.msg || 'Gagal mengirim balasan');
        }
    }).catch(function(){alert('Gagal mengirim balasan');});
}

function _esc(str){
    var d=document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

function showGuestLoginPrompt(type){
    const msgs = {
        wishlist: { title:'Tambah ke Wishlist', msg:'Login dulu untuk menyimpan destinasi favoritmu ke wishlist!' },
        trip: { title:'Buat Trip', msg:'Login dulu untuk membuat dan mengelola rencana perjalananmu.' },
        like: { title:'Like Review', msg:'Login dulu untuk memberikan like pada review ini.' },
        reply: { title:'Balas Review', msg:'Login dulu untuk membalas review dari traveler lain.' }
    };
    const data = msgs[type] || msgs['wishlist'];
    document.getElementById('guestModalTitle').textContent = data.title;
    document.getElementById('guestModalMsg').textContent = data.msg;
    document.getElementById('guestLoginModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
        document.getElementById('guestLoginModal').style.display = 'none';
        document.body.style.overflow = '';
    }
});

</script>

<!-- LOGIN PROMPT MODAL (untuk guest di detail page) -->
<div id="guestLoginModal" style="display:none; position:fixed; inset:0; background:rgba(8,18,52,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;" onclick="if(event.target===this){this.style.display='none'; document.body.style.overflow='';}">
    <div style="background:white; border-radius:24px; padding:40px 36px 44px; max-width:380px; width:90%; text-align:center; box-shadow:0 24px 80px rgba(8,18,52,0.3); position:relative;">
        <button onclick="document.getElementById('guestLoginModal').style.display='none'; document.body.style.overflow='';" style="position:absolute; top:14px; right:16px; background:none; border:none; font-size:18px; color:#64748b; cursor:pointer; padding:6px; border-radius:8px;">✕</button>
        <div style="width:68px; height:68px; background:#eff6ff; color:#17375e; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:26px; margin:0 auto 18px;">🔒</div>
        <h3 id="guestModalTitle" style="font-family:'Cormorant Garamond',serif; font-size:24px; font-weight:700; color:#17375e; margin-bottom:10px;">Login Diperlukan</h3>
        <p id="guestModalMsg" style="color:#64748b; font-size:14px; line-height:1.7; margin-bottom:26px;">Login dulu untuk mengakses fitur ini.</p>
        <div style="display:flex; gap:10px; justify-content:center;">
            <a href="login.php" style="flex:1; padding:12px; background:#17375e; color:white; border-radius:12px; text-decoration:none; font-size:14px; font-weight:700; display:block; text-align:center;">Masuk</a>
        </div>
    </div>
</div>

</body>
</html>