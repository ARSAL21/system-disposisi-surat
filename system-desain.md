# System Design — Sistem Disposisi Surat

## 1. Tujuan Sistem

Sistem Disposisi Surat adalah aplikasi internal Pemerintah Kota untuk mengelola **surat masuk** sejak diterima Bagian Umum sampai seluruh tindak lanjut disposisi selesai.

Sistem harus menjamin:

* alur disposisi mengikuti hierarki;
* satu surat dapat memiliki beberapa cabang disposisi;
* akses surat hanya diberikan kepada pihak yang berwenang;
* histori tindakan tidak hilang ketika pejabat berganti;
* dokumen asli dapat diverifikasi integritasnya;
* seluruh tindakan penting dapat diaudit;
* data dapat digunakan untuk laporan periodik.

---

## 2. Scope MVP

Workflow formal MVP:

```text
Surat dari instansi/organisasi luar
            ↓
Bagian Umum / Tata Usaha
            ↓
Registrasi + Upload PDF
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

* Bagian Umum merupakan entry point seluruh surat.
* Surat diarahkan ke Wali Kota **atau** Sekda.
* Wali Kota dan Sekda berada pada level penerimaan awal yang sama dalam workflow.
* Disposisi wajib mengikuti hierarki dan tidak boleh melompati level.
* Satu disposisi dapat menghasilkan beberapa cabang.
* Kepala Bagian adalah terminal formal workflow MVP.
* Staff belum menjadi penerima disposisi formal pada MVP.

---

# 3. High-Level Architecture

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
│ Workflow Coordination         │
└───────────────┬───────────────┘
                │
                ▼
┌───────────────────────────────┐
│        Domain Rules           │
│ Position / Authorization      │
│ Disposition / Completion      │
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
* authorization;
* routing;
* validation;
* business workflow;
* persistence;
* audit.

Vue bertanggung jawab pada presentation dan interaction, bukan business authority.

---

# 4. Domain Utama

Domain inti terdiri dari:

```text
User
Role
Permission

Position
PositionAssignment

IncomingLetter
LetterDocument

Disposition
DispositionBranch

DispositionInstruction

AuditLog
```

## User

Merepresentasikan identitas account.

## Role dan Permission

Mengatur kapabilitas aplikasi.

Contoh:

```text
letter.create
letter.view
letter.route

disposition.create
disposition.forward
disposition.complete

report.view
audit.view
```

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
Kepala Bagian Pemerintahan
...
```

`Kepala Bagian` tidak cukup dimodelkan sebagai satu posisi generik jika terdapat beberapa bagian berbeda.

## PositionAssignment

Menghubungkan user dengan Position dalam periode tertentu.

```text
User
  ↓
PositionAssignment
  ↓
Position
```

Ini memungkinkan pergantian pejabat tanpa merusak histori.

---

# 5. Role Tidak Menentukan Hierarki

RBAC dan struktur organisasi adalah dua sistem berbeda.

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
"Bolehkah tindakan dilakukan terhadap surat ini?"
```

Workflow tidak boleh bergantung langsung pada nama role.

Contoh:

User mungkin mempunyai permission:

```text
disposition.forward
```

tetapi jika Position aktifnya adalah `Asisten II`, workflow hanya mengizinkan penerima pada level Kepala Bagian.

---

# 6. Position-Based Assignment

Disposisi diarahkan kepada **Position**, bukan sekadar `user_id`.

Contoh:

```text
Disposition
    ↓
Kepala Bagian Hukum
```

bukan:

```text
Disposition
    ↓
Budi
```

User yang bertindak tetap dicatat ketika aksi dilakukan.

Dengan demikian:

```text
Position
→ menentukan siapa yang memiliki pekerjaan aktif

User + PositionAssignment
→ menentukan siapa yang benar-benar melakukan tindakan
```

Jika pejabat berganti, pekerjaan aktif pada jabatan tersebut dapat tetap diteruskan oleh pemegang jabatan yang baru.

Histori tindakan lama tetap menunjukkan user dan assignment yang berlaku ketika tindakan tersebut terjadi.

---

# 7. Registrasi Surat Masuk

Bagian Umum menjalankan proses:

```text
Terima surat fisik
      ↓
