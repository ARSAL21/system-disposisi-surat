# Milestones Sistem Disposisi Surat

Status keseluruhan: **MVP selesai secara fungsional (release candidate)**.

MVP mencakup alur surat masuk daring maupun manual, pemeriksaan dan registrasi administrasi,
disposisi berjenjang, penyelesaian cabang independen, penyusunan balasan, register surat keluar,
serta publikasi balasan untuk pemohon daring. Surat keluar mandiri tetap menjadi backlog setelah
MVP; skemanya telah disiapkan tanpa mengaktifkan workflow mandiri.

| Milestone | Status | Cakupan selesai |
| --- | --- | --- |
| M0 - Authentication + Account Boundary | Selesai fungsional | Register, login, verifikasi email, reset kata sandi, pemisahan akun public/internal, provisioning internal, pemisahan route, dan security test. |
| M1 - Public Letter Submission | Selesai | Ownership authorization, penyimpanan PDF privat, validasi unggahan, rate limit, dan audit pengajuan dasar. |
| M2 - Internal Identity & Authorization | Selesai | RBAC, Position, Position Assignment, provisioning akun internal, serta audit perubahan hak akses. |
| M3 - Bagian Umum Intake | Selesai | Screening petugas, koreksi/resubmission, pengesahan administratif Kabag Umum, registrasi surat resmi, hashing/versi dokumen, transaksi, dan audit. |
| M4 - Audit & Document Integrity Hardening | Selesai | Enforcement append-only, cakupan audit, akses dokumen privat, verifikasi hash, dan histori versi dokumen. |
| M5 - Routing Sekda | Selesai | Pengantar administratif Kabag Umum ke Sekda dan routing awal sesuai hierarchy MVP. |
| M6.1 - Position-based Routing | Selesai | Disposisi berdasarkan Position Assignment: Sekda ke Asisten, kemudian Asisten ke Kepala Bagian. |
| M6.2 - Multiple Recipients | Selesai | Asisten dapat meneruskan ke beberapa Kepala Bagian yang berbeda; satu Kepala Bagian tidak dapat menjadi terminal recipient pada lebih dari satu cabang Asisten untuk surat yang sama. |
| M6.3 - Independent Branches | Selesai | Mulai proses, jurnal follow-up append-only, penyelesaian cabang independen, aggregate state, audit, dan proteksi konkurensi. |
| M7.1 - Branch Completion | Selesai | Lifecycle cabang `PENDING -> IN_PROGRESS -> COMPLETED`, termasuk penyelesaian langsung dari pending. |
| M7.2 - Aggregate Letter State | Selesai | Surat berubah menjadi `COMPLETED` tepat ketika seluruh cabang terminal selesai; audit `LETTER_COMPLETED` tercatat satu kali. |
| M7.3 - Periodic Reporting | Selesai | Dashboard laporan, decision tree/drilldown, scope berbasis Position, ekspor CSV aman, dan capability laporan. |
| M8.1 - Manual Intake & Incoming Register | Selesai | Pencatatan surat fisik/manual, scan PDF privat, approval Kabag Umum, buku agenda surat masuk, dan sumber surat daring/manual. |
| M8.2 - Response Dossier & Layered Composition | Selesai | Bahan teknis Kabag, proposal/versi Asisten, konsolidasi atau revisi Eksekutif, dan mandat balasan. |
| M8.3 - Outgoing Register & Issuance | Selesai | Nomor surat keluar unik, upload PDF bertanda tangan, verifikasi Kabag Umum, pengiriman oleh Petugas, dan register surat keluar. |
| M8.4 - Delivery Experience | Selesai | Workspace internal, preview/download privat scoped, tracker publik, serta akses balasan hanya bagi pemilik submission setelah dikirim. |
| M8.5 - Integrity, Tests & Release Gate | Selesai fungsional | Controller/Form Request/Policy/Action, transaksi dan lock deterministik, dokumen immutable versioned, audit, notifikasi setelah commit, serta hardening endpoint. |

## Alur MVP yang Ditetapkan

```text
Surat masuk online/manual
  -> screening Petugas Bagian Umum
  -> pengesahan administratif Kabag Umum
  -> surat masuk resmi dan route ke Sekda
  -> Sekda -> Asisten -> satu/lebih Kepala Bagian
  -> penyelesaian cabang dan bahan teknis Kabag
  -> proposal Asisten / konsolidasi Eksekutif
  -> mandat balasan resmi
  -> penomoran Petugas
  -> tanda tangan di luar sistem
  -> verifikasi Kabag Umum
  -> pengiriman Petugas
  -> tracker dan balasan tersedia untuk pemohon daring
```

`IncomingLetter::COMPLETED` berarti disposisi internal selesai. Ketersediaan balasan pemohon
diturunkan terpisah: `IN_PROCESS`, `PREPARING_RESPONSE`, dan `RESPONSE_AVAILABLE`.

## Hasil Release Gate Terakhir

- Pest: **422 passed**, 3 smoke test MySQL sengaja di-skip pada database kerja biasa.
- PHPStan: **0 error**.
- Laravel Pint: **passed**.
- Frontend lint, type-check, dan production build: **passed**.
- `git diff --check`: **passed**.

## Checklist Operasional Sebelum Produksi

Fitur MVP selesai, tetapi langkah berikut harus dijalankan pada lingkungan staging/produksi yang
terkendali:

1. Jalankan migration dan `php artisan authorization:sync`.
2. Tetapkan permission baru untuk custom role operasional melalui UI RBAC dan pastikan seluruh akun
   internal memiliki Position Assignment aktif yang benar.
3. Jalankan tiga smoke test konkurensi MySQL pada database **terisolasi** dengan
   `RUN_MYSQL_CONCURRENCY_TESTS=true`; test tersebut melakukan `migrate:fresh` dan tidak boleh
   diarahkan ke database kerja/produksi.
4. Lakukan UAT per jabatan: Petugas, Kabag Umum, Sekda, Asisten, Kepala Bagian, dan pemohon publik.
5. Siapkan backup database, private storage, email/queue, serta prosedur pemulihan sebelum go-live.

## Backlog Setelah MVP

- Workflow surat keluar mandiri (tanpa surat masuk asal).
- Penyempurnaan aturan operasional lintas-Asisten yang muncul dari UAT, tanpa mengubah riwayat
  disposisi yang sudah tercatat.
