<?php
session_start();
include '../config/koneksi.php';

header('Content-Type: application/json');

if(!isset($_SESSION['login'])){
    echo json_encode(['status'=>'error','msg'=>'Login dulu']);
    exit;
}

$user_id   = $_SESSION['user_id'];
$action    = $_GET['action'] ?? $_POST['action'] ?? '';
$review_id = (int)($_GET['review_id'] ?? $_POST['review_id'] ?? 0);

// Auto create tables
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `review_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`review_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `review_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

// ==============================
// TOGGLE LIKE
// ==============================
if($action === 'toggle_like'){
    $cek = mysqli_query($conn,"SELECT id FROM review_likes WHERE review_id='$review_id' AND user_id='$user_id'");
    
    if(mysqli_num_rows($cek) > 0){
        // Unlike
        mysqli_query($conn,"DELETE FROM review_likes WHERE review_id='$review_id' AND user_id='$user_id'");
        $liked = false;
    } else {
        // Like
        mysqli_query($conn,"INSERT INTO review_likes(review_id, user_id) VALUES('$review_id','$user_id')");
        $liked = true;

        // Notifikasi ke pemilik review (jika bukan diri sendiri)
        $rev_row = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT review.user_id, users.nama AS nama_liker, wisata.nama AS wisata_nama, wisata.id AS wisata_id
             FROM review
             JOIN users ON users.id='$user_id'
             JOIN wisata ON wisata.id = review.wisata_id
             WHERE review.id='$review_id'"));
        
        if($rev_row && $rev_row['user_id'] != $user_id){
            $owner_id   = (int)$rev_row['user_id'];
            $nama_liker = mysqli_real_escape_string($conn, $rev_row['nama_liker']);
            $wisata_nama= mysqli_real_escape_string($conn, $rev_row['wisata_nama']);
            $wisata_id  = (int)$rev_row['wisata_id'];
            $link_url   = "detail.php?id=$wisata_id";

            // Hitung total like
            $total_like = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM review_likes WHERE review_id='$review_id'"))['c'];

            // Ambil daftar nama liker (max 3)
            $likers_q = mysqli_query($conn,"SELECT users.nama FROM review_likes JOIN users ON users.id=review_likes.user_id WHERE review_likes.review_id='$review_id' ORDER BY review_likes.created_at DESC LIMIT 3");
            $liker_names = [];
            while($lr = mysqli_fetch_assoc($likers_q)) $liker_names[] = $lr['nama'];
            $liker_str = implode(', ', $liker_names);
            if($total_like > 3) $liker_str .= " dan lainnya";

            $msg = mysqli_real_escape_string($conn, "Review-mu di $wisata_nama mendapat $total_like like. Disukai oleh: $liker_str.");
            mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message, link_url)
                VALUES('$owner_id','$user_id','review','$msg','$link_url')");
        }
    }

    // Hitung total like
    $count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM review_likes WHERE review_id='$review_id'"))['c'];
    echo json_encode(['status'=>'ok','liked'=>$liked,'count'=>(int)$count]);
    exit;
}

// ==============================
// ADD REPLY
// ==============================
if($action === 'add_reply'){
    $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar'] ?? ''));
    if(!$komentar){
        echo json_encode(['status'=>'error','msg'=>'Balasan tidak boleh kosong']);
        exit;
    }

    $ins = mysqli_query($conn,"INSERT INTO review_replies(review_id, user_id, komentar) VALUES('$review_id','$user_id','$komentar')");
    if(!$ins){
        echo json_encode(['status'=>'error','msg'=>'Gagal simpan: '.mysqli_error($conn)]);
        exit;
    }
    $reply_id = mysqli_insert_id($conn);

    // Notifikasi ke pemilik review (opsional, tidak boleh block response)
    $rev_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT r.user_id AS owner_id, u_sender.nama AS nama_pengirim, w.nama AS wisata_nama, w.id AS wisata_id
         FROM review r
         JOIN users AS u_sender ON u_sender.id='$user_id'
         JOIN wisata w ON w.id = r.wisata_id
         WHERE r.id='$review_id'"));

    if($rev_row && (int)$rev_row['owner_id'] !== (int)$user_id){
        $owner_id    = (int)$rev_row['owner_id'];
        $nama_sender = mysqli_real_escape_string($conn, $rev_row['nama_pengirim']);
        $wisata_nama = mysqli_real_escape_string($conn, $rev_row['wisata_nama']);
        $wisata_id   = (int)$rev_row['wisata_id'];
        $link_url    = "detail.php?id=$wisata_id";
        $short_komen = mb_substr($rev_row['wisata_nama'] ? $komentar : $komentar, 0, 60);
        // Buat msg tanpa tanda kutip di dalam string agar aman
        $preview = mysqli_real_escape_string($conn, mb_substr(trim($_POST['komentar']), 0, 80) . (mb_strlen(trim($_POST['komentar'])) > 80 ? '...' : ''));
        $msg     = mysqli_real_escape_string($conn, "$nama_sender membalas review-mu di $wisata_nama: $preview");
        @mysqli_query($conn,"INSERT INTO notifications(user_id, from_user_id, type, message, link_url)
            VALUES('$owner_id','$user_id','review','$msg','$link_url')");
    }

    // Return reply data
    $me = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama, foto FROM users WHERE id='$user_id'"));
    $nama_me = $me['nama'] ?? 'User';
    $foto_me = $me['foto'] ?? 'kimi.jpg';

    echo json_encode([
        'status'     => 'ok',
        'reply_id'   => $reply_id,
        'nama'       => $nama_me,
        'foto'       => $foto_me,
        'komentar'   => $_POST['komentar'],
        'created_at' => date('d M Y H:i')
    ]);
    exit;
}

// ==============================
// GET REPLIES
// ==============================
if($action === 'get_replies'){
    $q = mysqli_query($conn,"
        SELECT review_replies.*, users.nama, users.foto
        FROM review_replies
        JOIN users ON users.id = review_replies.user_id
        WHERE review_replies.review_id='$review_id'
        ORDER BY review_replies.created_at ASC
    ");
    $replies = [];
    while($r = mysqli_fetch_assoc($q)){
        $replies[] = [
            'id'         => $r['id'],
            'nama'       => $r['nama'],
            'foto'       => $r['foto'] ?? 'kimi.jpg',
            'komentar'   => $r['komentar'],
            'created_at' => date('d M Y H:i', strtotime($r['created_at']))
        ];
    }
    echo json_encode(['status'=>'ok','replies'=>$replies]);
    exit;
}

// ==============================
// GET LIKE STATUS
// ==============================
if($action === 'like_status'){
    $cek   = mysqli_query($conn,"SELECT id FROM review_likes WHERE review_id='$review_id' AND user_id='$user_id'");
    $liked = mysqli_num_rows($cek) > 0;
    $count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM review_likes WHERE review_id='$review_id'"))['c'];
    echo json_encode(['status'=>'ok','liked'=>$liked,'count'=>(int)$count]);
    exit;
}

echo json_encode(['status'=>'error','msg'=>'Action tidak dikenali']);
