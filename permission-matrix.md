# Permission Matrix

Dokumen ini adalah katalog awal RBAC aplikasi. Katalog akan ditambah secara
bertahap bersama milestone yang benar-benar membutuhkan capability baru.

## Prinsip

* Guard RBAC yang digunakan hanya `web`.
* Role merepresentasikan kumpulan capability aplikasi, bukan jabatan atau
  hierarki organisasi.
* Permission diberikan kepada role. Pemberian permission langsung kepada user
  bukan alur administrasi normal.
* Role tidak menggantikan boundary akun. Akses internal tetap mensyaratkan akun
  `INTERNAL`, aktif, terverifikasi, dan permission yang sesuai.
* `super-admin` adalah administrator teknis tertinggi, tetapi tidak memiliki
  bypass universal melalui `Gate::before`.
* Akses terhadap surat tetap harus melewati Policy, Position Assignment aktif,
  visibility scope, dan aturan workflow yang relevan.

## Katalog M2.1

| Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `authorization.view` | Melihat konfigurasi role dan permission. |
| `super-admin` | `authorization.manage` | Mengelola konfigurasi role dan permission melalui alur yang terotorisasi. |

## Penambahan M2.3

| Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `position-assignments.manage` | Menetapkan, mengganti, dan mengakhiri pemegang Position melalui alur yang terotorisasi dan teraudit. |

## Struktur Organisasi Operasional M2

| Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `organization.view` | Membaca katalog Position Level, unit organisasi, jabatan, keterisian, dan histori Position Assignment. |
| `super-admin` | `organization.manage` | Membuat, memperbarui, mengaktifkan, dan menonaktifkan unit serta jabatan konkret. |

`organization.manage` tidak memberikan hak untuk mengganti pejabat. Lifecycle
pejabat tetap membutuhkan `position-assignments.manage`. Sebaliknya,
`position-assignments.manage` tidak dapat mengubah struktur unit, kode jabatan,
atau Position Level.

UI operasional tersedia pada:

```text
GET /back-office/organization/structure
GET /back-office/organization/assignments
```

Seluruh mutasi memerlukan MFA dan recent password confirmation. Capability yang
dibagikan ke Vue hanya untuk presentation; Policy, middleware, Form Request, dan
Action tetap menjadi security boundary server-side.

## Penambahan Audit Perubahan Privilege

| Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `privilege-audits.view` | Membaca jejak provisioning akun internal serta perubahan role dan permission melalui console read-only. |

Permission ini terpisah dari `authorization.view`. Custom role auditor dapat
menerimanya secara eksplisit tanpa memperoleh capability untuk melihat atau
mengubah konfigurasi RBAC.

## Penambahan M3.1 Review Submission

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `intake.view` | Permission katalog untuk membaca antrean dan detail intake. |
| `super-admin` | `intake.screen` | Permission katalog untuk meminta koreksi publik atau mengajukan submission kepada Kepala Bagian Umum. |

Permission tersebut dapat diberikan kepada custom role operasional. Permission
saja tidak memberikan visibility surat: account tetap wajib `INTERNAL`, aktif,
terverifikasi, dan memiliki tepat satu Position Assignment aktif pada level
`GENERAL_AFFAIRS` ketika menjalankan screening. `intake.screen` tidak memberikan
hak menolak atau meregistrasikan surat. Capability keputusan Kepala Bagian Umum
ditambahkan pada slice registrasi berikutnya.

## Penambahan M3.2 Keputusan Kepala Bagian Umum

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `intake.decide` | Katalog capability untuk mengembalikan hasil screening kepada petugas, menolak pengajuan, atau meregistrasikan surat masuk resmi. |

Permission dapat diberikan kepada custom role operasional, tetapi tidak cukup
untuk membuka meja keputusan. Actor tetap wajib memakai account `INTERNAL` yang
aktif dan terverifikasi serta mempunyai tepat satu Position Assignment aktif
pada level `SECTION_HEAD` di Organizational Unit berkode `BAGIAN_UMUM`.

`intake.decide` tidak memberikan hak screening awal. Sebaliknya,
`intake.screen` tidak memberikan hak menolak atau meregistrasikan surat.
`super-admin` yang tidak menduduki Position tersebut tetap tidak dapat membaca
atau memutuskan submission pada meja Kepala Bagian Umum.

## Penambahan M3.5 Aktivitas Surat

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `letter-activities.view` | Membuka console read-only aktivitas intake dan registrasi dalam bentuk ringkasan tersanitasi. |

Permission ini dapat diberikan kepada custom role. Permission tidak otomatis
memberikan akses detail bisnis. Detail hanya diberikan jika account juga
memiliki Position Assignment aktif sebagai Wali Kota/Sekda pada level
`EXECUTIVE_ENTRY`, atau sebagai Kepala Bagian Umum pada level `SECTION_HEAD` di
unit `BAGIAN_UMUM`.

Super-admin tanpa Position bisnis tersebut tetap tidak menerima identitas
surat, pengirim, isi perubahan, dokumen, identitas pelaksana, atau jejak teknis.
Frontend capability hanya mengatur visibilitas menu; Policy, authorized query,
Position resolver, dan presenter allowlist menjadi security boundary.

