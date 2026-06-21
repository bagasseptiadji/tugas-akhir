# Integrasi Penuh Vuexy untuk SmartQua

## Tujuan

Menerapkan tampilan Vuexy versi Laravel ke seluruh antarmuka SmartQua tanpa mengubah kontrak backend, data, route, atau API perangkat ESP32 yang telah berjalan.

## Ruang Lingkup

Halaman yang menggunakan tema Vuexy:

- Dashboard.
- Histori pembacaan.
- Pengaturan perangkat, batas sensor, dan Telegram.
- Dokumentasi API.
- Token API.
- Login dan register.

Halaman ekspor Excel serta laporan cetak/PDF tetap menggunakan template khusus yang ringan agar hasil unduhan dan cetak stabil.

## Arsitektur Tampilan

### Layout terautentikasi

Satu layout Blade berbasis Vuexy diterapkan untuk seluruh route di dalam middleware `auth`.

- Sidebar vertikal menjadi navigasi utama.
- Navbar memuat identitas pengguna, tombol logout, dan pengubah tema.
- Struktur menu: Dashboard; Histori Pembacaan; Sistem > Pengaturan; API > Dokumentasi API dan Token API.
- Status menu aktif ditentukan dari route Laravel saat ini.

Setiap halaman SmartQua mempertahankan controller, data view, form action, endpoint AJAX, dan JavaScript perilaku yang sudah ada. Perubahan hanya pada markup, kelas, dan komponen presentasi Vuexy.

### Layout tamu

Login dan register menggunakan layout autentikasi Vuexy tanpa sidebar atau navbar aplikasi. Validasi, CSRF, nama field, dan handler autentikasi Laravel tidak berubah.

### Aset dan komponen

Aset Vuexy yang diperlukan dipindahkan/diadaptasi ke pipeline Vite aplikasi utama. Hanya aset dan dependency yang benar-benar digunakan yang diintegrasikan; halaman demo, route demo, dan fitur starter-kit yang tidak berkaitan tidak ikut dipindahkan.

Komponen visual dipakai secara konsisten: card metrik, badge status, alert, tabel responsif, form control, tab, modal, dropdown, dan ikon dari Vuexy.

## Tema

Tema awal mengikuti `prefers-color-scheme` perangkat. Pengguna dapat mengganti terang/gelap dari navbar; pilihan manual disimpan di browser dan mengalahkan preferensi sistem pada kunjungan berikutnya. Bila belum ada pilihan manual, tampilan kembali mengikuti sistem.

## Pemetaan Halaman

| Halaman SmartQua | Perlakuan Vuexy |
| --- | --- |
| Dashboard | Layout aplikasi, card metrik, chart container, ringkasan alert, dan status perangkat. |
| Histori | Header halaman, filter form, tabel DataTables, kartu grafik, dan tombol ekspor. |
| Pengaturan | Tab/section Vuexy untuk perangkat, batas sensor, dan Telegram. |
| Dokumentasi API | Card dokumentasi dan blok kode yang konsisten dengan tema. |
| Token API | Card token, form pembuatan token, dan tabel token. |
| Login/Register | Halaman autentikasi Vuexy. |

## Penanganan Kegagalan

- Jika aset Vite gagal dimuat, error build harus terlihat saat pengembangan; tidak ada fallback ke aset demo starter-kit.
- Kegagalan AJAX dashboard atau histori tetap menggunakan pesan error aplikasi saat ini, tetapi ditampilkan dengan komponen alert/toast Vuexy.
- Preferensi tema yang tidak valid dihapus dan aplikasi kembali ke preferensi sistem.

## Verifikasi

- Build aset Vite berhasil.
- Semua route web yang ada dapat dirender tanpa error.
- Login, register, logout, pengaturan, token API, grafik, filter histori, dan ekspor tetap bekerja.
- Sidebar aktif sesuai route dan responsif pada desktop maupun layar kecil.
- Tema mengikuti sistem pada kunjungan pertama dan pilihan manual bertahan setelah halaman dimuat ulang.
- Laporan cetak/PDF serta ekspor Excel tetap menghasilkan output yang benar.
