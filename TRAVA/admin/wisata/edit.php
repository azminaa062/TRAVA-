<?php
session_start();
include '../../config/koneksi.php';
include '../auth/cek_login.php';

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: data.php");
  exit;
}

$id = (int)$_GET['id'];

$data = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT * FROM wisata WHERE id=$id")
);

if(!$data){
  header("Location: data.php");
  exit;
}

if(isset($_POST['submit'])){

  $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
  $kategori  = mysqli_real_escape_string($conn, $_POST['kategori']);
  $harga     = (int)$_POST['harga'];
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? $data['deskripsi']);
  $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas'] ?? $data['fasilitas']);
  $aktivitas = mysqli_real_escape_string($conn, $_POST['aktivitas'] ?? $data['aktivitas']);
  $maps      = mysqli_real_escape_string($conn, $_POST['maps'] ?? $data['maps']);



  // =========================
  // UPLOAD GAMBAR BARU
  // =========================

  $gambar = $data['gambar'];

  if(isset($_FILES['gambar']) && $_FILES['gambar']['name'] != ''){

    $namaFile = time().'_'.$_FILES['gambar']['name'];

    $tmp = $_FILES['gambar']['tmp_name'];

    move_uploaded_file(
      $tmp,
      "../../assets/img/".$namaFile
    );

    $gambar = $namaFile;
  }



  // =========================
  // UPDATE DATA
  // =========================

  // Simpan harga lama sebelum update (untuk notif penurunan harga)
  $harga_lama = (int)$data['harga'];

  // Tambahkan kolom harga_sebelumnya jika belum ada
  @mysqli_query($conn,"ALTER TABLE `wisata` ADD COLUMN IF NOT EXISTS `harga_sebelumnya` int(11) DEFAULT NULL");

  mysqli_query($conn,"
    UPDATE wisata SET
    nama='$nama',
    kategori='$kategori',
    harga='$harga',
    harga_sebelumnya='$harga_lama',
    deskripsi='$deskripsi',
    fasilitas='$fasilitas',
    aktivitas='$aktivitas',
    maps='$maps',
    gambar='$gambar'
    WHERE id=$id
  ");

  // =========================
  // NOTIFIKASI HARGA TURUN
  // =========================
  if($harga < $harga_lama && $harga_lama > 0){
    // Auto-create notif tables
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
    @mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `link_url` varchar(255) DEFAULT NULL");

    // Ambil semua user wishlist wisata ini
    $wl_users = mysqli_query($conn,"SELECT user_id FROM wishlist WHERE wisata_id='$id'");
    $selisih  = $harga_lama - $harga;
    $persen   = round(($selisih / $harga_lama) * 100);
    $harga_fmt_baru = 'Rp ' . number_format($harga, 0, ',', '.');
    $harga_fmt_lama = 'Rp ' . number_format($harga_lama, 0, ',', '.');

    while($u = mysqli_fetch_assoc($wl_users)){
      $uid = (int)$u['user_id'];
      $msg = mysqli_real_escape_string($conn,
        "🎉 Harga wishlist-mu turun! $nama sekarang $harga_fmt_baru (dari $harga_fmt_lama, hemat {$persen}%). Segera rencanakan tripmu!");
      $link = "detail.php?id=$id";
      mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message, link_url)
          VALUES('$uid', 0, 'wishlist', '$msg', '$link')");
    }
  }

  header("Location: data.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Edit Wisata — TRAVA Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*,*::before,*::after{
  box-sizing:border-box;
  margin:0;
  padding:0
}

:root{
  --sidebar:#1a3a5c;
  --sidebar-hover:hsla(0, 0%, 100%, 0.07);
  --sidebar-active:rgba(255,255,255,0.13);
  --sidebar-border:rgba(255,255,255,0.1);
  --sidebar-w:264px;

  --page:#f0f2f5;
  --page-alt:#e4e7eb;
  --card:#ffffff;
  --card-hover:#f8f9fa;

  --border:rgba(0,0,0,0.08);

  --shadow:0 2px 8px rgba(0,0,0,0.06);
  --shadow-hi:0 6px 20px rgba(0,0,0,0.10);

  --tx-1:#1a202c;
  --tx-2:#4a5568;
  --tx-3:#a0aec0;

  --terra:#c0522a;
  --gold:#b08430;
  --forest:#3a6b45;
  --ocean:#1a5c7a;

  --s-tx:#d6e8f5;
  --s-muted:#7fa4c2;
}

