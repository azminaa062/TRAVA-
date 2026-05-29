<?php
session_start();
include '../config/koneksi.php';
include 'auth/cek_login.php';

$total_wisata = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM wisata"));
$total_user   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users"));
$total_review = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM review"));
$total_trip   = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM trip"));

// Fixed: use correct column names user_id & wisata_id per SQL schema
$review_terbaru = mysqli_query($conn,
    "SELECT r.*, u.nama AS nama_user, w.nama AS nama_wisata
     FROM review r
     JOIN users u ON r.user_id = u.id
     JOIN wisata w ON r.wisata_id = w.id
     ORDER BY r.id DESC
     LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TRAVA — Dashboard Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar:#1a3a5c;--sidebar-hover:rgba(255,255,255,0.07);--sidebar-active:rgba(255,255,255,0.13);
  --sidebar-border:rgba(255,255,255,0.1);--sidebar-w:264px;
  --page:#f0f2f5;--page-alt:#e4e7eb;--card:#ffffff;--card-hover:#f8f9fa;
  --border:rgba(0,0,0,0.08);--shadow:0 2px 8px rgba(0,0,0,0.06);--shadow-hi:0 6px 20px rgba(0,0,0,0.10);
  --tx-1:#1a202c;--tx-2:#4a5568;--tx-3:#a0aec0;
  --terra:#c0522a;--gold:#b08430;--forest:#3a6b45;--ocean:#1a5c7a;
  --s-tx:#d6e8f5;--s-muted:#7fa4c2;
}
body{background:var(--page);color:var(--tx-1);font-family:'Lato',sans-serif;font-size:14px;line-height:1.6;min-height:100vh;display:flex;}
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--sidebar);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:50;overflow:hidden;}
.sidebar::before{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg width='40' height='40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 40 L40 0' stroke='white' stroke-width='0.4' stroke-opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;}
.sidebar-logo{padding:40px 22px 14px;border-bottom:1px solid var(--sidebar-border);position:relative;}
.sidebar-logo::after{content:'';position:absolute;bottom:0;left:22px;right:22px;height:1px;background:linear-gradient(90deg,transparent,rgba(176,132,48,0.4),transparent);}
.logo-row{display:flex;align-items:center;}
.logo-img{height:38px;width:auto;object-fit:contain;display:block;}
.logo-tagline{margin-top:4px;font-size:10.5px;font-weight:300;color:var(--s-muted);letter-spacing:0.12em;text-transform:uppercase;}
.nav-item{display:flex;align-items:center;gap:12px;margin:2px 12px;padding:11px 14px;border-radius:8px;text-decoration:none;color:var(--s-muted);font-size:13.5px;font-weight:400;transition:all 0.2s;position:relative;}
.nav-item i{width:18px;text-align:center;font-size:14px;flex-shrink:0;}
.nav-item:hover{background:var(--sidebar-hover);color:var(--s-tx);}
.nav-item.active{background:var(--sidebar-active);color:#fff;font-weight:700;}
.nav-item.active i{color:#f0c060;}
.nav-item.active::before{content:'';position:absolute;left:0;top:6px;bottom:6px;width:3px;background:#f0c060;border-radius:0 3px 3px 0;}
.sidebar-bottom{margin-top:auto;padding:16px 12px;border-top:1px solid var(--sidebar-border);}
.admin-pill{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;background:rgba(255,255,255,0.06);border:1px solid var(--sidebar-border);margin-bottom:6px;}
.admin-avatar{width:32px;height:32px;border-radius:50%;background:rgba(240,192,96,0.2);border:1px solid rgba(240,192,96,0.3);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#f0c060;}
.admin-name{font-size:13px;font-weight:700;color:#fff;line-height:1.2;}
.admin-role{font-size:11px;color:var(--s-muted);}
.main{margin-left:var(--sidebar-w);flex:1;padding:38px 36px 60px;position:relative;z-index:1;}
.topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;}
.page-title{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--tx-1);letter-spacing:-0.3px;line-height:1;}
.page-title span{font-style:italic;font-weight:400;color:var(--gold);}
.topbar-date{display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:11px 18px;font-size:12.5px;color:var(--tx-2);box-shadow:var(--shadow);white-space:nowrap;align-self:flex-start;margin-top:4px;}
.topbar-date i{color:var(--gold);}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:38px;}
.scard{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px 22px;position:relative;overflow:hidden;box-shadow:var(--shadow);transition:transform 0.24s,box-shadow 0.24s;cursor:default;animation:riseUp 0.5s ease both;}
.scard:hover{transform:translateY(-4px);box-shadow:var(--shadow-hi);}
.scard::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;}
.scard.ct::after{background:var(--terra);}
.scard.cg::after{background:var(--gold);}
.scard.cf::after{background:var(--forest);}
.scard.co::after{background:var(--ocean);}
.scard:nth-child(1){animation-delay:.05s}
.scard:nth-child(2){animation-delay:.12s}
.scard:nth-child(3){animation-delay:.19s}
.scard:nth-child(4){animation-delay:.26s}
.sc-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:18px;}
.scard.ct .sc-icon{background:rgba(192,82,42,0.1);color:var(--terra);}
.scard.cg .sc-icon{background:rgba(176,132,48,0.1);color:var(--gold);}
.scard.cf .sc-icon{background:rgba(58,107,69,0.1);color:var(--forest);}
.scard.co .sc-icon{background:rgba(26,92,122,0.1);color:var(--ocean);}
.sc-label{font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--tx-3);margin-bottom:4px;}
.sc-val{font-family:'Cormorant Garamond',serif;font-size:46px;font-weight:700;letter-spacing:-1.5px;line-height:1;color:var(--tx-1);}
.sc-hint{margin-top:8px;font-size:12px;color:var(--tx-3);font-weight:300;}
.section-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
.section-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--tx-1);display:flex;align-items:center;gap:10px;}
.section-title::before{content:'';display:inline-block;width:10px;height:10px;background:var(--terra);clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);flex-shrink:0;}
.section-link{font-size:12.5px;color:var(--ocean);text-decoration:none;display:flex;align-items:center;gap:5px;font-weight:700;opacity:0.8;}
.section-link:hover{opacity:1;}
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);animation:riseUp 0.5s 0.32s ease both;}
table{width:100%;border-collapse:collapse;}
thead tr{background:var(--page-alt);border-bottom:2px solid var(--border);}
thead th{padding:13px 18px;font-size:10.5px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--tx-3);text-align:left;white-space:nowrap;}
tbody tr{border-bottom:1px solid rgba(0,0,0,0.04);transition:background 0.14s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(79,124,172,0.07);}
tbody td{padding:14px 18px;vertical-align:middle;color:var(--tx-1);font-size:13.5px;}
.row-no{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border:1px solid var(--border);border-radius:6px;font-size:11px;color:var(--tx-3);font-weight:700;}
.user-cell{display:flex;align-items:center;gap:11px;}
.u-avatar{width:32px;height:32px;border-radius:50%;background:var(--sidebar);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;text-transform:uppercase;}
.dest-tag{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;background:rgba(26,58,92,0.07);border:1px solid rgba(26,58,92,0.15);color:var(--sidebar);font-size:12px;font-weight:700;}
.rb{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:700;}
.rb.r5{background:rgba(58,107,69,0.1);border:1px solid rgba(58,107,69,0.2);color:var(--forest);}
.rb.r4{background:rgba(26,92,122,0.1);border:1px solid rgba(26,92,122,0.2);color:var(--ocean);}
.rb.r3{background:rgba(176,132,48,0.1);border:1px solid rgba(176,132,48,0.2);color:#8a6010;}
.rb.r2,.rb.r1{background:rgba(192,82,42,0.1);border:1px solid rgba(192,82,42,0.2);color:var(--terra);}
.komentar{color:var(--tx-2);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;}
@keyframes riseUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:1100px){.stats-grid{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:20px}.stats-grid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-row">
      <img src="../assets/img/logo-trava.png" alt="TRAVA" class="logo-img">
    </div>
    <div class="logo-tagline">Panel Pengelola Wisata</div>
  </div>
  <a href="index.php" class="nav-item active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
  <a href="wisata/data.php" class="nav-item"><i class="fa-solid fa-map-location-dot"></i> Data Wisata</a>
  <a href="user/data.php" class="nav-item"><i class="fa-solid fa-users"></i> Data Pengguna</a>
  <a href="review/data.php" class="nav-item"><i class="fa-solid fa-star"></i> Data Review</a>
  <a href="../logout.php" class="nav-item" style="margin-top:4px;"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
  <div class="sidebar-bottom">
    <div class="admin-pill">
      <div class="admin-avatar">A</div>
      <div><div class="admin-name">Administrator</div><div class="admin-role">Pengelola Utama</div></div>
    </div>
  </div>
</aside>
<main class="main">
  <div class="topbar">
    <div><div class="page-title">Dashboard <span>Admin</span></div></div>
    <div class="topbar-date"><i class="fa-regular fa-calendar"></i><span id="tanggal"></span></div>
  </div>
  <div class="stats-grid">
    <div class="scard ct">
      <div class="sc-icon"><i class="fa-solid fa-torii-gate"></i></div>
      <div class="sc-label">Total Wisata</div>
      <div class="sc-val"><?= $total_wisata ?></div>
      <div class="sc-hint">Destinasi terdaftar</div>
    </div>
    <div class="scard cg">
      <div class="sc-icon"><i class="fa-solid fa-users"></i></div>
      <div class="sc-label">Total Pengguna</div>
      <div class="sc-val"><?= $total_user ?></div>
      <div class="sc-hint">Akun aktif</div>
    </div>
    <div class="scard cf">
      <div class="sc-icon"><i class="fa-solid fa-star"></i></div>
      <div class="sc-label">Total Review</div>
      <div class="sc-val"><?= $total_review ?></div>
      <div class="sc-hint">Review masuk</div>
    </div>
    <div class="scard co">
      <div class="sc-icon"><i class="fa-solid fa-route"></i></div>
      <div class="sc-label">Total Perjalanan</div>
      <div class="sc-val"><?= $total_trip ?></div>
      <div class="sc-hint">Trip tercatat</div>
    </div>
  </div>
  <div class="section-row">
    <div class="section-title">Review Terbaru</div>
    <a href="review/data.php" class="section-link">Lihat semua &rarr;</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No</th><th>Pengguna</th><th>Destinasi</th><th>Penilaian</th><th>Komentar</th>
        </tr>
      </thead>
      <tbody>
      <?php if(mysqli_num_rows($review_terbaru)===0): ?>
        <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--tx-3);">Belum ada review.</td></tr>
      <?php else: $no=1; while($row=mysqli_fetch_assoc($review_terbaru)):
        $init=strtoupper(mb_substr($row['nama_user'],0,1));
        $rating=(int)$row['rating'];
        $stars=str_repeat('★',$rating).str_repeat('☆',5-$rating);
      ?>
        <tr>
          <td><span class="row-no"><?= $no++ ?></span></td>
          <td><div class="user-cell"><div class="u-avatar"><?= $init ?></div><?= htmlspecialchars($row['nama_user']) ?></div></td>
          <td><span class="dest-tag"><i class="fa-solid fa-location-dot" style="font-size:10px;"></i><?= htmlspecialchars($row['nama_wisata']) ?></span></td>
          <td><span class="rb r<?= $rating ?>"><?= $stars ?> <?= $rating ?>/5</span></td>
          <td><span class="komentar" title="<?= htmlspecialchars($row['komentar']) ?>"><?= htmlspecialchars($row['komentar']) ?></span></td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</main>
<script>document.getElementById('tanggal').textContent=new Date().toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});</script>
</body>
</html>
