<?php
session_start();
include 'config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$_user_nav_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT foto, nama FROM users WHERE id='$user_id'"));
$_nav_foto = !empty($_user_nav_row['foto']) ? $_user_nav_row['foto'] : '';
$_nav_initial = strtoupper(mb_substr($_user_nav_row['nama'] ?? $_SESSION['nama'] ?? 'U', 0, 1));

$filter = $_GET['filter'] ?? 'semua';

$unread_count = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM notifications WHERE user_id='$user_id' AND is_read=0"))['c'];

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$search_cond = $search ? "AND (n.message LIKE '%$search%' OR n.type LIKE '%$search%')" : '';

$filter_cond = '';
if($filter === 'belum_dibaca') $filter_cond = "AND n.is_read=0";
elseif($filter === 'review') $filter_cond = "AND n.type='review'";
elseif($filter === 'wishlist') $filter_cond = "AND n.type='wishlist'";
elseif($filter === 'trip') $filter_cond = "AND n.type='trip'";
elseif($filter === 'wacana') $filter_cond = "AND n.type='wacana'";
elseif($filter === 'akun') $filter_cond = "AND n.type='akun'";
elseif($filter === 'chat') $filter_cond = "AND n.type IN ('chat_personal','chat_group','invite')";

$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$total_q = mysqli_query($conn,"SELECT COUNT(*) as c FROM notifications n WHERE n.user_id='$user_id' $filter_cond $search_cond");
$total = (int)mysqli_fetch_assoc($total_q)['c'];
$total_pages = max(1, ceil($total / $per_page));