body{
  background:var(--page);
  color:var(--tx-1);
  font-family:'Lato',sans-serif;
  font-size:14px;
  line-height:1.6;
  min-height:100vh;
  display:flex;
}

.sidebar{
  width:var(--sidebar-w);
  min-height:100vh;
  background:var(--sidebar);
  display:flex;
  flex-direction:column;
  position:fixed;
  top:0;
  left:0;
  z-index:50;
}

.sidebar-logo{
  padding:16px 22px 14px;
  border-bottom:1px solid var(--sidebar-border);
  position:relative;
}

.sidebar-logo::after{
  content:'';
  position:absolute;
  bottom:0;
  left:22px;
  right:22px;
  height:1px;
  background:linear-gradient(
    90deg,
    transparent,
    rgba(176,132,48,0.4),
    transparent
  );
}

.logo-row{
  display:flex;
  align-items:center;
  gap:12px;
}

.logo-img{height:38px;width:auto;object-fit:contain;display:block;}



.logo-tagline{
  margin-top:4px;
  font-size:10.5px;
  font-weight:300;
  color:var(--s-muted);
  letter-spacing:0.12em;
  text-transform:uppercase;
}

.nav-item{
  display:flex;
  align-items:center;
  gap:12px;
  margin:2px 12px;
  padding:11px 14px;
  border-radius:8px;
  text-decoration:none;
  color:var(--s-muted);
  font-size:13.5px;
  font-weight:400;
  transition:all 0.2s;
  position:relative;
}

.nav-item i{
  width:18px;
  text-align:center;
  font-size:14px;
  flex-shrink:0;
}

.nav-item:hover{
  background:var(--sidebar-hover);
  color:var(--s-tx);
}

.nav-item.active{
  background:var(--sidebar-active);
  color:#fff;
  font-weight:700;
}

.nav-item.active i{
  color:#f0c060;
}

.nav-item.active::before{
  content:'';
  position:absolute;
  left:0;
  top:6px;
  bottom:6px;
  width:3px;
  background:#f0c060;
  border-radius:0 3px 3px 0;
}

.sidebar-bottom{
  margin-top:auto;
  padding:16px 12px;
  border-top:1px solid var(--sidebar-border);
}

.admin-pill{
  display:flex;
  align-items:center;
  gap:11px;
  padding:10px 12px;
  border-radius:8px;
  background:rgba(255,255,255,0.06);
  border:1px solid var(--sidebar-border);
  margin-bottom:6px;
}

.admin-avatar{
  width:32px;
  height:32px;
  border-radius:50%;
  background:rgba(240,192,96,0.2);
  border:1px solid rgba(240,192,96,0.3);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:12px;
  font-weight:700;
  color:#f0c060;
}

.admin-name{
  font-size:13px;
  font-weight:700;
  color:#fff;
  line-height:1.2;
}

.admin-role{
  font-size:11px;
  color:var(--s-muted);
}

.main{
  margin-left:var(--sidebar-w);
  flex:1;
  padding:38px 36px;
  position:relative;
  z-index:1;
}

.topbar{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  margin-bottom:24px;
}

.page-title{
  font-family:'Cormorant Garamond',serif;
  font-size:36px;
  font-weight:600;
  color:var(--tx-1);
  letter-spacing:-0.3px;
  line-height:1;
}

.page-title span{
  font-style:italic;
  font-weight:400;
  color:var(--gold);
}

.topbar-date{
  display:flex;
  align-items:center;
  gap:8px;
  background:var(--card);
  border:1px solid var(--border);
  border-radius:10px;
  padding:11px 18px;
  font-size:12.5px;
  color:var(--tx-2);
  box-shadow:var(--shadow);
  white-space:nowrap;
  align-self:flex-start;
  margin-top:4px;
}

