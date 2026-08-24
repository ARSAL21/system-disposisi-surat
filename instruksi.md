# Instruksi Khusus — Sistem Disposisi Surat

Dokumen ini menjadi aturan utama pengembangan Sistem Disposisi Surat Kantor Wali Kota.

Stack utama:

* Laravel
* Inertia.js
* Vue
* relational database

Sistem berfokus pada **surat masuk dan disposisi internal secara hierarkis**.

---

## 1. Prinsip Utama

Sistem ini bukan CRUD biasa.

Setiap implementasi harus memprioritaskan:

1. Security
2. Correctness
3. Data integrity
4. Traceability
5. Maintainability
6. Developer convenience

Gunakan prinsip:

* DRY
* SOLID
* KISS
* YAGNI
* least privilege
* secure by default
* fail securely

Jangan membuat abstraction, dependency, atau arsitektur kompleks tanpa kebutuhan nyata.

---

## 2. Alur Bisnis Utama

Alur disposisi MVP wajib mengikuti hierarki organisasi:

```text
Bagian Umum / Tata Usaha
        ↓
Wali Kota ATAU Sekda
        ↓
Asisten I / II / III
        ↓
Satu atau lebih Kepala Bagian
```

Bagian Umum menjadi pintu pertama surat masuk dan bertanggung jawab terhadap pencatatan metadata serta upload PDF.

Bagian Umum tidak melakukan disposisi substantif.

Wali Kota dan Sekda berada pada level penerimaan awal yang sama dalam workflow.

Disposisi **tidak boleh melompati hierarki**.

Satu disposisi dapat mempunyai beberapa penerima sehingga satu surat dapat dikerjakan beberapa bagian secara bersamaan.

Riwayat parent-child setiap disposisi wajib dipertahankan.

**Untuk MVP, Kepala Bagian merupakan titik akhir disposisi formal. Staff merupakan future capability dan belum menjadi bagian dari workflow MVP.**

---

## 3. User, Role, Permission, dan Jabatan Harus Dipisahkan

Jangan mencampur konsep berikut:

* `User` → identitas pengguna.
* `Role` → kumpulan permission.
* `Permission` → kemampuan menggunakan fitur tertentu.
* `Position` → jabatan dalam organisasi.
* `Position Assignment` → siapa menduduki jabatan tersebut dan pada periode kapan.

Contoh jabatan:

* Wali Kota
* Sekda
* Asisten I
* Asisten II
* Asisten III
* Kepala Bagian

Workflow organisasi harus berdasarkan **jabatan dan assignment aktif**, bukan nama role.

Histori assignment tidak boleh hilang ketika pejabat berganti.

---

## 4. RBAC Bukan Workflow Engine

Gunakan RBAC untuk mengatur kapabilitas.

Contoh permission:

```text
letter.create
letter.view
letter.route

disposition.create
disposition.forward
disposition.complete

report.view
audit.view

user.manage
role.manage
system.configure
```

Jangan menyebarkan pengecekan seperti:

```php
$user->role === 'sekda'
```

ke seluruh aplikasi.

Mental model yang wajib digunakan:

```text
Role / Permission
→ apa yang boleh dilakukan?

Position
→ user sedang bertindak sebagai siapa?

Workflow Rule
→ kepada siapa tindakan boleh diteruskan?

Authorization
→ bolehkah tindakan tersebut dilakukan terhadap resource ini?
```

Keempatnya berbeda.

---

## 5. Wali Kota Bukan System Administrator

Wali Kota adalah otoritas organisasi.

`System Administrator` adalah otoritas teknis.

Jangan membuat:

```text
Wali Kota = Super Admin
```

Orang yang sama boleh memegang jabatan Wali Kota/Sekda sekaligus role Administrator pada MVP, tetapi permission keduanya harus tetap terpisah.

System Administrator juga tidak otomatis berhak membaca seluruh surat.

---

## 6. Authorization Wajib Server-Side

Frontend bukan security boundary.

Gunakan Laravel Policy untuk authorization terhadap resource/action tertentu.

Gunakan authorized query/scope untuk membatasi collection yang boleh diketahui user.

Contoh:

```text
Policy
→ bolehkah user membuka Surat #123?

Authorized Query
→ surat mana saja yang boleh muncul pada inbox user?
```

Jangan mengambil seluruh surat kemudian menyembunyikannya di Vue.

Mengetahui URL atau ID sebuah surat tidak pernah berarti user berhak mengaksesnya.

IDOR dan privilege escalation harus dianggap sebagai ancaman utama.

---

## 7. Visibility Surat

Wali Kota dan Sekda dapat memiliki global business visibility sesuai policy.

Pejabat lain hanya dapat melihat surat jika mempunyai hubungan yang sah dengan workflow/disposisi surat tersebut atau mempunyai permission khusus yang memang diberikan.

Contoh:

```text
Asisten II
 ├── Kabag Kesehatan
 └── Kabag Aset
```

Kedua Kabag tersebut dapat melihat surat yang sama.

