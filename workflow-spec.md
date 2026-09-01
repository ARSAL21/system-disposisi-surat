# Workflow Specification — Sistem Disposisi Surat

## 1. Tujuan

Dokumen ini mendefinisikan **state dan transition resmi** untuk submission, surat masuk, dan disposisi pada MVP.

Dokumen ini tidak mendefinisikan:

* struktur tabel;
* permission matrix;
* detail UI;
* implementation class.

Hal tersebut mengikuti dokumen masing-masing.

---

## 2. Workflow Formal MVP

```text
Bagian Umum / Tata Usaha
        ↓
Wali Kota ATAU Sekda
        ↓
Asisten I / II / III
        ↓
Satu atau lebih Kepala Bagian
        ↓
Selesai
```

Aturan:

* seluruh surat masuk dimulai dari Bagian Umum;
* initial routing hanya ke **satu** Wali Kota atau Sekda;
* Wali Kota/Sekda memilih **satu Asisten**;
* Asisten memilih **satu atau lebih Kepala Bagian**;
* Kepala Bagian merupakan terminal formal workflow MVP;
* Staff belum termasuk workflow MVP;
* hierarchy tidak boleh dilompati.

---

# 3. Submission State

State submission:

```text
DRAFT
SUBMITTED
REVISION_REQUIRED
READY_FOR_APPROVAL
INTERNAL_REVISION_REQUIRED
REGISTERED
REJECTED
```

### `DRAFT`

Submission online telah dibuat oleh Public User, tetapi belum diserahkan ke Bagian Umum. Metadata dan satu dokumen PDF aktif masih dapat diperbarui oleh pemiliknya. Draft belum merupakan intake resmi dan dapat dihapus oleh pemiliknya.

### `SUBMITTED`

Submission telah diserahkan ke antrean staf administrasi Bagian Umum. Metadata dan dokumennya immutable selama pemeriksaan, dan `submitted_at` ditetapkan oleh server.

### `REVISION_REQUIRED`

Staf administrasi menemukan kekurangan yang harus diperbaiki pengirim. Pemilik Public User dapat memperbarui metadata atau mengganti dokumen, tetapi tidak dapat menghapus submission. Setelah lengkap, pemilik mengirim ulang dan state kembali menjadi `SUBMITTED`.

### `READY_FOR_APPROVAL`

Submission telah lolos screening teknis staf dan menunggu keputusan administratif Kepala Bagian Umum. Public User dan staf tidak dapat mengubah metadata atau dokumen pada state ini.

### `INTERNAL_REVISION_REQUIRED`

Kepala Bagian Umum mengembalikan hasil screening kepada staf untuk diperbaiki secara internal. State ini tidak membuka akses perubahan kepada Public User. Staf dapat mengajukan ulang ke `READY_FOR_APPROVAL` setelah catatan internal dipenuhi.

### `REGISTERED`

Kepala Bagian Umum mengesahkan registrasi dan sistem membuat tepat satu `IncomingLetter`. State ini final pada lifecycle submission. Perubahan setelah registrasi mengikuti lifecycle surat masuk dan versioning dokumen surat.

### `REJECTED`

Kepala Bagian Umum menolak submission disertai alasan administratif formal. State ini final pada MVP dan tidak dapat dibuka kembali.

---

# 4. Submission State Transition

```text
DRAFT
  ↓
SUBMITTED
  ├──→ REVISION_REQUIRED ──→ SUBMITTED
  └──→ READY_FOR_APPROVAL
              ├──→ INTERNAL_REVISION_REQUIRED ──→ READY_FOR_APPROVAL
              ├──→ REGISTERED
              └──→ REJECTED
```

Aturan:

