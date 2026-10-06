# SISTEM SURAT MASUK BAPPERIDA

> **Status: DEVELOPMENT — LOCAL**
>
> Dokumen ini merupakan acuan utama untuk pengembangan project Sistem Surat Masuk BAPPERIDA.
>
> Project sudah memiliki implementasi sebelumnya. Pengembangan berikutnya harus dilakukan sebagai **upgrade terhadap project existing**, bukan membuat project baru dari awal.

---

# 1. TUJUAN SISTEM

Sistem ini merupakan aplikasi internal untuk pengelolaan **Surat Masuk BAPPERIDA**.

Sistem digunakan untuk:

* pencatatan surat masuk
* pengelolaan metadata surat
* penyimpanan dan akses dokumen surat
* pencarian arsip
* pengelolaan disposisi
* pengelolaan link dokumen asli pada Google Drive
* pencatatan aktivitas dan autentikasi pengguna
* pengelolaan akses pegawai

Sistem **BUKAN website publik**.

Tidak terdapat akses publik terhadap data surat maupun halaman aplikasi.

---

# 2. STATUS DEVELOPMENT

Saat ini project berada pada:

```text
DEVELOPMENT
LOCAL ENVIRONMENT
```

Jangan menganggap konfigurasi production/hosting sudah final.

Development harus terlebih dahulu menyelesaikan:

1. Struktur aplikasi
2. Struktur API
3. Data flow
4. Security
5. Database architecture
6. Authentication
7. Authorization
8. Google Drive API handling
9. Metadata synchronization
10. Error handling
11. Logging
12. Performance

Deployment hosting akan ditentukan setelah arsitektur aplikasi stabil.

---

# 3. ARSITEKTUR AKSES PRIVATE

Sistem menggunakan konsep:

```text
Internet
   ↓
Authentication
Username + Password
   ↓
Authorization
   ↓
Session / Device Recognition
   ↓
Surat & Dokumen
```

## 3.1 Tidak ada akses publik

Pengguna publik tidak boleh dapat:

* melihat dashboard
* melihat surat
* mencari surat
* mengakses metadata
* mengakses PDF
* mengakses API internal
* mengakses dokumen Google Drive melalui sistem tanpa authorization

Semua akses aplikasi harus melalui mekanisme authentication dan authorization.

IP audit diambil dari koneksi langsung (`REMOTE_ADDR`); header proxy tidak dipercaya sebagai identitas klien. Deployment wajib menggunakan HTTPS dan tetap menerapkan pembatasan akses sesuai infrastruktur yang dipilih.

---

# 4. AUTHENTICATION

Authentication menggunakan:

```text
Username
+
Password
```

User dapat melakukan login melalui halaman authentication.

Password wajib disimpan menggunakan mekanisme password hashing yang aman.

Password tidak boleh disimpan dalam bentuk plaintext.

---

# 5. DEVICE / SESSION RECOGNITION

Setelah pegawai berhasil login:

```text
Login berhasil
      ↓
Sistem mencatat aktivitas authentication
      ↓
Informasi perangkat/session dicatat
      ↓
Perangkat dianggap sebagai authenticated device
```

Konsep yang diinginkan:

> Pegawai yang sudah berhasil melakukan authentication pada perangkat tidak perlu memasukkan username dan password kembali setiap kali membuka/mengakses aplikasi selama session/device authorization masih berlaku.

Login membuat token perangkat acak yang disimpan dalam cookie HttpOnly dan hanya hash token yang disimpan pada MySQL. Masa berlaku awal token adalah 30 hari. IP dan user-agent dicatat untuk audit, tetapi tidak digunakan sebagai satu-satunya identitas perangkat. Aplikasi memeriksa status session di database pada setiap request terlindungi agar pencabutan berlaku pada request berikutnya.

Sistem perlu mempunyai pencatatan terhadap:

* waktu login
* IP
* session
* status authentication
* perangkat/session identifier jika tersedia
* aktivitas login
* logout/revocation

### Catatan penting

IP perangkat merupakan bagian dari pencatatan dan kontrol yang diminta oleh sistem.

Jangan menganggap IP sebagai satu-satunya identitas perangkat.