Kabag lain tidak otomatis boleh melihatnya hanya karena memiliki jabatan setara.

---

## 8. Validation

Semua input client dianggap tidak terpercaya.

Gunakan Laravel **Form Request** untuk input request yang membutuhkan validasi.

Jangan mempercayai nilai client seperti:

* actor ID
* sender ID
* role
* permission
* position
* status
* workflow state
* timestamp tindakan

jika nilainya dapat ditentukan oleh server.

Validation dan authorization adalah dua hal berbeda.

Input yang valid belum tentu boleh dilakukan oleh user tersebut.

---

## 9. Struktur Backend

Controller harus tipis.

Tanggung jawab Controller:

1. menerima request;
2. menggunakan validated data;
3. menjalankan authorization;
4. memanggil Service/Action;
5. mengembalikan response.

Business logic ditempatkan pada Service atau Action dengan responsibility yang jelas.

Contoh:

```text
RegisterIncomingLetter
RouteIncomingLetter
CreateDisposition
ForwardDisposition
CompleteDisposition
AssignPosition
```

Hindari `God Service` dan business logic besar di Controller atau Vue.

---

## 10. Database dan Transaction

Operasi yang mengubah beberapa data terkait wajib menggunakan database transaction.

Contoh:

```text
buat disposisi
→ buat recipients
→ ubah workflow state
→ catat audit
```

harus berhasil seluruhnya atau gagal seluruhnya.

Gunakan:

* foreign key;
* unique constraint;
* index;
* database constraint;

jika database dapat membantu menjaga invariant.

Jangan hanya mengandalkan validation frontend.

---

## 11. Status Surat dan Disposisi

Status setiap cabang disposisi dan status surat secara keseluruhan adalah konsep berbeda.

Contoh:

```text
Kabag A → COMPLETED
Kabag B → IN_PROGRESS
```

surat secara keseluruhan belum boleh dianggap selesai.

Branch state menjadi dasar penentuan aggregate letter state.

Jika aggregate status disimpan di database untuk kebutuhan query/reporting, perubahannya harus dilakukan secara konsisten melalui business rule yang terpusat.

Jangan mengubah status surat secara manual dari frontend.

---

## 12. Instruksi Disposisi Harus Configurable

Disposisi dapat memiliki instruksi seperti:

* Untuk diketahui
* Untuk ditindaklanjuti
* Untuk dipelajari
* Untuk dikoordinasikan
* Untuk menghadiri
* Untuk disiapkan jawabannya
* Segera

Daftar tersebut harus dapat ditambah, dinonaktifkan, atau diubah tanpa migration maupun deployment kode.

Jangan menggunakan ENUM atau boolean column terpisah untuk setiap jenis instruksi.

Jika nantinya terdapat klasifikasi seperti `Rahasia`, tentukan secara eksplisit apakah hanya label atau benar-benar memengaruhi authorization.

---

## 13. File Surat

MVP menggunakan upload PDF hasil scan surat fisik.

Dokumen harus disimpan di **private storage**, bukan public webroot.

Akses file harus melewati backend dan authorization.

Upload minimal memvalidasi:

* MIME/type;
* extension;
* ukuran;
* validitas file.

Jangan mempercayai nama file asli sebagai storage path.

Future scanner integration tidak boleh memengaruhi domain inti.

---

## 14. Integritas Dokumen

Setiap file surat yang diregistrasi harus memiliki hash minimal SHA-256.

Hash digunakan untuk:

> membuktikan apakah isi file masih sama dengan file ketika pertama diterima.

Hash bukan encryption dan bukan access control.

File original tidak boleh diam-diam ditimpa.

Jika terdapat koreksi scan, simpan sebagai version/correction baru dan pertahankan histori.

Jangan membuat custom cryptographic signature atau custom hash-chain untuk menggantikan teknologi tanda tangan elektronik resmi.

---

## 15. Audit Trail

Audit trail wajib dan bersifat **application-level append-only**.

Tidak boleh ada workflow normal untuk mengubah atau menghapus audit record.

Minimal catat:

* actor;
* active position;
* action;
* target resource;
* timestamp server;
* perubahan state penting;
* konteks yang relevan.

Audit juga wajib untuk operasi administratif seperti:

* perubahan role;
* perubahan permission;
* perubahan user;
* assignment jabatan;
* perubahan konfigurasi workflow.

Application log dan audit log adalah dua hal berbeda.

Jangan memasukkan password, token, MFA secret, atau recovery code ke log.

Production dapat menambahkan database privilege restriction, backup retention, dan external/immutable audit storage untuk hardening tambahan.

---

## 16. Authentication dan MFA

Gunakan authentication session Laravel standar melalui Inertia.

Wajib:

* secure password hashing;
* CSRF protection;
* session regeneration;
* login rate limiting;
* secure cookies;
* session expiration;
* password recovery yang aman.

MFA wajib minimal untuk:

* Wali Kota;
* Sekda;
* System Administrator.

