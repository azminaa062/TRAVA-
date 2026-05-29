<?php
session_start();
include '../config/koneksi.php';

if(!isset($_SESSION['login'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$trip_id = (int)($_POST['trip_id'] ?? $_GET['trip_id'] ?? 0);

// Auto-create notifications table
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

// Helper kirim notifikasi — aman meski kolom link_url belum ada
function kirimNotif($conn, $to_user_id, $from_user_id, $trip_id, $type, $message, $link_url=null){
    $msg = mysqli_real_escape_string($conn, $message);
    // Tentukan link default berdasarkan type
    if(!$link_url && $trip_id){
        if($type==='chat_personal') $link_url = "trip_group.php?id={$trip_id}&tab=personal";
        elseif($type==='chat_group') $link_url = "trip_group.php?id={$trip_id}&tab=chat";
        elseif($type==='invite') $link_url = "trip_group.php?id={$trip_id}";
        elseif($type==='trip'||$type==='wacana') $link_url = "trip_group.php?id={$trip_id}";
    }
    // Cek apakah kolom link_url ada
    $cek = mysqli_query($conn,"SHOW COLUMNS FROM `notifications` LIKE 'link_url'");
    if(mysqli_num_rows($cek) > 0 && $link_url){
        $lnk = mysqli_real_escape_string($conn, $link_url);
        mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message,link_url) VALUES('$to_user_id','$from_user_id','$trip_id','$type','$msg','$lnk')");
    } else {
        mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message) VALUES('$to_user_id','$from_user_id','$trip_id','$type','$msg')");
    }
}

// Helper: cek apakah user adalah member trip
function isMember($conn, $trip_id, $user_id){
    // Creator juga dianggap member
    $q = mysqli_query($conn,"SELECT id FROM trip WHERE id='$trip_id' AND creator_id='$user_id'");
    if(mysqli_num_rows($q) > 0) return true;
    $q2 = mysqli_query($conn,"SELECT id FROM trip_members WHERE trip_id='$trip_id' AND user_id='$user_id'");
    return mysqli_num_rows($q2) > 0;
}

// ===================================================
// INVITE COLLABORATOR
// ===================================================
if($action === 'invite'){
    $email_invite = trim($_POST['email'] ?? '');
    if(!empty($email_invite)){
        $cek_user = mysqli_query($conn,"SELECT id, nama FROM users WHERE email='".mysqli_real_escape_string($conn,$email_invite)."'");
        if(mysqli_num_rows($cek_user) > 0){
            $invited = mysqli_fetch_assoc($cek_user);
            $inv_id = $invited['id'];
            // Cek sudah member?
            $cek_dup = mysqli_query($conn,"SELECT id FROM trip_members WHERE trip_id='$trip_id' AND user_id='$inv_id'");
            $cek_creator = mysqli_query($conn,"SELECT id FROM trip WHERE id='$trip_id' AND creator_id='$inv_id'");
            if(mysqli_num_rows($cek_dup) == 0 && mysqli_num_rows($cek_creator) == 0){
                mysqli_query($conn,"INSERT INTO trip_members(trip_id,user_id,role) VALUES('$trip_id','$inv_id','member')");
                // Ambil nama trip untuk notifikasi
                $trip_info = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama_trip FROM trip WHERE id='$trip_id'"));
                $trip_nama = $trip_info['trip_info'] ?? '';
                $sender_info = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM users WHERE id='$user_id'"));
                $sender_nama = $sender_info ? $sender_info['nama'] : 'Seseorang';
                $trip_info2 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama_trip FROM trip WHERE id='$trip_id'"));
                $trip_nama2 = $trip_info2 ? $trip_info2['nama_trip'] : 'sebuah trip';
                kirimNotif($conn, $inv_id, $user_id, $trip_id, 'invite', $sender_nama.' mengundangmu ikut trip: '.$trip_nama2);
                header("Location: ../trip_group.php?id=$trip_id&tab=members&msg=invite_ok");
            } else {
                header("Location: ../trip_group.php?id=$trip_id&tab=members&msg=already");
            }
        } else {
            header("Location: ../trip_group.php?id=$trip_id&tab=members&msg=notfound");
        }
    }
    exit;
}

// ===================================================
// KIRIM PESAN CHAT
// ===================================================
if($action === 'send_chat'){
    if(!isMember($conn,$trip_id,$user_id)){
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','msg'=>'not_member']);
        exit;
    }
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'group';
    $to_user_id = (int)($_POST['to_user_id'] ?? 0);
    if(!empty($message)){
        $message_esc = mysqli_real_escape_string($conn, $message);
        if($type === 'personal' && $to_user_id > 0){
            mysqli_query($conn,"INSERT INTO trip_chat(trip_id,user_id,message,type,to_user_id) VALUES('$trip_id','$user_id','$message_esc','personal','$to_user_id')");
            // Notif personal chat
            $sender_n = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM users WHERE id='$user_id'"));
            $snama = $sender_n ? $sender_n['nama'] : 'Seseorang';
            $preview = mb_strimwidth($message, 0, 60, '...');
            kirimNotif($conn, $to_user_id, $user_id, $trip_id, 'chat_personal', $snama.': '.$preview);
        } else {
            mysqli_query($conn,"INSERT INTO trip_chat(trip_id,user_id,message,type) VALUES('$trip_id','$user_id','$message_esc','group')");
            // Notif group chat ke semua member selain pengirim
            $sender_n2 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM users WHERE id='$user_id'"));
            $snama2 = $sender_n2 ? $sender_n2['nama'] : 'Seseorang';
            $trip_info3 = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama_trip FROM trip WHERE id='$trip_id'"));
            $tname3 = $trip_info3 ? $trip_info3['nama_trip'] : 'trip';
            $preview2 = mb_strimwidth($message, 0, 60, '...');
            $members_notif = mysqli_query($conn,"SELECT user_id FROM trip_members WHERE trip_id='$trip_id' AND user_id != '$user_id'");
            while($mn = mysqli_fetch_assoc($members_notif)){
                kirimNotif($conn, $mn['user_id'], $user_id, $trip_id, 'chat_group', $snama2.' di grup '.$tname3.': '.$preview2);
            }
            // Juga notif ke creator jika bukan pengirim
            $creator_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT creator_id FROM trip WHERE id='$trip_id'"));
            if($creator_row && $creator_row['creator_id'] != $user_id){
                kirimNotif($conn, $creator_row['creator_id'], $user_id, $trip_id, 'chat_group', $snama2.' di grup '.$tname3.': '.$preview2);
            }
        }
        // Return JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode(['status'=>'ok']);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error']);
    exit;
}

// ===================================================
// LOAD CHAT (AJAX)
// ===================================================
if($action === 'load_chat'){
    if(!isMember($conn,$trip_id,$user_id)){
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
    $type = $_GET['type'] ?? 'group';
    $to_user_id = (int)($_GET['to_user_id'] ?? 0);

    if($type === 'personal' && $to_user_id > 0){
        $msgs = mysqli_query($conn,"
            SELECT trip_chat.*, users.nama
            FROM trip_chat
            JOIN users ON trip_chat.user_id = users.id
            WHERE trip_chat.trip_id='$trip_id'
            AND trip_chat.type='personal'
            AND (
                (trip_chat.user_id='$user_id' AND trip_chat.to_user_id='$to_user_id')
                OR
                (trip_chat.user_id='$to_user_id' AND trip_chat.to_user_id='$user_id')
            )
            ORDER BY trip_chat.created_at ASC
        ");
    } else {
        $msgs = mysqli_query($conn,"
            SELECT trip_chat.*, users.nama
            FROM trip_chat
            JOIN users ON trip_chat.user_id = users.id
            WHERE trip_chat.trip_id='$trip_id'
            AND trip_chat.type='group'
            ORDER BY trip_chat.created_at ASC
        ");
    }

    $result = [];
    while($m = mysqli_fetch_assoc($msgs)){
        $result[] = [
            'id'        => $m['id'],
            'user_id'   => $m['user_id'],
            'nama'      => $m['nama'],
            'message'   => $m['message'],
            'mine'      => ($m['user_id'] == $user_id),
            'time'      => date('H:i', strtotime($m['created_at']))
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// ===================================================
// TAMBAH ITINERARY
// ===================================================
if($action === 'add_itinerary'){
    if(!isMember($conn,$trip_id,$user_id)){ exit; }
    $hari      = (int)($_POST['hari'] ?? 1);
    $waktu     = mysqli_real_escape_string($conn, $_POST['waktu'] ?? '');
    $aktivitas = mysqli_real_escape_string($conn, $_POST['aktivitas'] ?? '');
    $lokasi    = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
    $catatan   = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');
    if(!empty($aktivitas)){
        mysqli_query($conn,"INSERT INTO trip_itinerary(trip_id,user_id,hari,waktu,aktivitas,lokasi,catatan) VALUES('$trip_id','$user_id','$hari','$waktu','$aktivitas','$lokasi','$catatan')");
    }
    header("Location: ../trip_group.php?id=$trip_id&tab=itinerary");
    exit;
}

// ===================================================
// HAPUS ITINERARY
// ===================================================
if($action === 'del_itinerary'){
    $item_id = (int)($_POST['item_id'] ?? 0);
    mysqli_query($conn,"DELETE FROM trip_itinerary WHERE id='$item_id' AND trip_id='$trip_id'");
    header("Location: ../trip_group.php?id=$trip_id&tab=itinerary");
    exit;
}

// ===================================================
// BUAT VOTING
// ===================================================
if($action === 'create_vote'){
    if(!isMember($conn,$trip_id,$user_id)){ exit; }
    $judul     = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $opsi      = $_POST['opsi'] ?? [];
    if(!empty($judul) && count($opsi) >= 2){
        mysqli_query($conn,"INSERT INTO trip_votes(trip_id,creator_id,judul,deskripsi) VALUES('$trip_id','$user_id','$judul','$deskripsi')");
        $vote_id = mysqli_insert_id($conn);
        foreach($opsi as $o){
            $o = mysqli_real_escape_string($conn, trim($o));
            if(!empty($o)){
                mysqli_query($conn,"INSERT INTO trip_vote_options(vote_id,opsi) VALUES('$vote_id','$o')");
            }
        }
    }
    header("Location: ../trip_group.php?id=$trip_id&tab=voting");
    exit;
}

// ===================================================
// CAST VOTE
// ===================================================
if($action === 'cast_vote'){
    if(!isMember($conn,$trip_id,$user_id)){ exit; }
    $vote_id   = (int)($_POST['vote_id'] ?? 0);
    $option_id = (int)($_POST['option_id'] ?? 0);
    // Hapus vote lama jika ada
    mysqli_query($conn,"DELETE FROM trip_vote_responses WHERE vote_id='$vote_id' AND user_id='$user_id'");
    // Insert vote baru
    mysqli_query($conn,"INSERT INTO trip_vote_responses(vote_id,option_id,user_id) VALUES('$vote_id','$option_id','$user_id')");
    header("Location: ../trip_group.php?id=$trip_id&tab=voting");
    exit;
}

// ===================================================
// TAMBAH BUDGET ITEM
// ===================================================
if($action === 'add_budget'){
    if(!isMember($conn,$trip_id,$user_id)){ exit; }
    $nama_item = mysqli_real_escape_string($conn, $_POST['nama_item'] ?? '');
    $jumlah    = (float)($_POST['jumlah'] ?? 0);
    $kategori  = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'Lainnya');
    if(!empty($nama_item) && $jumlah > 0){
        mysqli_query($conn,"INSERT INTO trip_budget_items(trip_id,user_id,nama_item,jumlah,kategori) VALUES('$trip_id','$user_id','$nama_item','$jumlah','$kategori')");
    }
    header("Location: ../trip_group.php?id=$trip_id&tab=budget");
    exit;
}

// ===================================================
// HAPUS BUDGET ITEM
// ===================================================
if($action === 'del_budget'){
    $item_id = (int)($_POST['item_id'] ?? 0);
    mysqli_query($conn,"DELETE FROM trip_budget_items WHERE id='$item_id' AND trip_id='$trip_id'");
    header("Location: ../trip_group.php?id=$trip_id&tab=budget");
    exit;
}

// ===================================================
// REMOVE MEMBER
// ===================================================
if($action === 'remove_member'){
    $member_uid = (int)($_POST['member_uid'] ?? 0);
    // Hanya creator yang bisa hapus member
    $cek_creator = mysqli_query($conn,"SELECT id FROM trip WHERE id='$trip_id' AND creator_id='$user_id'");
    if(mysqli_num_rows($cek_creator) > 0){
        mysqli_query($conn,"DELETE FROM trip_members WHERE trip_id='$trip_id' AND user_id='$member_uid'");
    }
    header("Location: ../trip_group.php?id=$trip_id&tab=members");
    exit;
}

header("Location: ../trip.php");
exit;
?>