Implementasi tetap harus mempunyai session/token/device identifier agar sistem tidak hanya bergantung pada IP.

---

# 6. LOGIN HISTORY / CHANGE LOG

Dashboard harus menyediakan riwayat authentication dan aktivitas terkait.

Contoh informasi:

* username
* waktu login
* IP
* status
* session/device
* waktu logout/revoke jika tersedia
* aktivitas penting

Pegawai yang mempunyai hak akses sesuai authorization dapat melihat change log yang diizinkan.

Pegawai melihat riwayat akun dan sesi miliknya sendiri. Administrator dapat melihat riwayat seluruh pengguna. Aktivitas dan alamat IP disimpan di tabel `audit_logs`; catatan lama pada file log tetap berada di `storage/logs`.

---

# 7. PENGHAPUSAN / REVOCATION SESSION

User yang memiliki hak akses dapat menghapus atau mencabut riwayat/session authentication tertentu.

Contoh:

```text
Device A
Authenticated
     ↓
Revoke
     ↓
Session/device authorization dihapus
     ↓
Device harus authentication kembali
```

Fitur ini digunakan untuk:

* menghapus perangkat yang pernah login
* mencabut akses perangkat
* mengakhiri session yang dianggap tidak aman
* mengontrol perangkat yang masih mempunyai akses

Jangan hanya menghapus record database tanpa benar-benar membuat session/device tersebut tidak valid.

Tindakan pencabutan menandai sesi sebagai revoked dan menolak tokennya pada request selanjutnya. Baris riwayat tidak dihapus, agar jejak audit tetap tersedia. Pegawai dapat mencabut sesi pada akunnya; administrator dapat mencabut sesi seluruh pengguna.

---

# 8. PENGELOLAAN USERNAME & PASSWORD

Credential aplikasi dapat dikelola dari dashboard sesuai hak akses.

Administrator yang berwenang dapat:

* mengubah username
* mengubah password
* mengedit data user
* menghapus user
* menonaktifkan user jika diperlukan
* mengelola akses user

Pengelolaan user harus mengikuti authorization.

User biasa tidak boleh mendapatkan kemampuan administrator hanya karena mengetahui URL dashboard.

Implementasi role awal menyediakan `admin` dan `pegawai`. Hanya admin dapat mengelola akun. Admin dapat menambah, mengubah username/password, menonaktifkan, atau menghapus akun. Sistem menolak penonaktifan, penurunan role, atau penghapusan administrator aktif terakhir. Password baru minimal 12 karakter.

---

# 9. AUTHORIZATION

Authentication dan authorization harus dipisahkan.

```text
Authentication
= Apakah user sudah login?

Authorization
= Apakah user tersebut boleh melakukan tindakan ini?
```

Authorization harus diterapkan pada:

* halaman
* controller
* endpoint API
* data surat
* dokumen
* user management
* session management
* Google Drive management
* metadata management

Jangan hanya menyembunyikan tombol pada UI.

Authorization harus diperiksa pada server-side.

---

# 10. ARSITEKTUR DATA IN / EX

Project menggunakan dua kelompok metadata:

```text
INTERNAL DATA
+
EXTERNAL DATA
```

Keduanya bukan database duplikat.

---

## 10.1 INTERNAL — MySQL

MySQL pada server aplikasi digunakan untuk data internal website.

Contoh:

* user
* authentication
* session
* authorization
* konfigurasi
* aktivitas aplikasi
* relasi aplikasi
* data operasional yang diperlukan website
* kontrol proses API
* status sinkronisasi

MySQL berfungsi sebagai bagian dari **operational application data**.

---

## 10.2 EXTERNAL — Supabase

Supabase digunakan sebagai penyimpanan metadata arsip eksternal.

Data yang dikirim ke Supabase berupa **metadata**, bukan sistem aplikasi utama.

Tujuan:

* mengurangi ketergantungan terhadap database MySQL hosting
* menyediakan penyimpanan metadata arsip eksternal
* memungkinkan data surat berkembang dalam jumlah besar
* memisahkan operational database dan archive metadata

Konsep:

```text
PHP Application
       │
       ├──────────────► MySQL
       │                 INTERNAL
       │
       └──────────────► Supabase
                         EXTERNAL METADATA
```

