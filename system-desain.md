# System Design — Sistem Disposisi Surat

## 1. Tujuan Sistem

Sistem Disposisi Surat adalah aplikasi Pemerintah Kota untuk menerima, meregistrasi, dan memproses **surat masuk** sampai seluruh tindak lanjut disposisi selesai.

Sistem mendukung dua kanal penerimaan surat:

```text
ONLINE
Public User
    ↓
Submit surat melalui sistem

MANUAL
Pengirim datang langsung
    ↓
Bagian Umum menerima surat fisik
```

Kedua kanal tersebut harus masuk ke proses administrasi Bagian Umum sebelum menjadi surat masuk resmi.

Sistem harus menjamin:

* surat dapat diterima secara online maupun manual;
* seluruh surat resmi tetap melalui Bagian Umum;
* alur disposisi mengikuti hierarki;
* satu surat dapat memiliki beberapa cabang disposisi;
* akses surat hanya diberikan kepada pihak yang berwenang;
* histori tindakan tidak hilang ketika pejabat berganti;
* dokumen dapat diverifikasi integritasnya;
* seluruh tindakan penting dapat diaudit;
* data dapat digunakan untuk laporan periodik.

---

# 2. Scope MVP

Alur umum MVP:

```text
              ┌───────────────────────────┐
              │       ONLINE INTAKE       │
              │                           │
              │ Public User               │
              │ Register + Verify Email   │
              │       ↓                   │
              │ Submit Surat              │
              └─────────────┬─────────────┘
                            │
                            ▼
                    Submission / Intake
                            ▲
                            │
              ┌─────────────┴─────────────┐
              │       MANUAL INTAKE       │
              │                           │
              │ Pengirim datang langsung │
              │       ↓                   │
              │ Bagian Umum input surat  │
              └───────────────────────────┘

                            ↓
                  Bagian Umum / Tata Usaha
                            ↓
                    Registrasi Surat
                            ↓
                      Incoming Letter
                            ↓
                 Wali Kota ATAU Sekda
                            ↓
                   Asisten I / II / III
                            ↓
             Satu atau lebih Kepala Bagian
                            ↓
                      Tindak lanjut
                            ↓
                         Selesai
```

Aturan utama:

* Bagian Umum tetap menjadi gerbang administratif seluruh surat.
* Public User hanya dapat mengirim surat melalui kanal online.
* Pengirim manual tidak wajib memiliki account.
* Submission belum otomatis menjadi `IncomingLetter`.
* Hanya Kepala Bagian Umum yang dapat mengesahkan registrasi surat masuk resmi.
* Surat resmi diarahkan ke Wali Kota atau Sekda.
* Wali Kota dan Sekda berada pada level penerimaan awal yang sama.
* Disposisi wajib mengikuti hierarchy.
* Kepala Bagian merupakan terminal formal workflow MVP.
* Staff belum menjadi bagian workflow formal MVP.

---

# 3. Prinsip Hybrid Intake

Sistem tidak memiliki dua workflow surat yang berbeda.

Gunakan satu model konseptual:

```text
Online Submission ─────┐
                       │
                       ▼
                Submission / Intake
                       ▲
                       │
Manual Submission ─────┘
                       ↓
               Bagian Umum Review
                       ↓
              Incoming Letter
```

Perbedaan hanya terdapat pada **sumber intake**.

### Online

Submission dibuat oleh Public User yang telah login dan melakukan email verification.

### Manual

Submission dicatat oleh Bagian Umum berdasarkan surat fisik yang diserahkan langsung.

Setelah itu, proses bisnis keduanya harus menyatu.

Jangan membuat:

```text
OnlineLetterWorkflow
ManualLetterWorkflow
```

sebagai dua domain terpisah.

---

# 4. Submission Bukan Incoming Letter

`Submission` dan `IncomingLetter` merupakan dua konsep berbeda.

### Submission

Artinya:

> sesuatu telah dikirim atau diserahkan kepada kantor dan menunggu proses administratif Bagian Umum.

Submission dapat berasal dari:

```text
ONLINE
MANUAL
```

### IncomingLetter

Artinya:

> surat telah diterima dan diregistrasi secara resmi oleh Bagian Umum sebagai surat masuk Pemerintah Kota.

Hubungannya:

```text
Submission
    ↓
Bagian Umum melakukan registrasi
    ↓
IncomingLetter
```

Public User tidak dapat membuat `IncomingLetter` secara langsung.

---

# 5. Online Submission Boundary

Public User menggunakan authentication Laravel yang sama dengan internal user.

Namun Public User tidak mempunyai:

* Position;
* Position Assignment;
* permission internal;
* akses workflow disposisi.

Alur:

```text
Register
    ↓
Email Verification
    ↓
Login
    ↓
Create Submission
    ↓
Upload PDF
    ↓
Submit
```

Email verification membuktikan kendali terhadap alamat email, bukan identitas manusia secara absolut.

