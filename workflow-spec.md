# Workflow Specification — Sistem Disposisi Surat

> **Aturan aktif.** Penyebutan lama `EXECUTIVE_ENTRY` atau “Wali Kota/Sekda”
> sebagai penerima setara pada catatan milestone historis di bawah ini telah
> digantikan oleh hierarchy `MAYOR → REGIONAL_SECRETARY → ASSISTANT →
> SECTION_HEAD`. Bagian Umum memilih jalur langsung ke Sekda atau melalui Wali
> Kota; Wali Kota hanya meneruskan secara formal kepada Sekda; Sekda yang
> mendisposisikan kepada Asisten.

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
Sekda (langsung atau melalui Wali Kota)
        ↓
Asisten I / II / III
        ↓
Satu atau lebih Kepala Bagian
        ↓
Selesai
```

Aturan:

* seluruh surat masuk dimulai dari Bagian Umum;
* initial routing memilih **langsung ke Sekda** atau **melalui Wali Kota**;
* Wali Kota hanya memberi arahan formal kepada Sekda;
* Sekda memilih **satu sampai tiga Asisten**;
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

Submission manual dibuat langsung pada state ini karena input surat fisik,
validasi PDF, dan screening lengkap dilakukan oleh Staf Bagian Umum dalam satu
aksi atomik. Hal tersebut bukan registrasi resmi dan tidak melewati keputusan
Kepala Bagian Umum.

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
* pencatatan source `MANUAL` membuat submission `READY_FOR_APPROVAL`, dokumen,
  screening review, dan audit dalam satu transaksi; email pengirim boleh kosong
  dan tidak membuat akun publik;
* Kepala Bagian Umum menjalankan transition dari `READY_FOR_APPROVAL` ke `INTERNAL_REVISION_REQUIRED`, `REGISTERED`, atau `REJECTED`;
* `INTERNAL_REVISION_REQUIRED → READY_FOR_APPROVAL` merupakan tanggung jawab staf administrasi;
* hanya submission manual pada `INTERNAL_REVISION_REQUIRED` yang dapat diperbaiki
  oleh Staf; submission online tetap dikoreksi oleh Public User pada
  `REVISION_REQUIRED`;
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

tetapi belum diarahkan langsung kepada Sekda atau melalui Wali Kota.

### `ROUTED`

Surat telah diarahkan ke:

```text
Sekda, atau Wali Kota sebagai jalur formal menuju Sekda
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

Routing Bagian Umum ke Sekda (langsung atau melalui Wali Kota) menggunakan:

```text
PENDING
COMPLETED
```

### `PENDING`

Surat sudah diarahkan tetapi penerima pada jalur terpilih belum melakukan tindakan berikutnya.

### `COMPLETED`

Sekda telah membuat disposisi yang valid kepada satu sampai tiga Asisten.

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
* tujuan ditentukan server dari `route_path`: Position `SEKDA` aktif pada level
  `REGIONAL_SECRETARY`, atau Position `WALI_KOTA` aktif pada level `MAYOR`,
  masing-masing dengan
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

# 9. Wali Kota dan Sekda

Bagian Umum memilih satu jalur routing awal: langsung kepada Sekda, atau
melalui Wali Kota kemudian kepada Sekda. Kedua jalur tidak dapat dipilih
bersamaan.

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

Wali Kota tidak membuat disposisi kepada Asisten. Ia hanya dapat memberi arahan
formal berlabel kepada Sekda. Arahan ini membuat satu recipient Sekda `PENDING`,
menyelesaikan route, dan mengubah surat menjadi `IN_PROGRESS`.

Sekda adalah satu-satunya pejabat yang dapat meneruskan substansi kepada
Asisten:

```text
Sekda
        ↓
satu sampai tiga Asisten
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
Setiap Assistant Recipient → PENDING
```

---

## 9.1 Implementasi Position-based Routing M6.1

Disposisi kepada Asisten hanya dapat dibuat oleh Position `SEKDA` aktif. Pada
route langsung, sumbernya adalah `letter_routes.status = PENDING` ketika surat
masih `ROUTED`. Pada jalur melalui Wali Kota, sumbernya adalah recipient Sekda
`PENDING` dari arahan Wali Kota. Pilihan tujuan dibatasi
server-side kepada Position aktif level `ASSISTANT` dengan tepat satu pemegang
internal, aktif, dan terverifikasi; Position actor sendiri, Position Asisten yang
sedang dipegang oleh user actor, Position sederajat, dan `SECTION_HEAD` tidak
pernah menjadi tujuan sah.