$notifs_q = mysqli_query($conn,"
    SELECT n.*, u.nama AS from_nama, u.foto AS from_foto, t.nama_trip
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    LEFT JOIN trip t ON n.trip_id = t.id
    WHERE n.user_id='$user_id' $filter_cond $search_cond
    ORDER BY n.created_at DESC
    LIMIT $per_page OFFSET $offset
");

function countCat($conn, $user_id, $type=''){
    if($type === 'chat') {
        $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM notifications WHERE user_id='$user_id' AND is_read=0 AND type IN ('chat_personal','chat_group','invite')"));
    } else {
        $tc = $type ? "AND type='$type'" : '';
        $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM notifications WHERE user_id='$user_id' AND is_read=0 $tc"));
    }
    return (int)$r['c'];
}
$cnt_all     = countCat($conn, $user_id);
$cnt_unread  = $cnt_all;
$cnt_review  = countCat($conn, $user_id, 'review');
$cnt_wishlist= countCat($conn, $user_id, 'wishlist');
$cnt_trip    = countCat($conn, $user_id, 'trip');
$cnt_wacana  = countCat($conn, $user_id, 'wacana');
$cnt_akun    = countCat($conn, $user_id, 'akun');
$cnt_chat    = countCat($conn, $user_id, 'chat');

function timeAgo($ts){
    $diff = max(0, time() - $ts);
    if($diff < 60) return "Baru saja";
    if($diff < 3600) return floor($diff/60)." menit lalu";
    if($diff < 86400) return floor($diff/3600)." jam lalu";
    if($diff < 2592000) return floor($diff/86400)." hari lalu";
    if($diff < 31536000) return floor($diff/2592000)." bulan lalu";
    return floor($diff/31536000)." tahun lalu";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi - TRAVA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --primary:#17375e;
  --primary-light:#234d7d;
  --accent:#f9844a;
  --accent2:#f9c74f;
  --bg:#f3f6fb;
  --white:#ffffff;
  --text:#1e293b;
  --text-muted:#64748b;
  --text-light:#94a3b8;
  --border:#e8edf4;
  --unread-bg:#eef4ff;
  --radius-lg:18px;
  --radius-md:12px;
  --radius-sm:8px;
  --shadow-sm:0 2px 10px rgba(15,23,42,0.06);
  --shadow-md:0 6px 24px rgba(15,23,42,0.10);
  --shadow-lg:0 16px 48px rgba(15,23,42,0.16);
}
body{background:var(--bg);font-family:'Plus Jakarta Sans',sans-serif;color:var(--text);}


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
.profile-avatar-btn img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #17375e;}.profile-avatar-initial{width:40px;height:40px;border-radius:50%;background:#17375e;color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;border:2px solid #c8a84b;cursor:pointer;text-transform:uppercase;flex-shrink:0;}
.profile-dropdown{position:absolute;top:52px;right:0;width:210px;background:white;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.16);z-index:9999;display:none;overflow:hidden;padding:8px 0;}
.profile-dropdown.open{display:block;}
.profile-dd-item{display:flex;align-items:center;gap:10px;padding:12px 18px;color:#1e293b;text-decoration:none;font-size:14px;font-weight:600;transition:0.15s;}
.profile-dd-item:hover{background:#f8fafc;color:#17375e;}
.profile-dd-item i{color:#17375e;width:16px;}
.profile-dd-divider{height:1px;background:#f1f5f9;margin:4px 0;}




/* ===== PAGE LAYOUT ===== */
.page-wrap{width:92%;max-width:1180px;margin:0 auto;padding:32px 0 64px;}
.page-grid{display:grid;grid-template-columns:236px 1fr;gap:24px;align-items:start;}

/* SIDEBAR */
.notif-sidebar{background:var(--white);border-radius:var(--radius-lg);padding:16px 0;box-shadow:var(--shadow-sm);position:sticky;top:80px;}
.sidebar-hd{font-size:14px;font-weight:800;color:var(--primary);padding:10px 20px 14px;display:flex;align-items:center;gap:8px;letter-spacing:.02em;}
.sb-item{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;cursor:pointer;border-radius:10px;margin:2px 8px;text-decoration:none;color:var(--text-muted);font-size:13.5px;font-weight:600;transition:.15s;}
.sb-item:hover{background:#f8fafc;color:var(--primary);}
.sb-item.active{background:var(--unread-bg);color:var(--primary);}
.sb-left{display:flex;align-items:center;gap:9px;}
.sb-left i{width:16px;text-align:center;}
.sb-count{background:#e2e8f0;color:var(--text-muted);border-radius:999px;font-size:11px;font-weight:700;min-width:22px;height:20px;display:flex;align-items:center;justify-content:center;padding:0 6px;}
.sb-item.active .sb-count{background:#dbeafe;color:#1d4ed8;}
.sb-divider{height:1px;background:var(--border);margin:8px 16px;}

/* MAIN */
.notif-main{background:var(--white);border-radius:var(--radius-lg);padding:24px 28px;box-shadow:var(--shadow-sm);}
.main-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.main-title{font-size:22px;font-weight:800;color:var(--text);}
.main-sub{font-size:12.5px;color:var(--text-muted);margin-top:3px;}
.main-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.search-box{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:7px 12px;width:200px;}
.search-box input{background:none;border:none;outline:none;font-size:12.5px;color:var(--text);width:100%;font-family:inherit;}
.search-box i{font-size:12px;color:var(--text-light);}
.btn-sm{display:flex;align-items:center;gap:5px;border-radius:var(--radius-sm);padding:7px 13px;font-size:12px;font-weight:700;cursor:pointer;transition:.15s;white-space:nowrap;font-family:inherit;}
.btn-outline-primary{background:none;border:1.5px solid var(--primary);color:var(--primary);}
.btn-outline-primary:hover{background:var(--primary);color:white;}
.btn-outline-danger{background:none;border:1.5px solid var(--border);color:var(--text-muted);}
.btn-outline-danger:hover{background:#fef2f2;border-color:#fca5a5;color:#dc2626;}

/* ===== NOTIF ROWS ===== */
.notif-row{display:flex;align-items:flex-start;gap:14px;padding:14px 12px;border-bottom:1px solid var(--border);border-radius:var(--radius-md);transition:.15s;cursor:pointer;position:relative;}
.notif-row:last-child{border-bottom:none;}
.notif-row:hover{background:#f8fafc;}
.notif-row.unread{background:var(--unread-bg);}
.nr-dot{width:9px;height:9px;background:#ef4444;border-radius:50%;flex-shrink:0;margin-top:7px;}
.nr-dot.hidden{visibility:hidden;}

/* icon circle */
.nr-icon{width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.nr-icon.review{background:#dbeafe;color:#1d4ed8;}
.nr-icon.wishlist{background:#fce7f3;color:#db2777;}
.nr-icon.trip{background:#d1fae5;color:#059669;}
.nr-icon.wacana{background:#fee2e2;color:#dc2626;}
.nr-icon.akun{background:#d1fae5;color:#16a34a;}
.nr-icon.invite{background:#ede9fe;color:#7c3aed;}
.nr-icon.chat_personal{background:#d1fae5;color:#059669;}
.nr-icon.chat_group{background:#fef3c7;color:#d97706;}

/* CHAT avatar style (like image reference) */
.nr-avatar-wrap{position:relative;flex-shrink:0;}
.nr-avatar{width:46px;height:46px;border-radius:50%;object-fit:cover;background:#e2e8f0;border:2px solid var(--white);}
.nr-avatar-badge{position:absolute;bottom:-2px;right:-2px;width:20px;height:20px;border-radius:50%;background:#2563eb;border:2px solid var(--white);display:flex;align-items:center;justify-content:center;font-size:10px;color:white;}
.nr-avatar-group{position:relative;width:46px;height:46px;flex-shrink:0;}
.nr-avatar-group .ga1{position:absolute;top:0;left:0;width:30px;height:30px;border-radius:50%;object-fit:cover;border:2px solid var(--white);background:#e2e8f0;}
.nr-avatar-group .ga2{position:absolute;top:0;right:0;width:24px;height:24px;border-radius:50%;object-fit:cover;border:2px solid var(--white);background:#cbd5e1;}
.nr-avatar-group .ga3{position:absolute;bottom:0;left:8px;width:24px;height:24px;border-radius:50%;object-fit:cover;border:2px solid var(--white);background:#94a3b8;}
.nr-avatar-group .gb{position:absolute;bottom:-2px;right:-2px;width:18px;height:18px;border-radius:50%;background:#2563eb;border:2px solid var(--white);display:flex;align-items:center;justify-content:center;font-size:9px;color:white;}

.nr-body{flex:1;min-width:0;}
.nr-title{font-size:13.5px;font-weight:700;color:var(--text);margin-bottom:2px;}
.nr-msg{font-size:12.5px;color:var(--text-muted);line-height:1.55;}
.nr-meta{display:flex;align-items:center;gap:6px;margin-top:5px;font-size:11.5px;color:var(--text-light);}
.nr-quote{background:#f1f5f9;border-left:3px solid #94a3b8;border-radius:4px;padding:5px 10px;font-size:12px;color:var(--text-muted);margin-top:5px;font-style:italic;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.nr-tag{display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;border-radius:999px;padding:2px 8px;font-size:11px;color:var(--text-muted);}
.nr-tag.green{background:#d1fae5;color:#059669;}
.nr-tag.blue{background:#dbeafe;color:#1d4ed8;}
.nr-tag.pink{background:#fce7f3;color:#db2777;}
.nr-tag.red{background:#fee2e2;color:#dc2626;}
.nr-tag.yellow{background:#fef3c7;color:#d97706;}
.nr-tag.purple{background:#ede9fe;color:#7c3aed;}

.nr-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0;}
.nr-time{font-size:11.5px;color:var(--text-light);white-space:nowrap;}
.nr-actions{display:flex;gap:6px;margin-top:4px;}
.btn-nr{border:1.5px solid var(--primary);color:var(--primary);background:none;border-radius:var(--radius-sm);padding:5px 12px;font-size:11.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;transition:.15s;font-family:inherit;}
.btn-nr:hover{background:var(--primary);color:white;}
.btn-nr.danger{border-color:#fca5a5;color:#ef4444;}
.btn-nr.danger:hover{background:#fef2f2;}

/* More btn */
.more-btn{background:none;border:none;color:var(--text-light);cursor:pointer;font-size:16px;padding:4px 6px;border-radius:var(--radius-sm);transition:.15s;}
.more-btn:hover{background:#f1f5f9;color:var(--text-muted);}

/* EMPTY */
.empty-state{text-align:center;padding:56px 20px;color:var(--text-light);}
.empty-state i{font-size:38px;margin-bottom:12px;display:block;opacity:.5;}
.empty-state p{font-size:14px;}

/* PAGINATION */
.pagination{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:22px;padding-top:14px;border-top:1px solid var(--border);}
.pg-info{font-size:13px;color:var(--text-muted);}
.pg-btn{width:34px;height:34px;border-radius:var(--radius-sm);border:1.5px solid var(--border);background:var(--white);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;transition:.15s;text-decoration:none;}
.pg-btn:hover{background:#f8fafc;color:var(--primary);}
.pg-sep{color:var(--text-light);font-size:13px;}

/* ===== MODAL ===== */
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);z-index:99999;display:none;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:var(--white);border-radius:24px;width:100%;max-width:460px;box-shadow:var(--shadow-lg);overflow:hidden;animation:modalIn .2s cubic-bezier(.34,1.36,.64,1);position:relative;}
@keyframes modalIn{from{opacity:0;transform:scale(.93) translateY(14px);}to{opacity:1;transform:none;}}
.modal-banner{width:100%;height:180px;display:flex;align-items:center;justify-content:center;font-size:52px;color:rgba(255,255,255,0.35);}
.modal-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.9);border:none;cursor:pointer;font-size:13px;color:#374151;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:.15s;}
.modal-close:hover{background:white;color:#ef4444;}
.modal-body{padding:22px 24px 26px;}
.modal-hdr{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;}
.modal-icon-wrap{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.modal-icon-wrap.review{background:#dbeafe;color:#1d4ed8;}
.modal-icon-wrap.wishlist{background:#fce7f3;color:#db2777;}
.modal-icon-wrap.trip{background:#d1fae5;color:#059669;}
.modal-icon-wrap.wacana{background:#fee2e2;color:#dc2626;}
.modal-icon-wrap.akun{background:#d1fae5;color:#16a34a;}
.modal-icon-wrap.invite{background:#ede9fe;color:#7c3aed;}
.modal-icon-wrap.chat_personal{background:#d1fae5;color:#059669;}
.modal-icon-wrap.chat_group{background:#fef3c7;color:#d97706;}
.modal-title2{font-size:17px;font-weight:800;color:var(--text);margin-bottom:2px;}
.modal-time2{font-size:12px;color:var(--text-light);}
.modal-msg2{font-size:14px;color:var(--text-muted);line-height:1.7;margin-bottom:18px;}
.modal-btn-main{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;border-radius:14px;background:var(--primary);color:white;font-size:14px;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:.2s;font-family:inherit;}
.modal-btn-main:hover{background:var(--primary-light);}
.modal-btn-back{background:none;border:1.5px solid var(--border);color:var(--text-muted);margin-top:8px;}
.modal-btn-back:hover{background:#f8fafc;color:var(--text);}
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




<!-- PAGE -->
<div class="page-wrap">
  <div class="page-grid">

    <!-- SIDEBAR -->
    <aside class="notif-sidebar">
      <div class="sidebar-hd"><i class="fa-regular fa-bell"></i> Notifikasi</div>
      <?php
      $sb = [
        ['f'=>'semua',       'i'=>'fa-solid fa-bell',                 'l'=>'Semua',            'c'=>$cnt_all],
        ['f'=>'belum_dibaca','i'=>'fa-regular fa-dot-circle',         'l'=>'Belum Dibaca',     'c'=>$cnt_unread],
        ['f'=>'wishlist',    'i'=>'fa-solid fa-heart',                'l'=>'Wishlist',         'c'=>$cnt_wishlist],
        ['f'=>'review',      'i'=>'fa-solid fa-star',                 'l'=>'Review',           'c'=>$cnt_review],
        ['f'=>'trip',        'i'=>'fa-solid fa-plane-departure',      'l'=>'Trip',             'c'=>$cnt_trip],
        ['f'=>'wacana',      'i'=>'fa-solid fa-fire',                 'l'=>'Wacana Trip',      'c'=>$cnt_wacana],
        ['f'=>'chat',        'i'=>'fa-solid fa-comments',             'l'=>'Pesan (Chat)',     'c'=>$cnt_chat],
        ['f'=>'akun',        'i'=>'fa-solid fa-user',                 'l'=>'Akun',             'c'=>$cnt_akun],
      ];
      foreach($sb as $si):
      ?>
      <a href="notifikasi.php?filter=<?= $si['f']; ?>" class="sb-item <?= $filter==$si['f']?'active':''; ?>">
        <span class="sb-left">
          <i class="<?= $si['i']; ?>"></i>
          <?= $si['l']; ?>
        </span>
        <?php if($si['c']>0): ?><span class="sb-count"><?= $si['c']; ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </aside>

    <!-- MAIN -->
    <div class="notif-main">
      <div class="main-top">
        <div>
          <div class="main-title">
            <?php
            $titles = ['semua'=>'Semua Notifikasi','belum_dibaca'=>'Belum Dibaca','wishlist'=>'Wishlist','review'=>'Review','trip'=>'Trip','wacana'=>'Wacana Trip','chat'=>'Pesan (Chat)','akun'=>'Akun'];
            echo $titles[$filter] ?? 'Notifikasi';
            ?>
          </div>
          <div class="main-sub"><?= $cnt_unread; ?> notifikasi belum dibaca</div>
        </div>
        <div class="main-actions">
          <form method="GET" style="display:flex;">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
            <div class="search-box">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" name="q" placeholder="Cari notifikasi..." value="<?= htmlspecialchars($search); ?>">
            </div>
          </form>
          <button class="btn-sm btn-outline-primary" onclick="markAllPage()"><i class="fa-regular fa-circle-check"></i> Tandai dibaca</button>
          <button class="btn-sm btn-outline-danger" onclick="deleteAll()"><i class="fa-regular fa-trash-can"></i> Hapus semua</button>
        </div>
      </div>

      <?php
      $type_icons = [
        'review'       => ['icon'=>'fa-solid fa-star',            'class'=>'review'],
        'wishlist'     => ['icon'=>'fa-solid fa-heart',           'class'=>'wishlist'],
        'trip'         => ['icon'=>'fa-solid fa-plane-departure', 'class'=>'trip'],
        'wacana'       => ['icon'=>'fa-solid fa-fire',            'class'=>'wacana'],
        'akun'         => ['icon'=>'fa-solid fa-user-check',      'class'=>'akun'],
        'invite'       => ['icon'=>'fa-solid fa-user-plus',       'class'=>'invite'],
        'chat_personal'=> ['icon'=>'fa-solid fa-message',         'class'=>'chat_personal'],
        'chat_group'   => ['icon'=>'fa-solid fa-comments',        'class'=>'chat_group'],
      ];

      function getNotifTitle($type, $msg, $from_nama='') {
        switch($type) {
          case 'wishlist':
            if(stripos($msg,'harga') !== false && (stripos($msg,'turun')!==false||stripos($msg,'diskon')!==false)) return 'Harga wishlist turun!';
            if(stripos($msg,'populer') !== false || stripos($msg,'trending') !== false) return 'Destinasi wishlist sedang populer';
            if(stripos($msg,'mirip') !== false || stripos($msg,'rekomendasi') !== false) return 'Rekomendasi untukmu';
            return 'Wishlist ditambahkan';
          case 'review':
            if(stripos($msg,'like') !== false || stripos($msg,'suka') !== false) return 'Review-mu mendapat like';
            if(stripos($msg,'balas') !== false || stripos($msg,'reply') !== false) return (!empty($from_nama)?$from_nama:'Seseorang').' membalas review-mu';
            return 'Terima kasih sudah memberi review!';
          case 'trip':
            if(stripos($msg,'besok') !== false || stripos($msg,'mulai') !== false || stripos($msg,'dimulai') !== false) return 'Trip akan segera dimulai!';
            if(stripos($msg,'diperbarui') !== false || stripos($msg,'jadwal') !== false || stripos($msg,'itinerary') !== false) return 'Jadwal trip diperbarui';
            if(stripos($msg,'undang') !== false || stripos($msg,'invite') !== false) return (!empty($from_nama)?$from_nama:'Seseorang').' mengundangmu ikut trip';
            return 'Info perjalanan';
          case 'wacana':
            return 'Wacana trip baru tersedia 🔥';
          case 'akun':
            if(stripos($msg,'level') !== false || stripos($msg,'naik') !== false || stripos($msg,'tingkat') !== false) return 'Level Traveller kamu naik!';
            if(stripos($msg,'nama') !== false && stripos($msg,'email') !== false) return 'Data akun berhasil diperbarui';
            if(stripos($msg,'nama') !== false) return 'Nama berhasil diperbarui';
            if(stripos($msg,'email') !== false) return 'Email berhasil diperbarui';
            if(stripos($msg,'foto') !== false || stripos($msg,'profil') !== false) return 'Foto profil berhasil diperbarui';
            return 'Profil berhasil diperbarui';
          case 'invite':
            return (!empty($from_nama)?$from_nama:'Seseorang').' mengundangmu bergabung ke trip';
          case 'chat_personal':
            return !empty($from_nama) ? $from_nama.' mengirim pesan' : 'Pesan masuk';
          case 'chat_group':
            return 'Pesan baru di grup trip';
          default:
            return 'Notifikasi';
        }
      }

      function getNotifSubtag($type, $msg) {
        switch($type) {
          case 'wishlist':
            if(stripos($msg,'harga') !== false && (stripos($msg,'turun')!==false||stripos($msg,'diskon')!==false)) return ['💰 Harga turun','pink'];
            if(stripos($msg,'populer') !== false) return ['🔥 Sedang populer','red'];
            return ['❤️ Wishlist','pink'];
          case 'review':
            if(stripos($msg,'like')!==false||stripos($msg,'suka')!==false) return ['⭐ Like baru','blue'];
            if(stripos($msg,'balas')!==false) return ['💬 Balasan','blue'];
            return ['⭐ Review','blue'];
          case 'trip':
            if(stripos($msg,'besok')!==false||stripos($msg,'mulai')!==false) return ['✈️ Segera berangkat','green'];
            if(stripos($msg,'diperbarui')!==false) return ['📋 Perubahan itinerary','yellow'];
            return ['✈️ Trip','green'];
          case 'wacana': return ['🔥 Wacana baru','red'];
          case 'akun':
            if(stripos($msg,'level')!==false) return ['🏅 Level up!','purple'];
            return ['✅ Akun','green'];
          case 'invite': return ['🎉 Undangan','purple'];
          case 'chat_personal': return ['💬 Pesan','green'];
          case 'chat_group': return ['👥 Grup','yellow'];
          default: return null;
        }
      }

      $type_actions = [
        'review'       => ['label'=>'Lihat Review',   'href'=>'detail.php'],
        'wishlist'     => ['label'=>'Buka Wishlist',  'href'=>'wishlist.php'],
        'trip'         => ['label'=>'Lihat Trip',     'href'=>'trip.php'],
        'wacana'       => ['label'=>'Cek Sekarang',   'href'=>'trip.php'],
        'akun'         => ['label'=>'Lihat Profil',   'href'=>'profil.php'],
        'invite'       => ['label'=>'Buka Trip',      'href'=>'trip.php'],
        'chat_personal'=> ['label'=>'Buka Chat',      'href'=>'trip_group.php'],
        'chat_group'   => ['label'=>'Buka Grup',      'href'=>'trip_group.php'],
      ];

      $banner_grad = [
        'review'       => 'linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%)',
        'wishlist'     => 'linear-gradient(135deg,#db2777 0%,#f472b6 100%)',
        'trip'         => 'linear-gradient(135deg,#059669 0%,#34d399 100%)',
        'wacana'       => 'linear-gradient(135deg,#dc2626 0%,#f87171 100%)',
        'akun'         => 'linear-gradient(135deg,#16a34a 0%,#4ade80 100%)',
        'invite'       => 'linear-gradient(135deg,#7c3aed 0%,#a78bfa 100%)',
        'chat_personal'=> 'linear-gradient(135deg,#0891b2 0%,#67e8f9 100%)',
        'chat_group'   => 'linear-gradient(135deg,#d97706 0%,#fcd34d 100%)',
      ];

      $notif_rows = [];
      while($n = mysqli_fetch_assoc($notifs_q)) $notif_rows[] = $n;

      if(empty($notif_rows)):
      ?>
      <div class="empty-state">
        <i class="fa-regular fa-bell-slash"></i>
        <p>Tidak ada notifikasi<?= $filter!='semua' ? ' di kategori ini' : ''; ?>.</p>
      </div>
      <?php else: foreach($notif_rows as $n):
        $type = $n['type'] ?? 'invite';
        $ic = $type_icons[$type] ?? $type_icons['invite'];
        $from_nama = $n['from_nama'] ?? '';
        $from_foto = $n['from_foto'] ?? 'kimi.jpg';
        $row_title = getNotifTitle($type, $n['message'], $from_nama);
        $subtag = getNotifSubtag($type, $n['message']);
        $act = $type_actions[$type] ?? ['label'=>'Lihat','href'=>'trip.php'];
        $action_href = $act['href'];
        if(!empty($n['link_url'])){
          $action_href = $n['link_url'];
        } elseif($n['trip_id'] && in_array($type,['trip','invite','chat_personal','chat_group','wacana'])) {
          $tab = $type==='chat_personal'?'&tab=personal':($type==='chat_group'?'&tab=chat':'');
          $action_href = "trip_group.php?id=".$n['trip_id'].$tab;
        }
        $time_fmt = timeAgo(strtotime($n['created_at']));
        $is_chat = in_array($type, ['chat_personal','chat_group']);
        $bg_grad = $banner_grad[$type] ?? 'linear-gradient(135deg,#17375e,#234d7d)';
        $j_title  = addslashes($row_title);
        $j_msg    = addslashes($n['message']);
        $j_time   = addslashes($time_fmt);
        $j_class  = $ic['class'];
        $j_icon   = addslashes($ic['icon']);
        $j_href   = addslashes($action_href);
        $j_label  = addslashes($act['label']);
        $j_grad   = addslashes($bg_grad);
      ?>
      <div class="notif-row <?= $n['is_read']==0?'unread':''; ?>" id="nrow-<?= $n['id']; ?>"
           onclick="openModal(<?= $n['id']; ?>,'<?= $j_title; ?>','<?= $j_msg; ?>','<?= $j_time; ?>','<?= $j_class; ?>','<?= $j_icon; ?>','<?= $j_href; ?>','<?= $j_label; ?>','<?= $j_grad; ?>',<?= $n['is_read']; ?>)">

        <div class="nr-dot <?= $n['is_read']==1?'hidden':''; ?>" id="dot-<?= $n['id']; ?>"></div>

        <?php if($is_chat && !empty($from_nama)): ?>
          <?php if($type==='chat_personal'): ?>
          <div class="nr-avatar-wrap">
            <img class="nr-avatar" src="assets/img/profil/<?= htmlspecialchars($from_foto); ?>" alt="<?= htmlspecialchars($from_nama); ?>" onerror="this.src='assets/img/profil/kimi.jpg'">
            <div class="nr-avatar-badge"><i class="fa-solid fa-message" style="font-size:7px;"></i></div>
          </div>
          <?php else: ?>
          <div class="nr-avatar-group">
            <img class="ga1" src="assets/img/profil/<?= htmlspecialchars($from_foto); ?>" alt="" onerror="this.src='assets/img/profil/kimi.jpg'">
            <div class="ga2" style="background:#c7d2fe;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#3730a3;">+2</div>
            <div class="ga3" style="background:#fde68a;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:#92400e;">G</div>
            <div class="gb"><i class="fa-solid fa-comments" style="font-size:7px;"></i></div>
          </div>
          <?php endif; ?>
        <?php else: ?>
        <div class="nr-icon <?= $ic['class']; ?>"><i class="<?= $ic['icon']; ?>"></i></div>
        <?php endif; ?>

        <div class="nr-body">
          <div class="nr-title"><?= htmlspecialchars($row_title); ?></div>
          <div class="nr-msg">
            <?php if($is_chat): ?>
              <?php
              $preview = htmlspecialchars(mb_strimwidth($n['message'], 0, 60, '...'));
              echo '"'.$preview.'"';
              ?>
            <?php elseif($type==='akun' && stripos($n['message'],'level')!==false): ?>
              <span style="color:#7c3aed;font-weight:600;"><?= htmlspecialchars($n['message']); ?></span>
            <?php else: ?>
              <?= htmlspecialchars($n['message']); ?>
            <?php endif; ?>
          </div>
          <?php if($is_chat): ?>
          <div class="nr-meta">
            <?php if($n['trip_id'] && !empty($n['nama_trip'])): ?>
            <span class="nr-tag <?= $type==='chat_group'?'yellow':'green'; ?>"><i class="fa-solid fa-users" style="font-size:10px;"></i> <?= htmlspecialchars($n['nama_trip']); ?></span>
            <?php endif; ?>
          </div>
          <?php elseif($type==='trip' && !empty($n['nama_trip'])): ?>
          <div class="nr-meta">
            <span class="nr-tag green"><i class="fa-regular fa-calendar" style="font-size:10px;"></i> <?= htmlspecialchars($n['nama_trip']); ?></span>
          </div>
          <?php elseif($subtag): ?>
          <div class="nr-meta"><span class="nr-tag <?= $subtag[1]; ?>"><?= $subtag[0]; ?></span></div>
          <?php endif; ?>
        </div>

        <div class="nr-right">
          <span class="nr-time"><?= $time_fmt; ?></span>
          <div class="nr-actions" onclick="event.stopPropagation()">
            <button class="more-btn" title="Hapus" onclick="deleteOne(<?= $n['id']; ?>)"><i class="fa-solid fa-ellipsis-vertical"></i></button>
          </div>
        </div>
      </div>
      <?php endforeach; endif; ?>

      <!-- PAGINATION -->
      <?php if($total_pages > 1): ?>
      <div class="pagination">
        <?php $prev=max(1,$page-1); $next=min($total_pages,$page+1); $qs=($search?'&q='.urlencode($search):''); ?>
        <a href="notifikasi.php?filter=<?= $filter; ?>&page=<?= $prev; ?><?= $qs; ?>" class="pg-btn" <?= $page<=1?'style="pointer-events:none;opacity:.35"':''; ?>><i class="fa-solid fa-chevron-left"></i></a>
        <span class="pg-sep">—</span>
        <span class="pg-info">Halaman <?= $page; ?> dari <?= $total_pages; ?></span>
        <span class="pg-sep">—</span>
        <a href="notifikasi.php?filter=<?= $filter; ?>&page=<?= $next; ?><?= $qs; ?>" class="pg-btn" <?= $page>=$total_pages?'style="pointer-events:none;opacity:.35"':''; ?>><i class="fa-solid fa-chevron-right"></i></a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-banner" id="modalBanner"></div>
    <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
    <div class="modal-body">
      <div class="modal-hdr">
        <div class="modal-icon-wrap" id="mIcon"></div>
        <div>
          <div class="modal-title2" id="mTitle"></div>
          <div class="modal-time2" id="mTime"></div>
        </div>
      </div>
      <div class="modal-msg2" id="mMsg"></div>
      <a href="#" class="modal-btn-main" id="mAction"></a>
      <button class="modal-btn-main modal-btn-back" onclick="closeModal()"><i class="fa-solid fa-arrow-left"></i> Kembali</button>
    </div>
  </div>
</div>

<script>
const _GRD={
  review:'linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%)',
  wishlist:'linear-gradient(135deg,#db2777 0%,#f472b6 100%)',
  trip:'linear-gradient(135deg,#059669 0%,#34d399 100%)',
  wacana:'linear-gradient(135deg,#dc2626 0%,#f87171 100%)',
  akun:'linear-gradient(135deg,#16a34a 0%,#4ade80 100%)',
  invite:'linear-gradient(135deg,#7c3aed 0%,#a78bfa 100%)',
  chat_personal:'linear-gradient(135deg,#0891b2 0%,#67e8f9 100%)',
  chat_group:'linear-gradient(135deg,#d97706 0%,#fcd34d 100%)',
};
const _ICONS={
  review:'<i class="fa-solid fa-star"></i>',
  wishlist:'<i class="fa-solid fa-heart"></i>',
  trip:'<i class="fa-solid fa-plane-departure"></i>',
  wacana:'<i class="fa-solid fa-fire"></i>',
  akun:'<i class="fa-solid fa-user-check"></i>',
  invite:'<i class="fa-solid fa-user-plus"></i>',
  chat_personal:'<i class="fa-solid fa-message"></i>',
  chat_group:'<i class="fa-solid fa-comments"></i>',
};

function openModal(id,title,msg,time,cls,icon,href,label,grad,isRead){
  if(!isRead){
    fetch('proses/notif_proses.php?action=read_one&id='+id);
    var d=document.getElementById('dot-'+id);
    if(d){d.classList.add('hidden');}
    var r=document.getElementById('nrow-'+id);
    if(r){r.classList.remove('unread');}
    _chkN();
  }
  document.getElementById('modalBanner').style.background=_GRD[cls]||'linear-gradient(135deg,#17375e,#234d7d)';
  document.getElementById('modalBanner').innerHTML='<i class="'+icon+'" style="font-size:52px;color:rgba(255,255,255,0.28);"></i>';
  var mi=document.getElementById('mIcon');
  mi.className='modal-icon-wrap '+cls;
  mi.innerHTML=_ICONS[cls]||'<i class="fa-solid fa-bell"></i>';
  document.getElementById('mTitle').textContent=title;
  document.getElementById('mTime').textContent=time;
  document.getElementById('mMsg').textContent=msg;
  var btn=document.getElementById('mAction');
  if(label){btn.style.display='flex';btn.textContent=label;btn.href=href;}
  else{btn.style.display='none';}
  document.getElementById('modalOverlay').classList.add('open');
  document.body.style.overflow='hidden';
}
function closeModal(){
  document.getElementById('modalOverlay').classList.remove('open');
  document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal();});

function toggleNotif(e){
  e.stopPropagation();
  document.getElementById('profileDropdown').classList.remove('open');
  var dd=document.getElementById('notifDropdown');
  dd.classList.toggle('open');
  if(dd.classList.contains('open')) _loadNL();
}
function toggleProfile(e){
  e.stopPropagation();
  document.getElementById('notifDropdown').classList.remove('open');
  document.getElementById('profileDropdown').classList.toggle('open');
}
document.addEventListener('click',function(){
  document.getElementById('notifDropdown').classList.remove('open');
  document.getElementById('profileDropdown').classList.remove('open');
});
['notifDropdown','profileDropdown'].forEach(function(id){
  var el=document.getElementById(id);
  if(el) el.addEventListener('click',function(e){e.stopPropagation();});
});

function _loadNL(){
  fetch('proses/notif_proses.php?action=list').then(function(r){return r.json();}).then(function(list){
    var el=document.getElementById('notifList');
    if(!list||!list.length){el.innerHTML='<div class="notif-empty-dd">Belum ada notifikasi</div>';return;}
    var html=list.slice(0,5).map(function(n){
      var ic=_ICONS[n.type]||'<i class="fa-solid fa-bell"></i>';
      var lk=n.link_url?n.link_url:(n.trip_id?'trip_group.php?id='+n.trip_id:'trip.php');
      var parts=n.message.split('.');
      var title=parts[0]||n.message;
      return '<div class="notif-item '+(n.is_read==0?'unread':'')+'\" onclick="_goN('+n.id+',\''+lk+'\')">'+
        '<div class="notif-icon-wrap '+(n.type||'invite')+'">'+ic+'</div>'+
        '<div class="notif-body">'+
          '<div class="notif-title">'+title+'</div>'+
          '<div class="notif-msg">'+n.message+'</div>'+
          '<div class="notif-time">'+n.time+'</div>'+
        '</div>'+
        (n.is_read==0?'<div class="notif-dot"></div>':'')+
      '</div>';
    }).join('');
    el.innerHTML=html;
  }).catch(function(){});
}
function _goN(id,link){
  fetch('proses/notif_proses.php?action=read_one&id='+id).then(function(){window.location.href=link;});
}
function readAll(){
  fetch('proses/notif_proses.php?action=read_all').then(function(){
    document.getElementById('notifBadge').style.display='none';
    _loadNL();
  });
}
function _chkN(){
  fetch('proses/notif_proses.php?action=count').then(function(r){return r.json();}).then(function(d){
    var b=document.getElementById('notifBadge');
    if(!b)return;
    if(d.count>0){b.textContent=d.count>99?'99+':d.count;b.style.display='flex';}
    else{b.style.display='none';}
  }).catch(function(){});
}
function markAllPage(){
  fetch('proses/notif_proses.php?action=read_all').then(function(){location.reload();});
}
function deleteAll(){
  if(!confirm('Hapus semua notifikasi?'))return;
  fetch('proses/notif_proses.php?action=delete_all',{method:'POST'}).then(function(){location.reload();});
}
function deleteOne(id){
  if(!confirm('Hapus notifikasi ini?'))return;
  fetch('proses/notif_proses.php?action=delete_one&id='+id,{method:'POST'}).then(function(){
    var r=document.getElementById('nrow-'+id);
    if(r)r.remove();
  });
}
_chkN();setInterval(_chkN,8000);
</script>


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