Karena registration dan submission merupakan public surface, sistem tetap harus menerapkan:

* rate limiting;
* validation;
* upload security;
* anti-abuse controls jika dibutuhkan.

Public User hanya dapat mengakses submission miliknya sendiri kecuali terdapat rule khusus.

---

# 6. Manual Submission Boundary

Pengirim yang datang langsung tidak diwajibkan membuat account.

Alur:

```text
Pengirim datang
      ↓
Menyerahkan surat fisik
      ↓
Bagian Umum
      ↓
Input metadata
      ↓
Upload hasil scan PDF
      ↓
Create Manual Submission
```

Karena Bagian Umum sudah melakukan pemeriksaan langsung, manual submission dapat dilanjutkan ke registrasi pada sesi kerja yang sama.

Namun secara domain:

```text
Manual Submission
≠
Incoming Letter
```

tetap dipertahankan.

Ini menjaga seluruh surat mempunyai intake history yang konsisten.

---

# 7. High-Level Architecture

```text
┌───────────────────────────────┐
│            Vue                │
│        Inertia Pages          │
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│         Laravel HTTP          │
│ Routes / Middleware / Request │
│ Controllers / Policies        │
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│      Application Layer        │
│ Actions / Services            │
│ Intake + Workflow Coordination│
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│        Domain Rules           │
│ Submission / Position         │
│ Authorization / Disposition   │
└───────────────┬───────────────┘
                │
       ┌────────┴────────┐
       ▼                 ▼
┌──────────────┐  ┌──────────────┐
│ Relational DB│  │Private Storage│
└──────────────┘  └──────────────┘
```

Laravel menjadi pusat:

* authentication;
* email verification;
* authorization;
* validation;
* intake workflow;
* disposition workflow;
* persistence;
* audit.

---

# 8. Domain Utama

Domain inti:

```text
User
Role
Permission

Position
PositionAssignment

LetterSubmission
IncomingLetter
LetterDocument

Disposition
DispositionBranch

DispositionInstruction

AuditLog
```

## User

Merepresentasikan account yang dapat login.

User dapat berupa:

```text
Public User
Internal User
```

Jenis akses tidak boleh hanya ditentukan dari field client.

---

## Public User

Public User adalah pengirim eksternal melalui kanal online.

Public User:

* dapat register;
* wajib melakukan email verification sebelum submit;
* dapat membuat submission;
* dapat melihat submission miliknya;
* tidak memiliki Position Assignment internal.

---

## Internal User

Internal User adalah account pegawai/pejabat.

Internal capability ditentukan melalui:

```text
Role / Permission
+
Position Assignment
```

---

## Role dan Permission

Mengatur capability aplikasi.

---

## Position

Merepresentasikan jabatan organisasi.

Contoh:

```text
Wali Kota
Sekda
Asisten I
Asisten II
Asisten III
Kepala Bagian Hukum
Kepala Bagian Aset
...
```

---

## PositionAssignment

Menghubungkan Internal User dengan Position dalam periode tertentu.

Public User tidak mempunyai Position Assignment.

## Administrasi Struktur Organisasi

Empat `PositionLevel` workflow merupakan katalog terlindungi:

```text
GENERAL_AFFAIRS
EXECUTIVE_ENTRY
ASSISTANT
SECTION_HEAD
```

Katalog tersebut disinkronkan secara idempotent melalui
`organization:sync-levels` dan tidak dapat diedit lewat web. Level asing tidak
dihapus otomatis, tetapi diperlakukan sebagai drift dan tidak dapat dipakai
untuk membuat Position baru.

`OrganizationalUnit` dan `Position` konkret dikelola pada back-office. Keduanya
menggunakan lifecycle aktif/nonaktif, bukan hard delete. Kode Position dan
Position Level-nya immutable setelah pembuatan; perubahan makna jabatan dilakukan
dengan membuat Position baru dan menonaktifkan Position lama setelah tidak lagi
memiliki assignment aktif.

Pemisahan capability:

```text
organization.view
organization.manage
position-assignments.manage
```

`organization.manage` mengelola struktur, sedangkan
`position-assignments.manage` mengelola siapa yang menduduki Position. Semua
mutasi memerlukan account internal aktif dan terverifikasi, MFA, recent password,
Policy, transaction, row locking, dan audit atomik.

Position Assignment menggunakan waktu efektif milik server. UI tidak boleh
mengirim `started_at` atau `ended_at`. Pergantian pemegang mengakhiri assignment
aktif dan membuat assignment baru pada transaction yang sama. Histori tidak
dapat diubah atau dihapus.

---

## LetterSubmission

Merepresentasikan intake surat sebelum menjadi surat masuk resmi.

Submission harus mengetahui source:

```text
ONLINE
MANUAL
```

Online submission mempunyai hubungan dengan Public User.

Manual submission dicatat oleh Internal User Bagian Umum.

---

## IncomingLetter