Operasi security-sensitive dapat meminta re-authentication.

---

## 17. Electronic Signature

Bedakan:

```text
Electronic Approval
≠
TTE resmi/tersertifikasi
```

Untuk MVP, approval/disposisi dapat dibuktikan melalui:

* authenticated user;
* MFA untuk account kritis;
* timestamp server;
* position assignment;
* audit trail;
* integrity protection.

Jangan menganggap gambar tanda tangan yang ditempel pada PDF sebagai tanda tangan digital yang aman.

Integrasi TTE resmi harus menjadi **integration boundary** terpisah dan tidak boleh membuat business logic bergantung langsung pada provider tertentu.

---

## 18. Vue + Inertia

Vue hanya menangani presentation dan interaction.

Vue boleh menyembunyikan tombol berdasarkan capability untuk UX, tetapi Laravel tetap wajib melakukan authorization ketika request diterima.

Page kompleks harus dipisahkan menjadi component berdasarkan responsibility.

Hindari giant component, tetapi jangan membuat component kecil tanpa alasan hanya demi abstraction.

Business rule tidak boleh diduplikasi di Vue.

---

## 19. Error Handling

Error harus ditangani secara eksplisit.

Jangan:

* swallow exception;
* mengekspos stack trace;
* mengekspos SQL error;
* mengekspos filesystem path;
* mengekspos credential.

Gunakan HTTP status code dengan tepat.

---

## 20. Naming dan Code Quality

Gunakan nama yang menjelaskan intent dan domain.

Hindari nama generik seperti:

```text
process()
handleData()
doAction()
data
temp
item
```

Gunakan nama seperti:

```text
createDisposition()
forwardDisposition()
incomingLetter
dispositionRecipient
activePositionAssignment
```

Function harus memiliki satu responsibility, alur sederhana, dan nesting minimal.

Gunakan early return/guard clause jika membuat alur lebih jelas.

Jangan memecah fungsi hanya untuk memenuhi batas jumlah baris tertentu.

---

## 21. Mass Assignment dan Security Data

Jangan gunakan:

```php
Model::create($request->all());
```

Gunakan validated data dan tentukan field sensitif dari server.

Gunakan `$fillable` eksplisit atau strategi mass-assignment yang sama ketatnya.

Field seperti actor, creator, workflow state, status, assignment, dan timestamp tindakan tidak boleh dipercaya dari frontend.

---

## 22. Reporting

Sistem harus mendukung laporan periodik.

Minimal desain data harus memungkinkan laporan:

* jumlah surat masuk;
* surat berdasarkan periode;
* instansi pengirim;
* surat belum diproses;
* surat sedang diproses;
* surat selesai;
* disposisi per pejabat/bagian;
* waktu penyelesaian.

Detail laporan ditentukan pada System Design.

---

## 23. Testing

Fitur kritis tidak dianggap selesai tanpa automated test.

Prioritaskan test untuk:

* authentication;
* MFA;
* RBAC;
* authorization;
* workflow hierarchy;
* multiple recipients;
* branching disposition;
* status completion;
* upload;
* file access;
* audit trail;
* privilege escalation prevention.

Selalu uji negative case.

Contoh:

```text
Kabag A dapat membuka surat yang diberikan kepadanya.
```

harus disertai:

```text
Kabag B yang tidak terlibat tidak dapat membuka surat tersebut
meskipun mengetahui ID suratnya.
```

---

## 24. Aturan untuk AI Coding Agent

Sebelum membuat fitur, AI wajib memahami:

1. requirement;
2. domain terkait;
3. hierarchy;
4. authorization;
5. audit requirement;
6. dampak terhadap data existing.

AI tidak boleh:

* melewati Form Request tanpa alasan kuat;
* menaruh business logic besar di Controller;
* mempercayai authorization dari frontend;
* menggunakan role sebagai workflow engine;
* hard-code hierarchy di banyak tempat;
* menggunakan public storage untuk dokumen surat;
* mengganti file original tanpa histori;
* menghapus audit trail;
* membuat custom cryptography;
* menonaktifkan security Laravel demi development;
* melakukan mass assignment tanpa kontrol;
* membuat abstraction prematur;
* melakukan premature optimization;
* menambahkan Staff sebagai penerima disposisi formal sebelum scope tersebut resmi ditambahkan.

Jika implementasi yang diminta bertentangan dengan security atau integritas data, risiko tersebut harus dijelaskan dan desain yang lebih aman harus dipilih.

---

## 25. Definition of Done

Fitur dianggap selesai jika:

* requirement bisnis terpenuhi;
* validation tersedia;
* authorization benar;
* hierarchy dipatuhi;
* database consistency terjaga;
* error path ditangani;
* audit tersedia bila diperlukan;
* sensitive data tidak terekspos;
* critical path memiliki test;
* naming jelas;
* implementasi tetap sederhana dan dapat dipelihara.

> **Security, correctness, integrity, traceability, dan maintainability lebih penting daripada kecepatan menambah fitur.**
