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
* Hanya Bagian Umum yang dapat melakukan registrasi surat masuk resmi.
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

Bagian Umum kemudian menentukan apakah submission dapat diregistrasi.

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

Hanya Bagian Umum yang dapat mengubah submission menjadi IncomingLetter.

Alur:

```text
Submission
      ↓
Bagian Umum memastikan data administratif
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

Bagian Umum dapat mengakses intake queue sesuai permission dan melakukan registrasi.

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

Semua dokumen berada di private storage.

Akses dokumen selalu melalui authorization.

SHA-256 digunakan untuk document integrity, bukan confidentiality.

Dokumen asli tidak ditimpa tanpa histori.

---

# 25. Audit Architecture

Audit trail bersifat application-level append-only.

Audit harus mencakup aktivitas penting pada kedua boundary.

Contoh public:

```text
SUBMISSION_CREATED
SUBMISSION_SUBMITTED
```

Contoh Bagian Umum:

```text
MANUAL_SUBMISSION_CREATED
INCOMING_LETTER_REGISTERED
LETTER_ROUTED
```

Contoh internal:

```text
DISPOSITION_CREATED
DISPOSITION_COMPLETED
```

Audit juga wajib terhadap privilege dan Position changes.

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
6. Hanya Bagian Umum yang dapat meregistrasikan IncomingLetter.
7. Surat resmi selalu melewati Bagian Umum.
8. IncomingLetter diarahkan ke Wali Kota atau Sekda.
9. Disposisi tidak boleh melompati hierarchy.
10. Kepala Bagian adalah terminal workflow formal MVP.
11. Satu surat dapat mempunyai beberapa branch aktif.
12. Role tidak digunakan sebagai pengganti Position.
13. Public User tidak mempunyai Position Assignment internal.
14. Internal privilege tidak dapat diperoleh melalui public registration.
15. Authorization selalu server-side.
16. System Administrator tidak otomatis mempunyai visibility seluruh surat.
17. File disimpan private.
18. Original document tidak ditimpa tanpa histori.
19. Audit tidak dapat diedit melalui workflow aplikasi normal.
20. Multi-resource business operation menjaga atomicity dan consistency.

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
