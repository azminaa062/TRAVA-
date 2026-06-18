# Projek Website TRAVA - Explore Cirebon

## 1. Deskripsi
Project ini adalah aplikasi web pariwisata dinamis yang dibuat menggunakan PHP Native dan MySQL. Sistem ini digunakan untuk menjelajahi destinasi wisata di Cirebon, memberikan ulasan dan rating, menyimpan wishlist, serta merencanakan trip secara kolaboratif bersama pengguna lain, dengan fitur role admin dan user.

## 2. Fitur

### Admin
- **Login admin**, akses khusus ke panel admin yang terpisah dari akun user biasa.
- **Dashboard admin**, menampilkan ringkasan statistik berupa total destinasi wisata, total user terdaftar, total review, dan total trip yang dibuat, lengkap dengan daftar review terbaru.
- **Mengelola data wisata (CRUD)**, admin dapat menambah destinasi wisata baru, mengedit informasi wisata (nama, deskripsi, lokasi, kategori, harga, fasilitas, aktivitas, foto, dan link Google Maps), serta menghapus data wisata yang sudah tidak relevan.
- **Mengelola review**, admin dapat melihat seluruh ulasan yang dikirim user dan menghapus review yang dianggap tidak pantas atau melanggar aturan.
- **Mengelola data user**, admin dapat melihat daftar seluruh pengguna terdaftar beserta levelnya, dan menghapus akun user jika diperlukan.

### User / Pengunjung
- **Registrasi dan login akun**, pengguna baru dapat membuat akun dan masuk ke sistem menggunakan email dan password.
- **Eksplorasi destinasi wisata**, menampilkan seluruh destinasi wisata di Cirebon yang tersedia, diurutkan berdasarkan rating maupun yang terbaru ditambahkan.
- **Pencarian dan filter wisata**, pengguna dapat mencari wisata berdasarkan nama, lokasi, atau kategori melalui kolom pencarian.
- **Detail wisata**, setiap destinasi memiliki halaman detail yang menampilkan deskripsi lengkap, alamat, kategori, harga tiket (termasuk harga sebelumnya jika ada diskon), fasilitas, daftar aktivitas yang bisa dilakukan, foto, serta lokasi pada Google Maps.
- **Review dan rating**, pengguna dapat memberikan ulasan tertulis beserta rating bintang pada destinasi wisata yang telah dikunjungi, dan rating tersebut otomatis dihitung sebagai rata-rata (rating_avg) pada setiap wisata.
- **Like dan balas review**, pengguna dapat menyukai (like) review milik pengguna lain dan memberikan balasan/komentar pada review tersebut, sehingga interaksi antar pengguna lebih hidup.
- **Wishlist**, pengguna dapat menyimpan destinasi wisata yang ingin dikunjungi ke dalam daftar wishlist pribadi untuk dilihat kembali nanti.
- **Membuat dan mengelola trip**, pengguna dapat membuat rencana perjalanan (trip) dengan menentukan nama trip, tanggal, transportasi, jumlah maksimal anggota, catatan, serta memilih satu atau beberapa destinasi wisata sebagai tujuan trip tersebut.
- **Kolaborasi trip (anggota/kolaborator)**, pembuat trip (host) dapat mengundang dan mengelola anggota (member) untuk bergabung dalam satu trip yang sama, sehingga trip dapat direncanakan bersama-sama.
- **Chat trip**, setiap trip memiliki ruang obrolan grup untuk seluruh anggota, dan juga mendukung chat personal antar dua anggota dalam trip yang sama.
- **Itinerary perjalanan**, anggota trip dapat menyusun rencana aktivitas harian (hari ke-1, hari ke-2, dst.) lengkap dengan waktu, lokasi, dan catatan kegiatan.
- **Manajemen budget trip**, anggota trip dapat mencatat estimasi pengeluaran berdasarkan kategori (misalnya transportasi, konsumsi, penginapan) sehingga biaya perjalanan dapat dipantau bersama.
- **Voting/polling dalam trip**, pembuat trip dapat membuat polling untuk pengambilan keputusan bersama (misalnya memilih destinasi atau jadwal), dan setiap anggota dapat memberikan satu suara per polling.
- **Notifikasi**, pengguna menerima notifikasi terkait undangan trip, balasan review, like, dan aktivitas lain yang melibatkan akun mereka.
- **Profil dan sistem level**, pengguna dapat melihat dan mengedit profil (nama, foto), serta memiliki level perjalanan yang naik secara otomatis berdasarkan jumlah trip yang berhasil diselesaikan, mulai dari Newbie, Explorer, Traveler, Expert Traveler, hingga Cirebon Master.