.topbar-date i{
  color:var(--gold);
}

.form-wrap{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:14px;
  padding:36px 38px;
  box-shadow:var(--shadow);
  position:relative;
  overflow:hidden;
  max-width:800px;
  animation:riseUp 0.5s 0.1s ease both;
}

.form-wrap::before{
  content:'';
  position:absolute;
  top:0;
  left:0;
  right:0;
  height:3px;
  border-radius:14px 14px 0 0;
  background:linear-gradient(
    90deg,
    var(--terra),
    var(--gold),
    var(--forest)
  );
}

.form-section-title{
  font-family:'Cormorant Garamond',serif;
  font-size:20px;
  font-weight:600;
  color:var(--tx-1);
  display:flex;
  align-items:center;
  gap:10px;
  margin:28px 0 16px;
  padding-top:24px;
  border-top:1px solid var(--border);
}

.form-section-title:first-of-type{
  margin-top:0;
  padding-top:0;
  border-top:none;
}

.form-section-title::before{
  content:'';
  display:inline-block;
  width:8px;
  height:8px;
  background:var(--terra);
  clip-path:polygon(
    50% 0%,
    100% 50%,
    50% 100%,
    0% 50%
  );
  flex-shrink:0;
}

.form-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:20px;
}

.form-group{
  display:flex;
  flex-direction:column;
  gap:6px;
}

.form-group.full{
  grid-column:1/-1;
}

.form-group label{
  font-size:11px;
  font-weight:700;
  letter-spacing:0.1em;
  text-transform:uppercase;
  color:var(--tx-3);
}

.form-group label span{
  color:var(--terra);
  margin-left:2px;
}

.form-control{
  width:100%;
  padding:11px 14px;
  background:var(--page);
  border:1.5px solid var(--border);
  border-radius:8px;
  font-family:'Lato',sans-serif;
  font-size:13.5px;
  color:var(--tx-1);
  outline:none;
  transition:border-color 0.18s,box-shadow 0.18s;
  resize:vertical;
}

.form-control:focus{
  border-color:var(--gold);
  box-shadow:0 0 0 3px rgba(176,132,48,0.1);
}

select.form-control{
  cursor:pointer;
  appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23a0aec0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 14px center;
  padding-right:36px;
}

.preview-img{
  width:220px;
  margin-top:10px;
  border-radius:10px;
  border:1px solid var(--border);
  object-fit:cover;
}

.form-actions{
  display:flex;
  gap:12px;
  margin-top:28px;
  padding-top:24px;
  border-top:1px solid var(--border);
}

.btn-kembali{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:12px 24px;
  border-radius:10px;
  background:transparent;
  color:var(--tx-2);
  font-family:'Lato',sans-serif;
  font-size:13.5px;
  font-weight:700;
  border:1.5px solid var(--border);
  text-decoration:none;
  transition:all 0.18s;
}

.btn-kembali:hover{
  border-color:var(--terra);
  color:var(--terra);
}

.btn-primary{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:12px 28px;
  border-radius:10px;
  background:var(--sidebar);
  color:#fff;
  font-family:'Lato',sans-serif;
  font-size:13.5px;
  font-weight:700;
  border:none;
  cursor:pointer;
  transition:all 0.18s;
}

.btn-primary:hover{
  background:#122841;
  transform:translateY(-1px);
  box-shadow:0 4px 12px rgba(26,58,92,0.3);
}

@keyframes riseUp{
  from{
    opacity:0;
    transform:translateY(14px)
  }
  to{
    opacity:1;
    transform:translateY(0)
  }
}

@media(max-width:768px){

  .sidebar{
    display:none
  }

  .main{
    margin-left:0;
    padding:20px
  }

  .form-grid{
    grid-template-columns:1fr
  }

}

</style>
</head>

<body>