Merepresentasikan surat yang telah diregistrasi secara resmi oleh Bagian Umum.

Tidak setiap submission otomatis menjadi IncomingLetter.

---

# 9. Authentication Architecture

Gunakan Laravel built-in session authentication melalui Inertia.

Satu authentication system digunakan untuk public dan internal user.

```text
                    Laravel Auth
                         │
               ┌─────────┴─────────┐
               ▼                   ▼
          Public User         Internal User
               │                   │
               ▼                   ├── Role / Permission
      Letter Submission            │
                                   └── PositionAssignment
                                               ↓
                                            Position
```

Public registration diaktifkan.

Email verification wajib sebelum Public User dapat membuat submission.

Internal privilege tidak pernah dapat dipilih melalui registration form.

Registration publik tidak boleh menerima:

```text
role
permission
position
is_admin
```

sebagai trusted input.

---

# 10. Internal Account Provisioning

Internal account tidak dibuat melalui self-service privilege selection.

Internal privilege diberikan melalui proses administratif.

Contoh:

```text
System Administrator
        ↓
Create / activate internal account
        ↓
Assign Role
        ↓
Assign Position
```

Seseorang yang register sebagai Public User tidak otomatis dapat memperoleh privilege internal.

Perubahan privilege harus eksplisit, authorized, dan diaudit.

---

# 11. Role Tidak Menentukan Hierarki

RBAC dan hierarchy organisasi merupakan konsep berbeda.

```text
Role / Permission
        ↓
"Apa yang boleh dilakukan?"

Position
        ↓
"Dalam kapasitas apa user bertindak?"

Workflow
        ↓
"Kepada jabatan mana tindakan boleh diteruskan?"

Authorization
        ↓
"Bolehkah tindakan dilakukan terhadap resource ini?"
```

Public User berada di luar hierarchy internal disposisi.

---

# 12. Position-Based Assignment

Disposisi diarahkan kepada Position, bukan user tertentu.

```text
Disposition
    ↓
Kepala Bagian Hukum
```

Actor tindakan tetap dicatat sebagai:

```text
User
+
PositionAssignment
```

Jika pejabat berganti, pekerjaan aktif tetap melekat pada Position.

---

# 13. Intake Workflow

## Online Intake

```text
Public User
    ↓
Create Submission
    ↓
Upload PDF
    ↓
Submit
    ↓
Bagian Umum Queue
```

Submission online melewati dua lapis tanggung jawab:

```text
Staf administrasi Bagian Umum
    ↓
periksa identitas, metadata, PDF, dan scope surat
    ↓
Kepala Bagian Umum
    ↓
keputusan administratif resmi
```

Staf dapat meminta koreksi kepada pengirim atau menyatakan submission siap
ditinjau. Staf tidak dapat menolak atau meregistrasikan surat. Kepala Bagian
Umum dapat mengembalikan hasil screening kepada staf, menolak secara
administratif, atau mengesahkan registrasi.

Pemisahan jabatan operasional pada implementasi:

```text
Staf Administrasi Surat
→ Position Level GENERAL_AFFAIRS

Kepala Bagian Umum
→ Position Level SECTION_HEAD
→ Organizational Unit berkode BAGIAN_UMUM
```

Permission tidak menggantikan kedua batas Position Assignment tersebut.

## Manual Intake

```text
Surat fisik
    ↓
Bagian Umum
    ↓
Create Manual Submission
    ↓
Upload scan PDF
    ↓
Register
```

Manual intake boleh lebih cepat secara operasional, tetapi tetap melewati domain submission.

---

# 14. Registrasi Surat Masuk

Hanya Kepala Bagian Umum yang berwenang mengesahkan perubahan submission
menjadi `IncomingLetter`. Staf administrasi menyiapkan hasil screening, tetapi
tidak mengambil keputusan resmi.

Alur:

```text
Submission
      ↓
Staf memastikan kelengkapan administratif
      ↓
Kepala Bagian Umum mengesahkan
      ↓
Register Incoming Letter
      ↓
Nomor agenda / metadata resmi
      ↓
IncomingLetter
```

Registrasi merupakan boundary penting.

Sebelum boundary:

```text
Submission
```

Sesudah boundary:

```text
Official Incoming Letter
```

Tindakan ini harus menghasilkan audit trail.

---

# 15. Document Handling

Online submission:

```text
Public User Upload
      ↓
Validate
      ↓
Private Temporary/Submission Storage
```

Manual submission:

```text
Bagian Umum Scan
      ↓
Validate
      ↓
Private Submission Storage
```

Setelah registrasi:

```text
Submission Document
      ↓
Associated with registered IncomingLetter
      ↓
SHA-256 integrity recorded
```

Detail persistence ditentukan pada database schema.

File tidak pernah menjadi public asset.

---

# 16. Disposition Model

Setelah menjadi IncomingLetter, surat masuk ke workflow internal.

```text
IncomingLetter
      ↓
Disposition
      ↓
Disposition Branch
```

