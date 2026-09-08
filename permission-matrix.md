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

## Role Operasional Baku

Role operasional berikut dikelola sebagai katalog immutable. Permission-nya
disinkronkan secara exact melalui `authorization:sync`, tetapi selain
`super-admin` role tetap dapat ditetapkan kepada akun internal melalui UI RBAC.

| Role | Permission |
| --- | --- |
| `petugas-surat` | `intake.view`, `intake.screen`, `intake.create-manual`, `incoming-register.view`, `letter-activities.view`, `document-versions.view`, `letter-routing.view`, `outgoing-register.view`, `outgoing-letters.number`, `outgoing-letters.deliver` |
| `kabag-umum` | `intake.view`, `intake.decide`, `incoming-register.view`, `letter-activities.view`, `document-versions.view`, `document-versions.create`, `letter-routing.view`, `letter-routing.create`, `dispositions.view`, `dispositions.process`, `reports.view`, `reports.export`, `disposition-instructions.view`, `letter-responses.view`, `letter-responses.contribute`, `outgoing-register.view`, `outgoing-letters.verify` |
| `pimpinan-eksekutif` | `executive-inbox.view`, `dispositions.create`, `document-versions.view`, `letter-activities.view`, `reports.view`, `reports.export`, `disposition-instructions.view`, `letter-responses.view`, `letter-responses.contribute`, `letter-responses.review`, `letter-responses.authorize`, `outgoing-register.view` |
| `asisten` | `dispositions.view`, `dispositions.create`, `reports.view`, `reports.export`, `disposition-instructions.view`, `letter-responses.view`, `letter-responses.contribute`, `letter-responses.review`, `outgoing-register.view` |
| `kepala-bagian` | `dispositions.view`, `dispositions.process`, `reports.view`, `reports.export`, `disposition-instructions.view`, `letter-responses.view`, `letter-responses.contribute`, `outgoing-register.view` |

Role adalah capability bundle, bukan identitas jabatan. Wali Kota dan Sekda
berbagi role `pimpinan-eksekutif`, tetapi resource yang dapat diakses tetap
dibatasi oleh Position dan Position Assignment masing-masing. Prinsip yang sama
berlaku untuk tiga Asisten dan seluruh Kepala Bagian.

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