Periksa administrasi dasar
      ↓
Input metadata
      ↓
Upload PDF
      ↓
Hitung SHA-256
      ↓
Registrasi surat
      ↓
Route ke Wali Kota / Sekda
```

Registrasi surat dan routing merupakan operasi berbeda secara domain.

Bagian Umum memiliki kewenangan administratif terhadap registrasi, tetapi tidak mempunyai kewenangan disposisi substantif.

---

# 8. Disposition Model

Satu surat tidak memiliki satu `current_owner`.

Model mentalnya adalah:

```text
IncomingLetter
      ↓
Disposition
      ↓
Disposition Branch
```

`Disposition` merepresentasikan tindakan seseorang meneruskan surat.

Setiap penerima menghasilkan sebuah **branch**.

Contoh:

```text
Sekda
  ↓
Asisten II
  ↓
Disposition
  ├── Kabag Kesehatan
  └── Kabag Aset
```

Dua Kepala Bagian tersebut merupakan dua branch aktif terhadap surat yang sama.

---

# 9. Disposition Tree

Branch dapat menjadi sumber disposisi berikutnya sehingga seluruh perjalanan surat dapat direpresentasikan sebagai tree.

```text
Surat
  │
  ▼
Sekda
  │
  ▼
Asisten II
  │
  ├──────────────┐
  ▼              ▼
Kabag A        Kabag B
```

Sistem harus mengetahui untuk setiap branch:

* berasal dari branch mana;
* dibuat oleh siapa;
* dalam Position apa;
* ditujukan kepada Position apa;
* kapan dibuat;
* instruksi yang diberikan;
* status branch.

Histori tree tidak boleh diganti dengan hanya menyimpan penerima terakhir.

---

# 10. Workflow Enforcement

Workflow MVP:

```text
GENERAL_AFFAIRS
      ↓
MAYOR / SECRETARY
      ↓
ASSISTANT
      ↓
SECTION_HEAD
```

Workflow rule menentukan transisi yang diperbolehkan.

Contoh:

```text
Wali Kota → Asisten II      VALID
Sekda → Asisten I           VALID
Asisten II → Kabag Hukum    VALID

Wali Kota → Kabag Hukum     INVALID
Sekda → Kabag Aset          INVALID
Kabag → Asisten             INVALID
```

Vue hanya menampilkan tujuan yang valid.

Backend tetap wajib memverifikasi workflow setiap kali disposisi dibuat.

Workflow rule harus tersentralisasi agar tidak tersebar pada Controller, Policy, dan Vue.

---

# 11. Multiple Recipients

Domain wajib mendukung beberapa penerima pada satu tindakan disposisi.

Contoh:

```text
Asisten I
   │
   ├── Kabag Pemerintahan
   ├── Kabag Hukum
   └── Kabag Aset
```

Setiap penerima memiliki branch dan lifecycle sendiri.

Kegagalan satu branch tidak boleh menghilangkan atau menimpa branch lainnya.

Pembuatan disposition beserta seluruh recipient dilakukan secara transactional.

---

# 12. State Management

Status surat dan status branch adalah dua konsep berbeda.

## Branch State

Minimal state konseptual:

```text
PENDING
    ↓
IN_PROGRESS
    ↓
COMPLETED
```

Detail exact state dan transition akan dikunci pada database/workflow design.

## Letter State

Status keseluruhan diturunkan dari workflow.

Contoh:

```text
Kabag A → COMPLETED
Kabag B → IN_PROGRESS
```

maka:

```text
Incoming Letter → IN_PROGRESS
```

Surat baru dapat dianggap selesai ketika seluruh terminal branch yang masih aktif telah selesai.

Aggregate state dapat disimpan untuk kebutuhan query/reporting, tetapi hanya boleh diperbarui melalui business rule terpusat.

---

# 13. Authorization Model

Authorization menggunakan kombinasi:

```text
RBAC
+
Position aktif
+
Workflow
+
Disposition participation
+
Resource Policy
```

## Global Business Visibility

Wali Kota dan Sekda dapat diberikan visibility terhadap seluruh surat.

## Scoped Visibility

Asisten dan Kepala Bagian hanya dapat melihat surat yang terkait dengan jalur disposisinya.

Contoh:

```text
Asisten II
 ├── Kabag Kesehatan
 └── Kabag Aset