Pada disposisi berikutnya, actor wajib memegang Position `ASSISTANT` yang
menjadi recipient sumber. Target hanya boleh Position aktif level
`SECTION_HEAD` dengan unit aktif yang `parent_id`-nya tepat sama dengan unit
aktif Position Asisten sumber. Scope berasal dari recipient Asisten yang sedang
dibuka, bukan gabungan semua Position Asisten yang mungkin dipegang actor.
Target lintas unit koordinasi ditolak sebagai resource di luar scope; nama
jabatan atau mapping hierarchy yang di-hardcode tidak boleh digunakan.

Satu transaksi mengunci akun actor, route atau recipient sumber, surat, dokumen resmi terkini,
assignment actor, seluruh Position tujuan, assignment tujuan, akun pemegang tujuan,
serta seluruh label instruksi aktif. Transaksi kemudian membuat satu
`dispositions`, satu sampai tiga recipient `PENDING`, relasi label,
mengubah route menjadi `COMPLETED`, mengubah surat menjadi `IN_PROGRESS`, dan
menulis audit `DISPOSITION_CREATED`. Kegagalan pada salah satu langkah
membatalkan seluruh perubahan. Unique `source_route_id` mencegah dua disposisi
pertama akibat request bersaing.

Inbox Asisten berasal dari authorized query `disposition_recipients` berdasarkan
Position Assignment aktif, bukan hasil filter collection. Permission yang tidak
dimiliki menghasilkan `403`; Position/resource tidak cocok menghasilkan `404`;
input tujuan atau label tidak valid menghasilkan `422`; stale state dan konflik
metadata menghasilkan `409`; rate limit menghasilkan `429`.

---

# 10. Asisten

Setiap Asisten terpilih menerima branch independen dari Sekda.

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

## 14.1 Implementasi Independent Branch Lifecycle M6.3

Setiap recipient level `SECTION_HEAD` adalah cabang terminal independen.
Pemegang Position Kepala Bagian yang sama, dengan `dispositions.view` dan
`dispositions.process`, dapat menjalankan transition berikut:

```text
PENDING -> IN_PROGRESS -> COMPLETED
PENDING ----------------> COMPLETED
```

`started_at` hanya diisi pada transition pertama. Penyelesaian langsung dari
`PENDING` mempertahankan `started_at = null`. Follow-up hanya dapat ditambahkan
saat cabang `IN_PROGRESS`; cabang `COMPLETED` bersifat final dan tidak dapat
ditambah catatan, dibuka kembali, atau diselesaikan ulang.

Endpoint produksi:

```text
POST /back-office/dispositions/inbox/recipients/{dispositionRecipient}/start
POST /back-office/dispositions/inbox/recipients/{dispositionRecipient}/follow-ups
POST /back-office/dispositions/inbox/recipients/{dispositionRecipient}/complete
```

Catatan follow-up dan hasil penyelesaian wajib di-trim dan berukuran 10–2.000
karakter. Actor, status, timestamp, dan Position Assignment selalu ditentukan
server. Ketiga endpoint berbagi limiter 60 request/menit per user dan 120
request/menit per IP.

Setiap Action mengunci surat, seluruh recipient terminal berdasarkan ID, actor,
kemudian Position Assignment aktif dengan urutan yang sama. State surat wajib
`IN_PROGRESS`; graph tanpa cabang terminal atau hierarchy yang tidak konsisten
ditolak dengan `409`. Satu cabang selesai tidak mengubah cabang lain. Surat baru
berubah menjadi `COMPLETED` setelah seluruh cabang `SECTION_HEAD` selesai.
Karena surat dikunci lebih dahulu, penyelesaian dua cabang terakhir yang
bersaing hanya dapat menghasilkan satu transition surat dan satu audit
`LETTER_COMPLETED`.

