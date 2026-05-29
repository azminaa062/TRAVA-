<?php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_trip = (int)($_GET['id'] ?? 0);
$active_tab = $_GET['tab'] ?? 'chat';
$msg = $_GET['msg'] ?? '';

// Helper: render avatar img atau inisial fallback
function avatarSmHtml($foto, $nama, $extra_class=''){
    $fallback = strtoupper(substr($nama,0,1));
    if(!empty($foto)){
        $src = 'assets/img/profil/'.htmlspecialchars($foto);
        return '<div class="avatar-sm '.$extra_class.'"><img src="'.$src.'" alt="'.htmlspecialchars($nama).'" onerror="this.parentElement.innerHTML=\''.htmlspecialchars($fallback).'\'"></div>';
    }
    return '<div class="avatar-sm '.$extra_class.'">'.$fallback.'</div>';
}

// ========================
// AUTO-CREATE TABLES
// ========================
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('creator','member') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_member` (`trip_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('group','personal') DEFAULT 'group',
  `to_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_itinerary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hari` int(11) NOT NULL DEFAULT 1,
  `waktu` varchar(10) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_vote_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vote_id` int(11) NOT NULL,
  `opsi` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_vote_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vote_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`vote_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `trip_budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_item` varchar(255) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0,
  `kategori` varchar(100) DEFAULT 'Lainnya',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ========================
// AMBIL DATA TRIP
// ========================
$trip_q = mysqli_query($conn,"SELECT trip.*, users.nama AS creator_nama FROM trip JOIN users ON trip.creator_id=users.id WHERE trip.id='$id_trip'");
if(mysqli_num_rows($trip_q) == 0){
    header("Location: trip.php");
    exit;
}
$trip = mysqli_fetch_assoc($trip_q);
$is_creator = ($trip['creator_id'] == $user_id);

// Cek member
$cek_member = mysqli_query($conn,"SELECT id FROM trip_members WHERE trip_id='$id_trip' AND user_id='$user_id'");
$is_member  = ($is_creator || mysqli_num_rows($cek_member) > 0);

if(!$is_member){
    header("Location: trip.php");
    exit;
}

