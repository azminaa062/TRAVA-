<?php
session_start();
include '../../config/koneksi.php';
include '../auth/cek_login.php';

if(isset($_POST['submit'])){
  $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $kategori  = mysqli_real_escape_string($conn, $_POST['kategori']);
  $harga     = (int)$_POST['harga'];
  $fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
  $aktivitas = mysqli_real_escape_string($conn, $_POST['aktivitas']);
  $maps      = mysqli_real_escape_string($conn, $_POST['maps']);
  $gambar    = $_FILES['gambar']['name'];
  $tmp       = $_FILES['gambar']['tmp_name'];
  if($gambar) move_uploaded_file($tmp, "../../assets/img/".$gambar);
  mysqli_query($conn,
    "INSERT INTO wisata (nama,deskripsi,kategori,harga,fasilitas,aktivitas,maps,gambar)
     VALUES ('$nama','$deskripsi','$kategori','$harga','$fasilitas','$aktivitas','$maps','$gambar')"
  );
  header("Location: data.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Wisata — TRAVA Admin</title>
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
.main{margin-left:var(--sidebar-w);flex:1;padding:38px 36px;position:relative;z-index:1;}
.topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px;}
.page-title{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--tx-1);letter-spacing:-0.3px;line-height:1;}
.page-title span{font-style:italic;font-weight:400;color:var(--gold);}
.topbar-date{display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:11px 18px;font-size:12.5px;color:var(--tx-2);box-shadow:var(--shadow);white-space:nowrap;align-self:flex-start;margin-top:4px;}
.topbar-date i{color:var(--gold);}
.form-wrap{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:36px 38px;box-shadow:var(--shadow);animation:riseUp 0.5s 0.1s ease both;margin-bottom:38px;position:relative;overflow:hidden;}
.form-wrap::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;background:linear-gradient(90deg,var(--terra),var(--gold),var(--forest));}
.form-section-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--tx-1);display:flex;align-items:center;gap:10px;margin:28px 0 16px;padding-top:24px;border-top:1px solid var(--border);}
.form-section-title:first-of-type{margin-top:0;padding-top:0;border-top:none;}
.form-section-title::before{content:'';display:inline-block;width:8px;height:8px;background:var(--terra);clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);flex-shrink:0;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
.form-group label{font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--tx-3);}
.form-group label span{color:var(--terra);margin-left:2px;}
.form-control{width:100%;padding:11px 14px;background:var(--page);border:1.5px solid var(--border);border-radius:8px;font-family:'Lato',sans-serif;font-size:13.5px;color:var(--tx-1);outline:none;transition:border-color 0.18s,box-shadow 0.18s;resize:vertical;}
.form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(176,132,48,0.1);}
.form-control::placeholder{color:var(--tx-3);}
select.form-control{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23a0aec0' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px;}
.form-hint{font-size:11.5px;color:var(--tx-3);margin-top:3px;}
.file-area{border:2px dashed var(--border);border-radius:8px;padding:22px;text-align:center;cursor:pointer;transition:border-color 0.18s,background 0.18s;background:var(--page);}
.file-area:hover{border-color:var(--gold);background:rgba(176,132,48,0.04);}
.file-area input[type=file]{display:none;}
.file-area-icon{font-size:24px;color:var(--tx-3);margin-bottom:8px;}
.file-area-text{font-size:13px;color:var(--tx-2);}
.file-area-text strong{color:var(--ocean);}
.file-name-display{margin-top:8px;font-size:12px;color:var(--forest);font-weight:700;display:none;}
/* BUTTON ORDER: Kembali LEFT, Simpan RIGHT */
.form-actions{display:flex;gap:12px;margin-top:28px;padding-top:24px;border-top:1px solid var(--border);}
.btn-kembali{display:inline-flex;align-items:center;gap:9px;padding:12px 24px;border-radius:10px;text-decoration:none;background:transparent;border:1.5px solid var(--border);color:var(--tx-2);font-size:13.5px;font-weight:700;transition:all 0.18s;}
.btn-kembali:hover{border-color:var(--terra);color:var(--terra);background:#fff;}
.btn-primary{display:inline-flex;align-items:center;gap:9px;padding:12px 32px;border-radius:10px;border:none;cursor:pointer;background:var(--sidebar);color:#fff;font-family:'Lato',sans-serif;font-size:13.5px;font-weight:700;letter-spacing:0.04em;transition:all 0.2s;}
.btn-primary:hover{background:#122841;transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,58,92,0.3);}
@keyframes riseUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:1100px){.form-grid{grid-template-columns:1fr}}
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
  <a href="../wisata/data.php" class="nav-item active"><i class="fa-solid fa-map-location-dot"></i> Data Wisata</a>
  <a href="../user/data.php" class="nav-item"><i class="fa-solid fa-users"></i> Data Pengguna</a>
  <a href="../review/data.php" class="nav-item"><i class="fa-solid fa-star"></i> Data Review</a>
  <a href="../../logout.php" class="nav-item" style="margin-top:4px;"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
  <div class="sidebar-bottom">
    <div class="admin-pill">
      <div class="admin-avatar">A</div>
      <div><div class="admin-name">Administrator</div><div class="admin-role">Pengelola Utama</div></div>
    </div>
  </div>
</aside>
<main class="main">
  <div class="topbar">
    <div><div class="page-title">Tambah <span>Wisata</span></div></div>
    <div class="topbar-date"><i class="fa-regular fa-calendar"></i><span id="tanggal"></span></div>
  </div>
  <div class="form-wrap">
    <form method="POST" enctype="multipart/form-data" id="formWisata">
      <div class="form-section-title">Informasi Dasar</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Nama Wisata <span>*</span></label>
          <input type="text" name="nama" class="form-control" placeholder="cth. Pantai Kejawanan" required>
        </div>
        <div class="form-group">
          <label>Kategori <span>*</span></label>
          <select name="kategori" class="form-control">
            <option value="Alam">Alam</option>
            <option value="Sejarah">Sejarah</option>
            <option value="Kuliner">Kuliner</option>
            <option value="Pantai">Pantai</option>
          </select>
        </div>
        <div class="form-group">
          <label>Harga Tiket (Rp)</label>
          <input type="number" name="harga" class="form-control" placeholder="cth. 15000" min="0">
          <span class="form-hint">Isi 0 jika gratis</span>
        </div>
        <div class="form-group full">
          <label>Deskripsi <span>*</span></label>
          <textarea name="deskripsi" class="form-control" rows="4" placeholder="Ceritakan tentang destinasi wisata ini..."></textarea>
        </div>
      </div>
      <div class="form-section-title">Detail &amp; Aktivitas</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Fasilitas</label>
          <textarea name="fasilitas" class="form-control" rows="4" placeholder="cth. Parkir luas, toilet umum, musholla..."></textarea>
        </div>
        <div class="form-group">
          <label>Aktivitas</label>
          <textarea name="aktivitas" class="form-control" rows="4" placeholder="cth. Berenang, snorkeling, memancing..."></textarea>
        </div>
        <div class="form-group full">
          <label>Google Maps Embed</label>
          <textarea name="maps" class="form-control" rows="3" placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'></textarea>
          <span class="form-hint">Salin kode embed dari Google Maps → Bagikan → Sematkan peta</span>
        </div>
      </div>
      <div class="form-section-title">Gambar Utama</div>
      <div class="form-grid">
        <div class="form-group full">
          <label>Upload Foto Destinasi</label>
          <div class="file-area" onclick="document.getElementById('inputGambar').click()">
            <input type="file" name="gambar" id="inputGambar" accept="image/*" onchange="showFileName(this)">
            <div class="file-area-icon"><i class="fa-solid fa-image"></i></div>
            <div class="file-area-text">Klik untuk memilih gambar atau <strong>drag &amp; drop</strong> di sini</div>
            <div class="file-area-text" style="font-size:11px;color:var(--tx-3);margin-top:4px;">PNG, JPG, WEBP — maks. 5MB</div>
            <div class="file-name-display" id="fileNameDisplay"><i class="fa-solid fa-check"></i> <span id="fileName"></span></div>
          </div>
        </div>
      </div>
      <!-- Kembali LEFT, Simpan RIGHT -->
      <div class="form-actions">
        <a href="data.php" class="btn-kembali">
          <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
        <button type="submit" name="submit" class="btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Wisata
        </button>
      </div>
    </form>
  </div>
</main>
<script>
document.getElementById('tanggal').textContent=new Date().toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
function showFileName(input){
  if(input.files&&input.files[0]){
    const display=document.getElementById('fileNameDisplay');
    document.getElementById('fileName').textContent=input.files[0].name;
    display.style.display='block';
  }
}
</script>
</body>
</html>
