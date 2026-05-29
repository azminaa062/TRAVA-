<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login'])){
    header('Content-Type: application/json');
    echo json_encode(['count'=>0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

// Auto-create notifications table jika belum ada
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'invite',
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

// ==============================
// GET UNREAD COUNT (untuk badge)
// ==============================
if($action === 'count'){
    $q = mysqli_query($conn,"SELECT COUNT(*) as c FROM notifications WHERE user_id='$user_id' AND is_read=0");
    $r = mysqli_fetch_assoc($q);
    header('Content-Type: application/json');
    echo json_encode(['count'=> (int)$r['c']]);
    exit;
}

// ==============================
// GET ALL NOTIFICATIONS
// ==============================
if($action === 'list'){
    $q = mysqli_query($conn,"
        SELECT notifications.*, 
               users.nama AS from_nama,
               trip.nama_trip
        FROM notifications
        LEFT JOIN users ON notifications.from_user_id = users.id
        LEFT JOIN trip  ON notifications.trip_id = trip.id
        WHERE notifications.user_id='$user_id'
        ORDER BY notifications.created_at DESC
        LIMIT 30
    ");
    $notifs = [];
    while($n = mysqli_fetch_assoc($q)){
        $notifs[] = [
            'id'        => $n['id'],
            'type'      => $n['type'],
            'from_nama' => $n['from_nama'],
            'trip_id'   => $n['trip_id'],
            'nama_trip' => $n['nama_trip'],
            'message'   => $n['message'],
            'link_url'  => $n['link_url'] ?? null,
            'is_read'   => (int)$n['is_read'],
            'time'      => date('d M H:i', strtotime($n['created_at']))
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($notifs);
    exit;
}

// ==============================
// MARK ALL AS READ
// ==============================
if($action === 'read_all'){
    mysqli_query($conn,"UPDATE notifications SET is_read=1 WHERE user_id='$user_id'");
    header('Content-Type: application/json');
    echo json_encode(['status'=>'ok']);
    exit;
}

// ==============================
// MARK ONE AS READ
// ==============================
if($action === 'read_one'){
    $notif_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    mysqli_query($conn,"UPDATE notifications SET is_read=1 WHERE id='$notif_id' AND user_id='$user_id'");
    header('Content-Type: application/json');
    echo json_encode(['status'=>'ok']);
    exit;
}

// ==============================
// DELETE ALL
// ==============================
if($action === 'delete_all'){
    mysqli_query($conn,"DELETE FROM notifications WHERE user_id='$user_id'");
    header('Content-Type: application/json');
    echo json_encode(['status'=>'ok']);
    exit;
}

// ==============================
// DELETE ONE
// ==============================
if($action === 'delete_one'){
    $notif_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    mysqli_query($conn,"DELETE FROM notifications WHERE id='$notif_id' AND user_id='$user_id'");
    header('Content-Type: application/json');
    echo json_encode(['status'=>'ok']);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['status'=>'error']);
exit;