// ========================
// DAFTAR MEMBERS
// ========================
$members_q = mysqli_query($conn,"
    SELECT users.id, users.nama, users.email, users.foto, 'member' as role, trip_members.joined_at
    FROM trip_members
    JOIN users ON trip_members.user_id = users.id
    WHERE trip_members.trip_id='$id_trip'
");
$members = [];
while($m = mysqli_fetch_assoc($members_q)){ $members[] = $m; }

// Creator sebagai first member
$creator_q = mysqli_query($conn,"SELECT id, nama, email, foto FROM users WHERE id='".$trip['creator_id']."'");
$creator_data = mysqli_fetch_assoc($creator_q);

// All members termasuk creator
$all_members_list = [];
$all_members_list[] = ['id'=>$creator_data['id'],'nama'=>$creator_data['nama'],'email'=>$creator_data['email'],'foto'=>$creator_data['foto'],'role'=>'creator'];
foreach($members as $m){ $all_members_list[] = $m; }

// ========================
// ITINERARY
// ========================
$itinerary_q = mysqli_query($conn,"
    SELECT trip_itinerary.*, users.nama AS pembuat
    FROM trip_itinerary
    JOIN users ON trip_itinerary.user_id = users.id
    WHERE trip_itinerary.trip_id='$id_trip'
    ORDER BY trip_itinerary.hari ASC, trip_itinerary.waktu ASC
");
$itinerary_by_day = [];
while($it = mysqli_fetch_assoc($itinerary_q)){
    $itinerary_by_day[$it['hari']][] = $it;
}
$max_day = !empty($itinerary_by_day) ? max(array_keys($itinerary_by_day)) : 1;

// ========================
// VOTING
// ========================
$votes_q = mysqli_query($conn,"
    SELECT trip_votes.*, users.nama AS creator_nama
    FROM trip_votes
    JOIN users ON trip_votes.creator_id = users.id
    WHERE trip_votes.trip_id='$id_trip'
    ORDER BY trip_votes.created_at DESC
");
$votes = [];
while($v = mysqli_fetch_assoc($votes_q)){
    // Ambil opsi
    $opts_q = mysqli_query($conn,"SELECT * FROM trip_vote_options WHERE vote_id='".$v['id']."'");
    $v['options'] = [];
    while($o = mysqli_fetch_assoc($opts_q)){
        // Hitung vote per opsi
        $cnt_q = mysqli_query($conn,"SELECT COUNT(*) as c FROM trip_vote_responses WHERE option_id='".$o['id']."'");
        $cnt = mysqli_fetch_assoc($cnt_q);
        $o['count'] = (int)$cnt['c'];
        // Cek sudah vote?
        $myv_q = mysqli_query($conn,"SELECT id FROM trip_vote_responses WHERE vote_id='".$v['id']."' AND user_id='$user_id'");
        $v['my_vote_option'] = null;
        $v['options'][] = $o;
    }
    // Cek my vote
    $myv_q2 = mysqli_query($conn,"SELECT option_id FROM trip_vote_responses WHERE vote_id='".$v['id']."' AND user_id='$user_id'");
    if($myv_row = mysqli_fetch_assoc($myv_q2)){
        $v['my_vote_option'] = $myv_row['option_id'];
    }
    // Total votes
    $total_q = mysqli_query($conn,"SELECT COUNT(*) as t FROM trip_vote_responses WHERE vote_id='".$v['id']."'");
    $total_r  = mysqli_fetch_assoc($total_q);
    $v['total_votes'] = (int)$total_r['t'];
    $votes[] = $v;
}

// ========================
// BUDGET
// ========================
$budget_q = mysqli_query($conn,"
    SELECT trip_budget_items.*, users.nama AS pembuat
    FROM trip_budget_items
    JOIN users ON trip_budget_items.user_id = users.id
    WHERE trip_budget_items.trip_id='$id_trip'
    ORDER BY trip_budget_items.created_at DESC
");
$budget_items = [];
$total_budget = 0;
while($b = mysqli_fetch_assoc($budget_q)){
    $budget_items[] = $b;
    $total_budget += $b['jumlah'];
}

// Budget per member
$member_count = count($all_members_list);
$split_amount = $member_count > 0 ? $total_budget / $member_count : 0;

// Parse trip budget
preg_match('/Budget\s*:\s*(.+)/i', $trip['deskripsi'], $m_budget);
$trip_budget_str = isset($m_budget[1]) ? trim($m_budget[1]) : 'Tidak ditentukan';

// ========================
// MAP - COORDINATES
// ========================
$map_dest_q = mysqli_query($conn,"
    SELECT wisata.nama, wisata.lokasi, wisata.gambar
    FROM trip_detail
    JOIN wisata ON trip_detail.wisata_id = wisata.id
    WHERE trip_detail.trip_id='$id_trip'
");
$map_destinations = [];
while($md = mysqli_fetch_assoc($map_dest_q)){ $map_destinations[] = $md; }

// Status color
$status_colors = ['planning'=>'#64748b','ongoing'=>'#0ea5e9','selesai'=>'#22c55e','batal'=>'#ef4444'];
$status_color  = $status_colors[strtolower($trip['status'])] ?? '#64748b';

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
<title>Trip Group - <?= htmlspecialchars($trip['nama_trip']); ?> - TRAVA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>

@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Manrope:wght@400;500;600;700&display=swap');

*{ margin:0; padding:0; box-sizing:border-box; }

:root{
    --navy: #17375e;
    --navy2: #234d7d;
    --bg: #f5f7fb;
    --white: #ffffff;
    --border: #e8edf5;
    --text: #1e293b;
    --muted: #64748b;
    --green: #22c55e;
    --red: #ef4444;
    --orange: #f59e0b;
}

body{
    background:var(--bg);
    font-family:'Manrope',sans-serif;
    color:var(--text);
    min-height:100vh;
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




/* ===== PROFILE DROPDOWN ===== */
.profile-wrapper{ position:relative; display:inline-flex; align-items:center; }
.profile-avatar-btn{ background:none; border:none; cursor:pointer; padding:0; width:38px; height:38px; border-radius:50%; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.profile-avatar-btn img{ width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid var(--navy); }.profile-avatar-initial{width:40px;height:40px;border-radius:50%;background:#17375e;color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;border:2px solid #c8a84b;cursor:pointer;text-transform:uppercase;flex-shrink:0;}
.profile-dropdown{ position:absolute; top:50px; right:0; width:200px; background:white; border-radius:12px; box-shadow:0 8px 32px rgba(15,23,42,0.16); z-index:9999; display:none; overflow:hidden; padding:8px 0; }
.profile-dropdown.open{ display:block; }
.profile-dd-item{ display:flex; align-items:center; gap:10px; padding:11px 18px; color:var(--text); text-decoration:none; font-size:13px; font-weight:600; transition:0.15s; }
.profile-dd-item:hover{ background:#f8fafc; color:var(--navy); }
.profile-dd-item i{ color:var(--navy); width:16px; }
.profile-dd-divider{ height:1px; background:#f1f5f9; margin:4px 0; }

.nav-menu a{ text-decoration:none; color:var(--muted); font-size:14px; font-weight:700; transition:0.2s; }
.nav-menu .active, .nav-menu a:hover{ color:var(--navy); }

/* ===== HERO STRIP ===== */
.hero-strip{
    background:linear-gradient(135deg,#17375e,#234d7d);
    padding:28px 5%; display:flex; align-items:center; gap:20px;
    flex-wrap:wrap;
}
.hero-strip .back-link{
    color:rgba(255,255,255,0.7); text-decoration:none; font-size:13px;
    font-weight:700; display:flex; align-items:center; gap:6px; transition:0.2s;
    flex-shrink:0;
}
.hero-strip .back-link:hover{ color:white; }
.hero-strip .trip-title{
    font-family:'Cormorant Garamond',serif; font-size:26px; color:white;
    font-weight:700; flex:1;
}
.hero-strip .status-pill{
    padding:7px 16px; border-radius:999px; font-size:12px; font-weight:700;
    color:white; background:var(--status-color,#64748b);
}
.hero-strip .members-pill{
    display:flex; align-items:center; gap:6px;
    background:rgba(255,255,255,0.12); color:white;
    padding:7px 14px; border-radius:999px; font-size:12px; font-weight:700;
}

/* ===== LAYOUT ===== */
.main-layout{
    display:grid;
    grid-template-columns:220px 1fr;
    min-height:calc(100vh - 140px);
}

/* ===== SIDEBAR ===== */
.sidebar{
    background:white;
    border-right:1px solid var(--border);
    padding:24px 0;
    position:sticky; top:70px; height:calc(100vh - 70px);
    overflow-y:auto;
}

.sidebar-section{ margin-bottom:8px; padding:0 16px; }
.sidebar-label{
    font-size:10px; font-weight:700; color:var(--muted);
    text-transform:uppercase; letter-spacing:0.1em;
    padding:12px 8px 8px;
}

.tab-btn{
    display:flex; align-items:center; gap:12px;
    width:100%; padding:12px 14px; border:none; background:none;
    border-radius:14px; font-family:'Manrope',sans-serif;
    font-size:13.5px; font-weight:700; color:var(--muted);
    cursor:pointer; transition:0.2s; text-align:left;
    text-decoration:none;
}
.tab-btn i{ width:18px; font-size:15px; }
.tab-btn:hover{ background:#f1f5f9; color:var(--navy); }
.tab-btn.active{ background:#eff6ff; color:var(--navy); }
.tab-btn .badge{
    margin-left:auto; background:var(--navy); color:white;
    font-size:10px; padding:2px 7px; border-radius:999px;
    font-weight:700;
}

.sidebar-divider{
    height:1px; background:var(--border);
    margin:10px 16px;
}

/* ===== MEMBERS MINI LIST ===== */
.member-mini{
    padding:6px 24px; display:flex; align-items:center; gap:10px;
    font-size:12px; color:var(--muted);
}
.avatar-sm{
    width:28px; height:28px; border-radius:50%;
    background:linear-gradient(135deg,#17375e,#234d7d);
    display:flex; align-items:center; justify-content:center;
    color:white; font-size:11px; font-weight:700; flex-shrink:0;
    text-transform:uppercase; overflow:hidden;
}
.avatar-sm img{ width:28px; height:28px; border-radius:50%; object-fit:cover; display:block; }
.avatar-sm.orange{ background:linear-gradient(135deg,#f59e0b,#d97706); }
.avatar-sm.green{ background:linear-gradient(135deg,#22c55e,#16a34a); }
.avatar-sm.red{ background:linear-gradient(135deg,#ef4444,#dc2626); }

/* ===== CONTENT AREA ===== */
.content-area{
    padding:28px 32px;
    max-width:900px;
}

/* ===== SECTION HEADER ===== */
.section-header{
    display:flex; align-items:center; gap:12px;
    margin-bottom:22px; flex-wrap:wrap;
}
.section-icon{
    width:44px; height:44px; border-radius:14px;
    background:linear-gradient(135deg,#17375e,#234d7d);
    display:flex; align-items:center; justify-content:center;
    color:white; font-size:17px;
}
.section-header h2{
    font-family:'Cormorant Garamond',serif;
    font-size:24px; color:var(--navy);
}
.section-header p{ font-size:13px; color:var(--muted); }

/* ===== ALERT MSG ===== */
.alert{
    padding:12px 18px; border-radius:14px; font-size:13px;
    font-weight:700; margin-bottom:18px; display:flex; align-items:center; gap:10px;
}
.alert.success{ background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.alert.error{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.alert.warning{ background:#fffbeb; color:#d97706; border:1px solid #fde68a; }

/* ===== CARD ===== */
.card{
    background:white; border-radius:20px; border:1px solid var(--border);
    box-shadow:0 4px 16px rgba(15,23,42,0.04);
    overflow:hidden; margin-bottom:16px;
}
.card-header{
    padding:16px 20px; display:flex; align-items:center;
    justify-content:space-between; border-bottom:1px solid var(--border);
}
.card-header h3{
    font-size:15px; font-weight:700; color:var(--navy);
    display:flex; align-items:center; gap:8px;
}
.card-body{ padding:20px; }

/* ===== FORM ELEMENTS ===== */
.form-row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.form-group{ margin-bottom:14px; }
.form-group label{ display:block; font-size:12px; font-weight:700; color:var(--muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.06em; }
.form-group input, .form-group select, .form-group textarea{
    width:100%; padding:12px 16px; border:1px solid var(--border);
    border-radius:12px; font-size:13px; font-family:'Manrope',sans-serif;
    background:#f8fafc; color:var(--text); outline:none; transition:0.2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus{
    border-color:var(--navy); background:white; box-shadow:0 0 0 3px rgba(23,55,94,0.08);
}
.form-group textarea{ resize:vertical; height:80px; }

.btn{
    display:inline-flex; align-items:center; gap:8px;
    padding:11px 18px; border-radius:12px; font-size:13px; font-weight:700;
    cursor:pointer; border:none; transition:0.2s; font-family:'Manrope',sans-serif;
}
.btn:hover{ transform:translateY(-1px); }
.btn-primary{ background:var(--navy); color:white; }
.btn-primary:hover{ background:var(--navy2); }
.btn-danger{ background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.btn-danger:hover{ background:#fee2e2; }
.btn-sm{ padding:7px 13px; font-size:12px; }
.btn-full{ width:100%; justify-content:center; }

/* ===========================
   TAB: CHAT
=========================== */
.chat-layout{ display:grid; grid-template-columns:200px 1fr; gap:0; height:500px; border-radius:20px; overflow:hidden; border:1px solid var(--border); }
.chat-sidebar{ background:#f8fafc; border-right:1px solid var(--border); overflow-y:auto; }
.chat-sidebar-header{ padding:14px 16px; font-size:12px; font-weight:700; color:var(--muted); border-bottom:1px solid var(--border); text-transform:uppercase; letter-spacing:0.08em; }
.chat-room-btn{
    display:flex; align-items:center; gap:10px; padding:12px 14px;
    cursor:pointer; transition:0.2s; border:none; background:none;
    width:100%; text-align:left; font-family:'Manrope',sans-serif;
}
.chat-room-btn:hover{ background:#eff6ff; }
.chat-room-btn.active{ background:#eff6ff; }
.chat-room-btn .room-name{ font-size:13px; font-weight:700; color:var(--navy); }
.chat-room-btn .room-sub{ font-size:11px; color:var(--muted); }
.chat-main{ display:flex; flex-direction:column; background:white; }
.chat-header{ padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
.chat-header .room-title{ font-size:14px; font-weight:700; color:var(--navy); }
.chat-header .room-desc{ font-size:12px; color:var(--muted); }
.chat-messages{ flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:10px; background:#f8fafc; }
.msg-bubble{ display:flex; gap:8px; max-width:80%; }
.msg-bubble.mine{ flex-direction:row-reverse; align-self:flex-end; }
.msg-avatar{ width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#17375e,#234d7d); display:flex; align-items:center; justify-content:center; color:white; font-size:12px; font-weight:700; flex-shrink:0; text-transform:uppercase; overflow:hidden; }
.msg-avatar img{ width:32px; height:32px; border-radius:50%; object-fit:cover; display:block; }
.msg-content{ background:white; padding:10px 14px; border-radius:16px; border-radius-topleft:4px; box-shadow:0 2px 8px rgba(15,23,42,0.06); border:1px solid var(--border); }
.msg-bubble.mine .msg-content{ background:var(--navy); color:white; border:none; }
.msg-name{ font-size:11px; font-weight:700; color:var(--navy); margin-bottom:3px; }
.msg-bubble.mine .msg-name{ color:rgba(255,255,255,0.7); }
.msg-text{ font-size:13px; line-height:1.5; }
.msg-time{ font-size:10px; color:var(--muted); margin-top:3px; }
.msg-bubble.mine .msg-time{ color:rgba(255,255,255,0.5); text-align:right; }
.chat-input-area{ padding:12px 16px; border-top:1px solid var(--border); display:flex; gap:10px; background:white; }
.chat-input{ flex:1; padding:11px 16px; border:1px solid var(--border); border-radius:12px; font-size:13px; font-family:'Manrope',sans-serif; outline:none; background:#f8fafc; transition:0.2s; }
.chat-input:focus{ border-color:var(--navy); background:white; }
.chat-send-btn{ padding:11px 16px; background:var(--navy); color:white; border:none; border-radius:12px; cursor:pointer; transition:0.2s; font-size:14px; }
.chat-send-btn:hover{ background:var(--navy2); }

/* ===========================
   TAB: ITINERARY
=========================== */
.day-header{
    background:linear-gradient(135deg,#17375e,#234d7d);
    color:white; padding:12px 20px; border-radius:14px;
    font-family:'Cormorant Garamond',serif; font-size:18px;
    margin-bottom:12px; display:flex; align-items:center; gap:10px;
}
.iti-item{
    background:white; border-radius:16px; border:1px solid var(--border);
    padding:14px 18px; margin-bottom:10px; display:flex; align-items:flex-start; gap:14px;
    transition:0.2s; box-shadow:0 2px 8px rgba(15,23,42,0.04);
}
.iti-item:hover{ transform:translateY(-1px); box-shadow:0 6px 16px rgba(15,23,42,0.08); }
.iti-time{
    background:#eff6ff; color:var(--navy); padding:6px 10px;
    border-radius:10px; font-size:12px; font-weight:700; flex-shrink:0;
    min-width:56px; text-align:center;
}
.iti-info{ flex:1; }
.iti-aktivitas{ font-size:14px; font-weight:700; color:var(--text); margin-bottom:3px; }
.iti-lokasi{ font-size:12px; color:var(--muted); display:flex; align-items:center; gap:5px; margin-bottom:3px; }
.iti-catatan{ font-size:12px; color:#94a3b8; font-style:italic; }
.iti-pembuat{ font-size:11px; color:var(--muted); }
.iti-del{ background:none; border:none; color:#dc2626; cursor:pointer; padding:5px; border-radius:8px; transition:0.2s; }
.iti-del:hover{ background:#fef2f2; }

/* ===========================
   TAB: VOTING
=========================== */
.vote-card{
    background:white; border-radius:20px; border:1px solid var(--border);
    padding:20px; margin-bottom:16px; box-shadow:0 4px 12px rgba(15,23,42,0.04);
}
.vote-title{ font-family:'Cormorant Garamond',serif; font-size:19px; color:var(--navy); margin-bottom:6px; }
.vote-desc{ font-size:13px; color:var(--muted); margin-bottom:14px; }
.vote-options{ display:flex; flex-direction:column; gap:8px; }
.vote-option-form{ display:flex; align-items:center; gap:10px; }
.vote-option-bar{
    flex:1; background:#f1f5f9; border-radius:10px; height:36px;
    position:relative; overflow:hidden; cursor:pointer; transition:0.2s;
    border:2px solid transparent;
}
.vote-option-bar.selected{ border-color:var(--navy); }
.vote-option-fill{
    height:100%; background:linear-gradient(90deg,rgba(23,55,94,0.12),rgba(23,55,94,0.06));
    border-radius:8px; transition:0.4s ease;
}
.vote-option-label{
    position:absolute; top:50%; left:12px; transform:translateY(-50%);
    font-size:13px; font-weight:700; color:var(--text);
}
.vote-option-count{
    position:absolute; top:50%; right:12px; transform:translateY(-50%);
    font-size:12px; font-weight:700; color:var(--muted);
}
.vote-option-bar.selected .vote-option-label{ color:var(--navy); }
.vote-meta{ font-size:11px; color:var(--muted); margin-top:10px; }
.vote-btn{ padding:7px 16px; font-size:12px; }

/* ===========================
   TAB: MAP
=========================== */
.map-container{
    border-radius:20px; overflow:hidden;
    border:1px solid var(--border);
    height:420px; margin-bottom:20px;
    position:relative; background:#e9ecf3;
    display:flex; align-items:center; justify-content:center;
}
#map{ width:100%; height:100%; }
.dest-map-card{
    background:white; border-radius:16px; border:1px solid var(--border);
    padding:14px 18px; display:flex; align-items:center; gap:14px;
    margin-bottom:10px; box-shadow:0 2px 8px rgba(15,23,42,0.04);
}
.dest-map-card img{ width:56px; height:56px; border-radius:12px; object-fit:cover; flex-shrink:0; }
.dest-map-info h4{ font-size:14px; font-weight:700; color:var(--text); margin-bottom:3px; }
.dest-map-info p{ font-size:12px; color:var(--muted); }
.map-pin-num{
    width:28px; height:28px; border-radius:50%;
    background:var(--navy); color:white; font-size:12px; font-weight:700;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}

/* ===========================
   TAB: BUDGET
=========================== */
.budget-summary{
    background:linear-gradient(135deg,#17375e,#234d7d);
    border-radius:20px; padding:24px; color:white; margin-bottom:20px;
}
.budget-summary .total{ font-family:'Cormorant Garamond',serif; font-size:34px; font-weight:700; }
.budget-summary .label{ font-size:12px; color:rgba(255,255,255,0.7); margin-bottom:6px; }
.budget-stats{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-top:16px; }
.budget-stat{ background:rgba(255,255,255,0.1); border-radius:14px; padding:14px; }
.budget-stat .stat-val{ font-size:17px; font-weight:700; }
.budget-stat .stat-label{ font-size:11px; color:rgba(255,255,255,0.65); margin-top:2px; }
.budget-item{
    background:white; border-radius:16px; border:1px solid var(--border);
    padding:14px 18px; display:flex; align-items:center; gap:14px;
    margin-bottom:10px; box-shadow:0 2px 8px rgba(15,23,42,0.04);
}
.budget-item .kategori-tag{
    padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700;
    background:#eff6ff; color:var(--navy);
}
.budget-item .item-amount{ font-size:15px; font-weight:700; color:var(--navy); margin-left:auto; margin-right:10px; }

/* ===========================
   TAB: MEMBERS
=========================== */
.member-card{
    background:white; border-radius:16px; border:1px solid var(--border);
    padding:14px 18px; display:flex; align-items:center; gap:14px;
    margin-bottom:10px; box-shadow:0 2px 8px rgba(15,23,42,0.04);
}
.avatar-md{
    width:42px; height:42px; border-radius:50%;
    background:linear-gradient(135deg,#17375e,#234d7d);
    display:flex; align-items:center; justify-content:center;
    color:white; font-size:16px; font-weight:700; text-transform:uppercase; flex-shrink:0;
}
.member-info h4{ font-size:14px; font-weight:700; color:var(--text); margin-bottom:2px; }
.member-info p{ font-size:12px; color:var(--muted); }
.role-badge{
    padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; margin-left:8px;
}
.role-badge.creator{ background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
.role-badge.member{ background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }

/* ===========================
   RESPONSIVE
=========================== */
@media(max-width:900px){
    .main-layout{ grid-template-columns:1fr; }
    .sidebar{ position:static; height:auto; display:flex; flex-wrap:wrap; padding:12px; border-right:none; border-bottom:1px solid var(--border); }
    .sidebar-section{ margin:0; padding:4px; }
    .sidebar-label{ display:none; }
    .sidebar-divider{ display:none; }
    .tab-btn{ padding:10px 14px; border-radius:10px; }
    .chat-layout{ grid-template-columns:1fr; height:auto; }
    .chat-sidebar{ max-height:150px; }
    .form-row{ grid-template-columns:1fr; }
    .budget-stats{ grid-template-columns:1fr 1fr; }
}
@media(max-width:600px){
    .content-area{ padding:20px 16px; }
    .hero-strip{ padding:20px 16px; }
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




<!-- HERO STRIP -->
<div class="hero-strip">
    <a href="trip_detail.php?id=<?= $id_trip; ?>" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Detail Trip
    </a>
    <div class="trip-title"><?= htmlspecialchars($trip['nama_trip']); ?></div>
    <div class="status-pill" style="--status-color:<?= $status_color; ?>">
        <?= strtoupper($trip['status']); ?>
    </div>
    <div class="members-pill">
        <i class="fa-solid fa-users"></i>
        <?= count($all_members_list); ?> Anggota
    </div>
</div>


<!-- MAIN LAYOUT -->
<div class="main-layout">

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">

    <div class="sidebar-section">
        <div class="sidebar-label">Fitur Group</div>

        <a href="?id=<?= $id_trip; ?>&tab=chat"
           class="tab-btn <?= $active_tab=='chat'?'active':'' ?>">
            <i class="fa-solid fa-comments"></i>
            Chat Group
        </a>

        <a href="?id=<?= $id_trip; ?>&tab=personal"
           class="tab-btn <?= $active_tab=='personal'?'active':'' ?>">
            <i class="fa-solid fa-message"></i>
            Personal Chat
        </a>

        <a href="?id=<?= $id_trip; ?>&tab=itinerary"
           class="tab-btn <?= $active_tab=='itinerary'?'active':'' ?>">
            <i class="fa-solid fa-list-check"></i>
            Itinerary
        </a>

        <a href="?id=<?= $id_trip; ?>&tab=voting"
           class="tab-btn <?= $active_tab=='voting'?'active':'' ?>">
            <i class="fa-solid fa-check-to-slot"></i>
            Voting
        </a>

        <a href="?id=<?= $id_trip; ?>&tab=map"
           class="tab-btn <?= $active_tab=='map'?'active':'' ?>">
            <i class="fa-solid fa-map-location-dot"></i>
            Map & Lokasi
        </a>

        <a href="?id=<?= $id_trip; ?>&tab=budget"
           class="tab-btn <?= $active_tab=='budget'?'active':'' ?>">
            <i class="fa-solid fa-wallet"></i>
            Budget Split
        </a>

    </div>

    <div class="sidebar-divider"></div>

    <div class="sidebar-section">
        <div class="sidebar-label">Anggota</div>

        <a href="?id=<?= $id_trip; ?>&tab=members"
           class="tab-btn <?= $active_tab=='members'?'active':'' ?>">
            <i class="fa-solid fa-user-group"></i>
            Kelola Anggota
            <span class="badge"><?= count($all_members_list); ?></span>
        </a>

        <?php foreach($all_members_list as $i => $m): ?>
        <div class="member-mini">
            <?php echo avatarSmHtml($m['foto'] ?? '', $m['nama']); ?>
            <span style="font-size:12px;color:var(--text);font-weight:600;"><?= htmlspecialchars(explode(' ',$m['nama'])[0]); ?></span>
            <?php if($m['role']=='creator'): ?>
            <i class="fa-solid fa-crown" style="color:#f59e0b;font-size:10px;margin-left:auto;"></i>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>


<!-- ===== CONTENT AREA ===== -->
<div class="content-area">

<?php if($msg): ?>
<?php
$alerts = [
    'invite_ok' => ['success','Collaborator berhasil ditambahkan!'],
    'already'   => ['warning','User sudah menjadi anggota trip ini.'],
    'notfound'  => ['error','Email tidak ditemukan. Pastikan user sudah terdaftar di TRAVA.'],
];
if(isset($alerts[$msg])):
[$atype,$atxt] = $alerts[$msg];
?>
<div class="alert <?= $atype; ?>">
    <i class="fa-solid fa-circle-<?= $atype=='success'?'check':($atype=='error'?'xmark':'exclamation'); ?>"></i>
    <?= $atxt; ?>
</div>
<?php endif; ?>
<?php endif; ?>


<!-- ================================================
     TAB: CHAT GROUP
================================================ -->
<?php if($active_tab === 'chat'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-comments"></i></div>
    <div>
        <h2>Chat Group</h2>
        <p>Diskusi bersama semua anggota trip</p>
    </div>
</div>

<div class="chat-layout">
    <!-- Chat sidebar (room list) -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">Ruangan</div>
        <button class="chat-room-btn active" onclick="switchChatRoom('group', 0, 'Group <?= htmlspecialchars($trip['nama_trip']); ?>', 'Semua Anggota')">
            <div class="avatar-sm"><i class="fa-solid fa-users" style="font-size:10px;"></i></div>
            <div>
                <div class="room-name" style="font-size:12px;">Group Chat</div>
                <div class="room-sub"><?= count($all_members_list); ?> anggota</div>
            </div>
        </button>
    </div>

    <!-- Chat main -->
    <div class="chat-main">
        <div class="chat-header">
            <div class="avatar-sm"><i class="fa-solid fa-users" style="font-size:10px;"></i></div>
            <div>
                <div class="room-title" id="chatRoomTitle">Group Chat</div>
                <div class="room-desc" id="chatRoomDesc">Semua Anggota</div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div style="text-align:center;color:var(--muted);font-size:12px;padding:20px;">Memuat pesan...</div>
        </div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatInput" placeholder="Ketik pesan..." onkeypress="if(event.key==='Enter')sendMsg()">
            <button class="chat-send-btn" onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
let currentChatType = 'group';
let currentToUser   = 0;
let currentToName   = '';
const TRIP_ID = <?= $id_trip; ?>;
const MY_ID   = <?= $user_id; ?>;
const MY_NAME = "<?= htmlspecialchars($_SESSION['nama'] ?? ''); ?>";
// Map user_id -> foto path untuk bubble chat
const MEMBER_FOTO = {
<?php foreach($all_members_list as $m):
    $foto_path = !empty($m['foto']) ? 'assets/img/profil/'.addslashes($m['foto']) : '';
?>
    <?= (int)$m['id']; ?>: "<?= htmlspecialchars($foto_path); ?>",
<?php endforeach; ?>
};
function getMsgAvatar(userId, nama){
    const foto = MEMBER_FOTO[userId];
    if(foto) return `<div class="msg-avatar"><img src="${foto}" alt="${escHtml(nama)}" onerror="this.parentElement.innerHTML='${nama.charAt(0).toUpperCase()}'"></div>`;
    return `<div class="msg-avatar">${nama.charAt(0).toUpperCase()}</div>`;
}

function switchChatRoom(type, toUserId, title, desc){
    currentChatType = type;
    currentToUser   = toUserId;
    document.getElementById('chatRoomTitle').textContent = title;
    document.getElementById('chatRoomDesc').textContent  = desc;
    document.querySelectorAll('.chat-room-btn').forEach(b=>b.classList.remove('active'));
    event.currentTarget.classList.add('active');
    loadChat();
}

function loadChat(){
    let url = `proses/group_proses.php?action=load_chat&trip_id=${TRIP_ID}&type=${currentChatType}&to_user_id=${currentToUser}`;
    fetch(url)
    .then(r=>r.json())
    .then(msgs=>{
        const box = document.getElementById('chatMessages');
        if(msgs.length === 0){
            box.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:12px;padding:20px;"><i class="fa-solid fa-comment-slash" style="font-size:28px;margin-bottom:8px;display:block;"></i>Belum ada pesan. Mulai percakapan!</div>';
            return;
        }
        box.innerHTML = msgs.map(m=>`
        <div class="msg-bubble ${m.mine?'mine':''}">
            ${getMsgAvatar(m.user_id, m.nama)}
            <div class="msg-content">
                ${!m.mine ? `<div class="msg-name">${m.nama}</div>` : ''}
                <div class="msg-text">${escHtml(m.message)}</div>
                <div class="msg-time">${m.time}</div>
            </div>
        </div>`).join('');
        box.scrollTop = box.scrollHeight;
    }).catch(()=>{});
}

function sendMsg(){
    const input = document.getElementById('chatInput');
    const msg   = input.value.trim();
    if(!msg) return;
    const fd = new FormData();
    fd.append('action','send_chat');
    fd.append('trip_id', TRIP_ID);
    fd.append('message', msg);
    fd.append('type', currentChatType);
    fd.append('to_user_id', currentToUser);
    input.value = '';
    fetch('proses/group_proses.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(()=>loadChat());
}

function escHtml(t){ const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }

// Auto-reload every 3 seconds
loadChat();
setInterval(loadChat, 3000);
</script>


<!-- ================================================
     TAB: PERSONAL CHAT
================================================ -->
<?php elseif($active_tab === 'personal'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-message"></i></div>
    <div>
        <h2>Personal Chat</h2>
        <p>Chat privat dengan anggota tertentu</p>
    </div>
</div>

<div class="chat-layout">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">Anggota</div>
        <?php foreach($all_members_list as $i => $m):
            if($m['id'] == $user_id) continue; ?>
        <button class="chat-room-btn <?= $i==1?'active':'' ?>"
                onclick="switchChatRoom('personal', <?= $m['id']; ?>, '<?= htmlspecialchars(addslashes($m['nama'])); ?>', 'Chat Privat')">
            <?php echo avatarSmHtml($m['foto'] ?? '', $m['nama']); ?>
            <div>
                <div class="room-name" style="font-size:12px;"><?= htmlspecialchars(explode(' ',$m['nama'])[0]); ?></div>
                <div class="room-sub"><?= $m['role']=='creator'?'Creator':'Anggota'; ?></div>
            </div>
        </button>
        <?php endforeach; ?>

        <?php if(count($all_members_list) <= 1): ?>
        <div style="padding:16px;font-size:12px;color:var(--muted);text-align:center;">
            Belum ada anggota lain.<br>Invite teman di tab Anggota.
        </div>
        <?php endif; ?>
    </div>

    <div class="chat-main">
        <div class="chat-header">
            <div class="avatar-sm"><i class="fa-solid fa-message" style="font-size:10px;"></i></div>
            <div>
                <div class="room-title" id="chatRoomTitle">Pilih Anggota</div>
                <div class="room-desc" id="chatRoomDesc">Chat Privat</div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div style="text-align:center;color:var(--muted);font-size:12px;padding:20px;">Pilih anggota untuk mulai chat privat</div>
        </div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatInput" placeholder="Ketik pesan privat..." onkeypress="if(event.key==='Enter')sendMsg()">
            <button class="chat-send-btn" onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
let currentChatType = 'personal';
let currentToUser   = 0;
const TRIP_ID = <?= $id_trip; ?>;
const MY_ID   = <?= $user_id; ?>;

function switchChatRoom(type, toUserId, title, desc){
    currentChatType = type;
    currentToUser   = toUserId;
    document.getElementById('chatRoomTitle').textContent = title;
    document.getElementById('chatRoomDesc').textContent  = desc;
    document.querySelectorAll('.chat-room-btn').forEach(b=>b.classList.remove('active'));
    event.currentTarget.classList.add('active');
    loadChat();
}

function loadChat(){
    if(!currentToUser) return;
    let url = `proses/group_proses.php?action=load_chat&trip_id=${TRIP_ID}&type=personal&to_user_id=${currentToUser}`;
    fetch(url)
    .then(r=>r.json())
    .then(msgs=>{
        const box = document.getElementById('chatMessages');
        if(msgs.length === 0){
            box.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:12px;padding:20px;"><i class="fa-solid fa-lock" style="font-size:28px;margin-bottom:8px;display:block;"></i>Belum ada pesan privat. Mulai percakapan!</div>';
            return;
        }
        box.innerHTML = msgs.map(m=>`
        <div class="msg-bubble ${m.mine?'mine':''}">
            ${getMsgAvatar(m.user_id, m.nama)}
            <div class="msg-content">
                ${!m.mine ? `<div class="msg-name">${m.nama}</div>` : ''}
                <div class="msg-text">${escHtml(m.message)}</div>
                <div class="msg-time">${m.time}</div>
            </div>
        </div>`).join('');
        box.scrollTop = box.scrollHeight;
    }).catch(()=>{});
}

function sendMsg(){
    if(!currentToUser){ alert('Pilih anggota dulu!'); return; }
    const input = document.getElementById('chatInput');
    const msg   = input.value.trim();
    if(!msg) return;
    const fd = new FormData();
    fd.append('action','send_chat');
    fd.append('trip_id', TRIP_ID);
    fd.append('message', msg);
    fd.append('type', 'personal');
    fd.append('to_user_id', currentToUser);
    input.value = '';
    fetch('proses/group_proses.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(d=>{ if(d.status==='ok') loadChat(); })
    .catch(()=>loadChat());
}

function escHtml(t){ const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
setInterval(()=>{ if(currentToUser) loadChat(); }, 3000);
// Auto open first member
<?php
$first_other = null;
foreach($all_members_list as $m){ if($m['id'] != $user_id){ $first_other = $m; break; } }
if($first_other):
?>
window.addEventListener('load', ()=>{
    currentToUser = <?= $first_other['id']; ?>;
    document.getElementById('chatRoomTitle').textContent = "<?= htmlspecialchars(addslashes($first_other['nama'])); ?>";
    document.querySelectorAll('.chat-room-btn')[0]?.classList.add('active');
    loadChat();
});
<?php endif; ?>
</script>


<!-- ================================================
     TAB: ITINERARY PLANNER
================================================ -->
<?php elseif($active_tab === 'itinerary'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-list-check"></i></div>
    <div>
        <h2>Itinerary Planner</h2>
        <p>Susun jadwal perjalanan bersama</p>
    </div>
</div>

<!-- Tambah Aktivitas -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-plus"></i> Tambah Aktivitas</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="proses/group_proses.php">
            <input type="hidden" name="action" value="add_itinerary">
            <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Hari Ke-</label>
                    <input type="number" name="hari" min="1" max="30" value="1" required>
                </div>
                <div class="form-group">
                    <label>Waktu</label>
                    <input type="time" name="waktu">
                </div>
            </div>
            <div class="form-group">
                <label>Aktivitas / Kegiatan</label>
                <input type="text" name="aktivitas" placeholder="Contoh: Sarapan di warung lokal" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="lokasi" placeholder="Contoh: Pantai Kejawan">
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="catatan" placeholder="Opsional...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah ke Itinerary
            </button>
        </form>
    </div>
</div>

<!-- Daftar Itinerary -->
<?php if(empty($itinerary_by_day)): ?>
<div style="text-align:center;padding:48px;color:var(--muted);">
    <i class="fa-solid fa-calendar-days" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.3;"></i>
    <p style="font-size:14px;">Belum ada itinerary. Mulai susun jadwal perjalananmu!</p>
</div>
<?php else: ?>
<?php for($day = 1; $day <= $max_day; $day++): ?>
<?php if(isset($itinerary_by_day[$day])): ?>
<div class="day-header">
    <i class="fa-solid fa-calendar-day"></i>
    Hari Ke-<?= $day; ?>
</div>
<?php foreach($itinerary_by_day[$day] as $it): ?>
<div class="iti-item">
    <div class="iti-time"><?= $it['waktu'] ?: '--:--'; ?></div>
    <div class="iti-info">
        <div class="iti-aktivitas"><?= htmlspecialchars($it['aktivitas']); ?></div>
        <?php if($it['lokasi']): ?>
        <div class="iti-lokasi"><i class="fa-solid fa-location-dot" style="font-size:11px;"></i><?= htmlspecialchars($it['lokasi']); ?></div>
        <?php endif; ?>
        <?php if($it['catatan']): ?>
        <div class="iti-catatan"><?= htmlspecialchars($it['catatan']); ?></div>
        <?php endif; ?>
        <div class="iti-pembuat"><i class="fa-solid fa-user" style="font-size:10px;"></i> <?= htmlspecialchars($it['pembuat']); ?></div>
    </div>
    <?php if($it['user_id'] == $user_id || $is_creator): ?>
    <form method="POST" action="proses/group_proses.php" onsubmit="return confirm('Hapus aktivitas ini?');" style="margin:0;">
        <input type="hidden" name="action" value="del_itinerary">
        <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
        <input type="hidden" name="item_id" value="<?= $it['id']; ?>">
        <button type="submit" class="iti-del"><i class="fa-solid fa-trash"></i></button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endfor; ?>
<?php endif; ?>


<!-- ================================================
     TAB: VOTING SYSTEM
================================================ -->
<?php elseif($active_tab === 'voting'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-check-to-slot"></i></div>
    <div>
        <h2>Voting System</h2>
        <p>Buat polling & voting keputusan trip bersama</p>
    </div>
</div>

<!-- Buat Vote Baru -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-plus"></i> Buat Voting Baru</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="proses/group_proses.php">
            <input type="hidden" name="action" value="create_vote">
            <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
            <div class="form-group">
                <label>Judul Voting</label>
                <input type="text" name="judul" placeholder="Contoh: Mau makan siang dimana?" required>
            </div>
            <div class="form-group">
                <label>Deskripsi (Opsional)</label>
                <input type="text" name="deskripsi" placeholder="Jelaskan sedikit...">
            </div>
            <div class="form-group">
                <label>Pilihan / Opsi (Min. 2)</label>
                <div id="opsiContainer">
                    <input type="text" name="opsi[]" placeholder="Opsi 1..." style="margin-bottom:8px;" required>
                    <input type="text" name="opsi[]" placeholder="Opsi 2..." style="margin-bottom:8px;" required>
                </div>
                <button type="button" class="btn btn-sm" style="background:#f1f5f9;color:var(--navy);" onclick="addOpsi()">
                    <i class="fa-solid fa-plus"></i> Tambah Opsi
                </button>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check-to-slot"></i> Buat Voting
            </button>
        </form>
    </div>
</div>
<script>
function addOpsi(){
    const c = document.getElementById('opsiContainer');
    const n = c.children.length + 1;
    const inp = document.createElement('input');
    inp.type = 'text'; inp.name = 'opsi[]';
    inp.placeholder = `Opsi ${n}...`;
    inp.style.marginBottom = '8px';
    inp.className = 'form-group input';
    inp.style.cssText = 'width:100%;padding:12px 16px;border:1px solid var(--border);border-radius:12px;font-size:13px;font-family:Manrope,sans-serif;background:#f8fafc;color:var(--text);outline:none;transition:0.2s;margin-bottom:8px;';
    c.appendChild(inp);
}
</script>

<!-- Daftar Vote -->
<?php if(empty($votes)): ?>
<div style="text-align:center;padding:48px;color:var(--muted);">
    <i class="fa-solid fa-check-to-slot" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.3;"></i>
    <p style="font-size:14px;">Belum ada voting. Buat voting pertama!</p>
</div>
<?php else: ?>
<?php foreach($votes as $v): ?>
<div class="vote-card">
    <div class="vote-title"><?= htmlspecialchars($v['judul']); ?></div>
    <?php if($v['deskripsi']): ?>
    <div class="vote-desc"><?= htmlspecialchars($v['deskripsi']); ?></div>
    <?php endif; ?>

    <div class="vote-options">
        <?php foreach($v['options'] as $opt):
            $pct = $v['total_votes'] > 0 ? round(($opt['count'] / $v['total_votes']) * 100) : 0;
            $selected = ($v['my_vote_option'] == $opt['id']);
        ?>
        <div class="vote-option-form">
            <form method="POST" action="proses/group_proses.php" style="flex:1;margin:0;">
                <input type="hidden" name="action" value="cast_vote">
                <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
                <input type="hidden" name="vote_id" value="<?= $v['id']; ?>">
                <input type="hidden" name="option_id" value="<?= $opt['id']; ?>">
                <button type="submit" class="vote-option-bar <?= $selected?'selected':'' ?>" style="width:100%;cursor:pointer;">
                    <div class="vote-option-fill" style="width:<?= $pct; ?>%;"></div>
                    <span class="vote-option-label"><?= htmlspecialchars($opt['opsi']); ?> <?= $selected?'<i class="fa-solid fa-check" style="color:var(--navy);"></i>':'' ?></span>
                    <span class="vote-option-count"><?= $pct; ?>% (<?= $opt['count']; ?>)</span>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="vote-meta">
        <i class="fa-solid fa-chart-bar"></i> <?= $v['total_votes']; ?> total vote &nbsp;•&nbsp;
        <i class="fa-solid fa-user"></i> Dibuat oleh <?= htmlspecialchars($v['creator_nama']); ?> &nbsp;•&nbsp;
        <?= date('d M Y', strtotime($v['created_at'])); ?>
        <?php if($v['my_vote_option']): ?>
        &nbsp;•&nbsp; <span style="color:var(--green);font-weight:700;"><i class="fa-solid fa-circle-check"></i> Sudah Vote</span>
        <?php else: ?>
        &nbsp;•&nbsp; <span style="color:var(--orange);font-weight:700;"><i class="fa-solid fa-circle-exclamation"></i> Klik opsi untuk vote</span>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>


<!-- ================================================
     TAB: MAP & LOCATION
================================================ -->
<?php elseif($active_tab === 'map'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-map-location-dot"></i></div>
    <div>
        <h2>Map & Lokasi</h2>
        <p>Peta destinasi dalam trip ini</p>
    </div>
</div>

<!-- Map Container (OpenStreetMap via Leaflet) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="map-container">
    <div id="map"></div>
</div>

<?php if(empty($map_destinations)): ?>
<div style="text-align:center;padding:24px;color:var(--muted);">
    <p>Belum ada destinasi dalam trip ini.</p>
</div>
<?php else: ?>
<h3 style="font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--navy);margin-bottom:12px;">
    <i class="fa-solid fa-location-dot"></i> Destinasi Trip
</h3>
<?php foreach($map_destinations as $i => $dest): ?>
<div class="dest-map-card">
    <div class="map-pin-num"><?= $i+1; ?></div>
    <?php if($dest['gambar']): ?>
    <img src="assets/img/<?= htmlspecialchars($dest['gambar']); ?>" alt="<?= htmlspecialchars($dest['nama']); ?>">
    <?php endif; ?>
    <div class="dest-map-info">
        <h4><?= htmlspecialchars($dest['nama']); ?></h4>
        <p><i class="fa-solid fa-location-dot" style="font-size:11px;"></i> <?= htmlspecialchars($dest['lokasi']); ?></p>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
// Default map center: Cirebon
var map = L.map('map').setView([-6.7051, 108.5574], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© OpenStreetMap',
    maxZoom:18
}).addTo(map);

// Geocode destinations
const destinations = <?php
    $dest_json = [];
    foreach($map_destinations as $i => $dest){
        $dest_json[] = [
            'nama'   => htmlspecialchars($dest['nama'], ENT_QUOTES),
            'lokasi' => htmlspecialchars($dest['lokasi'], ENT_QUOTES),
            'num'    => $i+1
        ];
    }
    echo json_encode($dest_json);
?>;

const cirebon_coords = {
    'Kasepuhan': [-6.7082, 108.5622],
    'Goa Sunyaragi': [-6.7274, 108.5392],
    'Kejawan': [-6.7051, 108.6102],
    'Gronggong': [-6.7920, 108.5960],
};

let bounds = [];
let markerIndex = 0;

function geocodeNext(){
    if(markerIndex >= destinations.length) {
        if(bounds.length > 0) map.fitBounds(bounds, {padding:[30,30]});
        return;
    }
    const d = destinations[markerIndex];
    markerIndex++;

    // Check known coords
    for(const [key, coord] of Object.entries(cirebon_coords)){
        if(d.nama.includes(key) || d.lokasi.includes(key)){
            addMarker(coord[0], coord[1], d.nama, d.num);
            return geocodeNext();
        }
    }

    // Try Nominatim geocoding
    const query = encodeURIComponent(d.nama + ', Cirebon, Indonesia');
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`)
    .then(r=>r.json())
    .then(data=>{
        if(data && data.length > 0){
            const lat = parseFloat(data[0].lat);
            const lon = parseFloat(data[0].lon);
            addMarker(lat, lon, d.nama, d.num);
        } else {
            // Fallback: Cirebon center + offset
            const lat = -6.7051 + (markerIndex * 0.008);
            const lon = 108.5574 + (markerIndex * 0.005);
            addMarker(lat, lon, d.nama, d.num);
        }
        geocodeNext();
    }).catch(()=>{
        geocodeNext();
    });
}

function addMarker(lat, lon, nama, num){
    const icon = L.divIcon({
        className:'',
        html:`<div style="background:#17375e;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:3px solid white;box-shadow:0 4px 12px rgba(0,0,0,0.3);">${num}</div>`,
        iconSize:[32,32], iconAnchor:[16,16]
    });
    const marker = L.marker([lat,lon], {icon}).addTo(map);
    marker.bindPopup(`<b>${nama}</b>`);
    bounds.push([lat,lon]);
}

geocodeNext();
</script>


<!-- ================================================
     TAB: BUDGET SPLIT
================================================ -->
<?php elseif($active_tab === 'budget'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-wallet"></i></div>
    <div>
        <h2>Budget Split</h2>
        <p>Lacak pengeluaran & bagi biaya rata</p>
    </div>
</div>

<!-- Summary -->
<div class="budget-summary">
    <div class="label">Total Pengeluaran Group</div>
    <div class="total">Rp <?= number_format($total_budget, 0, ',', '.'); ?></div>
    <div class="budget-stats">
        <div class="budget-stat">
            <div class="stat-val"><?= count($budget_items); ?></div>
            <div class="stat-label">Item</div>
        </div>
        <div class="budget-stat">
            <div class="stat-val"><?= $member_count; ?></div>
            <div class="stat-label">Anggota</div>
        </div>
        <div class="budget-stat">
            <div class="stat-val">Rp <?= number_format($split_amount, 0, ',', '.'); ?></div>
            <div class="stat-label">Per Orang</div>
        </div>
    </div>
</div>

<!-- Split per member -->
<?php if($member_count > 0 && $total_budget > 0): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-people-arrows"></i> Pembagian per Anggota</h3>
    </div>
    <div class="card-body" style="padding:12px 16px;">
        <?php foreach($all_members_list as $m): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
            <?php echo avatarSmHtml($m['foto'] ?? '', $m['nama']); ?>
            <div style="flex:1;font-size:13px;font-weight:700;color:var(--text);"><?= htmlspecialchars($m['nama']); ?></div>
            <div style="font-size:14px;font-weight:700;color:var(--navy);">Rp <?= number_format($split_amount,0,',','.'); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tambah Item -->
<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-plus"></i> Catat Pengeluaran</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="proses/group_proses.php">
            <input type="hidden" name="action" value="add_budget">
            <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Item</label>
                    <input type="text" name="nama_item" placeholder="Contoh: Bensin" required>
                </div>
                <div class="form-group">
                    <label>Jumlah (Rp)</label>
                    <input type="number" name="jumlah" placeholder="50000" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori">
                    <option>Transportasi</option>
                    <option>Makan</option>
                    <option>Tiket Masuk</option>
                    <option>Akomodasi</option>
                    <option>Oleh-oleh</option>
                    <option>Lainnya</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Tambah Pengeluaran
            </button>
        </form>
    </div>
</div>

<!-- Daftar Item -->
<?php if(empty($budget_items)): ?>
<div style="text-align:center;padding:40px;color:var(--muted);">
    <i class="fa-solid fa-receipt" style="font-size:36px;margin-bottom:12px;display:block;opacity:0.3;"></i>
    <p style="font-size:14px;">Belum ada pengeluaran tercatat.</p>
</div>
<?php else: ?>
<h3 style="font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--navy);margin-bottom:12px;">
    <i class="fa-solid fa-receipt"></i> Rincian Pengeluaran
</h3>
<?php foreach($budget_items as $b): ?>
<div class="budget-item">
    <div>
        <div style="font-size:14px;font-weight:700;color:var(--text);"><?= htmlspecialchars($b['nama_item']); ?></div>
        <div style="font-size:11px;color:var(--muted);">oleh <?= htmlspecialchars($b['pembuat']); ?> &nbsp;•&nbsp; <?= date('d M', strtotime($b['created_at'])); ?></div>
    </div>
    <span class="kategori-tag"><?= htmlspecialchars($b['kategori']); ?></span>
    <div class="item-amount">Rp <?= number_format($b['jumlah'],0,',','.'); ?></div>
    <?php if($b['user_id'] == $user_id || $is_creator): ?>
    <form method="POST" action="proses/group_proses.php" onsubmit="return confirm('Hapus item ini?');" style="margin:0;">
        <input type="hidden" name="action" value="del_budget">
        <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
        <input type="hidden" name="item_id" value="<?= $b['id']; ?>">
        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>


<!-- ================================================
     TAB: MEMBERS / COLLABORATORS
================================================ -->
<?php elseif($active_tab === 'members'): ?>

<div class="section-header">
    <div class="section-icon"><i class="fa-solid fa-user-group"></i></div>
    <div>
        <h2>Kelola Anggota</h2>
        <p>Invite collaborator & kelola anggota trip</p>
    </div>
</div>

<!-- Invite Form -->
<?php if($is_creator): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="fa-solid fa-user-plus"></i> Invite Collaborator</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="proses/group_proses.php">
            <input type="hidden" name="action" value="invite">
            <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
            <div class="form-group">
                <label>Email Pengguna TRAVA</label>
                <input type="email" name="email" placeholder="email@contoh.com" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-user-plus"></i> Kirim Undangan
            </button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert warning">
    <i class="fa-solid fa-circle-info"></i>
    Hanya creator trip yang bisa invite anggota baru.
</div>
<?php endif; ?>

<!-- Daftar Anggota -->
<h3 style="font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--navy);margin-bottom:12px;">
    <i class="fa-solid fa-users"></i> Anggota Trip (<?= count($all_members_list); ?>)
</h3>

<?php foreach($all_members_list as $m): ?>
<div class="member-card">
    <div class="avatar-md"><?= strtoupper(substr($m['nama'],0,1)); ?></div>
    <div class="member-info" style="flex:1;">
        <h4>
            <?= htmlspecialchars($m['nama']); ?>
            <span class="role-badge <?= $m['role']; ?>"><?= $m['role'] == 'creator' ? '👑 Creator' : 'Anggota'; ?></span>
        </h4>
        <p><?= htmlspecialchars($m['email']); ?></p>
    </div>
    <?php if($is_creator && $m['role'] !== 'creator'): ?>
    <form method="POST" action="proses/group_proses.php" onsubmit="return confirm('Hapus anggota ini dari trip?');" style="margin:0;">
        <input type="hidden" name="action" value="remove_member">
        <input type="hidden" name="trip_id" value="<?= $id_trip; ?>">
        <input type="hidden" name="member_uid" value="<?= $m['id']; ?>">
        <button type="submit" class="btn btn-danger btn-sm">
            <i class="fa-solid fa-user-xmark"></i> Keluarkan
        </button>
    </form>
    <?php endif; ?>
</div>
<?php endforeach; ?>

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
      return '<div class="notif-item '+(n.is_read==0?"unread":"")+'\" onclick="_goN('+n.id+',\''+lk+'\')">'+
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