## Penambahan M4.5 Histori Versi Dokumen

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `document-versions.view` | Katalog capability untuk membuka arsip dan histori versi dokumen resmi. |
| `super-admin` | `document-versions.create` | Katalog capability untuk membuat versi koreksi dokumen resmi sebelum surat diteruskan. |

Permission di atas dapat diberikan kepada custom role operasional, tetapi tidak
pernah menjadi bypass akses surat. `document-versions.view` tetap memerlukan
account `INTERNAL` aktif dan terverifikasi serta salah satu Position Assignment
aktif berikut:

```text
GENERAL_AFFAIRS + unit BAGIAN_UMUM
SECTION_HEAD + unit BAGIAN_UMUM
EXECUTIVE_ENTRY
```

`document-versions.create` hanya dapat dijalankan oleh Kepala Bagian Umum
(`SECTION_HEAD` pada unit `BAGIAN_UMUM`) dan hanya saat surat masih berstatus
`REGISTERED`. Asisten, Kepala Bagian lain, dan super-admin tanpa Position bisnis
tidak memperoleh visibility. Capability Inertia `can_view_document_versions`
hanya bernilai benar jika permission dan konteks Position sama-sama terpenuhi.

Setelah deployment M4.5, jalankan:

```text
php artisan authorization:sync
```

Kemudian berikan kedua permission baru kepada custom role operasional yang
sesuai melalui UI RBAC. Exact-sync `super-admin` memasukkan kedua permission ini,
tetapi tetap tidak memberikan global business visibility tanpa Position.

## Penambahan M5 Routing Awal dan Inbox Pimpinan

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `letter-routing.view` | Katalog capability untuk membaca antrean surat resmi yang menunggu atau telah memperoleh routing awal. |
| `super-admin` | `letter-routing.create` | Katalog capability untuk mengarahkan satu surat `REGISTERED` kepada tepat satu Wali Kota atau Sekda. |
| `super-admin` | `executive-inbox.view` | Katalog capability untuk membaca inbox surat pada Position eksekutif aktif pengguna. |

Permission tidak menjadi bypass Position maupun resource. Visibility antrean
routing memerlukan account `INTERNAL` aktif dan terverifikasi dengan Position
Assignment aktif sebagai staf `GENERAL_AFFAIRS` atau `SECTION_HEAD` pada unit
`BAGIAN_UMUM`. Pembuatan routing hanya dapat dilakukan oleh `SECTION_HEAD` pada
unit tersebut, hanya terhadap surat `REGISTERED`, dan tujuan wajib satu Position
aktif pada level `EXECUTIVE_ENTRY` yang mempunyai tepat satu pejabat internal
aktif dan terverifikasi.

Inbox pimpinan hanya menampilkan route `PENDING` dengan
`recipient_position_id` yang sama dengan Position Assignment aktif pengguna
pada level `EXECUTIVE_ENTRY`. Wali Kota tidak dapat membaca route milik Sekda,
dan sebaliknya. Asisten, Kepala Bagian lain, serta super-admin tanpa Position
bisnis menerima `404`, sekalipun permission katalog dimiliki.

Capability Inertia berikut hanya bernilai benar jika permission dan Position
sama-sama terpenuhi:

```text
can_view_letter_routing
can_create_letter_routing
can_view_executive_inbox
```

Setelah deployment M5, jalankan `php artisan authorization:sync`, lalu berikan
permission yang sesuai kepada custom role operasional melalui UI RBAC.

## Provisioning dan Sinkronisasi M2.4–M2.5

Alur administrative console:

```text
php artisan internal:user
php artisan authorization:sync
php artisan organization:sync-levels
php artisan authorization:super-admin {email?}
```

`internal:user` hanya membuat account `INTERNAL` yang aktif dan terverifikasi.
Command tersebut tidak memberikan Role, Permission langsung, atau Position.

`authorization:sync` menyinkronkan permission Role resmi secara exact. Role dan
Permission di luar katalog tidak dihapus otomatis, tetapi dilaporkan sebagai
catalog drift.

`organization:sync-levels` melakukan exact-sync hanya terhadap empat Position
Level workflow terlindungi: `GENERAL_AFFAIRS`, `EXECUTIVE_ENTRY`, `ASSISTANT`, dan
`SECTION_HEAD`. Level asing dipertahankan dan dilaporkan sebagai drift. Unit,
Position konkret, dan Position Assignment tidak dibuat atau diubah oleh command
ini.

`authorization:super-admin` hanya menerima account internal aktif dan
terverifikasi. Role dapat diberikan sebelum MFA dikonfigurasi, tetapi seluruh
akses administratif internal account tersebut tetap diblokir sampai MFA aktif
dan terkonfirmasi.

Seluruh perubahan account, Role, dan Permission melalui alur ini dicatat pada
audit append-only. UI administrasi privilege belum termasuk tahap ini dan kelak
wajib menggunakan Action teraudit yang sama.

Command mutasi bawaan package seperti `permission:create-role`,
`permission:create-permission`, dan `permission:assign-role` bukan administrative
flow yang didukung aplikasi karena tidak membawa audit context. Akses shell dan
database tetap harus dibatasi sebagai infrastructure security boundary.
