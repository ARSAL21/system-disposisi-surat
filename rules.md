# Engineering Rules — Store Financial Monitoring System

## 1. Tujuan

Dokumen ini mendefinisikan aturan implementasi proyek.

Semua kode backend dan frontend harus mengikuti aturan ini selama tidak bertentangan dengan kebutuhan bisnis atau `system-design.md`.

Prioritas utama:

1. Correctness.
2. Readability.
3. Simplicity.
4. Maintainability.
5. Security.

Jangan membuat abstraksi atau optimasi yang belum dibutuhkan.

---

## 2. Prinsip Dasar

### KISS

Gunakan solusi paling sederhana yang benar dan mudah dipahami.

Hindari:
- abstraction berlapis tanpa kebutuhan nyata;
- pattern tambahan hanya karena terlihat lebih "enterprise";
- helper generik untuk kode yang hanya digunakan sekali;
- konfigurasi dinamis jika kebutuhan masih statis.

---

### DRY

Jangan menduplikasi aturan bisnis yang sama di beberapa tempat.

Jika aturan yang sama digunakan berulang kali, pindahkan ke lokasi yang memiliki tanggung jawab paling tepat.

Namun:

> DRY tidak berarti semua kode yang terlihat mirip harus langsung diabstraksikan.

Duplikasi kecil lebih baik daripada abstraksi prematur yang sulit dipahami.

---

### SOLID

Gunakan SOLID sebagai pedoman desain, bukan alasan untuk menambah banyak class.

Setiap class dan function harus memiliki tanggung jawab yang jelas.

Contoh:

```text
StoreController
-> mengatur HTTP flow

StoreService
-> menjalankan use case bisnis

StorePolicy
-> menentukan hak akses

StoreRequest
-> memvalidasi input
```

Jangan mencampurkan semua tanggung jawab tersebut ke Controller.

---

## 3. Naming Rules

Nama harus menjelaskan **apa yang disimpan atau dilakukan**, bukan sekadar tipe datanya.

### Variable

Gunakan:

```php
$store
$owner
$transaction
$importBatch
$totalIncome
$failedRows
$transactionDate
```

Hindari:

```php
$data
$item
$obj
$tmp
$value
$x
$a
$result
```

kecuali konteks lokal yang sangat kecil dan tidak ambigu.

Nama boolean harus berbentuk kondisi yang dapat dibaca sebagai pertanyaan:

```php
$isAdmin
$isActive
$canExport
$hasInvalidRows
```

Hindari:

```php
$admin
$active
$export
$flag
```

jika nilainya merupakan boolean.

---

### Function / Method

Nama function harus menjelaskan aksi dan targetnya.

Gunakan:

```php
createStore()
updateTransaction()
calculateStoreSummary()
importTransactions()
validateImportRows()
canOwnerExport()
```

Hindari:

```php
handle()
process()
execute()
run()
doSomething()
manage()
```

kecuali method tersebut merupakan kontrak framework atau interface yang memang menetapkan nama tersebut.

Untuk query:

```php
findStoreById()
getTransactionsForPeriod()
getStoreMonthlySummary()
```

Untuk boolean:

```php
isOwnerOfStore()
canAccessStore()
hasImportErrors()
```

---

### Vue Naming

Component menggunakan PascalCase:

```text
StoreSummaryCard.vue
TransactionTable.vue
TransactionFilter.vue
IncomeExpenseChart.vue
ExcelImportPreview.vue
```

Hindari:

```text
Card.vue
Table.vue
Chart.vue
Form.vue
Component1.vue
```

Nama component harus menunjukkan konteks bisnisnya.

Props juga harus eksplisit:

```js
store
transactions
dateRange
canExport
summary
```

Hindari nama seperti:

```js
data
value
info
items
```

jika konteksnya tidak jelas.

---

## 4. Backend Layering

Gunakan pembagian tanggung jawab berikut:

```text
HTTP Request
    |
    v
Controller
    |
    v
Service
    |
    v
Model / Query
    |
    v
Database
```