Audit `DISPOSITION_STARTED`, `FOLLOW_UP_ADDED`, `DISPOSITION_COMPLETED`, dan
`LETTER_COMPLETED` ditulis atomik. Isi jurnal dan hasil akhir tidak dimasukkan
ke metadata Activity Console umum; detail tersebut hanya tersedia melalui
presenter recipient yang terotorisasi.

## 14.2 Acceptance Branch Completion M7.1

M7.1 tidak menambah transition baru. Endpoint `complete` dari M6.3 menjadi
satu-satunya jalur penyelesaian cabang terminal. Kontrak finalnya adalah:

* hanya pemegang aktif Position `SECTION_HEAD` recipient dengan
  `dispositions.view` dan `dispositions.process` yang dapat menyelesaikan;
* hasil penyelesaian wajib di-trim dan sepanjang 10–2.000 karakter;
* penyelesaian dari `PENDING` mempertahankan `started_at = null`;
* penyelesaian dari `IN_PROGRESS` mempertahankan timestamp mulai semula;
* `COMPLETED` tidak dapat diedit, dihapus, dibuka kembali, atau diselesaikan
  ulang;
* user dan Position Assignment historis wajib konsisten dengan Position
  recipient;
* kegagalan audit cabang maupun audit aggregate menggagalkan seluruh transaction.

Pergantian pemegang jabatan tidak mengubah `recipient_position_id`. Pemegang
lama ditolak setelah assignment berakhir dan pemegang baru melanjutkan branch
dengan assignment aktifnya. Frontend hanya mengirim hasil penyelesaian serta
tidak pernah mengirim actor, status, timestamp, atau aggregate letter state.

Release gate M7.1 mencakup smoke test pada database MySQL terisolasi. Dua
request yang menyelesaikan dua cabang terakhir secara bersamaan harus membuat
kedua cabang `COMPLETED`, satu surat `COMPLETED`, dan tepat satu audit
`LETTER_COMPLETED`. Dua request bersamaan pada cabang yang sama menghasilkan
satu keberhasilan dan satu respons stale-state `409`. Suite SQLite tetap
menguji invariant transaksi, tetapi bukan pengganti verifikasi row lock MySQL.
Test ini dijalankan melalui grup `mysql-concurrency` dengan
`RUN_MYSQL_CONCURRENCY_TESTS=true`; test gagal tertutup jika nama database tidak
mengikuti pola khusus `disposisi_surat_concurrency_test_*`.

---

## 14.3 Aggregate Letter State M7.2

M7.2 menetapkan agregasi dari M6.3 sebagai satu-satunya sumber perubahan status
surat setelah disposisi dimulai. Selama sedikitnya satu recipient terminal
`SECTION_HEAD` belum selesai, surat tetap `IN_PROGRESS`. Setelah seluruh
recipient terminal selesai, service aggregate mengubah surat menjadi
`COMPLETED` di dalam transaction dan lock surat yang sama dengan penyelesaian
cabang terakhir.

Graph tanpa cabang terminal atau graph dengan hierarchy/status yang tidak
konsisten ditolak dengan `409`. Hanya transaksi yang benar-benar melakukan
transition surat yang menulis audit `LETTER_COMPLETED`; frontend tidak dapat
menentukan atau mengubah aggregate letter state.

---

## 14.4 Laporan Periodik M7.3

Reporting bersifat read-only dan tidak memperkenalkan transition, status, atau
jalur penyelesaian baru. Sumber waktu laporan mengikuti kejadian domain:

* submission online/manual dihitung pada `submitted_at`;
* surat diterima dihitung pada `incoming_letters.received_at`;
* mulai diproses dihitung pada disposition pertama yang bersumber dari initial
  route;
* surat selesai hanya dihitung ketika terdapat recipient terminal dan seluruh
  recipient `SECTION_HEAD` selesai, memakai timestamp penyelesaian terakhir;
* durasi cabang dimulai dari `received_at` recipient dan berakhir di
  `completed_at` recipient tersebut.

Filter tanggal inklusif memakai zona waktu kantor dan dibatasi maksimum 366
hari. Pemilihan basis kejadian hanya mengubah collection surat yang ditampilkan;
masing-masing KPI dan seri tren tetap dihitung dari timestamp domainnya pada
periode yang sama. Pagination tidak boleh memengaruhi aggregate.

