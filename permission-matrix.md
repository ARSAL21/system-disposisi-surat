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

## Provisioning dan Sinkronisasi M2.4–M2.5

Alur administrative console:

```text
php artisan internal:user
php artisan authorization:sync
php artisan authorization:super-admin {email?}
```

`internal:user` hanya membuat account `INTERNAL` yang aktif dan terverifikasi.
Command tersebut tidak memberikan Role, Permission langsung, atau Position.

`authorization:sync` menyinkronkan permission Role resmi secara exact. Role dan
Permission di luar katalog tidak dihapus otomatis, tetapi dilaporkan sebagai
catalog drift.

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