Jangan membuat MySQL dan Supabase menjadi dua database yang identik tanpa alasan.

---

# 11. ARSIP SURAT

Metadata surat harus dapat berkembang dalam jumlah besar.

Sistem tidak boleh dirancang dengan asumsi bahwa jumlah surat hanya sedikit.

Data surat harus mempunyai struktur yang jelas antara:

### Metadata internal aplikasi

dan:

### Metadata arsip eksternal

Data yang dikirim ke Supabase harus melalui mekanisme kontrol dan sinkronisasi.

---

# 12. API DATA FLOW

Sistem tidak boleh melakukan pola:

```text
User request
   ↓
langsung kirim data besar
   ↓
Provider API
```

secara masal dan berulang.

Sistem harus memiliki **lapisan kontrol data/API**.

Konsep:

```text
Application
     ↓
Data Control Layer
     ↓
Queue / Pending Data
     ↓
Validation
     ↓
Send Control
     ↓
External API
     ↓
Provider
```

Tujuannya:

* mencegah request berlebihan
* mencegah overload
* mengontrol jumlah data yang dikirim
* mengurangi request berulang
* menangani retry
* mengetahui status pengiriman
* menjaga konsistensi data
* mencegah kehilangan data ketika API mengalami gangguan

---

# 13. API CONTROL / SYNCHRONIZATION

Setiap data yang akan dikirim ke external provider harus mempunyai status.

Contoh:

```text
PENDING
   ↓
PROCESSING
   ↓
SENT
   ↓
CONFIRMED
```

Jika gagal:

```text
PENDING
   ↓
PROCESSING
   ↓
FAILED
   ↓
RETRY
```

Sistem harus dapat mengetahui:

* data belum dikirim
* sedang dikirim
* berhasil
* gagal
* perlu retry
* terakhir dikirim kapan
* error terakhir
* jumlah retry

Jangan mengirim ulang data yang sudah dikonfirmasi berhasil tanpa alasan.

---

# 14. GOOGLE DRIVE API

Dokumen fisik/PDF surat akan diupload menggunakan **Google Drive API**.

Google Drive menjadi tempat penyimpanan dokumen asli.

Konsep:

```text
Surat
   ↓
Application
   ↓
Google Drive API
   ↓
Google Drive
   ↓
Original Document
```

Metadata surat tetap dikelola oleh aplikasi.

Google Drive digunakan untuk file fisik/original document.

---

# 15. GOOGLE DRIVE API RECOVERY

Sistem harus dirancang agar tidak bergantung hanya pada satu konfigurasi API Google Drive.

Tersedia hingga:

```text
Google Drive API 1
Google Drive API 2
Google Drive API 3
```

Konfigurasi tersebut digunakan sebagai opsi recovery/fallback.

Contoh:

```text
API 1
  ↓
Error / unavailable
  ↓
API 2
  ↓
Error / unavailable
  ↓
API 3
```

Tujuannya adalah meningkatkan reliability.

Namun sistem **tidak boleh melakukan failover secara membabi buta**.

Setiap API harus mempunyai:

* status
* konfigurasi
* credential/configuration terpisah
* health/status
* error information
* penggunaan terakhir jika diperlukan

Admin dapat mengatur API tersebut melalui dashboard sesuai authorization.

---

# 16. GOOGLE DRIVE LINK

Selain upload melalui API, setiap surat dapat memiliki:

```text
Google Drive URL
```

Link ini digunakan sebagai referensi terhadap dokumen asli/private.

Tujuan utamanya:

> Pegawai dapat menemukan dokumen asli dengan mudah dari halaman/search result surat.

Contoh:

```text
Pencarian Surat
     ↓
Detail Surat
     ↓
Google Drive
     ↓
Dokumen asli
```

Link bukan pengganti metadata surat.

---

# 17. DATA SURAT

Struktur data surat harus mengikuti kebutuhan administrasi surat masuk.

Field utama:

* Nama
* TGL
* Sifat
* No Agenda
* TGL Diterima
* No Surat
* Surat Dari
* Judul Surat
* ID Pencarian Surat
* Pengusul
* Uraian
* Keterangan

Pengusul dan uraian **WAJIB dipisahkan**.