```

Kedua Kabag tersebut dapat membaca surat terkait.

Kabag lain tidak boleh mengaksesnya.

## System Administrator

System Administrator mengelola aspek teknis tetapi tidak otomatis memperoleh global business visibility.

---

# 14. Collection Authorization

Authorization terhadap satu resource dan collection dipisahkan secara jelas.

```text
Policy
→ bolehkah user membuka / mengubah resource ini?

Authorized Query
→ resource mana yang boleh muncul untuk user?
```

Daftar surat harus dibatasi sejak database query.

Tidak diperbolehkan:

```text
SELECT seluruh surat
        ↓
kirim ke Vue
        ↓
sembunyikan berdasarkan role
```

---

# 15. Instruksi Disposisi

Disposition mempunyai data struktural seperti:

* actor;
* source branch;
* recipients;
* catatan;
* timestamp;
* instruction labels.

Instruction label dapat dikonfigurasi.

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

Label tidak boleh menjadi ENUM yang membutuhkan migration setiap kali berubah.

Klasifikasi keamanan seperti `Rahasia` tidak diaktifkan sebagai access-control rule pada MVP sampai requirement tersebut diputuskan secara resmi.

---

# 16. Document Storage dan Integrity

Dokumen surat disimpan pada private storage.

```text
Upload
  ↓
Validate
  ↓
Generate internal filename
  ↓
Store privately
  ↓
SHA-256
  ↓
Persist fingerprint
```

Akses file:

```text
Request document
      ↓
Authentication
      ↓
Authorization
      ↓
Private storage
      ↓
Response
```

Tidak ada direct public URL permanen untuk dokumen surat.

PDF original tidak ditimpa secara diam-diam.

Koreksi menghasilkan versi baru sehingga:

```text
Original
   ↓
Correction Version
   ↓
Correction Version
```

tetap dapat ditelusuri.

---

# 17. Audit Architecture

Audit trail bersifat **application-level append-only**.

Audit minimal merekam:

```text
Actor
Position Assignment
Action
Resource
Timestamp
Relevant State Change
Request Context
```

Contoh:

```text
Sekda
14:10
FORWARD_DISPOSITION
Surat SM-001
→ Asisten II
```

Business mutation dan audit event yang berkaitan harus diperlakukan sebagai satu kesatuan transaksi jika memungkinkan.

Tidak tersedia workflow aplikasi normal untuk mengedit atau menghapus audit record.

Audit log berbeda dari application log.

Production hardening dapat menambahkan:

* database privilege restriction;
* backup retention;
* external audit sink;
* infrastructure monitoring.

Tidak menggunakan custom hash-chain sebagai pengganti mekanisme audit yang benar.

---

# 18. Authentication dan Session

Web menggunakan Laravel session authentication melalui Inertia.

Security minimum:

* password hashing;
* CSRF protection;
* login rate limiting;
* session regeneration;
* secure cookie;
* session expiration;
* password recovery;
* session invalidation.

MFA wajib untuk:

```text
Wali Kota
Sekda
System Administrator
```

Tindakan security-sensitive dapat menggunakan re-authentication.

---

# 19. Electronic Approval dan TTE

MVP menggunakan **electronic approval**, bukan mengklaim TTE tersertifikasi.

Approval dibuktikan menggunakan:

```text
Authenticated User
+
Active Position Assignment
+
Server Timestamp
+
Audit Trail
+
Document Integrity
```

TTE resmi merupakan integration boundary terpisah.

Konsep jangka panjang:

```text
Application
     ↓
Signature Contract
     ↓
Signature Provider
```

Business workflow tidak boleh bergantung langsung pada provider tertentu.

Gambar tanda tangan yang ditempel pada PDF tidak dianggap sebagai digital signature yang aman.

---

# 20. Reporting Architecture

Operational data harus dapat mendukung laporan:

* surat masuk per periode;
* instansi pengirim;
* surat belum diproses;
* surat sedang diproses;
* surat selesai;
* disposisi per pejabat;
* disposisi per bagian;
* waktu penyelesaian.

MVP menggunakan query agregasi pada relational database.

Tidak diperlukan analytics database atau data warehouse pada tahap awal.

Index database akan ditentukan berdasarkan pola query laporan aktual.

---

# 21. Backend Responsibility

Struktur konseptual:

```text
Form Request
     ↓
