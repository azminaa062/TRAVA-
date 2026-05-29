<?php
session_start();
include 'config/koneksi.php';

// Hanya bisa diakses kalau sudah login
if(!isset($_SESSION['login'])){
    die("Harap login dulu. <a href='login.php'>Login</a>");
}
$user_id = $_SESSION['user_id'];

echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}
table{border-collapse:collapse;width:100%;margin-bottom:20px;background:white;}
th,td{border:1px solid #ccc;padding:6px 10px;text-align:left;font-size:13px;}
th{background:#17375e;color:white;}
tr:nth-child(even){background:#f9f9f9;}
h2{color:#17375e;margin-top:30px;}
.ok{color:green;font-weight:bold;}
.err{color:red;font-weight:bold;}
.box{background:white;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #ddd;}
</style>";

echo "<h1>🔍 DEBUG NOTIFIKASI TRAVA</h1>";
echo "<div class='box'><b>User ID login:</b> $user_id</div>";

// ============================================================
// 1. CEK STRUKTUR TABEL notifications
// ============================================================
echo "<h2>1. Struktur Tabel notifications</h2>";
$cols = mysqli_query($conn, "SHOW COLUMNS FROM `notifications`");
if(!$cols){
    echo "<p class='err'>❌ Tabel notifications TIDAK ADA atau error: ".mysqli_error($conn)."</p>";
    echo "<p>Jalankan file <b>database/fix_notifications.sql</b> di phpMyAdmin dulu.</p>";
} else {
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    $has_link_url = false;
    $has_type = false;
    $has_trip_id = false;
    while($c = mysqli_fetch_assoc($cols)){
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Default']}</td></tr>";
        if($c['Field']==='link_url') $has_link_url = true;
        if($c['Field']==='type') $has_type = true;
        if($c['Field']==='trip_id') $has_trip_id = true;
    }
    echo "</table>";
    echo "<p>link_url: ".($has_link_url?"<span class='ok'>✅ Ada</span>":"<span class='err'>❌ TIDAK ADA</span>")."</p>";
    echo "<p>type: ".($has_type?"<span class='ok'>✅ Ada</span>":"<span class='err'>❌ TIDAK ADA</span>")."</p>";
    echo "<p>trip_id: ".($has_trip_id?"<span class='ok'>✅ Ada</span>":"<span class='err'>❌ TIDAK ADA</span>")."</p>";

    // Auto-fix kolom yang kurang
    if(!$has_link_url){
        $r = mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN `link_url` varchar(255) DEFAULT NULL");
        echo "<p>".($r?"<span class='ok'>✅ Kolom link_url berhasil ditambahkan otomatis</span>":"<span class='err'>❌ Gagal tambah link_url: ".mysqli_error($conn)."</span>")."</p>";
    }
    if(!$has_type){
        mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN `type` varchar(50) DEFAULT 'akun'");
        echo "<p><span class='ok'>✅ Kolom type ditambahkan</span></p>";
    }
    if(!$has_trip_id){
        mysqli_query($conn,"ALTER TABLE `notifications` ADD COLUMN `trip_id` int(11) DEFAULT NULL");
        echo "<p><span class='ok'>✅ Kolom trip_id ditambahkan</span></p>";
    }
}

// ============================================================
// 2. CEK KOLOM TABEL trip (creator_id atau user_id?)
// ============================================================
echo "<h2>2. Kolom Tabel trip (cek creator_id vs user_id)</h2>";
$trip_cols = mysqli_query($conn, "SHOW COLUMNS FROM `trip`");
if(!$trip_cols){
    echo "<p class='err'>❌ Tabel trip tidak ditemukan: ".mysqli_error($conn)."</p>";
} else {
    $has_creator = false;
    $has_user = false;
    $col_list = [];
    echo "<table><tr><th>Field</th><th>Type</th></tr>";
    while($tc = mysqli_fetch_assoc($trip_cols)){
        echo "<tr><td>{$tc['Field']}</td><td>{$tc['Type']}</td></tr>";
        $col_list[] = $tc['Field'];
        if($tc['Field']==='creator_id') $has_creator = true;
        if($tc['Field']==='user_id') $has_user = true;
    }
    echo "</table>";
    $creator_col = $has_creator ? 'creator_id' : ($has_user ? 'user_id' : '???');
    echo "<p>Kolom yang dipakai untuk pemilik trip: <b>$creator_col</b></p>";

    // Hitung trip selesai user ini
    $q_selesai = mysqli_query($conn,"SELECT COUNT(*) as c FROM trip WHERE {$creator_col}='$user_id' AND status='selesai'");
    $jml_selesai = $q_selesai ? (int)mysqli_fetch_assoc($q_selesai)['c'] : 0;
    echo "<p>Trip selesai milik user $user_id: <b>$jml_selesai</b></p>";

    // Level
    function dbgLevel($n){
        if($n >= 30) return 'Cirebon Master';
        if($n >= 15) return 'Expert Traveler';
        if($n >= 7)  return 'Traveler';
        if($n >= 3)  return 'Explorer';
        return 'Newbie';
    }
    echo "<p>Level sekarang: <b>".dbgLevel($jml_selesai)."</b> | Level sebelumnya (jika -1): <b>".dbgLevel($jml_selesai-1)."</b></p>";
    if(dbgLevel($jml_selesai) !== dbgLevel($jml_selesai-1)){
        echo "<p class='ok'>✅ Kondisi level naik TERPENUHI — notif level naik harusnya muncul</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Level belum naik pada jumlah trip selesai ini ($jml_selesai). Level naik di trip ke-3, 7, 15, 30.</p>";
    }
}

// ============================================================
// 3. ISI NOTIFIKASI USER INI (semua type)
// ============================================================
echo "<h2>3. Semua Notifikasi Milik User $user_id</h2>";
$all_notif = mysqli_query($conn,"SELECT * FROM notifications WHERE user_id='$user_id' ORDER BY created_at DESC LIMIT 20");
if(!$all_notif || mysqli_num_rows($all_notif)==0){
    echo "<p class='err'>❌ Tidak ada notifikasi sama sekali untuk user ini.</p>";
    echo "<p>Kemungkinan: INSERT gagal, atau salah user_id, atau tabel baru dibuat tapi belum ada data.</p>";
} else {
    echo "<table><tr><th>id</th><th>type</th><th>from_user_id</th><th>trip_id</th><th>message</th><th>link_url</th><th>is_read</th><th>created_at</th></tr>";
    while($n = mysqli_fetch_assoc($all_notif)){
        $lnk = htmlspecialchars($n['link_url'] ?? '-');
        echo "<tr>
            <td>{$n['id']}</td>
            <td><b>{$n['type']}</b></td>
            <td>{$n['from_user_id']}</td>
            <td>{$n['trip_id']}</td>
            <td>".htmlspecialchars($n['message'])."</td>
            <td>{$lnk}</td>
            <td>{$n['is_read']}</td>
            <td>{$n['created_at']}</td>
        </tr>";
    }
    echo "</table>";
}

// ============================================================
// 4. CEK: ADA NOTIF WACANA?
// ============================================================
echo "<h2>4. Notifikasi Wacana Trip</h2>";
$wacana_notif = mysqli_query($conn,"SELECT * FROM notifications WHERE user_id='$user_id' AND type='wacana' ORDER BY created_at DESC LIMIT 5");
$wacana_count = $wacana_notif ? mysqli_num_rows($wacana_notif) : 0;
if($wacana_count == 0){
    echo "<p class='err'>❌ Tidak ada notifikasi type='wacana' untuk user ini.</p>";
    echo "<p>Kemungkinan: trip dengan status 'batal' belum dibuat, atau INSERT gagal.</p>";

    // Cek apakah ada trip berstatus batal milik user
    $col_q = mysqli_query($conn,"SHOW COLUMNS FROM `trip` LIKE 'creator_id'");
    $c_col = mysqli_num_rows($col_q)>0 ? 'creator_id' : 'user_id';
    $batal_trip = mysqli_query($conn,"SELECT id, nama_trip, status FROM trip WHERE {$c_col}='$user_id' AND status='batal' LIMIT 5");
    $batal_count = $batal_trip ? mysqli_num_rows($batal_trip) : 0;
    echo "<p>Trip berstatus 'batal' milik user: <b>$batal_count</b></p>";
    if($batal_count > 0){
        echo "<p class='err'>⚠️ Ada trip batal tapi tidak ada notif wacana — INSERT ke notifications gagal waktu buat trip. Kemungkinan kolom type/link_url belum ada saat itu.</p>";
        // Insert manual notif wacana untuk trip batal yang ada
        echo "<p>Klik tombol di bawah untuk insert notif wacana manual:</p>";
        while($bt = mysqli_fetch_assoc($batal_trip)){
            $nm = htmlspecialchars($bt['nama_trip']);
            echo "<form method='POST' style='display:inline;margin-right:10px;'>
                <input type='hidden' name='fix_wacana' value='{$bt['id']}'>
                <input type='hidden' name='trip_nama' value=\"{$bt['nama_trip']}\">
                <button type='submit' style='background:#dc2626;color:white;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;'>
                    Fix notif wacana: {$nm}
                </button>
            </form>";
        }
    }
} else {
    echo "<p class='ok'>✅ Ada $wacana_count notifikasi wacana.</p>";
    echo "<table><tr><th>id</th><th>message</th><th>created_at</th></tr>";
    while($wn = mysqli_fetch_assoc($wacana_notif)){
        echo "<tr><td>{$wn['id']}</td><td>".htmlspecialchars($wn['message'])."</td><td>{$wn['created_at']}</td></tr>";
    }
    echo "</table>";
}

// Handle fix_wacana POST
if(isset($_POST['fix_wacana'])){
    $fix_tid = (int)$_POST['fix_wacana'];
    $fix_tnama = mysqli_real_escape_string($conn, $_POST['trip_nama']);
    $fix_link = "trip_group.php?id=$fix_tid";
    $fix_msg = "Wacana trip baru tersedia: \"{$fix_tnama}\". Belum ada tanggal pasti, yuk diskusikan!";
    $fix_msg_esc = mysqli_real_escape_string($conn, $fix_msg);
    $r = mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,trip_id,type,message,link_url)
        VALUES('$user_id','$user_id','$fix_tid','wacana','$fix_msg_esc','$fix_link')");
    if($r) echo "<p class='ok'>✅ Notif wacana untuk trip ID $fix_tid berhasil diinsert!</p>";
    else echo "<p class='err'>❌ Gagal: ".mysqli_error($conn)."</p>";
}

// ============================================================
// 5. CEK: ADA NOTIF AKUN/LEVEL?
// ============================================================
echo "<h2>5. Notifikasi Akun (level naik, profil)</h2>";
$akun_notif = mysqli_query($conn,"SELECT * FROM notifications WHERE user_id='$user_id' AND type='akun' ORDER BY created_at DESC LIMIT 5");
$akun_count = $akun_notif ? mysqli_num_rows($akun_notif) : 0;
if($akun_count == 0){
    echo "<p class='err'>❌ Tidak ada notifikasi type='akun'.</p>";
} else {
    echo "<p class='ok'>✅ Ada $akun_count notifikasi akun.</p>";
    echo "<table><tr><th>id</th><th>message</th><th>link_url</th><th>created_at</th></tr>";
    while($an = mysqli_fetch_assoc($akun_notif)){
        echo "<tr><td>{$an['id']}</td><td>".htmlspecialchars($an['message'])."</td><td>".htmlspecialchars($an['link_url']??'-')."</td><td>{$an['created_at']}</td></tr>";
    }
    echo "</table>";
}

// ============================================================
// 6. TES INSERT MANUAL notif level naik
// ============================================================
echo "<h2>6. Test INSERT Notifikasi</h2>";
if(isset($_POST['test_insert'])){
    $type_test = $_POST['type_test'];
    $msg_test  = mysqli_real_escape_string($conn, $_POST['msg_test']);
    $r = mysqli_query($conn,"INSERT INTO notifications(user_id,from_user_id,type,message,link_url)
        VALUES('$user_id','$user_id','$type_test','$msg_test','profil.php')");
    if($r){
        echo "<p class='ok'>✅ INSERT berhasil! ID: ".mysqli_insert_id($conn)."</p>";
    } else {
        echo "<p class='err'>❌ INSERT GAGAL: ".mysqli_error($conn)."</p>";
    }
}
echo "<form method='POST'>
    <input type='hidden' name='type_test' value='akun'>
    <input type='hidden' name='msg_test' value='TEST: Level Traveller kamu naik dari Newbie ke Explorer!'>
    <button type='submit' style='background:#17375e;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;'>
        🧪 Test Insert Notif Level Naik
    </button>
</form>
<form method='POST' style='margin-top:8px'>
    <input type='hidden' name='type_test' value='wacana'>
    <input type='hidden' name='msg_test' value='TEST: Wacana trip baru tersedia: Trip Test Wacana.'>
    <button type='submit' style='background:#dc2626;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;'>
        🧪 Test Insert Notif Wacana
    </button>
</form>";

echo "<br><p><a href='notifikasi.php' style='color:#17375e;font-weight:bold;'>→ Buka halaman Notifikasi</a></p>";
echo "<br><hr><p style='color:#aaa;font-size:11px;'>Hapus file debug_notif.php setelah selesai debug.</p>";
?>