Jangan menggunakan satu field gabungan seperti:

```text
uraian_pengusul
```

sebagai struktur utama baru.

---

# 18. UPLOAD PDF

Sistem mendukung upload dokumen PDF.

Namun file fisik utama akan diarahkan melalui Google Drive API sesuai arsitektur storage.

Upload harus tetap mempunyai:

* validasi
* MIME validation
* extension validation
* ukuran maksimal
* filename safety
* authentication
* authorization
* error handling

Dokumen tidak boleh menjadi file public tanpa authorization.

---

# 19. ID PENCARIAN SURAT

Setiap surat memiliki ID pencarian khusus.

ID:

* unik
* stabil
* mudah dicari
* tidak menggantikan primary key
* tidak menggantikan nomor surat
* tidak menggantikan nomor agenda

ID digunakan untuk mempercepat identifikasi surat.

---

# 20. PENCARIAN SURAT

Pencarian minimal mendukung:

* ID Pencarian
* No Surat
* No Agenda
* Nama
* Surat Dari
* Judul
* Pengusul
* Uraian
* Keterangan

Filter tanggal tetap tersedia.

Search harus menggunakan query yang aman dan efisien.

Jangan mengambil seluruh database kemudian melakukan filtering di PHP jika filtering dapat dilakukan oleh database.

---

# 21. DISPOSISI SURAT

Satu surat dapat memiliki maksimal tiga tahap disposisi.

```text
DISPOSISI 1
├── Asal
├── Tujuan
├── Tanggal
└── Keterangan

DISPOSISI 2
├── Asal
├── Tujuan
├── Tanggal
└── Keterangan

DISPOSISI 3
├── Asal
├── Tujuan
├── Tanggal
└── Keterangan
```

Ketiga tahap merupakan bagian dari satu surat.

Bukan tiga surat berbeda.

---

# 22. DETAIL SURAT

Detail surat harus menampilkan:

### Informasi Surat

* Nama
* TGL
* Sifat
* No Agenda
* TGL Diterima
* No Surat
* Surat Dari
* Judul
* ID Pencarian

### Isi

* Pengusul
* Uraian
* Keterangan

### Dokumen

* status dokumen
* PDF/file
* Google Drive link
* informasi dokumen

### Disposisi

* tahap 1
* tahap 2
* tahap 3

---

# 23. USER INTERFACE

UI/UX harus:

* clean
* profesional
* administratif
* mudah digunakan pegawai
* responsive
* tidak berlebihan

Hindari:

* glassmorphism
* gradient berlebihan
* animasi berlebihan
* dashboard terlalu ramai
* dekorasi yang tidak berguna
* terlalu banyak card
* visual AI/slop

Prioritaskan informasi dan workflow pekerjaan.

---

# 24. STRUKTUR APLIKASI

Request web melewati satu front controller di `public/index.php`. Daftar URL dan
HTTP method berada di `routes/web.php`; handler meneruskan request ke controller
yang memanggil service, model, lalu database. View hanya merender data.

```text
public/                 Web document root, front controller, dan aset publik
routes/web.php          Daftar route HTTP
app/Controllers/        Penanganan request dan response
app/Middleware/         Autentikasi, otorisasi, dan pemeriksaan akses
app/Services/            Validasi aturan dan alur bisnis
app/Models/              Akses data
app/Database/            Koneksi database
app/Views/               Template halaman
app/Helpers/             Helper bersama
config/                  Konfigurasi aplikasi
database/migrations/     Perubahan skema database
storage/                 Log dan data privat
```

```text
HTTP request → public/index.php → routes/web.php → middleware/controller
             → service → model → database
             ← view/response
```

Route baru ditambahkan di `routes/web.php`, bukan dengan membuat file PHP baru
di root. URL lama yang berakhiran `.php` tetap diarahkan ke handler yang sama
untuk menjaga bookmark lama; halaman dan form baru menggunakan URL bersih.

Integrasi:

* Supabase
* Google Drive
* authentication
* synchronization
* external API

harus mempunyai lokasi/komponen yang jelas.

Tujuannya:

* maintainability
* debugging
* security
* performance
* scalability

Untuk Laragon, atur document root virtual host ke folder `public`. Jika memakai
PHP built-in server dari root project, jalankan:

