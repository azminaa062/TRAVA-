<?php
session_start();
include '../../config/koneksi.php';
include '../auth/cek_login.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_col = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$allowed_sort = ['id','nama','kategori','harga','rating_avg'];
if(!in_array($sort_col, $allowed_sort)) $sort_col = 'id';

$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 5;
if(!in_array($per_page, [5,10,25,50])) $per_page = 5;

$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;

$where = $search ? "WHERE nama LIKE '%$search%' OR kategori LIKE '%$search%' OR deskripsi LIKE '%$search%' OR harga LIKE '%$search%'" : '';
$total_rows = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM wisata $where"))['c'];
$total_pages = max(1, ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Sort ASC for id so new data appears at end
$data = mysqli_query($conn, "SELECT * FROM wisata $where ORDER BY $sort_col $sort_dir LIMIT $per_page OFFSET $offset");

function sort_link_w($col, $label, $cc, $cd, $s, $pp) {
  $next_dir = ($cc===$col && $cd==='ASC') ? 'desc' : 'asc';
  $q = http_build_query(['search'=>$s,'sort'=>$col,'dir'=>$next_dir,'page'=>1,'per_page'=>$pp]);
  return "<a href='data.php?$q' class='sort-link'>$label</a>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Wisata — TRAVA Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--sidebar:#1a3a5c;--sidebar-hover:rgba(255,255,255,0.07);--sidebar-active:rgba(255,255,255,0.13);--sidebar-border:rgba(255,255,255,0.1);--sidebar-w:264px;--page:#f0f2f5;--page-alt:#e4e7eb;--card:#ffffff;--card-hover:#f8f9fa;--border:rgba(0,0,0,0.08);--border-hi:rgba(120,90,60,0.28);--shadow:0 2px 8px rgba(0,0,0,0.06);--tx-1:#1a202c;--tx-2:#4a5568;--tx-3:#a0aec0;--terra:#c0522a;--gold:#b08430;--forest:#3a6b45;--ocean:#1a5c7a;--s-tx:#d6e8f5;--s-muted:#7fa4c2;}
body{background:var(--page);color:var(--tx-1);font-family:'Lato',sans-serif;font-size:14px;line-height:1.6;min-height:100vh;display:flex;}
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='30' cy='30' r='1.5' fill='%23b08430' fill-opacity='0.07'/%3E%3Ccircle cx='60' cy='0' r='1.5' fill='%23b08430' fill-opacity='0.07'/%3E%3Ccircle cx='0' cy='60' r='1.5' fill='%23b08430' fill-opacity='0.07'/%3E%3C/svg%3E");pointer-events:none;z-index:0;}
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--sidebar);display:flex;flex-direction:column;position:fixed;top:0;left:0;z-index:50;}
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
.main{margin-left:var(--sidebar-w);flex:1;padding:38px 36px;position:relative;z-index:1;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.topbar-left{flex:1;}
.page-kicker{font-size:11px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--terra);margin-bottom:6px;}
.page-title{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--tx-1);letter-spacing:-0.3px;line-height:1;}
.page-title span{font-style:italic;font-weight:400;color:var(--gold);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-date{display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:11px 18px;font-size:12.5px;color:var(--tx-2);box-shadow:var(--shadow);white-space:nowrap;}
.topbar-date i{color:var(--gold);}
/* Toolbar: row1 = tambah button; row2 = show entries (left) + search (right) */
.toolbar-row1{display:flex;align-items:center;margin-bottom:10px;}
.toolbar-row2{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.toolbar-row2-left{display:flex;align-items:center;gap:8px;}
.toolbar-row2-right{display:flex;align-items:center;gap:8px;}
.btn-tambah{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;background:#b08430;color:#fff;font-size:13px;font-weight:700;text-decoration:none;transition:all 0.18s;border:none;cursor:pointer;white-space:nowrap;}
.btn-tambah:hover{background:#a3411f;transform:translateY(-1px);}
.show-entries{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--tx-2);white-space:nowrap;}
.show-entries select{padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;background:var(--page);font-family:'Lato',sans-serif;font-size:13px;color:var(--tx-1);outline:none;cursor:pointer;}
.show-entries select:focus{border-color:var(--gold);}
.search-wrap{position:relative;}
.search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--tx-3);font-size:13px;}
.search-wrap input{width:240px;padding:9px 14px 9px 36px;border:1.5px solid var(--border);border-radius:8px;background:var(--page);font-family:'Lato',sans-serif;font-size:13px;color:var(--tx-1);outline:none;transition:border-color 0.18s;}
.search-wrap input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(176,132,48,0.1);}
.btn-search{padding:9px 14px;border-radius:8px;background:#b08430;color:#fff;border:none;font-family:'Lato',sans-serif;font-size:13px;font-weight:700;cursor:pointer;}
.btn-reset{padding:9px 12px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--tx-2);font-size:13px;text-decoration:none;display:inline-flex;align-items:center;}
.table-info{font-size:12.5px;color:var(--tx-3);margin-bottom:8px;}
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);animation:riseUp 0.5s 0.12s ease both;}
table{width:100%;border-collapse:collapse;}
thead tr{background:var(--page-alt);border-bottom:2px solid var(--border);}
thead th{padding:13px 18px;font-size:10.5px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--tx-3);text-align:left;white-space:nowrap;}
tbody tr{border-bottom:1px solid rgba(120,90,60,0.07);transition:background 0.14s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(79,124,172,0.07);}
tbody td{padding:14px 18px;vertical-align:middle;color:var(--tx-1);font-size:13.5px;}
.row-no{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border:1px solid var(--border);border-radius:6px;font-size:11px;color:var(--tx-3);font-weight:700;}
.wisata-img{width:72px;height:52px;object-fit:cover;border-radius:8px;border:1px solid var(--border);}
.nama-cell{font-weight:600;color:var(--tx-1);}
.kat-text{font-size:13px;color:var(--tx-2);}
.harga-cell{font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--forest);}
.rating-cell{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:700;color:#8a6010;}
.btn-edit{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:20px;background:rgba(176,132,48,0.12);border:1px solid rgba(176,132,48,0.3);color:var(--gold);font-size:12px;font-weight:700;text-decoration:none;transition:all 0.18s;margin-right:6px;}
.btn-edit:hover{background:var(--gold);color:#fff;border-color:var(--gold);}
.btn-hapus{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:20px;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.25);color:#dc2626;font-size:12px;font-weight:700;text-decoration:none;transition:all 0.18s;}
.btn-hapus:hover{background:#dc2626;color:#fff;border-color:#dc2626;}
.empty-state{text-align:center;padding:52px 20px;color:var(--tx-3);}
.empty-state i{font-size:36px;margin-bottom:12px;opacity:0.4;display:block;}
/* Pagination with Prev/Next text */
.pagination-wrap{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px;}
.pagination-info{font-size:12.5px;color:var(--tx-3);}
.pagination{display:flex;align-items:center;gap:5px;}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;height:34px;padding:0 12px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;transition:all 0.15s;border:1px solid var(--border);color:var(--tx-2);background:var(--card);white-space:nowrap;}
.pagination a:hover{background:var(--page-alt);}
.pagination span.current{background:#b08430;color:#fff;border-color:#b08430;}
.pagination span.dots{border:none;background:none;color:var(--tx-3);padding:0 4px;}
.pagination a.prev-next{gap:5px;font-size:12.5px;}
.sort-link{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;}



@keyframes riseUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:768px){.sidebar{display:none}.main{margin-left:0;padding:20px}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-row">
      <img src="../../assets/img/logo-trava.png" alt="TRAVA" class="logo-img">
    </div>
    <div class="logo-tagline">Panel Pengelola Wisata</div>
  </div>
  <a href="../index.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
  <a href="../wisata/data.php" class="nav-item active"><i class="fa-solid fa-map-location-dot"></i>Data Wisata</a>
  <a href="../user/data.php" class="nav-item"><i class="fa-solid fa-users"></i>Data Pengguna</a>
  <a href="../review/data.php" class="nav-item"><i class="fa-solid fa-star"></i> Data Review</a>
  <a href="../../logout.php" class="nav-item" style="margin-top:4px;"><i class="fa-solid fa-right-from-bracket"></i>Keluar</a>
  <div class="sidebar-bottom">
    <div class="admin-pill">
      <div class="admin-avatar">A</div>
      <div><div class="admin-name">Administrator</div><div class="admin-role">Pengelola Utama</div></div>
    </div>
  </div>
</aside>
<main class="main">
  <div class="topbar">
    <div class="topbar-left">
      <div class="page-title">Data <span>Wisata</span></div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><i class="fa-regular fa-calendar"></i><span id="tanggal"></span></div>
    </div>
  </div>

  <form method="GET" id="mainForm">
    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_col) ?>">
    <input type="hidden" name="dir" value="<?= htmlspecialchars(strtolower($sort_dir)) ?>">
    <input type="hidden" name="page" value="1">

    <!-- Row 1: Tambah button -->
    <div class="toolbar-row1">
      <a href="tambah.php" class="btn-tambah"><i class="fa-solid fa-plus"></i> Tambah Wisata</a>
    </div>
    <!-- Row 2: Show entries (left) + Search (right) -->
    <div class="toolbar-row2">
      <div class="toolbar-row2-left">
        <div class="show-entries">
          Tampilkan
          <select name="per_page" onchange="document.getElementById('mainForm').submit()">
            <option value="5"  <?= $per_page==5  ? 'selected' : '' ?>>5</option>
            <option value="10" <?= $per_page==10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $per_page==25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $per_page==50 ? 'selected' : '' ?>>50</option>
          </select>
          entri
        </div>
      </div>
      <div class="toolbar-row2-right">
        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="search" placeholder="Cari semua data..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-search">Cari</button>
        <?php if($search): ?><a href="data.php?per_page=<?= $per_page ?>" class="btn-reset"><i class="fa-solid fa-xmark"></i></a><?php endif; ?>
      </div>
    </div>
  </form>


  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>No</th>
          <th>Gambar</th>
          <th><?= sort_link_w('nama','Nama',$sort_col,$sort_dir,$search,$per_page) ?></th>
          <th><?= sort_link_w('kategori','Kategori',$sort_col,$sort_dir,$search,$per_page) ?></th>
          <th><?= sort_link_w('harga','Harga',$sort_col,$sort_dir,$search,$per_page) ?></th>
          <th><?= sort_link_w('rating_avg','Rating',$sort_col,$sort_dir,$search,$per_page) ?></th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if(mysqli_num_rows($data)===0): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-map-location-dot"></i>Tidak ada data wisata ditemukan.</div></td></tr>
      <?php else: $no=$offset+1; while($d=mysqli_fetch_assoc($data)): ?>
        <tr>
          <td><span class="row-no"><?= $no++ ?></span></td>
          <td><img src="../../assets/img/<?= $d['gambar'] ?>" class="wisata-img" alt="<?= htmlspecialchars($d['nama']) ?>"></td>
          <td><span class="nama-cell"><?= htmlspecialchars($d['nama']) ?></span></td>
          <td><span class="kat-text"><?= htmlspecialchars($d['kategori']) ?></span></td>
          <td><span class="harga-cell">Rp <?= number_format($d['harga'], 0, ',', '.') ?></span></td>
          <td><span class="rating-cell"><i class="fa-solid fa-star" style="color:var(--gold);font-size:12px;"></i><?= $d['rating_avg'] ?></span></td>
          <td>
            <a href="edit.php?id=<?= $d['id'] ?>" class="btn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="hapus.php?id=<?= $d['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus wisata ini?')"><i class="fa-solid fa-trash"></i> Hapus</a>
          </td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>

    <?php
      $qb=['search'=>$search,'sort'=>$sort_col,'dir'=>strtolower($sort_dir),'per_page'=>$per_page];
      $from = $total_rows==0 ? 0 : $offset+1;
      $to   = min($offset+$per_page,$total_rows);
    ?>
    <div class="pagination-wrap">
      <div class="pagination-info">Menampilkan <?= $from ?>–<?= $to ?> dari <?= $total_rows ?> entri</div>
      <div class="pagination">
        <?php if($page>1): ?>
          <a href="?<?= http_build_query(array_merge($qb,['page'=>$page-1])) ?>" class="prev-next">
            <i class="fa-solid fa-chevron-left" style="font-size:10px;"></i> Prev
          </a>
        <?php else: ?>
          <span style="opacity:0.4;cursor:default;"><i class="fa-solid fa-chevron-left" style="font-size:10px;"></i> Prev</span>
        <?php endif; ?>

        <?php
        $shown=[];
        for($i=1;$i<=$total_pages;$i++){
          if($i==1||$i==$total_pages||abs($i-$page)<=1) $shown[]=$i;
          elseif(abs($i-$page)==2) $shown[]='…';
        }
        $prev=null;
        foreach($shown as $s){
          if($s==='…'&&$prev!=='…') echo "<span class='dots'>…</span>";
          elseif($s!=='…'){
            if($s==$page) echo "<span class='current'>$s</span>";
            else echo "<a href='?".http_build_query(array_merge($qb,['page'=>$s]))."'>$s</a>";
          }
          $prev=$s;
        }
        ?>

        <?php if($page<$total_pages): ?>
          <a href="?<?= http_build_query(array_merge($qb,['page'=>$page+1])) ?>" class="prev-next">
            Next <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
          </a>
        <?php else: ?>
          <span style="opacity:0.4;cursor:default;">Next <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<script>document.getElementById('tanggal').textContent=new Date().toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});</script>
</body>
</html>