* hanya authenticated, verified, active Public User yang menjadi pemilik dapat menjalankan `DRAFT → SUBMITTED`;
* pemilik yang sama dapat menjalankan `REVISION_REQUIRED → SUBMITTED` setelah melakukan koreksi;
* transition hanya dilakukan server-side;
* submission wajib memiliki metadata valid dan tepat satu dokumen PDF sebelum submit;
* staf administrasi Bagian Umum hanya dapat menjalankan `SUBMITTED → REVISION_REQUIRED` atau `SUBMITTED → READY_FOR_APPROVAL`;
* Kepala Bagian Umum menjalankan transition dari `READY_FOR_APPROVAL` ke `INTERNAL_REVISION_REQUIRED`, `REGISTERED`, atau `REJECTED`;
* `INTERNAL_REVISION_REQUIRED → READY_FOR_APPROVAL` merupakan tanggung jawab staf administrasi;
* staf tidak dapat meregistrasi atau menolak submission dan Kepala Bagian Umum tidak melakukan screening teknis awal;
* withdrawal dan reopening state final tidak diperbolehkan pada MVP;
* pelanggaran state menghasilkan conflict dan tidak boleh diatasi dengan menimpa state dari frontend.

---

# 5. Incoming Letter State

State surat:

```text
REGISTERED
ROUTED
IN_PROGRESS
COMPLETED
```

### `REGISTERED`

Surat telah:

* dicatat Bagian Umum;
* memiliki metadata;
* memiliki dokumen yang valid;

tetapi belum diarahkan ke Wali Kota/Sekda.

### `ROUTED`

Surat telah diarahkan ke:

```text
Wali Kota ATAU Sekda
```

dan menunggu disposisi pertama.

### `IN_PROGRESS`

Surat sudah memasuki proses disposisi.

State ini mencakup kondisi ketika surat:

* berada pada Asisten;
* sudah diteruskan ke Kepala Bagian;
* memiliki satu atau lebih branch yang belum selesai.

### `COMPLETED`

Seluruh terminal branch Kepala Bagian yang aktif telah selesai.

`COMPLETED` merupakan state final MVP.

---

# 6. Letter State Transition

```text
REGISTERED
    ↓
ROUTED
    ↓
IN_PROGRESS
    ↓
COMPLETED
```

Transition mundur tidak diperbolehkan pada MVP.

Tidak boleh:

```text
COMPLETED → IN_PROGRESS
IN_PROGRESS → ROUTED
ROUTED → REGISTERED
```

Correction atau reopening belum menjadi bagian MVP dan tidak boleh diimplementasikan tanpa perubahan workflow specification.

---

## 6.1 Koreksi Versi Dokumen pada State `REGISTERED`

Pembuatan versi koreksi dokumen resmi bukan state transition surat. Operasi ini
hanya diperbolehkan selama:

```text
incoming_letters.status = REGISTERED
```

Setelah surat berubah menjadi `ROUTED`, `IN_PROGRESS`, atau `COMPLETED`, dokumen
acuan tidak dapat diganti pada MVP. Koreksi menghasilkan `letter_documents`
baru yang immutable dan menunjuk versi sebelumnya; status surat tetap
`REGISTERED` dan seluruh versi lama dipertahankan.

Operasi wajib menolak:

* actor tanpa permission `document-versions.create`;
* actor tanpa Position Assignment aktif sebagai Kepala Bagian Umum;
* surat yang tidak lagi `REGISTERED`;
* berkas non-PDF, terlalu besar, atau SHA-256 identik;
* hubungan versi, disk, path, MIME, hash, atau ukuran yang tidak konsisten.

Pembuatan metadata versi dan audit `DOCUMENT_VERSION_CREATED` harus atomic.
Karena filesystem tidak transactional, file baru wajib dibersihkan jika
transaction database gagal. Tidak ada transition baru, reopening, atau
perubahan status yang diperkenalkan oleh versioning.

---

# 7. Initial Route State

Routing Bagian Umum ke Wali Kota/Sekda menggunakan:

```text
PENDING
COMPLETED
```

### `PENDING`

Surat sudah diarahkan tetapi Wali Kota/Sekda belum membuat disposisi pertama.