Visibility drilldown mengikuti graph yang sudah tersimpan: eksekutif global,
Asisten hanya subtree recipient miliknya, dan Kepala Bagian hanya terminal
branch Position miliknya. Kepala Bagian Umum mempunyai aggregate global tetapi
tidak boleh memakai aggregate tersebut untuk membaca detail atau catatan cabang
lain. Semua batas diterapkan di query database. Beberapa Position Assignment
aktif milik user yang sama digabung sebagai union scope.

Ekspor tidak mengubah state dan tidak memuat instruksi, follow-up, atau hasil
akhir. Detail UI boleh memuat ketiganya hanya setelah Policy surat dan scope
cabang lolos. Permission kurang menghasilkan `403`, Position/resource di luar
scope menghasilkan `404`, filter invalid menghasilkan `422`, dan limiter ekspor
menghasilkan `429`.

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
FOLLOW_UP_ADDED
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
7. Initial route langsung menuju Sekda atau menuju Wali Kota untuk arahan formal kepada Sekda.
8. Hanya Sekda meneruskan ke satu sampai tiga Asisten dalam satu tindakan disposisi atomik.
9. Asisten meneruskan ke satu atau lebih Kepala Bagian.
10. Satu Position Kepala Bagian hanya boleh menjadi recipient terminal satu Asisten dalam surat yang sama.
11. Kepala Bagian adalah terminal formal MVP.
12. Hierarchy tidak dapat dilompati.
13. Setiap recipient mempunyai lifecycle sendiri.
14. Branch `COMPLETED` tidak dapat dimodifikasi menjadi aktif kembali.
15. Surat `COMPLETED` tidak dapat kembali ke state sebelumnya pada MVP.
16. Surat selesai hanya ketika semua terminal branch aktif selesai.
17. State transition dilakukan server-side.
18. State aggregate tidak dikendalikan frontend.
19. Tindakan baru wajib menggunakan Position Assignment aktif.
20. Historical actor tetap menggunakan assignment yang berlaku saat tindakan dilakukan.

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

## Telaah Staf Ahli pada jalur Wali Kota

Telaah tidak menciptakan disposisi baru dan tidak mengubah status surat:

```text
PENDING -> REPORTED
PENDING -> CANCELLED
```

Hanya Wali Kota pemegang active Position pada initial route `VIA_MAYOR` dapat
meminta atau membatalkan telaah, maksimal tiga Position Staf Ahli berbeda pada
satu waktu. Staf Ahli hanya dapat melaporkan tugas untuk Position-nya sendiri.
Wali Kota tidak dapat meneruskan arahan ke Sekda selama masih terdapat telaah
`PENDING`. Sekda hanya memperoleh ringkasan administratif dan tidak dapat
mengakses instruksi, laporan, maupun dokumen telaah.

---

# 21. Workflow Dossier Balasan M8.2

```text
RESPONSE DOSSIER

OPEN -> FINALIZED
```

Dossier dibuka atomik ketika cabang terminal pertama diselesaikan. Membuka
dossier tidak mengubah status surat. Dossier hanya dapat difinalisasi oleh
eksekutif penerima routing awal setelah `IncomingLetter::COMPLETED` dan minimal
satu mandat `AUTHORIZED` tersedia.

Dokumen tidak mempunyai transition yang menimpa versi:

```text
version N READY
    -> RETURNED (review append-only)
    -> version N+1 READY
```

Kepala Bagian dapat mengunggah bahan hanya untuk recipient Position-nya yang
sudah `COMPLETED`. Asisten dapat membuat proposal hanya setelah seluruh cabang
Kepala Bagian di bawah recipient Asisten tersebut selesai. Pengembalian bahan
tidak membuka kembali cabang disposisi. Versi yang sudah menjadi sumber
proposal, konsolidasi, atau mandat dikunci dari pengembalian dan revisi. Dokumen
tingkat berikutnya menyimpan hubungan sumber secara eksplisit dan versi
revisinya mewarisi hubungan tersebut.

Eksekutif penerima awal dapat mengunggah konsolidasi, memilih versi proposal
terkini yang tidak dikembalikan, membuat beberapa mandat, dan memilih dirinya
atau Asisten yang benar-benar terlibat sebagai penandatangan substantif. Asisten
tidak memperoleh permission permanen dari mandat. Setelah dossier `FINALIZED`,
kontribusi, review, revisi, dan mandat baru ditolak sebagai stale state `409`.