<aside class="sidebar">

  <div class="sidebar-logo">

    <div class="logo-row">

      <div class="logo-emblem">
        <i class="fa-solid fa-compass"></i>
      </div>

      <div>
      </div>

    </div>

    <div class="logo-tagline">
      Panel Pengelola Wisata
    </div>

  </div>

  <a href="../index.php" class="nav-item">
    <i class="fa-solid fa-gauge-high"></i>
    Dashboard
  </a>

  <a href="../wisata/data.php" class="nav-item active">
    <i class="fa-solid fa-map-location-dot"></i>
    Data Wisata
  </a>

  <a href="../user/data.php" class="nav-item">
    <i class="fa-solid fa-users"></i>
    Data Pengguna
  </a>

  <a href="../review/data.php" class="nav-item">
    <i class="fa-solid fa-star"></i>
    Data Review
  </a>

  <a href="../../logout.php" class="nav-item" style="margin-top:4px;">
    <i class="fa-solid fa-right-from-bracket"></i>
    Keluar
  </a>

  <div class="sidebar-bottom">

    <div class="admin-pill">

      <div class="admin-avatar">A</div>

      <div>
        <div class="admin-name">Administrator</div>
        <div class="admin-role">Pengelola Utama</div>
      </div>

    </div>

  </div>

</aside>

<main class="main">

  <div class="topbar">

    <div>
      <div class="page-title">
        Edit <span>Wisata</span>
      </div>
    </div>

    <div class="topbar-date">
      <i class="fa-regular fa-calendar"></i>
      <span id="tanggal"></span>
    </div>

  </div>

  <div class="form-wrap">

    <form method="POST" enctype="multipart/form-data">

      <div class="form-section-title">
        Informasi Wisata
      </div>

      <div class="form-grid">

        <div class="form-group">

          <label>
            Nama Wisata <span>*</span>
          </label>

          <input
          type="text"
          name="nama"
          class="form-control"
          value="<?= htmlspecialchars($data['nama']) ?>"
          required>

        </div>

        <div class="form-group">

          <label>
            Kategori <span>*</span>
          </label>

          <select name="kategori" class="form-control">

            <?php
            $kategori_list = ['Alam','Pantai','Sejarah','Kuliner'];

            foreach($kategori_list as $k){

              $sel = $data['kategori'] === $k
              ? 'selected'
              : '';

              echo "
              <option value='$k' $sel>
                $k
              </option>";
            }
            ?>

          </select>

        </div>

        <div class="form-group">

          <label>Harga Tiket (Rp)</label>

          <input
          type="number"
          name="harga"
          class="form-control"
          value="<?= htmlspecialchars($data['harga']) ?>"
          min="0">

        </div>

        <div class="form-group full">

          <label>Deskripsi</label>

          <textarea
          name="deskripsi"
          class="form-control"
          rows="4"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>

        </div>

        <div class="form-group">

          <label>Fasilitas</label>

          <textarea
          name="fasilitas"
          class="form-control"
          rows="4"><?= htmlspecialchars($data['fasilitas'] ?? '') ?></textarea>

        </div>

        <div class="form-group">

          <label>Aktivitas</label>

          <textarea
          name="aktivitas"
          class="form-control"
          rows="4"><?= htmlspecialchars($data['aktivitas'] ?? '') ?></textarea>

        </div>

        <div class="form-group full">

          <label>Google Maps Embed</label>

          <textarea
          name="maps"
          class="form-control"
          rows="3"><?= htmlspecialchars($data['maps'] ?? '') ?></textarea>

        </div>



        <!-- GAMBAR WISATA -->

        <div class="form-group full">

          <label>Gambar Wisata</label>

          <input
          type="file"
          name="gambar"
          class="form-control"
          accept="image/*">

          <img
          src="../../assets/img/<?= $data['gambar']; ?>"
          class="preview-img">

        </div>

      </div>

      <div class="form-actions">

        <a href="data.php" class="btn-kembali">
          <i class="fa-solid fa-arrow-left"></i>
          Kembali
        </a>

        <button
        type="submit"
        name="submit"
        class="btn-primary">

          <i class="fa-solid fa-floppy-disk"></i>
          Simpan Perubahan

        </button>

      </div>

    </form>

  </div>

</main>

<script>

document.getElementById('tanggal').textContent =
new Date().toLocaleDateString(
'id-ID',
{
weekday:'long',
year:'numeric',
month:'long',
day:'numeric'
}
);

</script>

</body>
</html>