## 3. Teknologi yang Digunakan
- PHP Native
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- Font Awesome

## 4. Struktur Project

```
TRAVA/
│
├── admin/
│   ├── auth/
│   │   └── cek_login.php
│   ├── partials/
│   │   ├── footer.php
│   │   ├── header.php
│   │   ├── navbar.php
│   │   └── sidebar.php
│   ├── review/
│   │   ├── data.php
│   │   └── hapus.php
│   ├── user/
│   │   ├── data.php
│   │   └── hapus.php
│   ├── wisata/
│   │   ├── data.php
│   │   ├── edit.php
│   │   ├── hapus.php
│   │   └── tambah.php
│   └── index.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── img/
│
├── config/
│   └── koneksi.php
│
├── database/
│   └── trava_db.sql
│
├── partials/
│   ├── footer.php
│   ├── header.php
│   └── navbar.php
│
├── proses/
│   ├── group_proses.php
│   ├── login_proses.php
│   ├── notif_proses.php
│   ├── register_proses.php
│   ├── review_like_proses.php
│   ├── review_proses.php
│   ├── trip_proses.php
│   ├── wishlist_notif_proses.php
│   └── wishlist_proses.php
│
├── index.php
├── landing.php
├── welcome.php
├── login.php
├── register.php
├── logout.php
├── detail.php
├── trip.php
├── trip_detail.php
├── trip_group.php
├── wishlist.php
├── notifikasi.php
└── profil.php
```

## 5. Cara Install
1. Clone repository:
   `git clone https://github.com/username/trava.git`
2. Pindahkan folder project ke:
   `htdocs/`
3. Buat database di phpMyAdmin:
   `trava_db`
4. Import file SQL dari folder `database`.
5. Jalankan project melalui browser:
   `http://localhost/TRAVA`

## 6. Tujuan Project
- Pembelajaran PHP Native
- Memahami konsep CRUD
- Implementasi relasi database yang kompleks (multi-table, many-to-many)
- Implementasi fitur kolaboratif seperti chat, voting, dan budget bersama dalam satu trip
- Pembuatan website dinamis
- Tugas kuliah dan latihan project web

## 7. Author
1. Faija Kulla Azmina (2488010034)
2. Faqih Huddin SM (2488010061)
3. Moh. Farid Ilham Ghifari (2488010066)
- Link Video demo project di youtube : https://youtu.be/gai13TpPe78

## 8. Lisensi
Project ini bersifat open source dan dapat digunakan, dipelajari, serta dikembangkan kembali untuk kebutuhan pembelajaran, tugas kuliah, maupun pengembangan project pribadi.

## 9. Kesimpulan
Aplikasi TRAVA berbasis web ini dibuat untuk mempermudah proses eksplorasi dan perencanaan wisata di Cirebon secara online. Dengan menggunakan PHP Native dan MySQL, sistem mampu menjalankan fitur manajemen destinasi wisata, review dan rating, wishlist, serta perencanaan trip kolaboratif lengkap dengan chat, itinerary, budget, dan voting antar anggota. Project ini juga dapat menjadi sarana pembelajaran dalam memahami konsep CRUD, relasi database, autentikasi, dan pengembangan website dinamis yang lebih kompleks.
