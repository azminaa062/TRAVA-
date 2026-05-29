<?php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

$id_trip = $_GET['id'];
$user_id = $_SESSION['user_id'];

// =========================
// AMBIL DATA TRIP
// =========================

$trip = mysqli_query($conn,"
SELECT *
FROM trip
WHERE id='$id_trip'
");
$data = mysqli_fetch_assoc($trip);
$status_db = strtolower(trim($data['status']));

// =========================
// PARSE DESKRIPSI
// =========================

$deskripsi_raw = $data['deskripsi'];
preg_match('/Transportasi\s*:\s*(.+)/i', $deskripsi_raw, $m_transport);
$transportasi = isset($m_transport[1]) ? trim($m_transport[1]) : '-';
preg_match('/Budget\s*:\s*(.+)/i', $deskripsi_raw, $m_budget);
$budget = isset($m_budget[1]) ? trim($m_budget[1]) : '-';
preg_match('/Catatan\s*:\s*([\s\S]*)/i', $deskripsi_raw, $m_catatan);
$catatan = isset($m_catatan[1]) ? trim($m_catatan[1]) : '-';
if(empty($catatan)) $catatan = '-';

// =========================
// UPDATE STATUS
// =========================

if(isset($_POST['update_status'])){
    $status = strtolower(trim($_POST['status']));
    $status_lama_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT status FROM trip WHERE id='$id_trip'"));
    $status_lama = strtolower(trim($status_lama_row['status'] ?? ''));

    mysqli_query($conn,"UPDATE trip SET status='$status' WHERE id='$id_trip'");

    // Jika baru saja di-set batal → insert notifikasi wacana
    if($status === 'batal' && $status_lama !== 'batal'){

        // Pastikan tabel & kolom ada
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

        $nama_trip_wacana = mysqli_real_escape_string($conn, $data['nama_trip'] ?? 'Trip ini');
        $wacana_msg = mysqli_real_escape_string($conn,
            "Trip \"{$nama_trip_wacana}\" dibatalkan dan masuk ke daftar wacana kamu 😅"
        );
        $lnk_wacana = "trip_detail.php?id={$id_trip}";

        $cek_lnk_w = mysqli_query($conn,"SHOW COLUMNS FROM `notifications` LIKE 'link_url'");
        if(mysqli_num_rows($cek_lnk_w) > 0){
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message,link_url) VALUES('$user_id','$user_id','$id_trip','wacana','$wacana_msg','$lnk_wacana')");
        } else {
            mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message) VALUES('$user_id','$user_id','$id_trip','wacana','$wacana_msg')");
        }

        // Juga kirim notifikasi ke semua member trip
        $members_q = mysqli_query($conn,"SELECT user_id FROM trip_members WHERE trip_id='$id_trip' AND user_id != '$user_id'");
        if($members_q){
            $cek_lnk_m = mysqli_query($conn,"SHOW COLUMNS FROM `notifications` LIKE 'link_url'");
            $has_lnk_m = mysqli_num_rows($cek_lnk_m) > 0;
            while($mb = mysqli_fetch_assoc($members_q)){
                $mb_id = (int)$mb['user_id'];
                if($has_lnk_m){
                    mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message,link_url) VALUES('$mb_id','$user_id','$id_trip','wacana','$wacana_msg','$lnk_wacana')");
                } else {
                    mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message) VALUES('$mb_id','$user_id','$id_trip','wacana','$wacana_msg')");
                }
            }
        }
    }

    // Jika baru saja di-set selesai → cek level naik
    if($status === 'selesai' && $status_lama !== 'selesai'){

        // Pastikan tabel ada
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

        // Cek kolom link_url sudah ada atau belum
        $cek_lnk2 = mysqli_query($conn,"SHOW COLUMNS FROM `notifications` LIKE 'link_url'");
        $has_lnk2 = mysqli_num_rows($cek_lnk2) > 0;

        // Deteksi kolom creator_id atau user_id pada tabel trip
        $col_check = mysqli_query($conn,"SHOW COLUMNS FROM `trip` LIKE 'creator_id'");
        $creator_col = mysqli_num_rows($col_check) > 0 ? 'creator_id' : 'user_id';

        // Hitung jumlah trip selesai sesudah update
        $selesai_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) as c FROM trip WHERE {$creator_col}='$user_id' AND status='selesai'"
        ))['c'];
        $selesai_sebelum = $selesai_count - 1;

        // Level (sama persis dengan profil.php)
        function getLevelTD($n){
            if($n >= 30) return 'Cirebon Master';
            if($n >= 15) return 'Expert Traveler';
            if($n >= 7)  return 'Traveler';
            if($n >= 3)  return 'Explorer';
            return 'Newbie';
        }
        $level_sebelum = getLevelTD($selesai_sebelum);
        $level_sesudah = getLevelTD($selesai_count);

        if($level_sesudah !== $level_sebelum){
            $lv_msg = mysqli_real_escape_string($conn,
                "Selamat! Level Traveller kamu naik dari {$level_sebelum} ke {$level_sesudah}. Terus jelajahi lebih banyak destinasi!"
            );
            if($has_lnk2){
                mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,type,message,link_url) VALUES('$user_id','$user_id','akun','$lv_msg','profil.php')");
            } else {
                mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,type,message) VALUES('$user_id','$user_id','akun','$lv_msg')");
            }
        }
    }

    header("Location: trip_detail.php?id=".$id_trip);
    exit;
}