Status mandat M8.2 hanya:

```text
AUTHORIZED
```

Transition penomoran hingga pengiriman baru ditambahkan pada M8.3.

---

# 22. Workflow Penerbitan Surat Keluar M8.3

```text
AUTHORIZED
    -> NUMBER_ASSIGNED
    -> SIGNED_DOCUMENT_UPLOADED
    -> ADMIN_VERIFIED
    -> DELIVERED

AUTHORIZED -> WITHDRAWN
```

Aturan transition:

* penomoran hanya boleh dilakukan ketika dossier `FINALIZED` dan menetapkan
  nomor, tahun agenda, tanggal surat, Petugas, Position Assignment, serta waktu
  server secara atomik;
* kombinasi nomor dan tahun agenda harus unik;
* hanya Position penyusun dokumen sumber atau Petugas Bagian Umum yang dapat
  mengunggah PDF final;
* upload pertama hanya dari `NUMBER_ASSIGNED`; upload revisi hanya diizinkan
  setelah versi terkini memperoleh review `RETURNED`;
* review `VERIFIED` dan `RETURNED` append-only, tepat satu keputusan per versi;
* verifikasi hanya dapat dilakukan Kepala Bagian Umum terhadap versi terkini;
* pengiriman hanya dapat dilakukan Petugas setelah versi terkini terverifikasi;
* pengajuan online selalu dipublikasikan melalui portal dan mendapat notifikasi
  tautan login tanpa lampiran;
* surat manual wajib mencatat metode non-portal, penerima, dan waktu penyerahan;
* `DELIVERED`, `WITHDRAWN`, versi dokumen, review, dan bukti pengiriman tidak
  dapat diedit atau dihapus;
* mandat yang sudah diberi nomor tidak dapat ditarik dan mandat aktif terakhir
  pada dossier final tidak boleh ditarik;
* konflik state atau graph menghasilkan `409`, sedangkan input tidak valid dan
  duplikasi PDF/nomor menghasilkan `422`.

Lifecycle dossier diperluas menjadi:

```text
OPEN -> FINALIZED -> FULFILLED
```

`FULFILLED` hanya terjadi setelah seluruh mandat non-`WITHDRAWN` berstatus
`DELIVERED`. Transition dihitung di dalam transaction pengiriman dengan lock
surat masuk, dossier, seluruh mandat terurut, Position Assignment aktor, dan
audit. Penyelesaian disposisi internal tetap memakai
`IncomingLetter::COMPLETED` dan tidak menunggu publikasi balasan.

Status yang ditampilkan kepada pemohon diturunkan tanpa menambah state surat
masuk:

```text
IN_PROCESS          disposisi internal belum selesai
PREPARING_RESPONSE  disposisi selesai, belum ada balasan terkirim
RESPONSE_AVAILABLE  minimal satu balasan resmi sudah terkirim
```

Koreksi setelah `DELIVERED` tidak mengubah surat lama. Koreksi harus menjadi
surat keluar baru dengan nomor baru dan referensi
`corrects_outgoing_letter_id` ke surat sebelumnya.

---

# 23. Persiapan Surat Keluar Mandiri M10.1â€“M10.3

Tahap ini terpisah dari mandat balasan M8. Ia menyiapkan surat keluar mandiri
oleh unit teknis dan belum memberi nomor, tanda tangan, maupun pengiriman.

```text
DRAFT
  -> SECTION_REVIEW
  -> ASSISTANT_REVIEW
  -> AWAITING_NUMBER

SECTION_REVIEW   -> REVISION_REQUIRED
ASSISTANT_REVIEW -> REVISION_REQUIRED
REVISION_REQUIRED -> SECTION_REVIEW
```

Aturan:

* hanya pemegang Position `UNIT_STAFF` aktif pada unit draft yang dapat membuat,
  mengubah metadata, mengunggah versi PDF, dan mengajukan konsep;