### `COMPLETED`

Wali Kota/Sekda telah membuat disposisi yang valid kepada satu Asisten.

Transition:

```text
PENDING
   ↓
COMPLETED
```

Route historis tidak dihapus setelah selesai.

## 7.1 Implementasi Routing Awal M5

Routing awal hanya dapat dibuat ketika seluruh invariant berikut terpenuhi:

* actor memiliki permission `letter-routing.create`;
* actor merupakan account `INTERNAL` aktif dan terverifikasi;
* actor mempunyai tepat satu Position Assignment aktif sebagai
  `SECTION_HEAD` pada unit `BAGIAN_UMUM`;
* `incoming_letters.status = REGISTERED` dan surat belum memiliki route;
* dokumen resmi terkini lolos storage metadata guard;
* tujuan merupakan satu Position aktif pada level `EXECUTIVE_ENTRY` dengan
  tepat satu pemegang assignment aktif yang merupakan account internal aktif
  dan terverifikasi.

Dalam satu database transaction, Action mengunci surat, dokumen terkini,
assignment actor, Position tujuan, dan assignment tujuan, kemudian:

```text
create letter_routes(status = PENDING)
incoming_letters.status: REGISTERED -> ROUTED
append audit LETTER_ROUTED
```

Kegagalan penulisan audit membatalkan route dan perubahan status surat. Unique
constraint `letter_routes.incoming_letter_id` mencegah dua routing awal akibat
request bersaing. M5 tidak menyediakan reroute, update, atau delete.

Inbox M5 bersifat read-only dan hanya menampilkan route `PENDING` yang
`recipient_position_id`-nya cocok dengan Position Assignment eksekutif aktif
pengguna. Pembuatan disposisi pertama dan transisi route ke `COMPLETED` baru
menjadi tanggung jawab M6.

Kontrak HTTP M5:

* permission tidak dimiliki: `403`;
* Position atau resource tidak sesuai: `404`;
* tujuan/input tidak valid: `422`;
* state berubah atau metadata/file dokumen rusak: `409`;
* rate limit terlampaui: `429`.

---

# 8. Disposition Recipient State

Setiap recipient branch menggunakan:

```text
PENDING
IN_PROGRESS
COMPLETED
```

### `PENDING`

Recipient telah menerima assignment tetapi belum memulai tindakan.

### `IN_PROGRESS`

Recipient sedang menangani branch tersebut.

### `COMPLETED`

Kewajiban recipient pada branch tersebut telah selesai.

Transition normal:

```text
PENDING
   ↓
IN_PROGRESS
   ↓
COMPLETED
```

Transition langsung:

```text
PENDING → COMPLETED
```

boleh terjadi jika aksi yang menyelesaikan tanggung jawab recipient dilakukan langsung tanpa membutuhkan fase kerja terpisah.

---

# 9. Wali Kota / Sekda

Bagian Umum hanya boleh melakukan:

```text
Bagian Umum
    ↓
Wali Kota
```

atau:

```text
Bagian Umum
    ↓
Sekda
```

Tidak boleh diarahkan ke keduanya sekaligus pada MVP.

Wali Kota/Sekda hanya dapat menyelesaikan initial route dengan membuat disposisi:

```text
Wali Kota / Sekda
        ↓
satu Asisten
```

Tidak boleh:

```text
Wali Kota → Kepala Bagian
Sekda → Kepala Bagian
```

Ketika disposisi pertama berhasil dibuat secara transactional:

```text
Initial Route → COMPLETED
Incoming Letter → IN_PROGRESS
Assistant Recipient → PENDING
```

---

# 10. Asisten

Asisten menerima satu branch dari Wali Kota/Sekda.

Asisten dapat meneruskan surat kepada:

```text
1..N Kepala Bagian
```

dalam satu tindakan disposisi.

Contoh valid:

```text
Asisten II
   ├── Kabag Kesehatan
   └── Kabag Aset
```

Contoh invalid:

```text
Asisten II → Asisten I
Asisten II → Sekda
```

Ketika disposisi Asisten berhasil:

```text
Assistant Branch → COMPLETED
```

dan setiap Kepala Bagian memperoleh branch:

```text
SECTION_HEAD Branch → PENDING
```

Pembuatan seluruh recipient wajib atomic.

Jika satu recipient gagal dibuat, seluruh disposisi gagal.

---

# 11. Kepala Bagian

Kepala Bagian merupakan terminal workflow MVP.

Kepala Bagian tidak dapat membuat disposisi formal lanjutan.

Branch Kepala Bagian dapat bergerak:

```text
PENDING
   ↓
IN_PROGRESS
   ↓
COMPLETED
```

Kepala Bagian dapat menambahkan catatan tindak lanjut selama branch masih aktif.

Branch hanya dapat ditandai selesai oleh user yang sedang memegang Position tersebut dan mempunyai permission yang diperlukan.

---

# 12. Multiple Branch Completion

Setiap Kepala Bagian mempunyai lifecycle independen.

Contoh:

```text
Asisten II
   ├── Kabag Kesehatan → COMPLETED
   └── Kabag Aset      → IN_PROGRESS
```

Maka:

```text
Incoming Letter = IN_PROGRESS
```

Surat belum selesai.

Jika:

```text
Kabag Kesehatan → COMPLETED
Kabag Aset      → COMPLETED
```

maka:

```text
Incoming Letter → COMPLETED
```

Rule utama:

> Surat selesai hanya ketika seluruh terminal branch aktif telah `COMPLETED`.

---

# 13. Aggregate Letter State

Branch merupakan sumber utama keadaan workflow.

`incoming_letters.status` adalah aggregate state yang digunakan untuk:

* inbox;
* filter;
* reporting;
* dashboard.

Aggregate state tidak boleh ditentukan oleh frontend.

Conceptual rule:

```text
belum memiliki route
→ REGISTERED

route masih pending
→ ROUTED

workflow sudah berjalan dan masih ada branch aktif
→ IN_PROGRESS

seluruh terminal branch selesai
→ COMPLETED
```

Perubahan branch dan aggregate letter state harus dilakukan dalam transaction yang sama ketika keduanya berkaitan.

---

# 14. Position Assignment dan Pergantian Pejabat

Pekerjaan aktif melekat pada:

```text
Position
```

bukan user tertentu.

Contoh:

```text
Disposition Recipient
→ Kepala Bagian Hukum
```

Jika pemegang jabatan berganti ketika branch masih aktif:

```text
Pejabat lama
    ↓ selesai masa assignment

Pejabat baru
    ↓ active assignment
```

branch tetap aktif pada `Kepala Bagian Hukum`.

Pemegang Position yang baru dapat melanjutkan pekerjaan sesuai authorization.

Namun tindakan historis tetap menyimpan:

```text
User
+
Position Assignment
+
Timestamp
```

yang berlaku ketika tindakan tersebut dilakukan.

Pergantian pejabat **tidak mengubah state workflow secara otomatis**.

---

# 15. Invalid Transition

Backend wajib menolak transition yang:

* dilakukan oleh Position yang tidak berwenang;
* melompati hierarchy;
* menarget Position yang tidak valid;
* mencoba mengubah branch yang sudah `COMPLETED`;
* mencoba menyelesaikan surat secara langsung;
* mencoba mengubah aggregate status dari frontend;
* menggunakan Position Assignment yang sudah tidak aktif untuk tindakan baru.

UI tidak cukup untuk mencegah transition invalid.

Semua aturan tetap diverifikasi server-side.

---

# 16. Concurrency dan Atomicity

Transition kritis harus dilakukan secara transactional.

Contoh:

```text
Asisten membuat disposisi
        ↓
create disposition
        ↓
create recipient A
        ↓
create recipient B
        ↓
complete Assistant branch
        ↓
recalculate Letter state
        ↓
create audit event
```