Surat tidak memiliki satu `current_owner`.

Setiap recipient menghasilkan branch independen.

---

# 17. Disposition Tree

Workflow:

```text
Incoming Letter
       ↓
Wali Kota / Sekda
       ↓
Asisten
       ↓
┌──────┴──────┐
▼             ▼
Kabag A     Kabag B
```

Sistem harus mengetahui:

* parent branch;
* actor;
* active Position;
* recipient Position;
* timestamp;
* instruction;
* state.

---

# 18. Workflow Enforcement

Workflow internal MVP:

```text
GENERAL_AFFAIRS
      ↓
MAYOR / SECRETARY
      ↓
ASSISTANT
      ↓
SECTION_HEAD
```

Submission berada **sebelum** workflow disposisi ini.

Public User tidak pernah menjadi bagian disposition hierarchy.

Backend wajib menolak hierarchy bypass.

---

# 19. Multiple Recipients

Asisten dapat meneruskan kepada satu atau lebih Kepala Bagian.

```text
Asisten I
   ├── Kabag Pemerintahan
   ├── Kabag Hukum
   └── Kabag Aset
```

Setiap recipient memiliki branch lifecycle sendiri.

---

# 20. State Management

Terdapat tiga state domain berbeda:

```text
Submission State
Incoming Letter State
Disposition Branch State
```

Ketiganya tidak boleh dicampur.

Submission state menjawab:

> bagaimana kondisi intake sebelum registrasi resmi?

Letter state menjawab:

> bagaimana kondisi surat resmi secara keseluruhan?

Branch state menjawab:

> bagaimana kondisi pekerjaan recipient tertentu?

Exact state dan transition didefinisikan di `workflow-spec.md`.

---

# 21. Authorization Model

Authorization terdiri atas beberapa konteks.

## Public Submission

Public User hanya dapat mengakses submission yang sah miliknya.

## Bagian Umum

Staf dan Kepala Bagian Umum mengakses meja kerja yang terpisah. Staf memerlukan
permission intake yang sesuai dan Position Assignment aktif pada level
`GENERAL_AFFAIRS`. Kepala Bagian Umum memerlukan `intake.decide` serta Position
Assignment aktif pada level `SECTION_HEAD` di unit berkode `BAGIAN_UMUM`.
Staf melakukan screening teknis, sedangkan Kepala Bagian Umum mengambil
keputusan administratif resmi.

## Internal Letter

Authorization menggunakan:

```text
RBAC
+
Position aktif
+
Workflow
+
Disposition participation
+
Policy
```

Wali Kota dan Sekda dapat mempunyai global business visibility.

System Administrator tidak otomatis memiliki global visibility.

---

# 22. Collection Authorization

Collection harus dibatasi sejak query database.

Contoh public:

```text
"My Submissions"
→ hanya submission milik authenticated Public User
```

Contoh internal:

```text
"Inbox Kabag"
→ hanya surat/disposition yang berkaitan dengan Position aktifnya
```

Jangan mengambil semua data lalu menyembunyikannya di Vue.

---

# 23. Instruksi Disposisi

Instruction label configurable.

Contoh:

```text
Untuk diketahui
Untuk ditindaklanjuti
Untuk dipelajari
Untuk dikoordinasikan
Untuk menghadiri
Untuk disiapkan jawabannya
Segera
```

Tidak menggunakan ENUM per label.

---

# 24. Document Storage dan Integrity

Semua dokumen berada di private storage (`storage_disk` allowlist: `submission-documents`, dan kelak `letter-documents`).

Akses dokumen selalu melalui server-side authorization (Policy & Position Assignment).

SHA-256 digunakan untuk document integrity, bukan confidentiality.

Dokumen asli tidak ditimpa tanpa histori.

## 24.1 File Access Policy Hardening (M4.3)

Akses berkas PDF privat (preview *inline* maupun download *attachment*) dikontrol secara terpusat oleh `PrivateDocumentResponse` dengan invariant keamanan:

### Matriks Otorisasi Akses Dokumen

| Boundary Pengguna | Kebutuhan Permission | Kebutuhan Position Assignment | Status Submission yang Diizinkan | Hasil Penolakan |
| :--- | :--- | :--- | :--- | :--- |
| **Public Applicant** | Kepemilikan akun publik terverifikasi (`submitted_by_user_id === actor.id`) | Tidak ada (dilarang) | Seluruh status (Draft, Submitted, Revision, Ready, Registered, Rejected) | **404 Not Found** (anti-enumeration) |
| **Intake Staff (Bagian Umum)** | `intake.view` | Aktif pada level `GENERAL_AFFAIRS` | Semua status kecuali `DRAFT` | Missing permission: **403 Forbidden**<br>Missing position / status draft: **404 Not Found** |
| **Kepala Bagian Umum** | `intake.decide` | Aktif pada level `SECTION_HEAD` di unit `BAGIAN_UMUM` | `READY_FOR_APPROVAL`, `INTERNAL_REVISION_REQUIRED`, `REGISTERED`, `REJECTED` | Missing permission: **403 Forbidden**<br>Missing position / status tidak sah: **404 Not Found** |
| **System Super-Admin** | Semua permission | Tanpa penugasan bisnis | Tidak ada bypass | **404 Not Found** |

