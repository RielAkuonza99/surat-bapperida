# Sistem Surat Masuk

Aplikasi tetap menggunakan PHP Native, MariaDB/MySQL, PDO, Bootstrap, CSS, dan JavaScript. URL lama (`index.php`, `dashboard.php`, `surat-masuk.php`, `tambah.php`, `detail.php`, `edit.php`, `hapus.php`, dan `logout.php`) dipertahankan sebagai adapter ke controller.

## Menjalankan lokal

1. Buat database `surat_masuk` lalu impor `database.sql` untuk instalasi baru.
2. Isi `.env` dengan kredensial database lokal. `.env` diabaikan Git dan diblokir Apache.
3. Untuk database yang sudah ada, jalankan `database/migrations/001_create_surat_sequence.sql` satu kali sebelum memakai fitur tambah surat.
4. Buka `http://localhost/ipong/` melalui Apache/PHP dan pastikan Apache menjalankan `mod_rewrite` serta mengizinkan `.htaccess` (`AllowOverride All`).

Konfigurasi produksi harus memakai `APP_ENV=production`, `APP_DEBUG=false`, dan akun database dengan hak minimum yang diperlukan. HSTS hanya dikirim ketika request terdeteksi HTTPS. Ganti kredensial development lokal sebelum deployment.

## Struktur aplikasi

- `app/Controllers`: request dan response halaman.
- `app/Services`: autentikasi, validasi, dan alur bisnis surat.
- `app/Models`: query PDO.
- `app/Middleware`: pemeriksaan login.
- `app/Security`: session/error headers, rate limit login, dan audit logger.
- `app/Views`: template HTML; SQL tidak diletakkan di sini.
- `config`: environment, database, dan security setup.
- `storage/logs`: log aplikasi dan audit aktivitas.
- `storage/private`: data internal seperti penghitung percobaan login.
- `database/migrations`: perubahan database incremental.

Role belum tersedia dalam schema awal, sehingga aplikasi mempertahankan permission setara untuk seluruh akun terautentikasi. Tidak ada upload atau kebutuhan API JSON pada fitur existing; keduanya tidak ditambahkan dalam refactor ini.