* konsep selalu masuk ke Kabag (`SECTION_HEAD`) unit yang sama terlebih dahulu;
* hanya setelah persetujuan Kabag, Asisten pada unit induk dapat memeriksa;
* pengembalian oleh Kabag maupun Asisten menghasilkan `REVISION_REQUIRED`;
  upload revisi tidak mengubah keputusan lama dan pengajuan ulang selalu kembali
  ke `SECTION_REVIEW`, sehingga tidak ada bypass Kabag;
* persetujuan Asisten menghasilkan `AWAITING_NUMBER`; M10.4 akan memulai
  penomoran dan tahap persetujuan/pengesahan berikutnya;
* PDF dan DOCX versi sebelumnya immutable. Hash duplikat dalam satu seri ditolak;
* template harus DOCX valid tanpa macro, embedded object, atau external relationship;
* kondisi workflow basi atau hierarchy/assignment tidak sah menghasilkan `409`/`404`;
  input tidak valid menghasilkan `422`.

---

# 24. Nomor dan Pengesahan Surat Keluar Mandiri M10.4-M10.5

```text
AWAITING_NUMBER
  -> NUMBER_ASSIGNED
  -> SEKDA_REVIEW
       -> READY_FOR_DELIVERY       (QR disahkan Sekda)
       -> AWAITING_MANUAL_SIGNATURE
            -> MANUAL_SCAN_REVIEW
                 -> READY_FOR_DELIVERY | REVISION_REQUIRED
       -> REVISION_REQUIRED

READY_FOR_DELIVERY -> DELIVERED
REVISION_REQUIRED  -> SECTION_REVIEW
```

Penomoran dan penyerahan ke meja Sekda adalah satu operasi atomik Petugas agar
surat bernomor tidak tersangkut pada antrean administratif. Nomor dan tanggal
tidak dapat diubah. Sekda saja dapat memilih QR, tanda tangan fisik, atau
mengembalikan konsep sebelum pengesahan. QR menghasilkan versi PDF final baru;
PDF sumber dan PDF ber-QR memiliki hash terpisah. Tanda tangan fisik dilakukan
di luar aplikasi; Staf atau Kabag unit asal mengunggah scan, lalu hanya Kabag
unit asal yang dapat memeriksanya. Surat siap kirim dapat dikirim Staf penyusun
atau Kabag aktif unit asal; pengiriman tidak mengubah draft maupun lifecycle M8.

## 24.1 Pengiriman dan Koreksi Surat Mandiri M10.6

```text
READY_FOR_DELIVERY -> DELIVERED
DELIVERED -> konsep koreksi baru (DRAFT) -> review normal -> nomor baru -> pengesahan -> DELIVERED
```

Aturan:

* Staf hanya mengirim surat yang ia susun pada unitnya sendiri; Kabag hanya
  mengirim surat unitnya sendiri.
* Email menerbitkan tautan token acak yang hanya disimpan hash, berlaku tujuh
  hari, dapat dikirim ulang atau dicabut, dan tidak pernah membawa lampiran PDF.
* Metode selain email harus mencatat penerima dan waktu penyerahan; semua bukti
  pengiriman append-only.
* Setelah `DELIVERED`, nomor, dokumen final, dan bukti lama immutable.
* Koreksi tidak membuka kembali surat lama. Ia menghasilkan draf mandiri baru
  yang menunjuk `corrects_outgoing_letter_id`, lalu memperoleh nomor serta
  pengesahan baru sebelum dapat menggantikannya.

## 24.2 Hardening dan Release Gate M10.7

Tidak ada status atau transisi baru. Untuk setiap mutasi yang menyentuh surat
mandiri bernomor, lock wajib berurutan: draf/proses, `outgoing_letters`, versi
dokumen terkait, Position Assignment aktor, dan audit. Operasi yang tidak
mempunyai surat atau versi pada tahapnya melewati lock tersebut tanpa membalik
urutan. Konflik state, file privat yang hilang, metadata/path yang tidak sah,
atau bukti audit yang gagal harus menggagalkan seluruh transaksi dengan `409`;
upload kandidat dikompensasi bila belum berhasil dicatat.

Limiter berlaku untuk upload, review, penomoran, pengesahan, delivery, QR
verification, dan tautan unduh. Tautan email kedaluwarsa atau tercabut tidak
dapat dipakai kembali. Notifikasi selalu dijalankan setelah commit sehingga
kegagalan email tidak dapat membatalkan status surat yang telah resmi.