Authorization dan validation harus terjadi sebelum operasi bisnis dijalankan.

---

## 5. Controller Rules

Controller harus tipis.

Controller bertanggung jawab untuk:

- menerima request;
- menjalankan authorization;
- meneruskan data valid ke Service;
- mengembalikan response atau redirect.

Controller tidak boleh berisi:

- kalkulasi bisnis kompleks;
- parsing Excel;
- query panjang;
- transaksi database kompleks;
- mapping import;
- authorization rule;
- business validation yang seharusnya berada di Service.

Contoh flow:

```php
public function store(StoreRequest $request, StoreService $storeService)
{
    $store = $storeService->createStore($request->validated());

    return redirect()->route('stores.show', $store);
}
```

---

## 6. Form Request Rules

Input dari user harus divalidasi menggunakan Laravel Form Request jika request memiliki aturan validasi yang berarti.

Gunakan Form Request untuk:

- create store;
- update store;
- create transaction;
- update transaction;
- upload Excel;
- mapping Excel;
- filter kompleks jika menerima input yang harus divalidasi.

Contoh:

```text
StoreStoreRequest
StoreUpdateRequest
TransactionStoreRequest
TransactionUpdateRequest
ImportUploadRequest
ImportMappingRequest
```

Jangan menaruh validasi request besar langsung di Controller:

```php
$request->validate([...]);
```

kecuali request sangat kecil dan benar-benar tidak membutuhkan reusable validation.

Validasi request hanya memastikan **format input** valid.

Contoh:

```text
amount harus numeric
transaction_date harus date
file harus xlsx
```

Aturan bisnis tetap berada di Service atau authorization layer.

Contoh:

```text
Owner tidak boleh membuat transaksi
```

bukan tanggung jawab Form Request.

---

## 7. Service Layer Rules

Use case bisnis harus melalui Service Layer.

Contoh service:

```text
StoreService
TransactionService
DashboardService
ImportService
ExportService
```

Service bertanggung jawab terhadap:

- business workflow;
- koordinasi beberapa model;
- database transaction;
- transformasi data domain;
- import processing;
- aggregation jika merupakan bagian dari use case.

Contoh:

```text
ImportService

upload/read
-> map
-> validate
-> preview
-> commit
```

Jangan membuat satu `AppService` atau `HelperService` besar untuk seluruh aplikasi.

Setiap Service harus mempunyai domain responsibility yang jelas.

---

## 8. Database Transaction

Gunakan database transaction ketika satu operasi mengubah beberapa record yang harus berhasil atau gagal sebagai satu kesatuan.

Contoh wajib:

```text
Confirm Excel Import
    |
    +-- Create ImportBatch
    +-- Create FinancialTransactions
```

Jika salah satu gagal:

```text
ROLLBACK
```

Jangan meninggalkan database dalam kondisi import sebagian tanpa keputusan eksplisit dari desain bisnis.

---

## 9. Model Rules

Model merepresentasikan data dan relationship.

Model boleh memiliki:

- relationship;
- cast;
- scope sederhana;
- helper domain kecil yang benar-benar berkaitan dengan state model.

Model tidak boleh menjadi tempat seluruh business workflow.

Hindari "fat model" yang menangani import, export, authorization, HTTP, dan berbagai use case sekaligus.

---

## 10. Authorization Rules

Authorization wajib ditegakkan di backend.

Gunakan Laravel Policy untuk akses resource.

Contoh:

```text
StorePolicy
TransactionPolicy
```

Owner hanya dapat membaca resource yang memiliki:

```text
resource.store_id === authenticatedUser.store_id
```

Admin dapat mengakses seluruh toko sesuai kewenangannya.

Frontend boleh menyembunyikan action yang tidak tersedia, tetapi itu hanya UX.

Backend tetap wajib menolak request yang tidak sah dengan response yang tepat, seperti:

```text
403 Forbidden
```

Jangan mempercayai `store_id` yang dikirim owner dari frontend.

Store owner harus ditentukan dari authenticated user.

---