```text
php -S 127.0.0.1:8000 -t public public/router.php
```

Jika aplikasi dipasang di subfolder URL, isi `APP_BASE_URL` di `.env` dengan
path tersebut, misalnya `/surat-bapperida`. Biarkan kosong jika aplikasi
diakses dari root domain.

---

# 25. PERFORMANCE

Project harus dirancang agar tidak membebani CPU/RAM server secara tidak perlu.

Hindari:

* query berulang
* request API berulang
* pengiriman data massal
* loading data terlalu besar
* loading seluruh arsip sekaligus
* query tanpa index
* proses external API langsung pada setiap page request jika tidak diperlukan
* request Google Drive yang tidak diperlukan
* request Supabase yang tidak diperlukan

Gunakan pagination untuk data besar.

Gunakan filtering database.

Gunakan caching jika memang diperlukan.

Gunakan kontrol sinkronisasi untuk external API.

---

# 26. API FAILURE HANDLING

Jika external API mengalami gangguan:

Aplikasi tidak boleh langsung gagal total.

Contoh:

```text
Application
    ↓
External API
    ↓
FAILED
    ↓
Record status = FAILED/PENDING
    ↓
Retry / Recovery
```

Error external API harus dicatat.

User harus mendapatkan status yang jelas tanpa melihat credential atau informasi internal server.

---

# 27. SECURITY

Karena sistem merupakan aplikasi private instansi, security menjadi prioritas utama.

Minimal perhatikan:

* Authentication
* Authorization
* Password hashing
* Session security
* CSRF
* Prepared statement
* Input validation
* Output escaping
* Upload validation
* API credential protection
* Google Drive credential protection
* Supabase credential protection
* Access control
* Audit logging
* Rate limiting jika diperlukan
* Secure cookie
* HTTPS
* Production error handling

Credential external API tidak boleh disimpan di frontend.

---

# 28. API CREDENTIAL

Credential Google Drive dan Supabase tidak boleh ditulis langsung di:

* HTML
* JavaScript frontend
* URL
* source code yang dikirim ke browser

Credential harus berada pada server-side configuration/environment.

Admin dashboard hanya mengelola konfigurasi melalui mekanisme yang aman.

---

# 29. LOGGING

Sistem harus mempunyai logging untuk aktivitas penting.

Contoh:

* login
* logout
* failed login
* revoke session
* create surat
* edit surat
* delete surat
* upload document
* Google Drive API failure
* Supabase synchronization failure
* retry
* user management
* authorization failure

Jangan mencatat:

* password
* API secret
* access token
* credential sensitif

---

# 30. AUDIT LOG

Audit log digunakan untuk mengetahui:

```text
Siapa
↓
Melakukan apa
↓
Pada data apa
↓
Kapan
↓
Dari session/device mana
```

Audit log tidak boleh menjadi tempat menyimpan credential.

---

# 31. BACKUP

MySQL hosting tidak boleh dianggap sebagai satu-satunya sumber keselamatan data.

Arsitektur harus mempertimbangkan:

```text
Application Database
+
External Metadata
+
Document Storage
+
Backup
```

Backup dan recovery harus dirancang sebagai bagian terpisah dari database operational.

Jangan menyimpan semua backup hanya di server yang sama.

---

# 32. DATABASE ARCHIVE

Sistem harus siap menangani pertumbuhan jumlah surat.

Jangan membuat asumsi:

```text
100 surat
```

saja.

Arsitektur harus dapat berkembang menjadi:

```text
10.000
100.000
dan lebih banyak metadata
```

tanpa mengambil seluruh data sekaligus pada setiap request.

Gunakan:

* index
* pagination
* filtering
* search
* controlled API request
* data synchronization

sesuai kebutuhan.

---

# 33. DATA FLOW UTAMA

Konsep umum aplikasi:

```text
USER
 ↓
Authentication
 ↓
Authorization
 ↓
Session / Device
 ↓
PHP Application
 ↓
Data Control Layer
 ├──────────────┐
 ↓              ↓
MySQL          Supabase
INTERNAL       EXTERNAL
DATA           METADATA
 ↓
Google Drive API
 ↓
Document Storage
```