Semua langkah tersebut merupakan satu logical operation.

Tidak boleh menghasilkan kondisi parsial seperti:

```text
Recipient A berhasil dibuat
Recipient B gagal
Assistant sudah COMPLETED
```

Jika operasi tidak lengkap, transaction harus rollback.

---

# 17. Audit pada State Transition

Transition penting wajib menghasilkan audit event.

Minimal:

```text
LETTER_REGISTERED
LETTER_ROUTED

DISPOSITION_CREATED
DISPOSITION_STARTED
DISPOSITION_COMPLETED

LETTER_COMPLETED
```

Audit merekam:

```text
actor
position assignment
resource
previous state
new state
server timestamp
relevant context
```

Audit record tidak menggantikan business state.

Audit adalah histori dari perubahan business state tersebut.

---

# 18. Correction, Withdrawal, dan Reopening

MVP **belum** memiliki transition:

```text
WITHDRAWN
CANCELLED
REOPENED
```

Jangan menambahkannya sebagai antisipasi.

Jika kebutuhan nyata ditemukan seperti:

* salah memilih Asisten;
* salah memilih Kepala Bagian;
* surat sudah selesai tetapi perlu diproses kembali;

workflow tersebut harus dibahas secara eksplisit sebelum state baru ditambahkan.

Historical disposition tidak boleh diselesaikan dengan menghapus record lama.

---

# 19. Workflow Invariants

Invariant MVP:

1. Submission online selalu dimulai sebagai `DRAFT` dan hanya dapat diketahui pemiliknya.
2. Public User hanya dapat mengubah submission pada `DRAFT` atau `REVISION_REQUIRED`; hanya `DRAFT` yang dapat dihapus.
3. Submission hanya dapat dikirim jika memiliki tepat satu dokumen PDF.
4. Submission `REGISTERED` menghasilkan tepat satu Incoming Letter.
5. Surat selalu dimulai dari Bagian Umum.
6. Surat hanya memiliki satu initial route aktif.
7. Initial route hanya menuju Wali Kota atau Sekda.
8. Wali Kota/Sekda hanya meneruskan ke satu Asisten.
9. Asisten meneruskan ke satu atau lebih Kepala Bagian.
10. Kepala Bagian adalah terminal formal MVP.
11. Hierarchy tidak dapat dilompati.
12. Setiap recipient mempunyai lifecycle sendiri.
13. Branch `COMPLETED` tidak dapat dimodifikasi menjadi aktif kembali.
14. Surat `COMPLETED` tidak dapat kembali ke state sebelumnya pada MVP.
15. Surat selesai hanya ketika semua terminal branch aktif selesai.
16. State transition dilakukan server-side.
17. State aggregate tidak dikendalikan frontend.
18. Tindakan baru wajib menggunakan Position Assignment aktif.
19. Historical actor tetap menggunakan assignment yang berlaku saat tindakan dilakukan.

---

# 20. State Summary

```text
SUBMISSION

DRAFT
  ↓
SUBMITTED
  ├──→ REVISION_REQUIRED ──→ SUBMITTED
  └──→ READY_FOR_APPROVAL
              ├──→ INTERNAL_REVISION_REQUIRED ──→ READY_FOR_APPROVAL
              ├──→ REGISTERED
              └──→ REJECTED
```

```text
INCOMING LETTER

REGISTERED
    ↓
ROUTED
    ↓
IN_PROGRESS
    ↓
COMPLETED
```

```text
INITIAL ROUTE

PENDING
    ↓
COMPLETED
```

```text
DISPOSITION RECIPIENT

PENDING
    ↓
IN_PROGRESS
    ↓
COMPLETED

atau

PENDING
    ↓
COMPLETED
```

Struktur ini adalah workflow resmi MVP. Penambahan state atau transition baru harus dilakukan berdasarkan requirement bisnis nyata dan memperbarui dokumen ini terlebih dahulu.