## Penambahan M8.1 Intake Manual dan Buku Agenda Masuk

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin`, `petugas-surat` | `intake.create-manual` | Mencatat surat fisik lengkap dengan scan privat dan screening, serta memperbaikinya setelah dikembalikan Kabag Umum. |
| `super-admin`, `petugas-surat`, `kabag-umum` | `incoming-register.view` | Membaca Buku Agenda Surat Masuk resmi yang menggabungkan sumber online dan manual. |

`intake.create-manual` tetap membutuhkan account internal aktif dan terverifikasi
serta Position Assignment aktif level `GENERAL_AFFAIRS` pada unit
`BAGIAN_UMUM`. `incoming-register.view` membutuhkan Position aktif pada unit
yang sama dengan level `GENERAL_AFFAIRS` atau `SECTION_HEAD`. Permission tidak
menjadi bypass: super-admin tanpa Position bisnis atau pejabat unit lain tetap
menerima `404`.

Capability Inertia `can_create_manual_intake` dan
`can_view_incoming_register` hanya mengendalikan presentasi menu. Policy dan
authorized query tetap menjadi boundary server-side.

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

## Penambahan M6.1 Disposisi Berbasis Position

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `dispositions.view` | Katalog capability untuk membaca recipient disposisi milik Position Asisten atau Kepala Bagian aktif pengguna. |
| `super-admin` | `dispositions.create` | Katalog capability untuk membuat disposisi sesuai hierarchy eksekutif ke satu/lebih Asisten atau Asisten ke satu/lebih Kepala Bagian. |
| `super-admin` | `dispositions.process` | Katalog capability untuk memulai, mencatat tindak lanjut, dan menyelesaikan cabang Kepala Bagian milik Position aktif pengguna. |
| `super-admin` | `disposition-instructions.view` | Melihat katalog label instruksi disposisi. |
| `super-admin` | `disposition-instructions.manage` | Membuat, memperbarui, mengaktifkan, dan menonaktifkan label instruksi dengan MFA serta konfirmasi password terbaru. |

`dispositions.create` selalu mengikuti hierarchy: pemegang Position
`EXECUTIVE_ENTRY` yang menjadi penerima route hanya dapat memilih satu sampai
tiga Asisten,
sedangkan Asisten hanya dapat memilih satu atau lebih Position
`SECTION_HEAD` yang eligible. Position actor atau Position lain yang dipegang
user actor tidak boleh menjadi tujuan.

`dispositions.view` hanya membuka recipient yang `recipient_position_id`-nya
sama dengan Position Assignment Asisten atau Kepala Bagian aktif pengguna.
`dispositions.process` juga membutuhkan `dispositions.view` dan hanya efektif
bagi pemegang Position `SECTION_HEAD` yang sama dengan recipient. Asisten,
Wali Kota, Sekda, Kepala Bagian lain, dan super-admin tanpa Position bisnis
tidak dapat memproses cabang meskipun mengetahui ID resource. Permission tidak
menjadi bypass Position: permission kurang menghasilkan `403`, sedangkan
Position/resource yang tidak sesuai menghasilkan `404`.

Acceptance M7.1 mempertahankan kontrak tersebut tanpa permission baru.
Penyelesaian dari `PENDING` maupun `IN_PROGRESS` menggunakan pemeriksaan Policy
yang sama. Assignment lama yang sudah berakhir tidak memberi akses, sementara
pemegang baru pada Position recipient yang sama dapat menyelesaikan dan dicatat
sebagai actor historis.

Capability Inertia baru:

```text
can_view_dispositions
can_create_dispositions
can_process_dispositions
can_view_disposition_instructions
can_manage_disposition_instructions
```

Setelah deployment M6.3, jalankan migration dan
`php artisan authorization:sync`, lalu berikan pasangan permission yang sesuai
kepada custom role eksekutif, Asisten, Kepala Bagian, dan pengelola workflow
melalui UI RBAC. Role Kepala Bagian yang menangani recipient membutuhkan
`dispositions.view` dan `dispositions.process`; role eksekutif dan Asisten tidak
memerlukan `dispositions.process`.

## Penambahan M7.3 Laporan Periodik

| Protected Role | Permission | Tujuan |
| --- | --- | --- |
| `super-admin` | `reports.view` | Katalog capability untuk membuka agregat dan drilldown laporan sesuai Position bisnis aktif. |
| `super-admin` | `reports.export` | Katalog capability untuk mengekspor ringkasan dan daftar surat terotorisasi dalam CSV. |

Kedua permission disinkronkan secara exact kepada `super-admin`, `kabag-umum`,
`pimpinan-eksekutif`, `asisten`, dan `kepala-bagian`. `petugas-surat` tidak
menerimanya. Permission tidak menjadi global bypass; tanpa Position pada level
`EXECUTIVE_ENTRY`, `ASSISTANT`, atau `SECTION_HEAD` yang sah, resource laporan
ditolak sebagai `404`.

| Position aktif | Aggregate | Daftar/detail |
| --- | --- | --- |
| Wali Kota/Sekda | Seluruh kota | Seluruh proses dan seluruh cabang |
| Kepala Bagian Umum | Operasional global | Cabang Bagian Umum miliknya |
| Asisten | Subtree Asisten | Recipient Asisten dan seluruh child branch langsung |
| Kepala Bagian lain | Cabang sendiri | Cabang Position sendiri |

Assignment aktif ganda menghasilkan union scope. Capability Inertia
`can_view_reports` dan `can_export_reports` hanya bernilai benar jika permission
dan scope Position sama-sama valid. Ekspor tetap memakai authorized query yang
sama seperti UI dan dilindungi limiter.

Setelah deployment M7.3, jalankan `php artisan authorization:sync`, kemudian
berikan permission baru kepada custom role struktural yang memang memerlukannya.

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

Bootstrap lokal setelah database fresh menggunakan urutan:

```text
php artisan migrate:fresh
php artisan authorization:sync
php artisan internal:user
php artisan authorization:super-admin {email}
php artisan db:seed
```

`db:seed` membuat struktur Setda inti, role operasional, 14 akun internal
generik, dan Position Assignment aktif. Tujuh label instruksi baku sudah dibuat
oleh migration disposisi. Seeder tidak membuat akun
super-admin, public user, submission, surat, dokumen, routing, atau disposisi.
Seluruh akun operasional seed menggunakan password lokal `password`; seeder
menolak berjalan pada environment production.

Command mutasi bawaan package seperti `permission:create-role`,
`permission:create-permission`, dan `permission:assign-role` bukan administrative
flow yang didukung aplikasi karena tidak membawa audit context. Akses shell dan
database tetap harus dibatasi sebagai infrastructure security boundary.

## Penambahan M8.2 Dossier Balasan

| Role resmi | view | contribute | review | authorize |
| --- | --- | --- | --- | --- |
| `petugas-surat` | - | - | - | - |
| `kabag-umum` | Ya | Ya | - | - |
| `kepala-bagian` | Ya | Ya | - | - |
| `asisten` | Ya | Ya | Ya | - |
| `pimpinan-eksekutif` | Ya | Ya | Ya | Ya |
| `super-admin` | katalog exact-sync | katalog exact-sync | katalog exact-sync | katalog exact-sync |

Permission resmi adalah `letter-responses.view`,
`letter-responses.contribute`, `letter-responses.review`, dan
`letter-responses.authorize`. Semuanya tetap membutuhkan account internal aktif,
email terverifikasi, serta Position Assignment yang sesuai resource. Super-admin
tanpa Position bisnis menerima `404`.

Kepala Bagian dibatasi pada bahan Position-nya, Asisten pada subtree langsung,
dan eksekutif pada surat yang routing awalnya ditujukan kepada Position-nya.
Capability Inertia mengikuti pasangan permission dan Position:
`can_view_letter_responses`, `can_contribute_letter_responses`,
`can_review_letter_responses`, dan `can_authorize_letter_responses`.

## Penambahan M8.3 Register dan Penerbitan Surat Keluar

| Role resmi | Lihat register | Beri nomor | Verifikasi | Kirim |
| --- | --- | --- | --- | --- |
| `petugas-surat` | Ya, global administratif | Ya | - | Ya |
| `kabag-umum` | Ya, global administratif | - | Ya | - |
| `kepala-bagian` | Ya, hanya mandat dari kontribusinya | - | - | - |
| `asisten` | Ya, hanya mandat dari subtree/proposalnya | - | - | - |
| `pimpinan-eksekutif` | Ya, seluruh mandat surat yang diterimanya | - | - | - |
| `super-admin` | katalog exact-sync | katalog exact-sync | katalog exact-sync | katalog exact-sync |

Permission resminya adalah `outgoing-register.view`,
`outgoing-letters.number`, `outgoing-letters.verify`, dan
`outgoing-letters.deliver`. Permission tidak menggantikan Position: Petugas dan
Kabag Umum harus berada pada unit `BAGIAN_UMUM`, sedangkan pejabat struktural
dibatasi oleh graph kontribusi dan routing surat. Super-admin tanpa Position
bisnis tetap menerima `404`.

Capability Inertia `can_view_outgoing_register`,
`can_number_outgoing_letters`, `can_verify_outgoing_letters`, dan
`can_deliver_outgoing_letters` hanya mengendalikan presentasi antarmuka.
Policy dan authorized query tetap menjadi batas akses produksi.