Tidak semua request harus melewati seluruh jalur.

Gunakan hanya provider yang memang diperlukan oleh operasi tersebut.

---

# 34. DEVELOPMENT RULE

Saat mengembangkan project:

### WAJIB

1. Periksa project existing terlebih dahulu.
2. Pahami struktur database.
3. Pahami authentication existing.
4. Pahami routing existing.
5. Pahami API/data flow existing.
6. Pertahankan fitur yang masih relevan.
7. Lakukan perubahan secara incremental.
8. Test setiap perubahan.
9. Jangan menghapus data/file tanpa alasan.
10. Dokumentasikan perubahan.

### JANGAN

* rebuild dari awal
* mengganti framework tanpa alasan
* mengganti PHP Native
* membuat arsitektur baru yang tidak diperlukan
* membuat API random di setiap controller
* menaruh credential di frontend
* mengirim data massal ke provider
* mengakses external API berkali-kali tanpa kontrol
* mengubah fitur di luar scope
* membuat fitur tanda tangan digital

---

# 35. PROJECT INPUT UNTUK CLAUDE

Pada update berikutnya, Claude akan menerima:

```text
1. FULL PROJECT
   ↓
   Project lengkap versi saat ini

2. UPDATE PROJECT
   ↓
   Hasil update/perubahan sebelumnya

3. README.md INI
   ↓
   Requirement dan arsitektur terbaru
```

Claude harus membandingkan kedua project sebelum melakukan perubahan.

Jangan menganggap UPDATE PROJECT sebagai project lengkap.

FULL PROJECT merupakan basis utama.

UPDATE PROJECT digunakan sebagai sumber perubahan yang perlu dipertahankan atau dievaluasi.

---

# 36. PRIORITAS PENGEMBANGAN

Urutan prioritas:

## PRIORITAS 1

Private Authentication & Authorization

## PRIORITAS 2

Struktur data Surat Masuk

## PRIORITAS 3

Struktur database IN / EX

## PRIORITAS 4

API/Data Control Layer

## PRIORITAS 5

Google Drive API

## PRIORITAS 6

Supabase Metadata Synchronization

## PRIORITAS 7

Audit Log & Device Session

## PRIORITAS 8

Search & Archive

## PRIORITAS 9

Performance

## PRIORITAS 10

UI/UX

---

# 37. SCOPE LOCK

Jika menemukan masalah di luar requirement:

JANGAN langsung memperluas pekerjaan.

Gunakan:

```text
FOUND
↓
CHECK
↓
APAKAH MENGHALANGI FITUR?
↓
YES → perbaikan minimum
NO  → laporkan saja
```

Jangan melakukan:

> "Sekalian saya refactor seluruh project."

atau:

> "Sekalian saya ganti architecture."

Semua perubahan harus mempunyai hubungan dengan kebutuhan sistem.

---

# 38. DEFINITION OF DONE

Sebuah fitur dianggap selesai jika:

* implementasi selesai
* database sesuai
* authorization sesuai
* security diperiksa
* API flow diperiksa
* error handling tersedia
* tidak menghasilkan request berlebihan
* tidak merusak fitur existing
* dapat diuji secara local
* dokumentasi perubahan tersedia

Project **belum dianggap production-ready** hanya karena halaman dapat dibuka dan CRUD berhasil.

---

# 39. CATATAN PENTING

Project ini ditujukan untuk lingkungan instansi dan bersifat private.

Karena itu:

> **Security, privacy, data integrity, maintainability, dan reliability lebih penting daripada menambahkan banyak fitur.**

Jangan mengejar kompleksitas.

Jangan membuat sistem terlihat canggih tetapi sulit dipelihara.

Target utama adalah:

```text
AMAN
TERKONTROL
TERSTRUKTUR
RINGAN
DAPAT DIPERLUAS
MUDAH DIPERBAIKI
```

---

# 40. STATUS REQUIREMENT

### SUDAH DITETAPKAN

