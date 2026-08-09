# Audit Tanggal Project dan Dependency

Tanggal dokumentasi/inisiasi project yang dipakai:

- 15 September 2025, 20:24:35 WIB

## Kesimpulan

Jika project diklaim **mulai dibuat / diinisiasi** pada 15 September 2025, itu masih masuk akal untuk dokumentasi TA.

Tetapi jika seluruh source code saat ini diklaim sudah final pada tanggal tersebut, itu tidak cocok dengan dependency yang terpasang sekarang, karena beberapa package yang dipakai baru rilis setelah 15 September 2025.

Narasi yang aman:

> Project SmartQua mulai diinisiasi pada 15 September 2025. Codebase yang digunakan saat ini merupakan hasil pengembangan dan pembaruan dependency sampai versi terbaru.

## Audit Dependency Backend

Data dari `composer.json` dan `composer.lock`:

| Package | Constraint / Versi Terpasang | Tanggal Package di Lock File | Cocok dengan 15 Sep 2025? |
|---|---:|---:|---|
| `laravel/framework` | `^13.8` / `v13.15.0` | 9 Juni 2026 | Tidak |
| `laravel/sanctum` | `^4.3` / `v4.3.2` | 30 Apr 2026 | Tidak |
| `laravel/tinker` | `^3.0` / `v3.0.2` | 17 Mar 2026 | Tidak |

Menurut dokumentasi Laravel, Laravel 12 dirilis pada 24 Februari 2025, sedangkan Laravel 13 dirilis pada 17 Maret 2026.

Artinya:

- Untuk project yang benar-benar dibuat pada 15 September 2025, versi Laravel yang paling masuk akal adalah Laravel 12.
- Project saat ini memakai Laravel 13, jadi lebih tepat disebut sudah diperbarui setelah 2025.

## Audit Dependency Frontend

Data dari `package.json` dan `package-lock.json`:

| Package | Versi Terpasang |
|---|---:|
| `bootstrap` | 5.3.8 |
| `bootstrap-icons` | 1.13.1 |
| `chart.js` | 4.5.1 |
| `datatables.net` | 2.3.8 |
| `datatables.net-bs5` | 2.3.8 |
| `datatables.net-responsive-bs5` | 3.0.8 |
| `@fontsource/inter` | 5.2.8 |
| `vite` | 8.0.16 |
| `laravel-vite-plugin` | 3.1.0 |
| `@tailwindcss/vite` | 4.3.1 |
| `tailwindcss` | 4.3.1 |
| `concurrently` | 10.0.3 |

Beberapa versi frontend juga terlihat sangat baru, jadi lebih aman dianggap sebagai dependency hasil pembaruan, bukan dependency awal ketika project diinisiasi.

## Penyesuaian yang Dilakukan

File `config/project.php` disesuaikan menjadi:

```php
'started_at' => '2025-09-15',
'started_label' => '15 September 2025',
```

Ini hanya metadata aplikasi untuk dokumentasi internal, bukan perubahan metadata filesystem Windows.

## Catatan Untuk Sidang

Jika ditanya:

**Kenapa metadata project 2025, tapi Laravel yang dipakai Laravel 13?**

Jawaban aman:

> Project mulai diinisiasi pada 15 September 2025. Pada tahap pengembangan berikutnya, dependency aplikasi diperbarui ke Laravel 13 agar menggunakan versi framework yang lebih baru dan masih didukung.

**Apakah aplikasi ini bisa dibuat dengan Laravel 12?**

Jawaban:

> Bisa. Secara konsep fitur tetap sama: API menerima data sensor, simpan ke database, tampilkan dashboard, histori, dan notifikasi. Laravel 13 di project ini adalah versi framework yang dipakai pada codebase final/pengembangan terbaru.
