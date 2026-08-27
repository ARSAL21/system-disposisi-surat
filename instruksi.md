# Instruksi Khusus — Sistem Disposisi Surat

Dokumen ini adalah aturan utama pengembangan Sistem Disposisi Surat Kantor Wali Kota.

Stack:

- Laravel
- Inertia.js
- Vue
- relational database

Prioritas sistem:

1. Security
2. Correctness
3. Data integrity
4. Traceability
5. Maintainability
6. Developer convenience

Gunakan DRY, SOLID, KISS, YAGNI, least privilege, secure by default, dan fail securely.

Jangan membuat abstraction, dependency, atau arsitektur kompleks tanpa kebutuhan nyata.

---

## 1. Workflow Utama

Workflow formal MVP:

```text
Bagian Umum / Tata Usaha
        ↓
Wali Kota ATAU Sekda
        ↓
Asisten I / II / III
        ↓
Satu atau lebih Kepala Bagian
```

Bagian Umum menjadi gerbang administratif surat masuk.

Bagian Umum bertanggung jawab pada intake, metadata, dokumen, dan registrasi surat, tetapi tidak melakukan disposisi substantif.

Disposisi tidak boleh melompati hierarki.

Satu disposisi dapat memiliki beberapa penerima.

Riwayat parent-child disposisi wajib dipertahankan.

Kepala Bagian adalah terminal formal workflow MVP.

Staff belum termasuk workflow formal MVP.

---

## 2. Pisahkan User, Role, Permission, Position, dan Assignment

Jangan mencampur:

```text
User
→ identitas account

Role
→ kumpulan permission

Permission
→ capability aplikasi

Position
→ jabatan organisasi

PositionAssignment
→ siapa menduduki Position dan pada periode kapan
```

Workflow harus berdasarkan Position dan assignment aktif, bukan nama Role.

Histori assignment tidak boleh hilang ketika pejabat berganti.

---

## 3. RBAC Bukan Workflow Engine

RBAC menjawab:

> Apa yang boleh dilakukan?

Position menjawab:

> User sedang bertindak sebagai siapa?

Workflow Rule menjawab:

> Kepada Position mana tindakan boleh diteruskan?

Authorization menjawab:

> Apakah tindakan tersebut boleh dilakukan terhadap resource ini?

Jangan menyebarkan pengecekan seperti:

```php
$user->role === 'sekda'
```

ke seluruh codebase.

---

## 4. Wali Kota Bukan System Administrator

Wali Kota adalah authority organisasi.

System Administrator adalah authority teknis.

Jangan membuat:

```text
Wali Kota = Super Admin
```

Orang yang sama boleh memegang Position Wali Kota/Sekda sekaligus Role Administrator, tetapi permission tetap terpisah.

System Administrator tidak otomatis boleh membaca seluruh surat.

---

## 5. Authorization Wajib Server-Side

Frontend bukan security boundary.

Gunakan:

```text
Policy
→ authorization terhadap satu resource/action

Authorized Query / Scope
→ membatasi collection sejak database query
```

Jangan mengambil seluruh surat lalu menyembunyikannya di Vue.

Mengetahui ID atau URL resource tidak memberikan hak akses.

IDOR dan privilege escalation adalah ancaman utama.

---

## 6. Visibility Surat

Wali Kota dan Sekda dapat memiliki global business visibility sesuai Policy.

Pejabat lain hanya dapat melihat surat jika:

- terlibat dalam workflow/disposisi yang sah; atau
- memiliki permission khusus.

Kepala Bagian tidak otomatis dapat melihat surat Kepala Bagian lain.

---

## 7. Validation

Semua input client dianggap tidak terpercaya.

Gunakan Laravel Form Request untuk input eksternal yang membutuhkan validasi.

Validasi meliputi hal seperti:

- required;
- type;
- format;
- allowed values;
- length;
- file MIME;
- extension;
- file size.

Jangan mempercayai actor ID, role, permission, Position, status, workflow state, atau timestamp dari frontend jika server dapat menentukannya.

Validation tidak sama dengan authorization.

---

## 8. Separation of Responsibilities

Gunakan alur:

```text
Route
  ↓
Form Request
  ↓
Controller
  ↓
Policy / Authorization
  ↓
Action
  ↓
Service / Domain Rule jika diperlukan
  ↓
Model / Database
```

Setiap layer harus mempunyai responsibility jelas.

Jangan membuat file besar hanya untuk mengurangi jumlah file.

Jangan membuat banyak file tanpa responsibility nyata.

---

## 9. Controller Harus Tipis

Controller hanya bertugas:

1. menerima request;
2. mengambil validated data;
3. melakukan authorization;
4. memanggil Action;
5. mengembalikan HTTP/Inertia response.

Controller tidak boleh menjadi tempat:

