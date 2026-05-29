<?php 
session_start();
include '../config/koneksi.php';


// =========================
// CEK LOGIN
// =========================

if(!isset($_SESSION['login'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// =========================
// AMBIL DATA WISATA
// =========================

$wisata = mysqli_query($conn,"
    SELECT *
    FROM wisata
    ORDER BY nama ASC
");


// =========================
// TAMBAH TRIP
// =========================

if(isset($_POST['buat_trip'])){

    $nama_trip     = htmlspecialchars($_POST['nama_trip']);
    $wisata_ids    = $_POST['wisata_id']; // array
    $tanggal       = $_POST['tanggal'];
    $budget        = $_POST['budget'];
    $transportasi  = $_POST['transportasi'];
    $catatan       = htmlspecialchars($_POST['catatan']);
    $status        = $_POST['status'];

    $deskripsi = "
Transportasi : $transportasi

Budget : Rp " . number_format($budget,0,',','.') . "

Catatan :
$catatan
";

    // Pastikan maksimal 3 destinasi
    if(!is_array($wisata_ids)) $wisata_ids = [$wisata_ids];
    $wisata_ids = array_unique(array_filter($wisata_ids));
    $wisata_ids = array_slice($wisata_ids, 0, 3);

    // ESCAPE DATA
    $deskripsi_esc   = mysqli_real_escape_string($conn, $deskripsi);
    $nama_trip_esc   = mysqli_real_escape_string($conn, $nama_trip);
    $tanggal_esc     = mysqli_real_escape_string($conn, $tanggal);
    $status_esc      = mysqli_real_escape_string($conn, $status);

    // INSERT TRIP - deteksi kolom otomatis agar tidak error
    $col_creator = mysqli_query($conn, "SHOW COLUMNS FROM `trip` LIKE 'creator_id'");
    $user_col    = mysqli_num_rows($col_creator) > 0 ? 'creator_id' : 'user_id';
    $col_max     = mysqli_query($conn, "SHOW COLUMNS FROM `trip` LIKE 'max_member'");
    $has_max     = mysqli_num_rows($col_max) > 0;

    if($has_max){
        $ins = mysqli_query($conn,"INSERT INTO trip(nama_trip, deskripsi, $user_col, max_member, tanggal, status, created_at) VALUES('$nama_trip_esc','$deskripsi_esc','$user_id',5,'$tanggal_esc','$status_esc',NOW())");
    } else {
        $ins = mysqli_query($conn,"INSERT INTO trip(nama_trip, deskripsi, $user_col, tanggal, status, created_at) VALUES('$nama_trip_esc','$deskripsi_esc','$user_id','$tanggal_esc','$status_esc',NOW())");
    }
    if(!$ins){ die("ERROR INSERT TRIP: " . mysqli_error($conn)); }

    // AMBIL ID TRIP TERBARU
    $trip_id = mysqli_insert_id($conn);

    // INSERT SEMUA DESTINASI - deteksi kolom trip_detail
    foreach($wisata_ids as $wid){
        $wid = (int)$wid;
        if($wid > 0){
            $td_check = mysqli_query($conn,"SHOW COLUMNS FROM `trip_detail` LIKE 'trip_id'");
            if(mysqli_num_rows($td_check) > 0){
                mysqli_query($conn,"INSERT INTO trip_detail(trip_id, wisata_id) VALUES('$trip_id','$wid')");
            } else {
                mysqli_query($conn,"INSERT INTO trip_detail VALUES(NULL,'$trip_id','$wid')");
            }
        }
    }

    // NOTIFIKASI: trip baru dibuat
    // Pastikan tabel ada
    mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `notifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `from_user_id` int(11) NOT NULL DEFAULT 0,
      `trip_id` int(11) DEFAULT NULL,
      `type` varchar(50) DEFAULT 'trip',
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

    // Format tanggal trip untuk notifikasi
    $tgl_formatted = $tanggal ? date('d M Y', strtotime($tanggal)) : $tanggal;
    $trip_link = "trip_group.php?id=$trip_id";

    // Cek apakah kolom link_url sudah ada
    $cek_lnk = mysqli_query($conn,"SHOW COLUMNS FROM `notifications` LIKE 'link_url'");
    $has_link_url = mysqli_num_rows($cek_lnk) > 0;

    if($status === 'batal'){
        $notif_msg = mysqli_real_escape_string($conn, "Wacana trip baru tersedia: \"{$nama_trip}\". Belum ada tanggal pasti, yuk diskusikan!");
        if($has_link_url){
            $lnk = mysqli_real_escape_string($conn, $trip_link);
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message,link_url) VALUES('$user_id','$user_id','$trip_id','wacana','$notif_msg','$lnk')");
        } else {
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message) VALUES('$user_id','$user_id','$trip_id','wacana','$notif_msg')");
        }
    } else {
        $notif_msg = mysqli_real_escape_string($conn, "Trip \"{$nama_trip}\" berhasil dibuat. Tanggal berangkat: {$tgl_formatted}.");
        if($has_link_url){
            $lnk = mysqli_real_escape_string($conn, $trip_link);
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message,link_url) VALUES('$user_id','$user_id','$trip_id','trip','$notif_msg','$lnk')");
        } else {
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message) VALUES('$user_id','$user_id','$trip_id','trip','$notif_msg')");
        }
    }

    header("Location: ../trip.php");
    exit;
}

$user_id_nav = $_SESSION['user_id'] ?? 0;
$_user_nav = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto FROM users WHERE id='$user_id_nav'"));
$_nav_foto = $_user_nav['foto'] ?? 'kimi.jpg';

?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Buat Trip - TRAVA</title>

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
.notif-icon-wrap.invite{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.trip{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.wacana{background:#fee2e2;color:#dc2626;}
.notif-icon-wrap.chat_personal,.notif-icon-wrap.chat_group{background:#f0fdf4;color:#16a34a;}
.notif-body{flex:1;min-width:0;}
.notif-title{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;}
.notif-msg{font-size:12px;color:#64748b;line-height:1.5;}
.notif-time{font-size:11px;color:#9ca3af;margin-top:3px;}
.notif-dot{width:8px;height:8px;background:#2563eb;border-radius:50%;flex-shrink:0;margin-top:8px;}
.notif-footer{padding:12px;text-align:center;border-top:1px solid #f1f5f9;}
.notif-footer a{color:#2563eb;font-size:13px;font-weight:600;text-decoration:none;}
.notif-empty{padding:24px;text-align:center;color:#94a3b8;font-size:13px;}
.profile-wrapper{position:relative;display:inline-flex;align-items:center;}
.profile-avatar-btn{background:none;border:none;cursor:pointer;padding:0;width:40px;height:40px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}
.profile-dropdown{position:absolute;top:50px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}

.nav-menu a{
    text-decoration:none;
    color:#64748b;
    font-size:14px;
    font-weight:700;
    transition:0.3s;
}

.nav-menu a:hover{
    color:#17375e;
}

.nav-menu .active{
    color:#17375e;
}


/* =========================
CONTAINER
========================= */

.container{
    width:88%;
    margin:auto;
    padding:50px 0;
}


/* =========================
LAYOUT
========================= */

.trip-layout{
    display:flex;
    gap:40px;
    align-items:flex-start;
}


/* =========================
LEFT SIDE
========================= */

.trip-left{
    width:34%;
    position:sticky;
    top:120px;
}

.trip-left-card{
    position:relative;
    overflow:hidden;

    padding:45px;
    border-radius:34px;

    background:
    linear-gradient(
    135deg,
    #17375e,
    #234d7d
    );

    box-shadow:
    0 20px 50px rgba(23,55,94,0.12);
}

.trip-left-card::before{
    content:'';
    position:absolute;
    inset:0;

    background:
    repeating-linear-gradient(
        135deg,
        rgba(255,255,255,0.03),
        rgba(255,255,255,0.03) 1px,
        transparent 1px,
        transparent 28px
    );
}

.trip-left-card h2{
    position:relative;
    z-index:2;

    font-family:'Cormorant Garamond', serif;
    font-size:28px;
    color:white;

    line-height:1.1;
    margin-bottom:20px;
}

.trip-left-card p{
    position:relative;
    z-index:2;

    color:rgba(255,255,255,0.78);

    line-height:2;
    font-size:15px;
}


/* =========================
RIGHT SIDE
========================= */

.trip-right{
    flex:1;

    background:white;

    border-radius:34px;

    padding:40px;

    border:1px solid #eef2f7;

    box-shadow:
    0 10px 30px rgba(15,23,42,0.05);
}


/* =========================
FORM GROUP
========================= */

.form-group{
    margin-bottom:24px;
}

.form-group label{
    display:block;

    margin-bottom:10px;

    font-size:14px;
    font-weight:700;

    color:#334155;
}

.form-group input,
.form-group textarea,
.form-group select{
    width:100%;

    padding:18px;

    border:none;
    outline:none;

    border-radius:18px;

    background:#f8fafc;

    font-size:14px;
    font-family:'Manrope', sans-serif;

    transition:0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus{

    background:white;

    border:
    1px solid #17375e;

    box-shadow:
    0 0 0 4px rgba(23,55,94,0.08);
}

.form-group textarea{
    resize:none;
    height:130px;
}


/* =========================
FORM ROW
========================= */

.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}


/* =========================
BUTTON
========================= */

.submit-btn{
    width:100%;

    padding:18px;

    border:none;
    border-radius:20px;

    background:
    linear-gradient(
    135deg,
    #17375e,
    #234d7d
    );

    color:white;

    font-size:15px;
    font-weight:700;

    cursor:pointer;

    transition:0.3s;
}

.submit-btn:hover{
    transform:translateY(-2px);

    box-shadow:
    0 15px 30px rgba(23,55,94,0.15);
}


/* =========================
FOOTER
========================= */

.footer{
    margin-top:80px;

    background:
    linear-gradient(
    135deg,
    #17375e,
    #234d7d
    );

    color:white;

    padding:60px 6% 25px;
}

.footer-grid{
    display:grid;
    grid-template-columns:2fr 2fr 1fr;
    gap:40px;
}

.footer-logo{
    font-family:'Cormorant Garamond', serif;
    font-size:42px;
    margin-bottom:15px;
}

.footer-desc{
    color:rgba(255,255,255,0.75);
    line-height:1.8;
}

.footer-title{
    font-family:'Cormorant Garamond', serif;
    font-size:28px;
    margin-bottom:18px;
}

.footer-text{
    color:rgba(255,255,255,0.75);
    line-height:2;
}

.footer-bottom{
    margin-top:45px;
    padding-top:25px;

    border-top:
    1px solid rgba(255,255,255,0.12);

    text-align:center;

    color:rgba(255,255,255,0.8);
}


/* =========================
RESPONSIVE
========================= */

@media(max-width:900px){

    .trip-layout{
        flex-direction:column;
    }

    .trip-left{
        width:100%;
        position:relative;
        top:0;
    }

    .form-row{
        grid-template-columns:1fr;
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
    align-items:center;
    gap:28px;
}
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
.notif-icon-wrap.invite{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.trip{background:#dbeafe;color:#1d4ed8;}
.notif-icon-wrap.wacana{background:#fee2e2;color:#dc2626;}
.notif-icon-wrap.chat_personal,.notif-icon-wrap.chat_group{background:#f0fdf4;color:#16a34a;}
.notif-body{flex:1;min-width:0;}
.notif-title{font-size:13px;font-weight:700;color:#1e293b;margin-bottom:2px;}
.notif-msg{font-size:12px;color:#64748b;line-height:1.5;}
.notif-time{font-size:11px;color:#9ca3af;margin-top:3px;}
.notif-dot{width:8px;height:8px;background:#2563eb;border-radius:50%;flex-shrink:0;margin-top:8px;}
.notif-footer{padding:12px;text-align:center;border-top:1px solid #f1f5f9;}
.notif-footer a{color:#2563eb;font-size:13px;font-weight:600;text-decoration:none;}
.notif-empty{padding:24px;text-align:center;color:#94a3b8;font-size:13px;}
.profile-wrapper{position:relative;display:inline-flex;align-items:center;}
.profile-avatar-btn{background:none;border:none;cursor:pointer;padding:0;width:40px;height:40px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}
.profile-dropdown{position:absolute;top:50px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}

    .trip-left-card h2{
        font-size:42px;
    }

    .trip-right{
        padding:28px;
    }

    .footer-grid{
        grid-template-columns:1fr;
    }

}

</style>
</head>
<body>



<!-- =========================
NAVBAR
========================= -->

<div class="navbar">

    <a href="../index.php" class="nav-logo">
        <img src="../assets/img/logo-trava.png" alt="TRAVA Logo">
    </a>

    <div class="nav-menu">
        <a href="../index.php">Home</a>
        <a href="../wishlist.php">Wishlist</a>
        <a href="../trip.php" class="active">Trip</a>

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
                    <a href="../notifikasi.php">Lihat semua notifikasi</a>
                </div>
            </div>
        </div>

        <!-- PROFIL AVATAR -->
        <div class="profile-wrapper" id="profileWrapper">
            <button class="profile-avatar-btn" onclick="toggleProfile(event)">
                <img src="../assets/img/profil/<?= htmlspecialchars($_nav_foto); ?>" alt="Profil" onerror="this.src='../assets/img/profil/kimi.jpg'">
            </button>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="../profil.php" class="profile-dd-item"><i class="fa-regular fa-user"></i> Lihat Profil Saya</a>
                <div class="profile-dd-divider"></div>
                <a href="../logout.php" class="profile-dd-item"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>

</div>



<!-- =========================
CONTAINER
========================= -->

<div class="container">


    <!-- LAYOUT -->

    <div class="trip-layout">


        <!-- LEFT -->

        <div class="trip-left">

            <div class="trip-left-card">

                <h2>
                    Buat Trip Baru
                </h2>

                <p>
                    Lengkapi detail perjalananmu dan mulai
                    petualangan baru bersama teman atau keluarga.
                    Atur destinasi, transportasi, budget,
                    dan semua rencana travelingmu dengan lebih
                    praktis bersama TRAVA.
                </p>

            </div>

        </div>



        <!-- RIGHT -->

        <div class="trip-right">

            <form method="POST">


                <!-- NAMA TRIP -->

                <div class="form-group">

                    <label>
                        Nama Trip
                    </label>

                    <input
                    type="text"
                    name="nama_trip"
                    placeholder="Contoh : Healing Pantai Cirebon"
                    required>

                </div>



                <!-- DESTINASI (maks 3) -->

                <div class="form-group">

                    <label>
                        Destinasi Wisata
                        <span style="color:#94a3b8;font-weight:500;font-size:12px;">
                            (maks. 3 destinasi)
                        </span>
                    </label>

                    <!-- Destinasi 1 (wajib) -->
                    <div style="margin-bottom:10px;">
                        <select name="wisata_id[]" required>
                            <option value="">-- Pilih Destinasi 1 (Wajib) --</option>
                            <?php
                            $w1 = mysqli_query($conn,"SELECT * FROM wisata ORDER BY nama ASC");
                            while($w = mysqli_fetch_assoc($w1)) : ?>
                            <option value="<?= $w['id']; ?>"><?= htmlspecialchars($w['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Destinasi 2 (opsional) -->
                    <div style="margin-bottom:10px;">
                        <select name="wisata_id[]">
                            <option value="">-- Destinasi 2 (Opsional) --</option>
                            <?php
                            $w2 = mysqli_query($conn,"SELECT * FROM wisata ORDER BY nama ASC");
                            while($w = mysqli_fetch_assoc($w2)) : ?>
                            <option value="<?= $w['id']; ?>"><?= htmlspecialchars($w['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Destinasi 3 (opsional) -->
                    <div>
                        <select name="wisata_id[]">
                            <option value="">-- Destinasi 3 (Opsional) --</option>
                            <?php
                            $w3 = mysqli_query($conn,"SELECT * FROM wisata ORDER BY nama ASC");
                            while($w = mysqli_fetch_assoc($w3)) : ?>
                            <option value="<?= $w['id']; ?>"><?= htmlspecialchars($w['nama']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                </div>



                <!-- ROW -->

                <div class="form-row">


                    <!-- TANGGAL -->

                    <div class="form-group">

                        <label>
                            Tanggal Berangkat
                        </label>

                        <input
                        type="date"
                        name="tanggal"
                        required>

                    </div>



                    <!-- BUDGET -->

                    <div class="form-group">

                        <label>
                            Budget Trip
                        </label>

                        <input
                        type="number"
                        name="budget"
                        placeholder="Contoh : 2000000"
                        required>

                    </div>

                </div>



                <!-- TRANSPORTASI -->

                <div class="form-group">

                    <label>
                        Transportasi
                    </label>

                    <select
                    name="transportasi"
                    required>

                        <option value="">
                            -- Pilih Transportasi --
                        </option>

                        <option value="Motor">Motor</option>
                        <option value="Mobil">Mobil</option>
                        <option value="Kereta">Kereta</option>
                        <option value="Bus">Bus</option>
                        <option value="Pesawat">Pesawat</option>

                    </select>

                </div>
                
                <div class="form-group">
                <label>Status Trip</label>

                <select name="status" required>

        <option value="">– Pilih Status –</option>

        <option value="planning">Planning</option>

        

        <option value="selesai">Selesai</option>

        <option value="batal">Batal</option>

    </select>
</div>


                <!-- CATATAN -->

                <div class="form-group">

                    <label>
                        Catatan Trip
                    </label>

                    <textarea
                    name="catatan"
                    placeholder="Contoh : berangkat pagi, bawa kamera, cari sunset terbaik..."
                    required></textarea>

                </div>



                <!-- BUTTON -->

                <button
                type="submit"
                name="buat_trip"
                class="submit-btn">

                    <i class="fa-solid fa-paper-plane"></i>

                    Buat Trip Sekarang

                </button>

            </form>

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

            <div class="footer-desc">

                Platform wisata modern untuk membantu
                traveler menemukan destinasi terbaik di Cirebon.

            </div>

        </div>



        <!-- CENTER -->

        <div>

            <div class="footer-title">
                TRAVA Team
            </div>

            <div class="footer-text">

                Moh. Farid Ilham Ghifari - 2488010066 <br>
                Teman 1 - NIM <br>
                Teman 2 - NIM

            </div>

        </div>



        <!-- RIGHT -->

        <div>

            <div class="footer-title">
                Info
            </div>

            <div class="footer-text">

                Cirebon, Indonesia

            </div>

        </div>

    </div>



    <div class="footer-bottom">

        © 2026 TRAVA

    </div>

</div>


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
  fetch("../proses/notif_proses.php?action=list").then(function(r){return r.json();}).then(function(list){
    var el=document.getElementById("notifList");
    if(!list||!list.length){el.innerHTML='<div class="notif-empty">Belum ada notifikasi</div>';return;}
    var html=list.slice(0,5).map(function(n){
      var ic=_NI[n.type]||'<i class="fa-solid fa-bell"></i>';
      var lk=n.link_url?"../"+n.link_url:(n.trip_id?"../trip_group.php?id="+n.trip_id:"../trip.php");
      var title=(n.message.split('.')[0])||n.message;
      return '<div class="notif-item '+(n.is_read==0?"unread":"")+'" onclick="_goN('+n.id+',\''+lk+'\')">' +
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
  fetch("../proses/notif_proses.php?action=read_one&id="+id).then(function(){window.location.href=link;});
}
function readAll(){
  fetch("../proses/notif_proses.php?action=read_all").then(function(){
    document.getElementById("notifBadge").style.display="none";
    _loadNL();
  });
}
function _chkN(){
  fetch("../proses/notif_proses.php?action=count").then(function(r){return r.json();}).then(function(d){
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