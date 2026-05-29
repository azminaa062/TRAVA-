<?php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* NAV PROFIL AVATAR */
$_user_nav_profil = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$user_id'"));
$_nav_foto = !empty($_user_nav_profil['foto']) ? $_user_nav_profil['foto'] : '';
$_nav_initial = strtoupper(mb_substr($_user_nav_profil['nama'] ?? $_SESSION['nama'] ?? 'U', 0, 1));

/* =========================
UPDATE PROFIL
========================= */

if(isset($_POST['update_profil'])){

    $nama  = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);

    // Ambil data lama untuk membandingkan apa yang berubah
    $data_lama = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama, email, foto FROM users WHERE id='$user_id'"));
    $nama_lama  = $data_lama['nama']  ?? '';
    $email_lama = $data_lama['email'] ?? '';

    $foto = $_FILES['foto']['name'];
    $foto_berubah = false;

    if($foto != ''){

        $tmp = $_FILES['foto']['tmp_name'];

        $namaFotoBaru = time().'_'.$foto;

        move_uploaded_file(
            $tmp,
            'assets/img/profil/'.$namaFotoBaru
        );

        mysqli_query($conn,"
            UPDATE users
            SET
            nama='$nama',
            email='$email',
            foto='$namaFotoBaru'
            WHERE id='$user_id'
        ");
        $foto_berubah = true;

    }else{

        mysqli_query($conn,"
            UPDATE users
            SET
            nama='$nama',
            email='$email'
            WHERE id='$user_id'
        ");

    }

    // Pastikan tabel notifications ada + kolom lengkap
    mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `notifications` (
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
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `type` varchar(50) DEFAULT 'akun'");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `message` varchar(255) DEFAULT ''");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `from_user_id` int(11) NOT NULL DEFAULT 0");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `trip_id` int(11) DEFAULT NULL");
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `link_url` varchar(255) DEFAULT NULL");

    // Deteksi field apa yang berubah secara spesifik
    $nama_berubah  = ($nama !== $nama_lama);
    $email_berubah = ($email !== $email_lama);

    $perubahan = [];
    if($nama_berubah)   $perubahan[] = 'nama';
    if($email_berubah)  $perubahan[] = 'email';
    if($foto_berubah)   $perubahan[] = 'foto profil';

    if(!empty($perubahan)){
        // Buat pesan notifikasi sesuai yang berubah
        if(count($perubahan) === 1){
            $field = $perubahan[0];
            if($field === 'nama')         $notif_msg_raw = "Nama berhasil diperbarui menjadi \"{$nama}\".";
            elseif($field === 'email')    $notif_msg_raw = "Email berhasil diperbarui menjadi \"{$email}\".";
            elseif($field === 'foto profil') $notif_msg_raw = "Foto profil berhasil diperbarui.";
            else                           $notif_msg_raw = "Profil berhasil diperbarui.";
        } elseif(count($perubahan) === 2){
            $notif_msg_raw = ucfirst($perubahan[0])." dan ".$perubahan[1]." berhasil diperbarui.";
        } else {
            $notif_msg_raw = "Nama, email, dan foto profil berhasil diperbarui.";
        }
        $notif_msg = mysqli_real_escape_string($conn, $notif_msg_raw);
        // Cek kolom link_url ada atau tidak sebelum INSERT
        $cek_lnk_p = mysqli_query($conn,"SHOW COLUMNS FROM `notifications` LIKE 'link_url'");
        if(mysqli_num_rows($cek_lnk_p) > 0){
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,type,message,link_url) VALUES('$user_id','$user_id','akun','$notif_msg','profil.php')");
        } else {
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,type,message) VALUES('$user_id','$user_id','akun','$notif_msg')");
        }
    }

    header("Location: profil.php");
    exit;
}


/* =========================
DATA USER
========================= */