- workflow logic;
- disposition hierarchy;
- aggregate state calculation;
- multi-record business mutation;
- audit construction;
- file integrity logic;
- reusable business rule.

Controller idealnya jauh di bawah 200 baris.

Jika mendekati atau melewati 200 baris, wajib review responsibility dan lakukan refactor jika logic sudah bercampur.

---

## 10. Action = Satu Business Use Case

Action merepresentasikan satu operasi bisnis.

Contoh:

```text
RegisterIncomingLetter
CreateLetterSubmission
RouteIncomingLetter
CreateDisposition
ForwardDisposition
CompleteDisposition
AssignPosition
```

Controller sebaiknya memanggil Action, bukan menjalankan workflow sendiri.

Action boleh mengorkestrasi beberapa langkah dalam satu use case.

Contoh:

```text
CreateDisposition
→ validate workflow
→ create disposition
→ create recipients
→ update state
→ audit
```

Jika seluruh langkah harus berhasil atau gagal bersama, gunakan database transaction.

---

## 11. Service Hanya Jika Dibutuhkan

Service digunakan jika logic:

- mempunyai responsibility domain yang jelas;
- digunakan oleh beberapa Action; atau
- cukup kompleks untuk dipisahkan.

Contoh:

```text
DispositionWorkflowService
LetterStateService
DocumentIntegrityService
```

Jangan membuat God Service seperti:

```text
LetterService
CommonService
HelperService
```

yang menangani terlalu banyak concern.

Jangan membuat Service hanya untuk memindahkan kode dari Controller.

---

## 12. Database dan Transaction

Gunakan foreign key, unique constraint, index, dan database constraint jika dapat menjaga invariant.

Operasi multi-record harus atomic.

Contoh:

```text
create disposition
→ create recipients
→ update branch
→ update aggregate state
→ audit
```

harus berhasil seluruhnya atau rollback.

---

## 13. Status Surat dan Branch

Status branch disposisi berbeda dengan status surat keseluruhan.

Contoh:

```text
Kabag A → COMPLETED
Kabag B → IN_PROGRESS
```

berarti surat belum selesai.

Branch state menjadi dasar aggregate letter state.

Aggregate state hanya boleh diubah melalui business rule terpusat.

Frontend tidak boleh menentukan status surat.

---

## 14. Instruksi Disposisi Configurable

Instruction label seperti:

- Untuk diketahui
- Untuk ditindaklanjuti
- Untuk dipelajari
- Untuk dikoordinasikan
- Untuk menghadiri
- Untuk disiapkan jawabannya
- Segera

harus dapat ditambah atau dinonaktifkan tanpa migration/deployment.

Jangan menggunakan ENUM atau boolean column terpisah per label.

---

## 15. File Surat dan Integrity

Dokumen surat harus disimpan di private storage.

Akses file wajib melalui backend dan authorization.

Upload minimal memvalidasi:

- MIME/type;
- extension;
- ukuran;
- validitas file.

Nama file asli tidak boleh digunakan langsung sebagai storage path.

Setiap file surat resmi harus mempunyai SHA-256 fingerprint.

Hash digunakan untuk integrity, bukan encryption, authorization, atau TTE.

Original file tidak boleh ditimpa diam-diam.

Koreksi harus menghasilkan version baru dan mempertahankan histori.

---

## 16. Audit Trail

Audit trail wajib dan application-level append-only.

Tidak ada workflow normal untuk mengubah atau menghapus audit record.

Minimal catat:

- actor;
- active Position Assignment;
- action;
- target resource;
- server timestamp;
- relevant state changes.

Audit juga wajib untuk perubahan:

- role;
- permission;
- user;
- Position Assignment;
- konfigurasi workflow.

Jangan menyimpan password, token, MFA secret, atau recovery code dalam audit/log.

---

## 17. Authentication dan MFA

Gunakan Laravel session authentication melalui Inertia.

Wajib:

- secure password hashing;
- CSRF protection;
- session regeneration;
- login rate limiting;
- secure cookies;
- session expiration;
- password recovery yang aman.

MFA wajib minimal untuk:

- Wali Kota;
- Sekda;
- System Administrator.

---

## 18. Electronic Approval dan TTE

Bedakan:

```text
Electronic Approval
≠
TTE resmi / tersertifikasi
```

MVP dapat menggunakan authenticated actor, Position Assignment, server timestamp, MFA untuk account kritis, audit trail, dan document integrity.

Jangan menganggap gambar tanda tangan pada PDF sebagai digital signature yang aman.

TTE resmi harus menjadi integration boundary terpisah.

---

## 19. Vue Page Harus Tipis

Vue Page adalah page composer/orchestrator.

Page terutama bertanggung jawab pada:

- menerima Inertia props;
- menyusun component;
- menghubungkan event;
- menyimpan state page-level.