### Invariant Keamanan Private Streaming

1. **Strict Path Sanitization**: Menolak null byte (`\0`), path traversal (`..`), absolute path marker, ekstensi selain `.pdf`, dan path yang tidak diawali direktori `{$submission->public_id}/`.
2. **Physical Existence & Readability Check**: Memverifikasi fisik file di disk privat sebelum memulai stream response.
3. **No Storage Leakage**: `storage_disk` dan `storage_path` tidak pernah diekspos dalam header HTTP, metadata response, atau pesan error.
4. **Uniform Security Headers**:
   - `Content-Type: application/pdf`
   - `X-Content-Type-Options: nosniff`
   - `Cache-Control: private, no-store, max-age=0`
   - `Content-Security-Policy: default-src 'none'; frame-ancestors 'self'; sandbox`
   - `X-Frame-Options: SAMEORIGIN`
   - `Referrer-Policy: no-referrer`
   - `Cross-Origin-Resource-Policy: same-origin`
   - `Content-Disposition`: `inline; filename="safe.pdf"` (preview) atau `attachment; filename="safe.pdf"` (download).
5. **Rate Limiting**: Rate limiter bersama `private-document-access` (60 req/menit per user, 120 req/menit per IP) aktif di seluruh endpoint dokumen.
6. **HTTP Error Contracts**:
   - `403 Forbidden`: Permission rute tidak dimiliki.
   - `404 Not Found`: Konteks jabatan tidak cocok, status tidak visible, atau non-owner.
   - `409 Conflict`: Kerusakan berkas, path/disk tidak valid, atau file hilang di private storage.
   - `429 Too Many Requests`: Melebihi kuota akses rate limit.

## 24.2 Hash Verification & Cryptographic Integrity (M4.4)

Integritas fisik seluruh berkas dokumen privat (`submission_documents` dan `letter_documents`) dijamin melalui verifikasi sidik jari kriptografi SHA-256 dan ukuran byte yang dikelola secara terpusat oleh `DocumentIntegrityVerifier`:

### Invariant Verifikasi Kriptografi

1. **Streaming Hash Calculation**:
   - Penghitungan hash SHA-256 dilakukan secara bertahap melalui stream chunking (`hash_init('sha256')`, `hash_update_stream()`, `hash_final()`) untuk mencegah konsumsi memori berlebih (*out of memory*) pada berkas PDF besar.
2. **Timing-Attack Safe Comparison**:
   - Perbandingan sidik jari SHA-256 selalu menggunakan `hash_equals()` terhadap string lowercase 64-karakter heksadesimal (`/^[a-f0-9]{64}$/`).
3. **Exact Byte Matching**:
   - Jumlah byte yang terbaca dari stream penyimpanan privat wajib persis sama dengan kolom `size_bytes` pada basis data. Ketidakcocokan byte (akibat pemotongan berkas atau penambahan *payload*) ditandai sebagai `SIZE_MISMATCH`.
4. **Fail-Secure Pre-Registration Gate**:
   - Pada saat proses registrasi surat masuk (`RegisterIncomingLetter`), integritas dokumen diverifikasi secara wajib di dalam *database transaction*.
   - Jika terdeteksi indikasi manipulasi berkas (*tampering*), kerusakan file (*corruption*), atau file tidak ditemukan di storage, operasi langsung dibatalkan secara atomik (`DocumentIntegrityConflict`) dan me-rollback seluruh mutasi basis data.
5. **On-Demand & Diagnostic Integrity Scanner (`documents:verify-integrity`)**:
   - Tersedia perintah artisan `php artisan documents:verify-integrity` dengan opsi `--submissions`, `--letters`, `--all`, dan `--fail-fast` untuk mengaudit kesehatan seluruh berkas fisik pada penyimpanan privat dan menghasilkan laporan diagnostik `DocumentIntegrityResult`.

## 24.3 Arsip dan Histori Versi Dokumen Resmi (M4.5)

Back-office menyediakan arsip dokumen resmi pada:

```text
GET /back-office/documents
GET /back-office/letters/{incomingLetter}/documents
```

Akses selalu membutuhkan permission `document-versions.view` dan Position
Assignment aktif yang memenuhi salah satu konteks bisnis berikut:

* staf `GENERAL_AFFAIRS` pada unit `BAGIAN_UMUM`;
* Kepala Bagian Umum (`SECTION_HEAD` pada unit `BAGIAN_UMUM`);
* Wali Kota atau Sekda pada level `EXECUTIVE_ENTRY`.

