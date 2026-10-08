# Catatan Kendala dan Pekerjaan Tertunda

**Tanggal pembaruan:** 7 Oktober 2026
**Lingkungan:** Development lokal, Laragon
**Status:** Belum siap production

Dokumen ini mencatat hambatan dan pekerjaan yang belum dapat dinyatakan selesai. Ini bukan pengganti requirement di [README.md](./README.md).

## Kendala yang masih terbuka

| Area | Status | Kendala dan tindak lanjut |
|---|---|---|
| Verifikasi production | Belum diuji | Pengujian autentikasi dan otorisasi dilakukan pada salinan aplikasi serta database sementara di lokal. Hasil lokal tidak membuktikan konfigurasi HTTPS, cookie/session, atau pembatasan akses server pada deployment. |
| Upload dan pemulihan Google Drive | Implementasi lokal, belum diverifikasi | Upload PDF melalui API dan hingga tiga konfigurasi recovery tersedia di aplikasi. Uji koneksi dan upload provider belum dilakukan pada audit ini; konfigurasi credential perlu diuji di lingkungan lokal. |
| Supabase dan sinkronisasi metadata | Implementasi lokal, belum diverifikasi | Integrasi server-side, antrean metadata, status pengiriman, retry, dan pencatatan kegagalan tersedia. Pengiriman provider serta worker belum diverifikasi ulang pada lingkungan aktif. |
| Backup dan recovery | Belum difinalkan | Strategi serta lokasi backup di luar server aplikasi belum diputuskan. Snapshot `database.sql` sebelum pembaruan tersedia di `database/backups/`; ini bukan dump data database aktif maupun strategi backup production. |
| Migrasi database existing | Skema aktif terdeteksi; riwayat penerapan belum diketahui | Audit lokal 7 Oktober 2026 berhasil terhubung ke database aplikasi dan menemukan 10 tabel, termasuk `surat_sequences` dan `system_metric_samples`. Ini memastikan tabel yang diperlukan tersedia, tetapi tidak membuktikan urutan maupun riwayat eksekusi migrasi. Jangan jalankan ulang migrasi pada database existing; cadangkan database dan cocokkan skema sebelum perubahan berikutnya. |

## Hasil pemeriksaan lokal

- Edit dan hapus akun pegawai berhasil diuji pada aplikasi sementara.
- Pegawai dapat melihat sesi dan riwayat akun sendiri; akses langsung ke halaman pengelolaan pengguna ditolak.
- Pencabutan sesi berhasil mengakhiri akses perangkat uji dan meminta login kembali.
- Pengujian memakai alamat lokal `127.0.0.1`; pencatatan IP klien di jaringan deployment belum terverifikasi.
- Database dan salinan aplikasi uji sebelumnya sudah dibersihkan. Snapshot skema sebelum pembaruan tersedia di `database/backups/`; snapshot tersebut bukan dump data database aktif dan backup aktif perlu dibuat terpisah.
- Audit lokal 7 Oktober 2026: PHP 8.1.10 meluluskan lint pada 54 file PHP; koneksi aplikasi, pencarian surat (termasuk full-text), ringkasan dashboard, dan pembacaan metrik berhasil. Uji metrik memakai transaksi yang di-rollback. Upload Google Drive, sinkronisasi Supabase, dan pengujian production belum dilakukan.

## Batasan penggunaan

- Gunakan `APP_ENV=local` hanya untuk development Laragon.
- Jangan menganggap aplikasi siap production sampai HTTPS, konfigurasi cookie/session, pembatasan akses server, integrasi yang memang dibutuhkan, dan prosedur backup/recovery ditinjau serta diuji pada lingkungan deployment.
- Jangan menaruh token atau credential provider di dokumen publik, frontend, maupun source code.
