<?php
/**
 * wishlist_notif_proses.php
 * Dipanggil saat admin update harga atau saat wisata dicek popularitas.
 * Bisa dipanggil via cron atau manual trigger dari admin.
 *
 * GET ?action=check_price&wisata_id=X  -> cek penurunan harga untuk wishlist users
 * GET ?action=check_popular            -> cek wisata populer minggu ini
 */

session_start();
include '../config/koneksi.php';

header('Content-Type: application/json');

$action    = $_GET['action'] ?? $_POST['action'] ?? '';
$wisata_id = (int)($_GET['wisata_id'] ?? 0);

// Auto-create tables
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
@mysqli_query($conn,"ALTER TABLE `wisata` ADD COLUMN IF NOT EXISTS `harga_sebelumnya` int(11) DEFAULT NULL");
@mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `wisata_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wisata_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_wisata_week` (`wisata_id`,`visited_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ==============================
// RECORD VISIT (dipanggil dari detail.php)
// ==============================
if($action === 'record_visit'){
    $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $uid_sql = $uid ? "'$uid'" : "NULL";
    mysqli_query($conn,"INSERT INTO wisata_visits(wisata_id, user_id) VALUES('$wisata_id', $uid_sql)");
    echo json_encode(['status'=>'ok']);
    exit;
}

// ==============================
// CHECK PRICE DROP
// Dipanggil admin saat edit harga
// ==============================
if($action === 'check_price' && $wisata_id > 0){
    $wisata = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM wisata WHERE id='$wisata_id'"));
    if(!$wisata){
        echo json_encode(['status'=>'error','msg'=>'Wisata tidak ditemukan']);
        exit;
    }

    $harga_baru      = (int)$wisata['harga'];
    $harga_sebelum   = (int)($wisata['harga_sebelumnya'] ?? $harga_baru);

    if($harga_baru < $harga_sebelum){
        // Ambil semua user yang punya wishlist ini
        $users_q = mysqli_query($conn,"SELECT wishlist.user_id FROM wishlist WHERE wisata_id='$wisata_id'");
        $notif_count = 0;
        $selisih = $harga_sebelum - $harga_baru;
        $persen  = $harga_sebelum > 0 ? round(($selisih / $harga_sebelum) * 100) : 0;

        while($u = mysqli_fetch_assoc($users_q)){
            $uid = (int)$u['user_id'];
            $nama_wisata = mysqli_real_escape_string($conn, $wisata['nama']);
            $harga_fmt_baru  = 'Rp ' . number_format($harga_baru, 0, ',', '.');
            $harga_fmt_lama  = 'Rp ' . number_format($harga_sebelum, 0, ',', '.');
            $msg = mysqli_real_escape_string($conn,
                "🎉 Harga wishlist-mu turun! {$wisata['nama']} sekarang {$harga_fmt_baru} (dari {$harga_fmt_lama}, hemat {$persen}%). Segera rencanakan tripmu!");
            $link = "detail.php?id=$wisata_id";
            mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message, link_url)
                VALUES('$uid', 0, 'wishlist', '$msg', '$link')");
            $notif_count++;
        }

        echo json_encode(['status'=>'ok','notified'=>$notif_count,'msg'=>"$notif_count user dinotifikasi harga turun"]);
    } else {
        echo json_encode(['status'=>'ok','notified'=>0,'msg'=>'Harga tidak turun, tidak ada notifikasi']);
    }
    exit;
}

// ==============================
// CHECK POPULAR WISATA (minggu ini)
// Dipanggil via cron atau manual
// ==============================
if($action === 'check_popular'){
    // Wisata yang kunjungan minggu ini >= threshold (misal 5 kunjungan)
    $threshold = 5;
    $popular_q = mysqli_query($conn,"
        SELECT wisata_id, COUNT(*) as visits
        FROM wisata_visits
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY wisata_id
        HAVING visits >= $threshold
        ORDER BY visits DESC
    ");

    $notified_total = 0;
    while($p = mysqli_fetch_assoc($popular_q)){
        $wid    = (int)$p['wisata_id'];
        $visits = (int)$p['visits'];
        $wisata = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM wisata WHERE id='$wid'"));
        if(!$wisata) continue;

        // Ambil user yang punya wishlist wisata ini
        $users_q = mysqli_query($conn,"SELECT user_id FROM wishlist WHERE wisata_id='$wid'");
        while($u = mysqli_fetch_assoc($users_q)){
            $uid = (int)$u['user_id'];

            // Cek apakah sudah dinotif hari ini untuk wisata ini (hindari spam)
            $cek_today = mysqli_fetch_assoc(mysqli_query($conn,"
                SELECT id FROM notifications
                WHERE user_id='$uid' AND type='wishlist'
                AND message LIKE '%populer%".$wisata['nama']."%'
                AND created_at >= CURDATE()
            "));
            if($cek_today) continue;

            $nama_wisata = mysqli_real_escape_string($conn, $wisata['nama']);
            $msg = mysqli_real_escape_string($conn,
                "🔥 Destinasi wishlist-mu sedang populer! {$wisata['nama']} dikunjungi $visits kali minggu ini. Jangan sampai kehabisan tempat!");
            $link = "detail.php?id=$wid";
            mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message, link_url)
                VALUES('$uid', 0, 'wishlist', '$msg', '$link')");
            $notified_total++;
        }
    }

    echo json_encode(['status'=>'ok','notified'=>$notified_total]);
    exit;
}

echo json_encode(['status'=>'error','msg'=>'Action tidak dikenali']);