## 11. Query dan Data Isolation

Semua query owner harus memiliki store scope yang jelas.

Tidak boleh:

```php
FinancialTransaction::findOrFail($id);
```

lalu baru mengecek toko secara sembarangan jika query dapat langsung dibatasi.

Lebih aman secara konseptual:

```text
Authenticated Owner
    |
    v
owner.store_id
    |
    v
query transaction inside that store
```

Data toko lain tidak boleh bocor melalui:

- URL manipulation;
- pagination;
- search;
- export;
- dashboard aggregation;
- API/Inertia props.

---

## 12. Financial Data Rules

Gunakan `FinancialTransaction` sebagai single source of truth.

Jangan menyimpan hasil agregasi dashboard sebagai data utama jika dapat dihitung dari transaction.

Contoh nilai turunan:

```text
totalIncome
totalExpense
netBalance
chartSeries
```

harus dihitung dari transaksi.

Nominal uang jangan menggunakan floating point.

Gunakan tipe database yang sesuai untuk nilai uang, misalnya integer unit terkecil atau decimal dengan presisi yang ditentukan.

---

## 13. Excel Import Rules

Excel tidak boleh langsung menulis ke database setelah upload.

Pipeline wajib:

```text
Upload
-> Read Headers
-> Mapping
-> Validation
-> Preview
-> Confirmation
-> Commit
```

Unknown column boleh di-ignore.

Missing optional field boleh menghasilkan `null`.

Missing required data harus menghasilkan validation error pada row terkait.

Tidak boleh silent skip.

Setiap error harus menjelaskan:

```text
row
field
reason
```

Contoh:

```text
Row 18 — transaction_date — tanggal tidak ditemukan
```

Import final harus mempunyai `ImportBatch`.

---

## 14. Error Handling

Error harus ditangani secara eksplisit.

Jangan:

- menelan exception tanpa logging;
- mengembalikan sukses ketika operasi sebenarnya gagal;
- menggunakan exception sebagai normal control flow jika kondisi dapat diperiksa biasa;
- menampilkan internal exception message kepada user.

User-facing error harus jelas dan dapat ditindaklanjuti.

Contoh buruk:

```text
Import failed.
```

Contoh lebih baik:

```text
Import tidak dapat dilanjutkan karena 12 row tidak memiliki tanggal transaksi.
```

---

## 15. Frontend Component Rules

Setiap bagian halaman yang memiliki tanggung jawab UI yang jelas harus dibuat sebagai component terpisah.

Contoh halaman Store Dashboard:

```text
StoreDashboardPage.vue
|
+-- StoreSummaryCards.vue
|   +-- StoreSummaryCard.vue
|
+-- IncomeExpenseChart.vue
|
+-- TransactionFilter.vue
|
+-- TransactionTable.vue
|
+-- ExportButton.vue
```

Jangan membuat satu file page berisi seluruh markup, chart, table, filter, modal, dan form sekaligus.

Namun jangan memecah component terlalu kecil tanpa manfaat.

Tidak perlu membuat component khusus hanya untuk:

```html
<span>{{ label }}</span>
```

jika hanya digunakan sekali dan tidak memiliki behavior atau visual responsibility tersendiri.

---

## 16. Vue Responsibility

Page component bertanggung jawab untuk composition.

Child component bertanggung jawab terhadap bagian UI tertentu.

Business rule tetap berasal dari backend.

Vue tidak boleh menentukan sendiri apakah user berhak melakukan operasi sensitif.

Contoh:

```js
canExport
```

boleh digunakan untuk menentukan apakah tombol ditampilkan.

Tetapi backend tetap harus memverifikasi permission ketika request export dijalankan.

---

## 17. Props dan Emits

Props harus memiliki tujuan yang jelas.

Jangan mengirim seluruh object besar ke component jika component hanya membutuhkan dua nilai sederhana, kecuali object tersebut memang merupakan domain object yang relevan.

Event harus diberi nama berdasarkan kejadian:

```text
filterChanged
importConfirmed
transactionSelected
```