// =========================
// HAPUS TRIP
// =========================

if(isset($_POST['hapus_trip'])){
    $cek = mysqli_query($conn,"
        SELECT id FROM trip WHERE id='$id_trip' AND creator_id='$user_id'
    ");
    if(mysqli_num_rows($cek) > 0){
        mysqli_query($conn,"DELETE FROM trip_detail WHERE trip_id='$id_trip'");
        mysqli_query($conn,"DELETE FROM trip WHERE id='$id_trip'");
    }
    header("Location: trip.php");
    exit;
}

// =========================
// TAMBAH DESTINASI
// =========================

if(isset($_POST['tambah_destinasi'])){
    $wisata_baru = (int)$_POST['wisata_baru'];
    $cek_jml = mysqli_query($conn,"SELECT COUNT(*) as total FROM trip_detail WHERE trip_id='$id_trip'");
    $jml = mysqli_fetch_assoc($cek_jml);
    if((int)$jml['total'] < 3){
        $cek_dup = mysqli_query($conn,"SELECT id FROM trip_detail WHERE trip_id='$id_trip' AND wisata_id='$wisata_baru'");
        if(mysqli_num_rows($cek_dup) === 0){
            mysqli_query($conn,"INSERT INTO trip_detail VALUES(NULL,'$id_trip','$wisata_baru')");
        }
    }
    header("Location: trip_detail.php?id=$id_trip");
    exit;
}

// =========================
// HAPUS DESTINASI
// =========================

if(isset($_POST['hapus_destinasi'])){
    $detail_wisata_id = (int)$_POST['detail_wisata_id'];
    $cek_jml2 = mysqli_query($conn,"SELECT COUNT(*) as total FROM trip_detail WHERE trip_id='$id_trip'");
    $jml2 = mysqli_fetch_assoc($cek_jml2);
    if((int)$jml2['total'] > 1){
        mysqli_query($conn,"DELETE FROM trip_detail WHERE trip_id='$id_trip' AND wisata_id='$detail_wisata_id' LIMIT 1");
    }
    header("Location: trip_detail.php?id=$id_trip");
    exit;
}

// =========================
// AMBIL DESTINASI
// =========================

$detail = mysqli_query($conn,"
SELECT wisata.*
FROM trip_detail
JOIN wisata ON trip_detail.wisata_id = wisata.id
WHERE trip_detail.trip_id='$id_trip'
");
$jml_dest = mysqli_num_rows($detail);
mysqli_data_seek($detail, 0);

$existing_ids = [];
$temp = mysqli_query($conn,"SELECT wisata_id FROM trip_detail WHERE trip_id='$id_trip'");
while($row_tmp = mysqli_fetch_assoc($temp)){
    $existing_ids[] = $row_tmp['wisata_id'];
}
$semua_wisata = mysqli_query($conn,"SELECT id, nama FROM wisata ORDER BY nama ASC");

// STATUS COLOR MAP
$status_color = [
    'planning' => '#64748b',
    'ongoing'  => '#0ea5e9',
    'selesai'  => '#22c55e',
    'batal'    => '#ef4444',
];
$color = $status_color[$status_db] ?? '#64748b';


$user_id_nav = $_SESSION['user_id'] ?? 0;
$_user_nav_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$user_id_nav'"));
$_nav_foto = !empty($_user_nav_row['foto']) ? $_user_nav_row['foto'] : '';
$_nav_initial = strtoupper(mb_substr($_user_nav_row['nama'] ?? $_SESSION['nama'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Trip - TRAVA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>

@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Manrope:wght@400;500;700&display=swap');

*{ margin:0; padding:0; box-sizing:border-box; }

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


.active{ color:#17375e; }

/* CONTAINER */
.container{
    width:88%;
    margin:auto;
    padding:36px 0 80px;
}

/* BACK */
.back-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    text-decoration:none;
    color:#17375e;
    font-size:14px;
    font-weight:700;
    margin-bottom:28px;
    transition:0.3s;
}
.back-link:hover{ opacity:0.7; }

/* ============================
   HERO CARD (gambar kiri + info kanan)
   ============================ */

.trip-hero{
    border-radius:28px;
    overflow:hidden;
    display:grid;
    grid-template-columns:1fr 1.6fr;
    min-height:220px;
    margin-bottom:28px;
    box-shadow:0 16px 40px rgba(23,55,94,0.12);
}

/* Gambar sisi kiri — pakai gambar destinasi pertama */
.trip-hero-img{
    position:relative;
    background:#17375e;
    overflow:hidden;
}
.trip-hero-img img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

/* Sisi kanan — navy */
.trip-hero-info{
    background:linear-gradient(135deg,#17375e,#234d7d);
    padding:38px 40px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    position:relative;
    overflow:hidden;
}

.trip-hero-info::before{
    content:'';
    position:absolute;
    inset:0;
    background:repeating-linear-gradient(
        135deg,
        rgba(255,255,255,0.02),
        rgba(255,255,255,0.02) 1px,
        transparent 1px,
        transparent 28px
    );
}

.trip-hero-info > *{ position:relative; z-index:2; }

.trip-hero-info h1{
    font-family:'Cormorant Garamond',serif;
    font-size:34px;
    color:white;
    margin-bottom:14px;
    line-height:1.1;
}

.status-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:9px 18px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
    color:white;
    margin-bottom:18px;
    width:fit-content;
}

.trip-hero-meta{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.meta-row{
    display:flex;
    align-items:center;
    gap:12px;
    color:rgba(255,255,255,0.85);
    font-size:14px;
}

.meta-row i{
    width:20px;
    color:rgba(255,255,255,0.6);
    font-size:14px;
}


/* ============================
   LAYOUT DUA KOLOM
   ============================ */

.detail-layout{
    display:grid;
    grid-template-columns:1.4fr 1fr;
    gap:24px;
    align-items:start;
}


/* ============================
   KIRI: Destinasi + Tambah
   ============================ */

.dest-section-title{
    font-family:'Cormorant Garamond',serif;
    font-size:20px;
    color:#17375e;
    margin-bottom:16px;
    display:flex;
    align-items:center;
    gap:10px;
}

.dest-section-title i{ font-size:18px; }

/* Destinasi row */
.dest-list{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-bottom:16px;
}

.dest-row{
    background:white;
    border-radius:20px;
    display:flex;
    align-items:center;
    gap:14px;
    padding:14px;
    box-shadow:0 4px 14px rgba(15,23,42,0.05);
    border:1px solid #f1f5f9;
    transition:0.3s;
}

.dest-row:hover{ transform:translateY(-2px); box-shadow:0 8px 20px rgba(15,23,42,0.08); }

.dest-row img{
    width:64px;
    height:64px;
    object-fit:cover;
    border-radius:14px;
    flex-shrink:0;
}

.dest-row-info{
    flex:1;
    min-width:0;
}

.dest-row-info h4{
    font-family:'Cormorant Garamond',serif;
    font-size:17px;
    color:#1e293b;
    margin-bottom:4px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.dest-row-info .dest-loc{
    color:#64748b;
    font-size:12px;
    margin-bottom:5px;
}

.dest-row-info .dest-rating{
    color:#f59e0b;
    font-size:12px;
    font-weight:700;
}

.dest-actions{
    display:flex;
    flex-direction:column;
    gap:6px;
    align-items:flex-end;
    flex-shrink:0;
}

.lihat-btn{
    text-decoration:none;
    padding:8px 14px;
    background:#f1f5f9;
    color:#17375e;
    border-radius:12px;
    font-size:12px;
    font-weight:700;
    transition:0.3s;
    white-space:nowrap;
}
.lihat-btn:hover{ background:#e2e8f0; }

.hapus-dest-btn{
    border:none;
    background:#fef2f2;
    color:#dc2626;
    padding:6px 12px;
    border-radius:12px;
    font-size:11px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
    white-space:nowrap;
}
.hapus-dest-btn:hover{ background:#fee2e2; }

/* Tambah destinasi */
.tambah-dest-box{
    background:white;
    border-radius:20px;
    border:2px dashed #cbd5e1;
    padding:20px;
    margin-top:4px;
}

.tambah-dest-box h4{
    font-size:14px;
    font-weight:700;
    color:#334155;
    margin-bottom:6px;
    display:flex;
    align-items:center;
    gap:8px;
}

.limit-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    margin-bottom:12px;
}
.limit-badge.ok{ background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.limit-badge.full{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

.tambah-form{
    display:flex;
    gap:10px;
}

.tambah-form select{
    flex:1;
    padding:12px 16px;
    border:1px solid #e2e8f0;
    border-radius:14px;
    font-size:13px;
    font-family:'Manrope',sans-serif;
    color:#1e293b;
    background:#f8fafc;
    outline:none;
    transition:0.3s;
}
.tambah-form select:focus{
    border-color:#17375e;
    background:white;
    box-shadow:0 0 0 3px rgba(23,55,94,0.08);
}

.tambah-form button{
    padding:12px 18px;
    background:#17375e;
    color:white;
    border:none;
    border-radius:14px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
    display:flex;
    align-items:center;
    gap:6px;
    white-space:nowrap;
}
.tambah-form button:hover{ transform:translateY(-2px); }
.tambah-form button:disabled{ opacity:0.4; cursor:not-allowed; transform:none; }


/* ============================
   KANAN: Detail Perjalanan
   ============================ */

.detail-right-card{
    background:white;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 8px 24px rgba(15,23,42,0.06);
    border:1px solid #f1f5f9;
}

.detail-right-header{
    background:linear-gradient(135deg,#17375e,#234d7d);
    padding:22px 26px;
    color:white;
    font-family:'Cormorant Garamond',serif;
    font-size:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.detail-right-body{
    padding:20px 24px;
}

.detail-item{
    display:flex;
    align-items:flex-start;
    gap:14px;
    padding:14px 0;
    border-bottom:1px solid #f1f5f9;
}

.detail-item:last-child{ border-bottom:none; }

.detail-item-icon{
    width:38px;
    height:38px;
    border-radius:12px;
    background:#f1f5f9;
    display:flex;
    justify-content:center;
    align-items:center;
    color:#17375e;
    font-size:14px;
    flex-shrink:0;
    margin-top:1px;
}

.detail-item-content{}

.detail-item-label{
    font-size:11px;
    font-weight:700;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:0.07em;
    margin-bottom:4px;
}

.detail-item-val{
    font-size:14px;
    font-weight:700;
    color:#1e293b;
    line-height:1.5;
}

.detail-item-val.green{ color:#16a34a; }
.detail-item-val.catatan{
    font-weight:500;
    color:#475569;
    font-size:13px;
    line-height:1.8;
    white-space:pre-line;
}

/* Status badge dalam detail */
.inline-status{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    color:white;
}

/* Status update slider */
.status-update-section{
    padding:16px 24px 0;
    border-top:1px solid #f1f5f9;
    background:#fafbfc;
}

.status-label-sm{
    font-size:11px;
    font-weight:700;
    color:#94a3b8;
    text-transform:uppercase;
    letter-spacing:0.07em;
    margin-bottom:10px;
}

.range-labels{
    display:flex;
    justify-content:space-between;
    font-size:11px;
    font-weight:700;
    color:#64748b;
    margin-bottom:6px;
}

.trip-range{
    width:100%;
    cursor:pointer;
    accent-color:#17375e;
    margin-bottom:12px;
}

.save-status-btn{
    width:100%;
    padding:13px;
    border:none;
    border-radius:14px;
    background:linear-gradient(135deg,#17375e,#234d7d);
    color:white;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
}
.save-status-btn:hover{ transform:translateY(-1px); }

/* Action buttons */
.action-btns{
    padding:10px 24px 24px;
    background:#fafbfc;
    display:flex;
    flex-direction:column;
    gap:8px;
}

.batalkan-btn{
    width:100%;
    padding:14px;
    border:2px solid #ef4444;
    background:white;
    color:#ef4444;
    border-radius:16px;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}
.batalkan-btn:hover{ background:#fef2f2; }

.hapus-btn{
    width:100%;
    padding:13px;
    border:none;
    background:#fef2f2;
    color:#dc2626;
    border-radius:14px;
    font-size:13px;
    font-weight:700;
    cursor:pointer;
    transition:0.3s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}
.hapus-btn:hover{ background:#fee2e2; }

.kembali-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    width:100%;
    padding:14px;
    border:1px solid #e2e8f0;
    background:white;
    color:#64748b;
    border-radius:16px;
    font-size:14px;
    font-weight:700;
    transition:0.3s;
    box-sizing:border-box;
}
.kembali-btn:hover{ background:#f8fafc; color:#1e293b; }


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


/* RESPONSIVE */
@media(max-width:900px){
    .trip-hero{ grid-template-columns:1fr; }
    .trip-hero-img{ height:200px; }
    .detail-layout{ grid-template-columns:1fr; }
}
@media(max-width:768px){
    .navbar{ flex-direction:column; gap:18px; }
    .nav-menu{ flex-wrap:wrap; justify-content:center; }
    .footer-grid{ grid-template-columns:1fr; }
    .tambah-form{ flex-direction:column; }
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




<!-- CONTAINER -->
<div class="container">

    <a href="trip.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Kembali ke Trip
    </a>


    <!-- HERO CARD: gambar kiri, info kanan -->
    <?php
    // Ambil gambar destinasi pertama
    $first_dest = mysqli_query($conn,"
        SELECT wisata.gambar, wisata.lokasi
        FROM trip_detail
        JOIN wisata ON trip_detail.wisata_id = wisata.id
        WHERE trip_detail.trip_id='$id_trip'
        LIMIT 1
    ");
    $first = mysqli_fetch_assoc($first_dest);
    ?>

    <div class="trip-hero">

        <!-- KIRI: foto -->
        <div class="trip-hero-img">
            <?php if($first && $first['gambar']) : ?>
            <img src="assets/img/<?= htmlspecialchars($first['gambar']); ?>" alt="Foto Destinasi">
            <?php else : ?>
            <div style="background:#17375e;width:100%;height:100%;"></div>
            <?php endif; ?>
        </div>

        <!-- KANAN: info trip -->
        <div class="trip-hero-info">

            <h1><?= htmlspecialchars($data['nama_trip']); ?></h1>

            <div class="status-badge" style="background:<?= $color; ?>;">
                <i class="fa-solid fa-circle-check"></i>
                <?= strtoupper($status_db); ?>
            </div>

            <div class="trip-hero-meta">
                <div class="meta-row">
                    <i class="fa-solid fa-calendar"></i>
                    <?= htmlspecialchars($data['tanggal']); ?>
                </div>
                <?php if($first && $first['lokasi']) : ?>
                <div class="meta-row">
                    <i class="fa-solid fa-location-dot"></i>
                    <?= htmlspecialchars($first['lokasi']); ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

    </div>


    <!-- LAYOUT DUA KOLOM -->
    <div class="detail-layout">


        <!-- KIRI: Destinasi dalam Trip -->
        <div>

            <div class="dest-section-title">
                <i class="fa-solid fa-location-dot"></i>
                Destinasi dalam Trip
            </div>

            <div class="dest-list">

            <?php while($d = mysqli_fetch_assoc($detail)) : ?>

                <div class="dest-row">

                    <img src="assets/img/<?= htmlspecialchars($d['gambar']); ?>" alt="<?= htmlspecialchars($d['nama']); ?>">

                    <div class="dest-row-info">
                        <h4><?= htmlspecialchars($d['nama']); ?></h4>
                        <div class="dest-loc">
                            <i class="fa-solid fa-location-dot" style="font-size:11px;"></i>
                            <?= htmlspecialchars($d['lokasi']); ?>
                        </div>
                        <div class="dest-rating">
                            <i class="fa-solid fa-star"></i>
                            <?= number_format($d['rating_avg'],1); ?>
                            (<?= $d['rating_count']; ?> review)
                        </div>
                    </div>

                    <div class="dest-actions">
                        <a href="detail.php?id=<?= $d['id']; ?>" class="lihat-btn">
                            Lihat Detail
                        </a>
                        <?php if($jml_dest > 1) : ?>
                        <form method="POST" onsubmit="return confirm('Hapus destinasi ini?');" style="margin:0;">
                            <input type="hidden" name="detail_wisata_id" value="<?= $d['id']; ?>">
                            <button type="submit" name="hapus_destinasi" class="hapus-dest-btn">
                                <i class="fa-solid fa-xmark"></i> Hapus
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>

            </div>


            <!-- TAMBAH DESTINASI -->
            <div class="tambah-dest-box">

                <h4><i class="fa-solid fa-plus"></i> Tambah Destinasi</h4>

                <?php if($jml_dest >= 3) : ?>

                <div class="limit-badge full">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Maksimal 3 destinasi sudah tercapai
                </div>
                <p style="font-size:13px;color:#64748b;">Hapus salah satu destinasi untuk menambah yang baru.</p>

                <?php else : ?>

                <div class="limit-badge ok">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= $jml_dest; ?>/3 destinasi — sisa <?= 3 - $jml_dest; ?> slot
                </div>

                <form method="POST" class="tambah-form">
                    <select name="wisata_baru" required>
                        <option value="">-- Pilih Destinasi --</option>
                        <?php while($w = mysqli_fetch_assoc($semua_wisata)) : ?>
                        <?php if(!in_array($w['id'], $existing_ids)) : ?>
                        <option value="<?= $w['id']; ?>"><?= htmlspecialchars($w['nama']); ?></option>
                        <?php endif; ?>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="tambah_destinasi">
                        <i class="fa-solid fa-plus"></i> Tambah
                    </button>
                </form>

                <?php endif; ?>

            </div>

        </div>


        <!-- KANAN: Detail Perjalanan -->
        <div>

            <div class="detail-right-card">

                <!-- HEADER -->
                <div class="detail-right-header">
                    <i class="fa-solid fa-circle-info"></i>
                    Detail Perjalanan
                </div>

                <!-- ISI -->
                <div class="detail-right-body">

                    <div class="detail-item">
                        <div class="detail-item-icon"><i class="fa-solid fa-car"></i></div>
                        <div class="detail-item-content">
                            <div class="detail-item-label">Transportasi</div>
                            <div class="detail-item-val"><?= htmlspecialchars($transportasi); ?></div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-item-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="detail-item-content">
                            <div class="detail-item-label">Budget</div>
                            <div class="detail-item-val green"><?= htmlspecialchars($budget); ?></div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-item-icon"><i class="fa-solid fa-calendar"></i></div>
                        <div class="detail-item-content">
                            <div class="detail-item-label">Tanggal Berangkat</div>
                            <div class="detail-item-val"><?= htmlspecialchars($data['tanggal']); ?></div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-item-icon"><i class="fa-solid fa-note-sticky"></i></div>
                        <div class="detail-item-content">
                            <div class="detail-item-label">Catatan Trip</div>
                            <div class="detail-item-val catatan"><?= htmlspecialchars($catatan); ?></div>
                        </div>
                    </div>

                </div>


                <!-- UPDATE STATUS -->
                <div class="status-update-section">

                    <div class="status-label-sm">Update Status</div>

                    <form method="POST">

                        <div class="range-labels">
                            <span>Planning</span>
                            <span>Selesai</span>
                            <span>Batal</span>
                        </div>

                        <input
                        type="range"
                        min="1" max="3" step="1"
                        value="<?=
                            $status_db == 'planning' ? 1 :
                            ($status_db == 'selesai' ? 2 : 3)
                        ?>"
                        class="trip-range"
                        oninput="updateStatus(this.value)">

                        <input type="hidden" name="status" id="statusInput" value="<?= $status_db; ?>">

                        <button type="submit" name="update_status" class="save-status-btn">
                            Simpan Status
                        </button>

                    </form>

                </div>


                <!-- ACTION BUTTONS -->
                <div class="action-btns">

                    <form method="POST" onsubmit="return confirm('Hapus trip ini permanen? Semua data akan dihapus.');" style="margin:0;">
                        <button type="submit" name="hapus_trip" class="hapus-btn">
                            <i class="fa-solid fa-trash"></i>
                            Hapus Trip
                        </button>
                    </form>

                </div>

            </div>

        </div>

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



<script>
function updateStatus(value){
    value = parseInt(value);
    let status = value===1 ? 'planning' : (value===2 ? 'selesai' : 'batal');
    document.getElementById('statusInput').value = status;
}
window.onload = function(){
    const range = document.querySelector('.trip-range');
    if(range) updateStatus(range.value);
}
</script>

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
</script>
</body>
</html>