Permission tidak menjadi bypass terhadap Position. Asisten, Kepala Bagian lain,
dan super-admin teknis tanpa Position bisnis tersebut menerima `404` dan tidak
melihat menu arsip. Collection dibatasi melalui authorized query sejak database,
bukan difilter setelah row dimuat.

Versi terkini selalu diturunkan dari `MAX(letter_documents.version_number)`.
Tidak ada kolom atau flag `is_current` yang menjadi source of truth. Arsip dapat
dicari berdasarkan nomor agenda, perihal, instansi, dan nama berkas pada seluruh
versi. Filter tanggal `received_at` memakai zona waktu kantor, sedangkan timestamp
tetap disimpan dalam UTC.

Kepala Bagian Umum dengan permission `document-versions.create` dapat membuat
versi koreksi hanya ketika surat masih `REGISTERED`:

```text
POST /back-office/letters/{incomingLetter}/documents
```

Operasi menyimpan file baru pada disk privat `letter-documents`, mengunci surat,
versi terakhir, dan Position Assignment aktif, lalu membuat metadata versi serta
audit `DOCUMENT_VERSION_CREATED` dalam satu database transaction. SHA-256 yang
identik ditolak. File baru dihapus sebagai kompensasi jika transaction gagal.
Versi sebelumnya tidak diubah atau dihapus dan tidak tersedia endpoint
`PATCH`, `PUT`, atau `DELETE` untuk `letter_documents`.

Preview dan download setiap versi menggunakan scoped nested binding, storage
guard M4.3, header keamanan private, dan limiter `private-document-access`.
Upload koreksi dibatasi 10 request/jam per user dan 30 request/jam per IP.
Presenter response menggunakan allowlist dan tidak mengirim `storage_disk`,
`storage_path`, email, IP, metadata audit mentah, atau data autentikasi ke Vue.

---

# 25. Audit Architecture

Audit trail bersifat application-level append-only.

`audit_logs` hanya dapat dibuat dan dibaca melalui jalur aplikasi. Model audit
menolak update/delete termasuk operasi quiet, sedangkan Eloquent Builder menolak
mass mutation seperti update, delete, upsert, increment, dan truncate. Tidak ada
database trigger pada tahap ini. Raw SQL atau Query Builder langsung terhadap
`audit_logs` tidak boleh digunakan oleh application code; akses SQL penuh tetap
merupakan boundary operasional database, bukan authorization aplikasi.

Audit harus mencakup aktivitas penting pada kedua boundary.

## Kontrak Audit Terpusat (M4.2)

Setiap event audit didefinisikan secara deklaratif pada `AuditActionContractRegistry` dan divalidasi saat penulisan (*write-time guard*) oleh `AuditLogGuard`:

| `AuditAction` Enum | Domain | Allowed `subject_type` | `subject_id` | `MutationType` | `PositionAssignment` |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `INTERNAL_ACCOUNT_PROVISIONED` | Account | `user` | Wajib | Create | Optional |
| `ROLE_CHANGED` | Authorization | `user`, `role` | Wajib | Flexible | Optional |
| `PERMISSION_CHANGED` | Authorization | `role`, `permission` | Wajib | Flexible | Optional |
| `SUBMISSION_CREATED` | Submission | `letter_submission` | Wajib | Create | Forbidden |
| `SUBMISSION_UPDATED` | Submission | `letter_submission` | Wajib | Update | Forbidden |
| `SUBMISSION_DOCUMENT_REPLACED` | Submission | `letter_submission` | Wajib | Flexible | Forbidden |
| `SUBMISSION_SUBMITTED` | Submission | `letter_submission` | Wajib | Update | Forbidden |
| `SUBMISSION_RESUBMITTED` | Submission | `letter_submission` | Wajib | Update | Forbidden |
| `SUBMISSION_REVISION_REQUESTED` | Intake Review | `letter_submission` | Wajib | Update | Required |
| `SUBMISSION_READY_FOR_APPROVAL` | Intake Review | `letter_submission` | Wajib | Update | Required |
| `SUBMISSION_RETURNED_TO_STAFF` | Intake Decision | `letter_submission` | Wajib | Update | Required |
| `SUBMISSION_REJECTED` | Intake Decision | `letter_submission` | Wajib | Update | Required |
| `SUBMISSION_DRAFT_DELETED` | Submission | `letter_submission` | Wajib | Delete | Forbidden |
| `LETTER_REGISTERED` | Registration | `incoming_letter` | Wajib | Update | Required |
| `DOCUMENT_VERSION_CREATED` | Document | `letter_document` | Wajib | Create | Required |
| `POSITION_ASSIGNED` | Organization | `position`, `position_assignment` | Wajib | Create | Optional |
| `POSITION_HOLDER_REPLACED` | Organization | `position`, `position_assignment` | Wajib | Update | Optional |
| `POSITION_ASSIGNMENT_ENDED` | Organization | `position`, `position_assignment` | Wajib | Update | Optional |
| `POSITION_LEVEL_CATALOG_SYNCHRONIZED` | Organization | `position_level_catalog` | Null | Update | Optional |
| `ORGANIZATIONAL_UNIT_CREATED` | Organization | `organizational_unit` | Wajib | Create | Optional |
| `ORGANIZATIONAL_UNIT_UPDATED` | Organization | `organizational_unit` | Wajib | Update | Optional |
| `ORGANIZATIONAL_UNIT_STATUS_CHANGED` | Organization | `organizational_unit` | Wajib | Update | Optional |
| `POSITION_CREATED` | Organization | `position` | Wajib | Create | Optional |
| `POSITION_UPDATED` | Organization | `position` | Wajib | Update | Optional |
| `POSITION_STATUS_CHANGED` | Organization | `position` | Wajib | Update | Optional |