Hindari:

```text
click
change
action
event
```

jika konteks bisnisnya menjadi tidak jelas.

---

## 18. Function Size dan Control Flow

Function harus memiliki satu tujuan utama.

Jika function:
- melakukan banyak pekerjaan berbeda;
- memiliki nesting dalam;
- sulit diberi nama dengan satu aksi;

maka pertimbangkan untuk memecahnya.

Gunakan early return untuk mengurangi nesting.

Hindari:

```text
if
  if
    if
      if
```

jika alur dapat dibuat lebih datar.

---

## 19. Comments

Comment menjelaskan **mengapa**, bukan mengulang kode.

Buruk:

```php
// Calculate total income
$totalIncome = ...
```

Lebih berguna:

```php
// Imported rows may contain both income and expense,
// so one Excel row can create two financial transactions.
```

Jika kode membutuhkan comment panjang hanya untuk menjelaskan apa yang dilakukan, pertimbangkan memperbaiki naming atau memecah function terlebih dahulu.

---

## 20. Configuration dan Magic Values

Hindari magic string dan magic number yang tersebar.

Contoh:

```text
INCOME
EXPENSE
ADMIN
OWNER
ACTIVE
```

gunakan enum atau constant jika sesuai.

Jangan mengulang string domain penting secara manual di banyak file.

---

## 21. Security Rules

Semua input eksternal dianggap tidak dapat dipercaya.

Wajib:
- validation;
- authorization;
- CSRF protection;
- safe file upload handling;
- pembatasan jenis dan ukuran file;
- sanitasi nama file;
- query melalui ORM/query builder;
- proteksi terhadap mass assignment.

Jangan menggunakan data request langsung untuk menentukan ownership atau permission.

---

## 22. Testing Priority

Minimal test harus mencakup business rule paling berisiko.

Prioritas:

1. Owner tidak dapat mengakses toko lain.
2. Owner tidak dapat CRUD transaction.
3. Owner tidak dapat import.
4. Export owner mengikuti `owner_can_export`.
5. Import invalid row tidak di-commit secara salah.
6. Import dengan income dan expense menghasilkan dua transaction.
7. Dashboard hanya menghitung transaction dari toko dan periode yang benar.

Tidak perlu mengejar coverage tinggi jika test tidak melindungi behavior penting.

---

## 23. File Organization

Struktur harus mengikuti tanggung jawab.

Contoh backend:

```text
app/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Models/
├── Policies/
├── Services/
└── Enums/
```

Contoh frontend:

```text
resources/js/
├── Pages/
│   ├── Admin/
│   └── Owner/
├── Components/
│   ├── Stores/
│   ├── Transactions/
│   ├── Dashboard/
│   └── Import/
└── Layouts/
```

Jangan membuat folder berdasarkan istilah generik seperti:

```text
misc
utils2
helpers-new
components-old
```

---

## 24. Definition of Done

Sebuah fitur belum dianggap selesai jika hanya bekerja pada happy path.

Sebelum dianggap selesai, periksa:

1. Input telah divalidasi.
2. Authorization telah ditegakkan di backend.
3. Business logic berada di layer yang tepat.
4. Error path ditangani.
5. Naming menjelaskan intent.
6. Tidak ada duplikasi aturan bisnis yang tidak perlu.
7. Vue page tidak menjadi monolithic component.
8. Loading, empty, success, dan error state tersedia jika relevan.
9. Data toko lain tidak dapat bocor.
10. Test tersedia untuk rule yang berisiko tinggi.

---

## 25. Aturan Utama Proyek

Jika terdapat beberapa solusi yang sama-sama benar, pilih solusi yang:

```text
lebih mudah dibaca
lebih sedikit moving parts
lebih jelas ownership logic-nya
lebih mudah dites
lebih sulit disalahgunakan
```

Jangan menambah kompleksitas untuk kebutuhan yang belum ada.

Kode yang sederhana, eksplisit, dan benar lebih diprioritaskan daripada kode yang terlihat abstrak atau canggih.