* Private website
* Username + password
* Authentication
* Authorization
* Session/device recognition
* IP logging
* Login/change log
* Session/device revoke
* User management
* MySQL sebagai INTERNAL
* Supabase sebagai EXTERNAL metadata
* Google Drive sebagai document storage
* Google Drive link pada surat
* Maksimal 3 konfigurasi Google Drive API
* API control/synchronization layer
* Queue/pending/retry concept
* Surat Masuk metadata
* Pengusul dan Uraian terpisah
* PDF
* 3 tahap disposisi
* Search
* Audit log
* Performance-oriented architecture
* Development masih LOCAL

### DIHAPUS DARI SCOPE

* Sistem tanda tangan digital

### BELUM DIFINALKAN

* Provider hosting production
* Detail strategi backup final
* Detail storage backup
* Detail permission role yang lebih granular
* Implementasi final API failover
* Detail struktur database Supabase
* Detail deployment production

---

## Catatan implementasi lokal

- Kendala dan pekerjaan yang belum selesai dicatat di [CATATAN-KENDALA.md](./CATATAN-KENDALA.md).
- Instalasi baru menggunakan `database.sql`, yang sudah memuat metadata surat dan tabel disposisi tiga tahap.
- Database lama perlu backup terlebih dahulu, lalu menjalankan migration `001_create_surat_sequence.sql`, `002_extend_surat_masuk.sql`, `003_private_authentication.sql`, `004_archive_sync_queue.sql`, `005_google_drive_api_configs.sql`, dan `006_system_metric_samples.sql` secara berurutan. Masing-masing migration dijalankan satu kali.
- Database baru mendapatkan role `admin` dan `pegawai`, status akun, tabel `auth_sessions`, dan `audit_logs` dari `database.sql`.
- `database.sql` menyediakan tiga surat bertanda `DATA CONTOH` untuk pengujian instalasi baru. Baris contoh dapat diperbarui ulang berdasarkan ID pencarian tanpa menggandakan entri. Salinan SQL sebelum pembaruan tersimpan lokal di `database/backups/`; backup data database aktif belum dibuat karena MySQL lokal berhenti.
- Untuk development Laragon, gunakan `APP_ENV=local`. Untuk deployment, set `APP_ENV=production`, aktifkan HTTPS, dan pastikan konfigurasi cookie/session serta pembatasan akses server sesuai lingkungan.
- Token perangkat berlaku 30 hari. Penghapusan akun mencabut sesi, sedangkan pencabutan sesi biasa mempertahankan riwayatnya. Pegawai melihat log dan sesi akunnya sendiri; admin melihat semuanya dan mengelola akun.
- Daftar surat menggunakan pencarian teks FULLTEXT, filter tanggal berindeks, dan pagination tetap 25 baris per halaman; pencarian singkat memakai pencocokan langsung di database.
- MySQL tetap menjadi sumber data operasional. Perubahan metadata surat masuk ke tabel outbox `archive_sync_queue` di transaksi yang sama dengan perubahan surat, lalu worker CLI mengirim metadata arsip ke Supabase dalam batch terbatas. Data user, sesi, audit, dan konfigurasi internal tidak dikirim. Penghapusan surat dari MySQL juga mengantrekan penghapusan metadata eksternal, sesuai keputusan pemilik data.
- Jalankan `database/supabase/archive_metadata.sql` pada SQL Editor Supabase. Isi `SUPABASE_URL`, `SUPABASE_SERVICE_ROLE_KEY`, dan `SUPABASE_ARCHIVE_TABLE` di konfigurasi server `.env`; service-role key tidak boleh diletakkan di frontend atau commit ke repository.
- Buat kunci enkripsi lokal dengan `php -r "echo bin2hex(random_bytes(32));"` lalu simpan nilainya sebagai `APP_ENCRYPTION_KEY` di `.env`. Simpan salinan kunci secara aman di luar server aplikasi; credential Drive di MySQL tidak dapat dibuka jika kunci ini hilang atau berubah.
- Jalankan worker secara terjadwal menggunakan `php bin/sync-archive.php --limit=10`. Ukuran batch dibatasi maksimal 100; kegagalan memakai exponential backoff, dan pengiriman upsert idempoten memakai ID pencarian. Pengiriman tidak dijalankan dari request halaman.
- Untuk memasukkan arsip MySQL yang sudah ada ke antrean secara bertahap, jalankan `php bin/queue-archive-backfill.php --after-id=0 --limit=100`, lalu lanjutkan dengan nilai `--after-id` yang dicetak. Perintah ini hanya membuat outbox lokal; pengiriman tetap ditangani worker dengan batch limit.
- Administrator dapat melihat status serta error antrean pada halaman Sinkronisasi metadata dan menjadwalkan ulang item gagal. Rute halaman dan aksi retry dilindungi authorization admin serta CSRF.
- Halaman Pengaturan tersedia untuk semua pengguna login dan menampilkan username, role, serta ID akun. Ringkasan metrik hanya tersedia untuk admin. Sampel memuat penggunaan memori proses PHP, ruang pada volume aplikasi, waktu query MySQL, dan jumlah antrean/API Drive. Sampel disimpan lokal paling banyak satu kali setiap lima menit saat admin membuka Pengaturan, dipertahankan 30 hari, dan grafik membaca rentang 24 jam.
- Analisis jaringan memakai status online browser dan estimasi RTT/jenis koneksi perangkat jika browser menyediakannya. Nilai browser tidak dikirim ke server. Waktu query MySQL mengukur round-trip aplikasi ke database; halaman tidak melakukan ping provider Supabase atau Google Drive. Grafik tidak mengklaim sebagai metrik CPU/RAM keseluruhan server atau latency API eksternal.
- Tombol Pengaturan pada navbar menampilkan profil singkat dan aksi logout dengan konfirmasi. Navigasi desktop dapat ditutup untuk memperluas area kerja; pada layar kecil menu menjadi drawer geser dengan backdrop dan tidak mendorong konten.
- Design Read: aplikasi administrasi internal BAPPERIDA untuk pegawai, dengan ENERGY 1 / RHYTHM 2 / MOTION 2. Navy mempertahankan identitas BAPPERIDA dan slate terang menjaga keterbacaan; aksen rust menandai fokus dan status penting. Profil, metrik, dan API memakai kelompok sesuai tugasnya. Tipografi system sans-serif dipilih agar konsisten pada perangkat kantor, ikon navigasi menggambarkan tujuan tiap menu, dan gerak dibatasi pada drawer serta indikator muat yang menyesuaikan estimasi koneksi. prefers-reduced-motion tetap dihormati.
- Jika metrik admin gagal dimuat, profil tetap tampil dan halaman memberi langkah pemulihan. Detail exception dicatat di log server, bukan ditampilkan kepada pengguna.
- Link Google Drive tetap dapat disimpan sebagai referensi. Upload PDF melalui Google Drive API mengirim dokumen ke folder yang ditentukan admin; file tidak disimpan pada web root.
- Pengusul dan uraian tersedia sebagai field terpisah. Data lama dipertahankan pada kolom kompatibilitas dan disalin ke uraian, karena sumber lama tidak menyimpan pemisahan kedua nilai.
- Worker sinkronisasi memakai konfigurasi Supabase server-side, tetapi credential provider, konfigurasi service account, pembagian akses folder Drive, dan migrasi Supabase tetap perlu disiapkan pada environment lokal sebelum integrasi dapat diuji end-to-end.
- Admin dapat menyimpan maksimal tiga credential service account Google Drive pada halaman Google Drive API. Isi folder tujuan dan bagikan folder tersebut hanya kepada service account serta pegawai/grup internal yang berwenang, bukan sebagai tautan publik. Credential JSON dienkripsi dengan AES-256-GCM menggunakan `APP_ENCRYPTION_KEY`, tidak dikirim kembali ke browser, dan hanya email akun yang ditampilkan.
- Upload menerima PDF maksimal 10 MiB dengan pemeriksaan extension, MIME, signature, authentication, dan authorization. API dicoba berdasarkan urutan slot; failover hanya untuk error retryable. Jika hasil upload tidak pasti, sistem mencari ID upload dan menghentikan upload ulang/failover bila hasilnya belum dapat dipastikan agar tidak membuat file ganda.
- Tes koneksi, penggunaan terakhir, status kesehatan API, dan error ringkas tersedia bagi admin. Perubahan konfigurasi, upload, kegagalan upload, sinkronisasi, dan retry dicatat tanpa menyimpan credential ke log.
- Sinkronisasi balik dari Supabase ke MySQL serta strategi backup final belum diimplementasikan.