Controller
     ↓
Policy / Authorization
     ↓
Action / Service
     ↓
Domain Rules
     ↓
Model / Repository Infrastructure
```

Controller tidak menyimpan business workflow.

Contoh Action:

```text
RegisterIncomingLetter
RouteIncomingLetter
CreateDisposition
ForwardDisposition
CompleteDisposition
AssignPosition
```

Action yang mengubah beberapa resource wajib menggunakan transaction.

---

# 22. Frontend Responsibility

Vue menangani:

* form;
* interactive disposition tree;
* document viewer;
* timeline;
* status presentation;
* reporting UI;
* feedback/error state.

Contoh komposisi halaman:

```text
IncomingLetterDetail.vue
 ├── LetterMetadata.vue
 ├── LetterDocumentViewer.vue
 ├── DispositionTimeline.vue
 ├── DispositionBranch.vue
 └── DispositionForm.vue
```

Component dipisahkan berdasarkan tanggung jawab nyata.

Business rules dan authorization tidak boleh hanya hidup di frontend.

---

# 23. Security Boundaries

Sistem diasumsikan dapat berjalan pada jaringan publik.

Karena itu desain tidak boleh bergantung pada asumsi:

> "aman karena hanya digunakan di kantor."

Boundary utama:

```text
Browser
   ↓ UNTRUSTED
Laravel Application
   ↓
Database / Private Storage
```

Ancaman minimum yang harus diuji:

* IDOR;
* horizontal privilege escalation;
* vertical privilege escalation;
* unauthorized disposition;
* workflow bypass;
* malicious file upload;
* document tampering;
* audit tampering;
* CSRF;
* XSS;
* mass assignment.

---

# 24. Future Capability

Tidak termasuk MVP:

* Staff sebagai terminal disposisi tambahan;
* scanner integration;
* official TTE/BSrE integration;
* advanced notification;
* advanced analytics;
* workload dashboard;
* external system integration;
* special confidential-letter workflow.

Penambahan future capability tidak boleh memaksa redesign terhadap domain inti surat, Position, disposition branching, dan authorization.

---

# 25. Architectural Invariants

Aturan berikut tidak boleh dilanggar oleh implementasi:

1. Surat selalu masuk melalui proses registrasi Bagian Umum.
2. Surat awal diarahkan ke Wali Kota atau Sekda.
3. Disposisi tidak boleh melompati hierarchy.
4. Kepala Bagian adalah terminal formal workflow MVP.
5. Satu surat dapat mempunyai beberapa branch aktif.
6. Setiap branch memiliki lifecycle sendiri.
7. Surat selesai hanya ketika seluruh terminal branch aktif selesai.
8. Role tidak digunakan sebagai pengganti Position.
9. Disposisi diarahkan berdasarkan Position, sementara actor tetap dicatat sebagai User + Position Assignment.
10. User hanya menerima data surat yang memang berhak diketahuinya.
11. File original tidak ditimpa tanpa histori.
12. Audit record tidak dapat dimodifikasi melalui workflow aplikasi normal.
13. System Administrator tidak otomatis mempunyai akses bisnis ke seluruh surat.
14. Frontend tidak pernah menjadi sumber kebenaran authorization.
15. Perubahan multi-resource yang berkaitan harus menjaga atomicity dan consistency.

---

# 26. Dokumen Lanjutan

`system-design.md` menentukan struktur konseptual sistem.

Detail implementasi berikut dipisahkan:

```text
database-schema.md
→ tabel, kolom, FK, constraint, index, ERD

permission-matrix.md
→ role, permission, dan akses setiap capability

workflow-spec.md
→ exact state dan transition

api-contract.md
→ request/response dan error contract jika dibutuhkan

deployment-notes.md
→ environment, storage, backup, HTTPS, queue, monitoring
```

Dengan pemisahan ini, System Design tetap menjadi sumber arsitektur tanpa berubah menjadi dokumentasi implementasi yang terlalu detail.