Bagian UI dengan responsibility sendiri wajib dipisahkan.

Contoh:

```text
IncomingLetterDetail.vue
├── LetterHeader.vue
├── LetterMetadata.vue
├── LetterDocumentViewer.vue
├── DispositionTimeline.vue
├── DispositionForm.vue
└── FollowUpSection.vue
```

Jangan membuat satu Page yang berisi seluruh form, table, modal, filter, timeline, dan document viewer jika masing-masing dapat berdiri sebagai responsibility berbeda.

Jika Page/component mendekati atau melewati 500 baris, wajib architecture review dan decomposition jika responsibility sudah bercampur.

500 baris adalah refactoring trigger, bukan target.

---

## 20. Vue Component Separation

Pisahkan component berdasarkan responsibility, bukan sekadar ukuran.

Gunakan:

```text
Layout
└── Page
    ├── Component
    ├── Component
    └── Component
```

Layout hanya untuk struktur lintas halaman seperti Sidebar, Navigation, dan AppShell.

Component fitur dipanggil oleh Page yang membutuhkan.

Business rule tidak boleh hidup hanya di Vue.

Frontend checks hanya untuk UX.

Backend tetap source of truth.

---

## 21. Error Handling

Error harus ditangani eksplisit.

Jangan:

- swallow exception;
- mengabaikan error;
- mengekspos stack trace;
- mengekspos SQL error;
- mengekspos filesystem path;
- mengekspos credential.

Gunakan HTTP status code yang sesuai.

Fail jelas dan sedini mungkin.

---

## 22. Naming dan Maintainability

Gunakan nama domain yang menjelaskan intent.

Hindari nama seperti:

```text
process()
handleData()
doAction()
data
temp
item
helper
manager
```

jika nama yang lebih spesifik tersedia.

Gunakan:

```text
CreateDisposition
ForwardDisposition
incomingLetter
dispositionRecipient
activePositionAssignment
```

Function harus memiliki satu responsibility dan nesting minimal.

Gunakan guard clause/early return jika membuat alur lebih jelas.

---

## 23. Mass Assignment dan Server-Owned Data

Jangan gunakan:

```php
Model::create($request->all());
```

Gunakan validated data.

Field sensitif ditentukan server, termasuk:

- actor;
- role;
- permission;
- Position;
- workflow state;
- status;
- timestamp.

Gunakan `$fillable` eksplisit atau perlindungan setara.

---

## 24. Testing

Critical path wajib memiliki automated test.

Prioritaskan:

- authentication;
- MFA;
- RBAC;
- authorization;
- Position-based access;
- hierarchy enforcement;
- multiple recipients;
- disposition branching;
- completion;
- upload;
- private file access;
- audit;
- privilege escalation.

Selalu test positive dan negative path.

---

## 25. AI Coding Agent Rules

Sebelum mengubah code, AI harus memahami requirement, domain, hierarchy, authorization, audit requirement, dan dampak terhadap existing data.

AI wajib mengevaluasi separation of responsibilities setiap kali menyentuh file besar.

AI tidak boleh:

- melewati Form Request tanpa alasan;
- menaruh business logic dalam Controller;
- membuat Controller besar;
- membuat giant Vue Page/component;
- menggunakan Role sebagai workflow engine;
- mempercayai authorization frontend;
- hard-code hierarchy di banyak tempat;
- menggunakan public storage untuk surat;
- mengganti original file tanpa histori;
- menghapus audit trail;
- membuat custom cryptography;
- membuat God Service;
- melakukan uncontrolled mass assignment;
- membuat abstraction prematur;
- melakukan premature optimization;
- menambahkan Staff ke workflow formal sebelum scope disetujui.

Controller mendekati/melewati 200 baris:

> review dan pisahkan responsibility bila diperlukan.

Vue Page/component mendekati/melewati 500 baris:

> review dan pecah component berdasarkan responsibility.

Jangan mengakali batas tersebut dengan memindahkan kode ke helper generik tanpa domain responsibility yang jelas.

---

## 26. Definition of Done

Fitur dianggap selesai jika:

- requirement terpenuhi;
- validation tersedia;
- authorization benar;
- hierarchy dipatuhi;
- Controller tetap tipis;
- use case berada di Action yang tepat;
- Service mempunyai responsibility nyata jika digunakan;
- Vue Page/component terpisah secara masuk akal;
- database consistency terjaga;
- transaction digunakan bila diperlukan;
- error path ditangani;
- audit tersedia bila relevan;
- sensitive data tidak terekspos;
- critical path memiliki test;
- naming jelas;
- code mudah dipahami dan dipelihara.

> **Security, correctness, integrity, traceability, dan maintainability lebih penting daripada kecepatan menambah fitur.**