$user = mysqli_query($conn,"
    SELECT *
    FROM users
    WHERE id='$user_id'
");

$data = mysqli_fetch_assoc($user);


/* =========================
TOTAL REVIEW
========================= */

$total_review = mysqli_num_rows(mysqli_query($conn,"
    SELECT *
    FROM review
    WHERE user_id='$user_id'
"));


/* =========================
TOTAL WISHLIST
========================= */

$total_wishlist = mysqli_num_rows(mysqli_query($conn,"
    SELECT *
    FROM wishlist
    WHERE user_id='$user_id'
"));


/* =========================
TOTAL TRIP
========================= */

$total_trip = mysqli_num_rows(mysqli_query($conn,"
    SELECT *
    FROM trip
    WHERE creator_id='$user_id'
"));


/* =========================
TOTAL TRIP SELESAI
(termasuk trip sebagai member)
========================= */

$total_trip_selesai = mysqli_num_rows(mysqli_query($conn,"
    SELECT id
    FROM trip
    WHERE status='selesai'
    AND (
        creator_id='$user_id'
        OR id IN (SELECT trip_id FROM trip_members WHERE user_id='$user_id')
    )
"));


/* =========================
TOTAL WACANA
(termasuk trip sebagai member)
========================= */

$total_wacana = mysqli_num_rows(mysqli_query($conn,"
    SELECT id
    FROM trip
    WHERE status='batal'
    AND (
        creator_id='$user_id'
        OR id IN (SELECT trip_id FROM trip_members WHERE user_id='$user_id')
    )
"));


/* =========================
LEVEL TRAVELER
========================= */

if($total_trip_selesai >= 30){

    $level = "Cirebon Master";

}
elseif($total_trip_selesai >= 15){

    $level = "Expert Traveler";

}
elseif($total_trip_selesai >= 7){

    $level = "Traveler";

}
elseif($total_trip_selesai >= 3){

    $level = "Explorer";

}
else{

    $level = "Newbie";

}


/* =========================
REVIEW TERBARU
========================= */

$review = mysqli_query($conn,"
    SELECT review.*, wisata.nama
    FROM review
    JOIN wisata
    ON review.wisata_id = wisata.id
    WHERE review.user_id='$user_id'
    ORDER BY review.id DESC
    LIMIT 3
");


/* =========================
WISHLIST TERBARU
========================= */

$wishlist = mysqli_query($conn,"
    SELECT wishlist.*, wisata.nama, wisata.gambar
    FROM wishlist
    JOIN wisata
    ON wishlist.wisata_id = wisata.id
    WHERE wishlist.user_id='$user_id'
    ORDER BY wishlist.id DESC
    LIMIT 3
");


/* =========================
TRIP TERBARU
========================= */

$trip = mysqli_query($conn,"
    SELECT
        trip.*,
        GROUP_CONCAT(wisata.nama ORDER BY wisata.nama SEPARATOR ', ') AS nama_destinasi,
        MIN(wisata.gambar) AS gambar_destinasi
    FROM trip
    LEFT JOIN trip_detail ON trip.id = trip_detail.trip_id
    LEFT JOIN wisata ON trip_detail.wisata_id = wisata.id
    WHERE (
        trip.creator_id='$user_id'
        OR trip.id IN (SELECT trip_id FROM trip_members WHERE user_id='$user_id')
    )
    GROUP BY trip.id
    ORDER BY trip.id DESC
    LIMIT 3
");

/* =========================
NEXT LEVEL INFO
========================= */

$levels = [
    ['name'=>'Newbie',         'min'=>0,  'next_min'=>3],
    ['name'=>'Explorer',       'min'=>3,  'next_min'=>7],
    ['name'=>'Traveler',       'min'=>7,  'next_min'=>15],
    ['name'=>'Expert Traveler','min'=>15, 'next_min'=>30],
    ['name'=>'Cirebon Master', 'min'=>30, 'next_min'=>null],
];

$current_min  = 0;
$next_level   = null;
$next_min     = null;

foreach($levels as $lv){
    if($lv['name'] === $level){
        $current_min = $lv['min'];
        $next_min    = $lv['next_min'];
        break;
    }
}

foreach($levels as $lv){
    if($lv['min'] === $next_min){
        $next_level = $lv['name'];
        break;
    }
}

if($next_min !== null){
    $progress_range = $next_min - $current_min;
    $progress_done  = $total_trip_selesai - $current_min;
    $progress_pct   = min(100, round($progress_done / $progress_range * 100));
}else{
    $progress_pct = 100;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Profil - TRAVA</title>

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
    font-family:'Manrope',sans-serif;
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


.container{
    width:88%;
    margin:auto;
    padding:40px 0;
}


/* HERO */

.profile-hero{
    position:relative;
    overflow:hidden;
    border-radius:32px;
    padding:40px;
    background: #07111f;
    margin-bottom:30px;
    min-height:160px;
    box-shadow:
        0 2px 0 rgba(255,255,255,0.06) inset,
        0 32px 80px rgba(5,12,30,0.5),
        0 8px 20px rgba(5,12,30,0.3);
}

/* Photo background layer */
.profile-hero-photo{
    position:absolute;
    inset:0;
    background: url('assets/img/Kejawan.jpg') center 55% / cover no-repeat;
    z-index:0;
    transform: scale(1.06);
    filter: saturate(1.1) brightness(0.45);
}

/* Gradient overlay — dark left, photo reveals right */
.profile-hero::before{
    content:'';
    position:absolute;
    inset:0;
    z-index:1;
    background:
        linear-gradient(100deg,
            #07111f 0%,
            #0c1c35 18%,
            rgba(7,17,31,0.9) 36%,
            rgba(6,14,28,0.55) 58%,
            rgba(5,10,22,0.18) 100%
        ),
        radial-gradient(ellipse 55% 65% at 30% 120%,
            rgba(30,100,200,0.18) 0%,
            transparent 60%
        );
}

/* Glass rim top */
.profile-hero::after{
    content:'';
    position:absolute;
    top:0; left:0; right:0;
    height:1px;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(255,255,255,0.18) 25%,
        rgba(255,255,255,0.3) 50%,
        rgba(255,255,255,0.18) 75%,
        transparent 100%
    );
    z-index:6;
    border-radius:32px 32px 0 0;
}

/* Mountain right deco — hide old SVG */
.profile-hero-deco{ display:none; }

.profile-top{
    display:flex;
    align-items:center;
    position:relative;
    z-index:2;
}

.profile-left{
    display:flex;
    align-items:center;
    gap:24px;
}


/* AVATAR */

.avatar-wrap{
    position:relative;
}

.avatar{
    width:120px;
    height:120px;

    border-radius:50%;

    overflow:hidden;

    border:3px solid rgba(255,255,255,0.35);
    box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 8px 32px rgba(0,0,0,0.4);

    background:white;
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.edit-avatar{

    position:absolute;

    right:0;
    bottom:0;

    width:42px;
    height:42px;

    border-radius:50%;

    background:white;
    color:#17375e;

    display:flex;
    justify-content:center;
    align-items:center;

    cursor:pointer;

    font-size:16px;

    box-shadow:
    0 10px 20px rgba(0,0,0,.15);
}


/* INFO */

.profile-info h2{
    font-family:'Cormorant Garamond',serif;
    color:white;
    font-size:24px;
    margin-bottom:10px;
}

.profile-info p{
    color:rgba(255,255,255,.75);
    margin-bottom:16px;
    font-size:16px;
}

.level-badge{
    display:inline-flex;
    align-items:center;
    gap:10px;

    padding:12px 20px;

    border-radius:50px;

    background:rgba(255,255,255,.08);

    color:#ffd166;
    font-size:14px;
    font-weight:700;
}


/* STATS */

.stats-section{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;

    margin-bottom:35px;
}

.stat-card{
    background:white;
    border-radius:28px;
    padding:30px;

    border:1px solid #eef2f7;

    box-shadow:
    0 10px 25px rgba(15,23,42,.05);

    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
}

.stat-card h1{
    font-family:'Cormorant Garamond',serif;
    font-size:34px;
    color:#17375e;

    margin-bottom:8px;
}

.stat-card span{
    color:#94a3b8;
    font-size:15px;
    font-weight:700;
}


/* LEVEL PROGRESS BAR */

.level-bar-section{
    background:white;
    border-radius:24px;
    padding:22px 28px;
    border:1px solid #eef2f7;
    box-shadow:0 10px 25px rgba(15,23,42,.04);
    margin-bottom:24px;
    display:flex;
    align-items:center;
    gap:24px;
}

.level-bar-icon{
    width:44px;
    height:44px;
    min-width:44px;
    border-radius:14px;
    background:#17375e;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:16px;
}

.level-bar-main{
    flex:1;
}

.level-bar-top{
    display:flex;
    align-items:baseline;
    gap:10px;
    margin-bottom:6px;
}

.level-bar-label{
    font-size:12px;
    font-weight:700;
    color:#94a3b8;
    letter-spacing:.8px;
    text-transform:uppercase;
}

.level-bar-name{
    font-family:'Cormorant Garamond',serif;
    font-size:22px;
    font-weight:700;
    color:#f59e0b;
}

.level-bar-count{
    font-size:12px;
    color:#94a3b8;
    font-weight:600;
}

.progress-caption{
    font-size:11px;
    color:#94a3b8;
    font-weight:600;
}

/* Pip dots - 1 pip per trip milestone in current level */
.progress-wrapper{
    width:100%;
    margin-bottom:6px;
}

.progress-pips{
    display:flex;
    gap:5px;
    width:100%;
    margin-bottom:6px;
}

.progress-pip{
    flex:1;
    height:10px;
    border-radius:50px;
    background:#f1f5f9;
    transition:background .4s ease;
}

.progress-pip.filled{
    background:linear-gradient(90deg,#f59e0b,#fbbf24);
}

.level-bar-next{
    text-align:center;
    min-width:120px;
    background:#f8fafc;
    border-radius:18px;
    padding:14px 20px;
    border:1px solid #eef2f7;
}

.level-bar-next-label{
    font-size:10px;
    color:#94a3b8;
    font-weight:700;
    letter-spacing:.6px;
    text-transform:uppercase;
    margin-bottom:4px;
}

.level-bar-next-name{
    font-family:'Cormorant Garamond',serif;
    font-size:20px;
    font-weight:700;
    color:#17375e;
}

.level-bar-next-req{
    font-size:11px;
    color:#94a3b8;
    margin-top:2px;
}


/* GRID */

.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:30px;
}

/* WACANA BOX - sisi kanan, merah muda */
.box-wacana{
    background:#fff5f5;
    border:1.5px solid #fecaca;
}

/* BOX */

.box{
    background:white;
    border-radius:24px;
    padding:20px 18px 14px;

    border:1px solid #eef2f7;

    box-shadow:
    0 10px 25px rgba(15,23,42,.04);

    display:flex;
    flex-direction:column;
}

.box-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:14px;
}

.box-title{
    display:flex;
    align-items:center;
    gap:10px;

    font-family:'Cormorant Garamond',serif;
    font-size:18px;
    font-weight:700;
    color:#0f172a;
}

.box-title i{
    font-size:15px;
    color:#17375e;
}

.box-see-all{
    font-size:12px;
    font-weight:700;
    color:#17375e;
    text-decoration:none;
    white-space:nowrap;
    opacity:0.7;
    transition:.2s;
}

.box-see-all:hover{
    opacity:1;
}

.box-wacana .box-title i{
    color:#dc2626;
}

.box-wacana .box-see-all{
    color:#dc2626;
}

.box-divider{
    height:1px;
    background:#f1f5f9;
    margin-bottom:10px;
}

/* ITEM */

.item{
    display:flex;
    align-items:flex-start;
    gap:12px;

    padding:9px 8px;
    border-radius:16px;

    transition:.3s;

    margin-bottom:4px;
}

.item:hover{
    background:#f8fafc;
}

.box-wacana .item:hover{
    background:#fff0f0;
}

.item-icon{
    width:40px;
    height:40px;
    min-width:40px;

    border-radius:14px;

    background:#17375e;

    display:flex;
    justify-content:center;
    align-items:center;

    color:white;
    font-size:14px;
}

.item-image img{
    width:44px;
    height:44px;
    min-width:44px;

    border-radius:14px;
    object-fit:cover;
}

.item-info h4{
    font-size:13px;
    font-weight:700;
    margin-bottom:4px;
    color:#1e293b;
    line-height:1.3;
}

.item-info p{
    color:#64748b;
    font-size:12px;
    line-height:1.6;
}

/* RATING STARS */
.stars{
    color:#f59e0b;
    font-size:11px;
    letter-spacing:1px;
    margin-bottom:3px;
}

/* TRIP STATUS BADGE */
.trip-badge{
    display:inline-block;
    padding:2px 10px;
    border-radius:50px;
    font-size:10px;
    font-weight:700;
    letter-spacing:.5px;
    margin-left:6px;
    vertical-align:middle;
    text-transform:uppercase;
}
.badge-selesai{ background:#dcfce7; color:#16a34a; }
.badge-ongoing{ background:#fef3c7; color:#d97706; }
.badge-batal  { background:#fee2e2; color:#dc2626; }

/* WACANA ITEM DESTINATIONS */
.wacana-dest{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    margin-top:5px;
}
.dest-chip{
    display:inline-flex;
    align-items:center;
    gap:4px;
    background:#fff0f0;
    border:1px solid #fecaca;
    color:#dc2626;
    font-size:10px;
    font-weight:600;
    padding:2px 8px;
    border-radius:50px;
}

/* ITEM DATE */
.item-date{
    color:#94a3b8;
    font-size:11px;
    margin-top:2px;
}

/* LIHAT SEMUA BUTTON */
.box-footer{
    margin-top:auto;
    padding-top:10px;
}
.box-lihat-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    width:100%;
    padding:9px;
    border-radius:14px;
    background:#f1f5f9;
    color:#17375e;
    font-size:12px;
    font-weight:700;
    text-decoration:none;
    transition:.2s;
    border:none;
    cursor:pointer;
}
.box-lihat-btn:hover{
    background:#e2e8f0;
}
.box-wacana .box-lihat-btn{
    background:#fee2e2;
    color:#dc2626;
}
.box-wacana .box-lihat-btn:hover{
    background:#fecaca;
}

/* WACANA IMAGE PER TRIP */
.wacana-imgs{
    display:flex;
    gap:4px;
    margin-top:6px;
}
.wacana-imgs img{
    width:36px;
    height:36px;
    border-radius:10px;
    object-fit:cover;
    border:2px solid white;
    box-shadow:0 2px 6px rgba(0,0,0,.1);
}
.wacana-imgs .more-imgs{
    width:36px;
    height:36px;
    border-radius:10px;
    background:#fecaca;
    color:#dc2626;
    font-size:10px;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
}


/* MODAL */

.modal{
    position:fixed;
    inset:0;

    background:rgba(0,0,0,.5);

    display:none;
    justify-content:center;
    align-items:center;

    z-index:999;
}

.modal-content{
    width:420px;
    background:white;
    border-radius:28px;
    padding:35px;
}

.modal-content h2{
    margin-bottom:25px;
    color:#17375e;
}

.input-group{
    margin-bottom:18px;
}

.input-group label{
    display:block;
    margin-bottom:8px;
    font-weight:700;
}

.input-group input{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:1px solid #cbd5e1;
    outline:none;
}

.save-btn{
    width:100%;
    padding:16px;
    border:none;
    border-radius:16px;
    background:#17375e;
    color:white;
    font-weight:700;
    cursor:pointer;
}


/* RESPONSIVE */

@media(max-width:1200px){
    .grid{
        grid-template-columns:1fr 1fr;
    }
}

@media(max-width:700px){
    .grid{
        grid-template-columns:1fr;
    }
}


/* FOOTER */
.footer-bar{
    margin-top:80px;
    background:linear-gradient(135deg,#17375e,#234d7d);
    color:white;
    padding:60px 6% 25px;
}
.footer-inner{
    display:grid;
    grid-template-columns:2fr 2fr 1fr;
    gap:40px;
    margin-bottom:0;
}
.footer-logo{
    font-family:'Cormorant Garamond',serif;
    font-size:42px;
    margin-bottom:15px;
}
.footer-desc,.footer-text{
    color:rgba(255,255,255,0.75);
    line-height:1.8;
}
.footer-title{
    font-family:'Cormorant Garamond',serif;
    font-size:28px;
    margin-bottom:18px;
}
.footer-bottom{
    margin-top:45px;
    padding-top:25px;
    border-top:1px solid rgba(255,255,255,0.12);
    text-align:center;
    color:rgba(255,255,255,0.8);
}

/* HERO STATS ROW */
.hero-stats{
    display:flex;
    align-items:center;
    gap:0;
    margin-top:28px;
    background:rgba(255,255,255,0.08);
    border-radius:20px;
    padding:16px 24px;
    flex-wrap:wrap;
    gap:0;
}
.hero-stat{
    text-align:center;
    flex:1;
    min-width:80px;
}
.hero-stat-num{
    font-family:'Cormorant Garamond',serif;
    font-size:28px;
    font-weight:700;
    color:white;
    line-height:1;
    margin-bottom:4px;
}
.hero-stat-label{
    font-size:11px;
    font-weight:700;
    color:rgba(255,255,255,0.65);
    text-transform:uppercase;
    letter-spacing:0.06em;
}
.hero-stat-label i{ margin-right:3px; }
.hero-stat-divider{
    width:1px;
    height:36px;
    background:rgba(255,255,255,0.15);
    flex-shrink:0;
}

/* TRIP GROUP MINI BUTTON */
.trip-group-mini-btn{
    display:inline-flex;
    align-items:center;
    gap:5px;
    margin-top:6px;
    padding:4px 10px;
    border-radius:8px;
    background:linear-gradient(135deg,#17375e,#234d7d);
    color:white;
    font-size:11px;
    font-weight:700;
    text-decoration:none;
    transition:0.2s;
}
.trip-group-mini-btn:hover{
    background:linear-gradient(135deg,#234d7d,#2d72c8);
    transform:translateY(-1px);
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





<!-- CONTAINER -->

<div class="container">


    <!-- HERO -->

    <div class="profile-hero">

        <!-- Photo background -->
        <div class="profile-hero-photo"></div>

        <div class="profile-top">

            <div class="profile-left">


                <!-- FOTO -->

                <div class="avatar-wrap">

                    <div class="avatar">

                        <?php if($data['foto'] != '') : ?>

                            <img src="assets/img/profil/<?= $data['foto']; ?>">

                        <?php else : ?>

                            <img src="https://ui-avatars.com/api/?name=<?= $data['nama']; ?>&background=17375e&color=fff&size=200">

                        <?php endif; ?>

                    </div>

                    <div
                    class="edit-avatar"
                    onclick="openModal()">

                        <i class="fa-solid fa-pen"></i>

                    </div>

                </div>


                <!-- INFO -->

                <div class="profile-info">

                    <h2>
                        <?= $data['nama']; ?>
                    </h2>

                    <p>
                        <?= $data['email']; ?>
                    </p>

                    <div class="level-badge">

                        <i class="fa-solid fa-crown"></i>

                        <?= $level; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- STATS -->

    <div class="stats-section">

        <div class="stat-card">

            <h1><?= $total_review; ?></h1>

            <span>Total Review</span>

        </div>

        <div class="stat-card">

            <h1><?= $total_wishlist; ?></h1>

            <span>Total Wishlist</span>

        </div>

        <div class="stat-card">

            <h1><?= $total_trip; ?></h1>

            <span>Total Trip</span>

        </div>

        <div class="stat-card">

            <h1><?= $total_wacana; ?></h1>

            <span>Total Wacana</span>

        </div>

    </div>



    <!-- GRID -->

    <div class="grid">


        <!-- REVIEW -->

        <div class="box">

            <div class="box-header">
                <div class="box-title">
                    <i class="fa-solid fa-star"></i>
                    Review Terbaru
                </div>
            </div>

            <div class="box-divider"></div>

            <?php if(mysqli_num_rows($review) > 0) : ?>

                <?php while($r = mysqli_fetch_assoc($review)) : ?>

                <div class="item">

                    <div class="item-icon">
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <div class="item-info">
                        <h4><?= htmlspecialchars($r['nama']); ?></h4>
                        <div class="stars">
                            <?php for($i=1;$i<=5;$i++) echo $i<=$r['rating'] ? '★' : '☆'; ?>
                            <span style="color:#94a3b8;font-size:10px;margin-left:2px;"><?= $r['rating']; ?>.0</span>
                        </div>
                        <p>"<?= htmlspecialchars($r['komentar']); ?>"</p>
                    </div>

                </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="item-info" style="padding:10px;">
                    <p>Belum ada review.</p>
                </div>

            <?php endif; ?>

            <div class="box-footer">
                <a href="#" class="box-lihat-btn"><i class="fa-solid fa-arrow-right"></i> Lihat Semua</a>
            </div>

        </div>



        <!-- WISHLIST -->

        <div class="box">

            <div class="box-header">
                <div class="box-title">
                    <i class="fa-solid fa-heart"></i>
                    Wishlist Terbaru
                </div>
            </div>

            <div class="box-divider"></div>

            <?php if(mysqli_num_rows($wishlist) > 0) : ?>

                <?php while($w = mysqli_fetch_assoc($wishlist)) : ?>

                <div class="item">

                    <div class="item-image">
                        <img src="assets/img/<?= $w['gambar']; ?>">
                    </div>

                    <div class="item-info">
                        <h4><?= htmlspecialchars($w['nama']); ?></h4>
                        <p>Disimpan</p>
                    </div>

                </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="item-info" style="padding:10px;">
                    <p>Belum ada wishlist.</p>
                </div>

            <?php endif; ?>

            <div class="box-footer">
                <a href="wishlist.php" class="box-lihat-btn"><i class="fa-solid fa-arrow-right"></i> Lihat Semua</a>
            </div>

        </div>



        <!-- TRIP -->

        <div class="box">

            <div class="box-header">
                <div class="box-title">
                    <i class="fa-solid fa-plane-departure"></i>
                    Trip Terbaru
                </div>
            </div>

            <div class="box-divider"></div>

            <?php if(mysqli_num_rows($trip) > 0) : ?>

                <?php while($t = mysqli_fetch_assoc($trip)) : ?>

                <div class="item">

                    <?php if(!empty($t['gambar_destinasi'])) : ?>
                    <div class="item-image">
                        <img src="assets/img/<?= htmlspecialchars($t['gambar_destinasi']); ?>">
                    </div>
                    <?php else : ?>
                    <div class="item-icon">
                        <i class="fa-solid fa-plane"></i>
                    </div>
                    <?php endif; ?>

                    <div class="item-info">
                        <h4>
                            <?= htmlspecialchars($t['nama_trip']); ?>
                            <span class="trip-badge badge-<?= strtolower($t['status']); ?>">
                                <?= strtoupper($t['status']); ?>
                            </span>
                        </h4>
                        <p>📅 <?= htmlspecialchars($t['tanggal']); ?></p>
                        <?php if($t['nama_destinasi']) : ?>
                        <p>📍 <?= htmlspecialchars($t['nama_destinasi']); ?></p>
                        <?php endif; ?>
                        <a href="trip_group.php?id=<?= $t['id']; ?>" class="trip-group-mini-btn">
                            <i class="fa-solid fa-users"></i> Trip Group
                        </a>
                    </div>

                </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="item-info" style="padding:10px;">
                    <p>Belum ada trip.</p>
                </div>

            <?php endif; ?>

            <div class="box-footer">
                <a href="trip.php" class="box-lihat-btn"><i class="fa-solid fa-arrow-right"></i> Lihat Semua</a>
            </div>

        </div>



        <!-- WACANA (TRIP BATAL) -->

        <div class="box box-wacana">

            <div class="box-header">
                <div class="box-title">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Wacana (Trip Batal)
                </div>
            </div>

            <div class="box-divider" style="background:#fecaca;"></div>

            <p style="font-size:11px;color:#94a3b8;margin-bottom:10px;">Berikut daftar trip yang batal dan menjadi wacana kamu 😅</p>

            <?php

            $wacana = mysqli_query($conn,"
                SELECT
                    trip.*,
                    GROUP_CONCAT(wisata.nama ORDER BY wisata.nama SEPARATOR '||') AS nama_destinasi_list,
                    GROUP_CONCAT(wisata.gambar ORDER BY wisata.nama SEPARATOR '||') AS gambar_list,
                    COUNT(trip_detail.id) AS jumlah_dest
                FROM trip
                LEFT JOIN trip_detail ON trip.id = trip_detail.trip_id
                LEFT JOIN wisata ON trip_detail.wisata_id = wisata.id
                WHERE trip.status='batal'
                AND (
                    trip.creator_id='$user_id'
                    OR trip.id IN (SELECT trip_id FROM trip_members WHERE user_id='$user_id')
                )
                GROUP BY trip.id
                ORDER BY trip.id DESC
                LIMIT 3
            ");

            ?>

            <?php if(mysqli_num_rows($wacana) > 0) : ?>

                <?php while($wc = mysqli_fetch_assoc($wacana)) : ?>

                <?php
                $dest_names  = $wc['nama_destinasi_list'] ? explode('||', $wc['nama_destinasi_list']) : [];
                $dest_images = $wc['gambar_list']         ? explode('||', $wc['gambar_list'])         : [];
                ?>

                <div class="item">

                    <!-- Gambar destinasi pertama sebagai thumbnail trip -->
                    <?php if(!empty($dest_images[0])) : ?>
                    <div class="item-image">
                        <img src="assets/img/<?= htmlspecialchars($dest_images[0]); ?>" alt="">
                    </div>
                    <?php else : ?>
                    <div class="item-icon" style="background:#dc2626;">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <?php endif; ?>

                    <div class="item-info" style="flex:1;min-width:0;">
                        <h4><?= htmlspecialchars($wc['nama_trip']); ?></h4>
                        <p class="item-date">📅 <?= htmlspecialchars($wc['tanggal']); ?></p>

                        <!-- Destinasi-destinasi dalam trip ini -->
                        <?php if(!empty($dest_names)) : ?>
                        <div class="wacana-dest">
                            <?php foreach(array_slice($dest_names,0,3) as $dn) : ?>
                            <span class="dest-chip">📍 <?= htmlspecialchars($dn); ?></span>
                            <?php endforeach; ?>
                            <?php if(count($dest_names) > 3) : ?>
                            <span class="dest-chip">+<?= count($dest_names)-3; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div>

                </div>

                <?php endwhile; ?>

            <?php else : ?>

                <div class="item-info" style="padding:10px;">
                    <p>Belum ada trip wacana 🎉</p>
                </div>

            <?php endif; ?>

            <div class="box-footer">
                <a href="trip.php" class="box-lihat-btn"><i class="fa-solid fa-arrow-right"></i> Lihat Semua Wacana</a>
            </div>

        </div>

    </div>



    <!-- LEVEL PROGRESS BAR -->

    <div class="level-bar-section">

        <div class="level-bar-icon">
            <i class="fa-solid fa-compass"></i>
        </div>

        <div class="level-bar-main">

            <div class="level-bar-top">
                <span class="level-bar-label">Level Traveler Kamu</span>
                <span class="level-bar-name"><?= $level; ?></span>
                <span class="level-bar-count">(<?= $total_trip_selesai; ?> trip selesai)</span>
            </div>

            <!-- Pip dots: 1 pip kuning per trip dalam range level ini -->
            <div class="progress-wrapper">
                <?php
                $pip_total = $next_min !== null ? min(10, $next_min - $current_min) : 10;
                $pip_done  = $next_min !== null
                    ? min($pip_total, $total_trip_selesai - $current_min)
                    : $pip_total;
                $pip_done = max(0, $pip_done);
                ?>
                <div class="progress-pips">
                    <?php for($p = 1; $p <= $pip_total; $p++): ?>
                    <div class="progress-pip <?= $p <= $pip_done ? 'filled' : ''; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if($next_min !== null) : ?>
            <div class="progress-caption">
                <?= $total_trip_selesai; ?> / <?= $next_min; ?> trip selesai menuju level <?= $next_level; ?>
            </div>
            <?php else : ?>
            <div class="progress-caption">🏆 Level tertinggi tercapai!</div>
            <?php endif; ?>

        </div>

        <?php if($next_level) : ?>
        <div class="level-bar-next">
            <div class="level-bar-next-label">Level Selanjutnya</div>
            <div class="level-bar-next-name"><?= $next_level; ?></div>
            <div class="level-bar-next-req"><?= $next_min; ?> trip selesai</div>
        </div>
        <?php else : ?>
        <div class="level-bar-next">
            <div class="level-bar-next-label">Status</div>
            <div class="level-bar-next-name" style="font-size:16px;">🏆 Master</div>
            <div class="level-bar-next-req">Level tertinggi</div>
        </div>
        <?php endif; ?>

    </div>



</div>



<!-- MODAL EDIT -->

<div class="modal" id="modal">

    <div class="modal-content">

        <h2>Edit Profil</h2>

        <form
        method="POST"
        enctype="multipart/form-data">

            <div class="input-group">

                <label>Nama</label>

                <input
                type="text"
                name="nama"
                value="<?= $data['nama']; ?>"
                required>

            </div>

            <div class="input-group">

                <label>Email</label>

                <input
                type="email"
                name="email"
                value="<?= $data['email']; ?>"
                required>

            </div>

            <div class="input-group">

                <label>Foto Profil</label>

                <input
                type="file"
                name="foto">

            </div>

            <button
            type="submit"
            name="update_profil"
            class="save-btn">

                Simpan Perubahan

            </button>

        </form>

    </div>

</div>



<script>

function openModal(){

    document.getElementById('modal')
    .style.display = 'flex';

}

window.onclick = function(e){

    let modal = document.getElementById('modal');

    if(e.target == modal){

        modal.style.display = 'none';

    }

}

</script>



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