Guard memindai seluruh kedalaman payload (`old_values`, `new_values`, `metadata`) dan menolak secara otomatis jika ditemukan key sensitif (`password`, `token`, `secret`, `recovery_code`, `cookie`, `authorization`, `private_key`) atau tipe data non-serializable. Penolakan audit melempar `AuditContractViolationException` dan memicu *database rollback* agar mutasi bisnis tidak pernah tersimpan tanpa audit yang sah.

## Audit Perubahan Privilege

Back-office menyediakan console read-only pada:

```text
GET /back-office/audits/privileges
```

Akses memerlukan account `INTERNAL` yang aktif, terverifikasi, dan permission
eksplisit:

```text
privilege-audits.view
```

Console ini hanya menampilkan:

```text
INTERNAL_ACCOUNT_PROVISIONED
ROLE_CHANGED
PERMISSION_CHANGED
```

Perubahan Position Assignment tetap berada pada audit domain organisasi dan
tidak dicampur ke halaman ini. Filter action, actor/source, target, rentang
tanggal, dan pagination dilakukan pada query server. Payload response memakai
allowlist dan tidak mengekspos password, token, MFA secret, recovery code, atau
metadata audit mentah. Actor console ditampilkan sebagai sistem. Jika subject
sudah tidak tersedia, identitas target dibentuk dari snapshot aman pada audit.

Tidak terdapat endpoint update atau delete untuk `audit_logs`.

## Aktivitas Surat M3

Back-office menyediakan console read-only untuk memantau aktivitas intake dan
registrasi melalui:

```text
GET /back-office/audits/letters
```

Akses awal memerlukan account `INTERNAL` yang aktif, terverifikasi, dan
permission eksplisit `letter-activities.view`. Permission hanya membuka fitur.
Kedalaman informasi tetap ditentukan oleh Position Assignment aktif:

* Wali Kota/Sekda pada level `EXECUTIVE_ENTRY` memperoleh detail bisnis;
* Kepala Bagian Umum pada level `SECTION_HEAD` dan unit `BAGIAN_UMUM`
  memperoleh detail bisnis;
* pemegang permission tanpa Position bisnis tersebut, termasuk super-admin
  teknis, hanya memperoleh ringkasan tersanitasi.

Ringkasan tersanitasi tidak memuat identitas surat, pengirim, isi perubahan,
dokumen, identitas pelaksana, request ID, IP, atau user agent. Filter yang dapat
mengungkap identitas juga diabaikan server pada mode ringkasan. Collection,
filter, rentang tanggal, dan pagination dibatasi pada query server.

Scope M3 hanya memuat:

```text
SUBMISSION_SUBMITTED
SUBMISSION_RESUBMITTED
SUBMISSION_REVISION_REQUESTED
SUBMISSION_READY_FOR_APPROVAL
SUBMISSION_RETURNED_TO_STAFF
SUBMISSION_REJECTED
LETTER_REGISTERED
DOCUMENT_VERSION_CREATED
```

Aktivitas draft, pembaruan draft, dan penggantian dokumen sebelum submit tidak
ditampilkan agar pekerjaan privat pemohon tidak menjadi noise operasional.
Rentang hari menggunakan zona waktu kantor yang configurable, dengan nilai awal
`Asia/Makassar`; timestamp audit tetap disimpan dalam UTC. Audit registrasi
surat dan penciptaan versi dokumen memakai request ID yang sama karena merupakan
satu operasi bisnis transactional.

---

# 26. Authentication dan Session

Gunakan Laravel session authentication.

Public User:

```text
Password
+
Verified Email
```

Critical internal account:

```text
Password
+
Verified Email
+
MFA
```

MFA wajib untuk System Administrator sebelum capability administratif dapat
digunakan.

Wali Kota, Sekda, dan pejabat internal lain dapat mengaktifkan MFA, tetapi pada
MVP penggunaannya merupakan keputusan masing-masing pemegang account.

---

# 27. Electronic Approval dan TTE

MVP menggunakan electronic approval internal.

TTE resmi tetap menjadi future integration boundary.

Public submission tidak dianggap sebagai signed official document hanya karena dikirim melalui authenticated account.

Jika di masa depan diperlukan tanda tangan elektronik resmi dari pengirim atau pejabat, integrasi tersebut harus ditangani secara eksplisit.

---

# 28. Reporting Architecture

Reporting harus mampu membedakan intake channel:

```text
ONLINE
MANUAL
```

Data harus dapat mendukung laporan:

* jumlah submission online;
* jumlah submission manual;
* jumlah submission yang menjadi surat masuk;
* surat masuk per periode;
* instansi pengirim;
* surat diproses;
* surat selesai;
* disposisi per pejabat/bagian;
* waktu penyelesaian.

MVP menggunakan relational database aggregation.

---

# 29. Frontend Areas

Frontend dibagi berdasarkan konteks pengguna.

## Public Area

```text
Landing
Register
Login
Email Verification

Public Dashboard
├── Buat Submission
├── Submission Saya
└── Status Submission
```

## Bagian Umum

```text
Intake Dashboard
├── Online Submission Queue
├── Manual Submission
├── Register Incoming Letter
└── Incoming Letters
```

## Pejabat

```text
Official Dashboard
├── Inbox
├── Disposition
├── Related Letters
└── Follow Up
```

Ini tetap satu Vue + Inertia application.

---

# 30. Security Boundaries

Browser selalu dianggap untrusted.

Public registration dan submission meningkatkan attack surface.

Perhatikan minimal:

* registration abuse;
* credential stuffing;
* email abuse;
* malicious PDF upload;
* oversized upload;
* IDOR pada submission;
* IDOR pada surat;
* privilege escalation;
* workflow bypass;
* document tampering;
* CSRF;
* XSS;
* mass assignment.

Public User tidak boleh dapat mengubah field yang menentukan privilege atau state internal.

---

# 31. Future Capability

Tidak termasuk MVP:

* Staff sebagai terminal disposisi;
* scanner integration langsung;
* official TTE integration;
* advanced notifications;
* advanced analytics;
* external institutional SSO;
* confidential-letter workflow khusus.

Future capability tidak boleh memaksa redesign terhadap:

* Submission boundary;
* IncomingLetter;
* Position;
* PositionAssignment;
* disposition branching;
* authorization.

---

# 32. Architectural Invariants

1. Sistem menerima surat melalui kanal online dan manual.
2. Kedua kanal masuk melalui Submission/Intake boundary.
3. Online submission dibuat oleh authenticated + verified Public User.
4. Manual submission dibuat oleh Bagian Umum dan tidak membutuhkan account pengirim.
5. Submission bukan IncomingLetter.
6. Staf Bagian Umum melakukan screening teknis, tetapi tidak dapat menolak atau meregistrasikan submission.
7. Hanya Kepala Bagian Umum yang dapat mengesahkan registrasi IncomingLetter.
8. Surat resmi selalu melewati Bagian Umum.
9. IncomingLetter diarahkan ke Wali Kota atau Sekda.
10. Disposisi tidak boleh melompati hierarchy.
11. Kepala Bagian adalah terminal workflow formal MVP.
12. Satu surat dapat mempunyai beberapa branch aktif.
13. Role tidak digunakan sebagai pengganti Position.
14. Public User tidak mempunyai Position Assignment internal.
15. Internal privilege tidak dapat diperoleh melalui public registration.
16. Authorization selalu server-side.
17. System Administrator tidak otomatis mempunyai visibility seluruh surat.
18. File disimpan private.
19. Original document tidak ditimpa tanpa histori.
20. Audit tidak dapat diedit melalui workflow aplikasi normal.
21. Multi-resource business operation menjaga atomicity dan consistency.

---

# 33. Boundary Summary

Keseluruhan sistem dapat dipahami sebagai tiga domain besar:

```text
┌──────────────────────────────┐
│        EXTERNAL INTAKE       │
│                              │
│ Online Public User           │
│ Manual Physical Submission   │
└──────────────┬───────────────┘
               ↓
        Submission Boundary
               ↓
┌──────────────────────────────┐
│      GENERAL AFFAIRS         │
│                              │
│ Review / Register            │
│ Incoming Letter              │
└──────────────┬───────────────┘
               ↓
        Official Boundary
               ↓
┌──────────────────────────────┐
│      INTERNAL WORKFLOW       │
│                              │
│ Wali Kota / Sekda            │
│        ↓                     │
│ Asisten                      │
│        ↓                     │
│ Kepala Bagian                │
└──────────────────────────────┘
```

Boundary ini harus tetap jelas dalam implementasi.

---

# 34. Dokumen Terkait

```text
instruksi.md
→ engineering dan security guardrail

database-schema.md
→ persistence model

workflow-spec.md
→ exact lifecycle Submission, IncomingLetter,
  route, dan disposition branch

AGENTS.md
→ context router untuk coding agent
```

`system-design.md` tetap mendefinisikan arsitektur konseptual dan tidak menjadi tempat detail migration atau exact workflow transition.
