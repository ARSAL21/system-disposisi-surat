# Skenario Demo End-to-End Sistem Disposisi Surat

Dokumen ini menjadi panduan presentasi dan uji manual MVP dari surat diterima
sampai balasan resmi diterbitkan. Terdapat dua skenario:

1. surat masuk online dari akun pemohon; dan
2. surat fisik yang dicatat Petugas Surat.

Gunakan database demo. Jangan menjalankan migrate:fresh pada database kerja atau
produksi karena seluruh data akan dihapus.

## 1. Istilah yang Perlu Dipahami

### Dossier Balasan

Nama menu saat ini adalah **Dossier Balasan**. Dalam bahasa sederhana, artinya:

> Ruang penyusunan balasan untuk satu surat masuk.

Ruang ini mengumpulkan semua bahan yang diperlukan untuk membuat balasan:

- hasil dan lampiran teknis dari Kepala Bagian;
- usulan balasan dari setiap Asisten;
- dokumen gabungan atau perbaikan dari Sekda/Wali Kota;
- keputusan dokumen mana yang akan diterbitkan;
- daftar surat balasan yang akan dibuat.

Dossier bukan surat keluar, bukan tempat disposisi baru, dan bukan dokumen yang
dapat dilihat pemohon. Istilah yang lebih mudah untuk UI ke depan adalah
**Penyusunan Balasan**.

### Mandat Balasan

Mandat adalah keputusan satu kali dari pimpinan penerima surat yang berarti:

> Gunakan dokumen ini sebagai dasar untuk menerbitkan satu surat balasan resmi,
> dengan perihal ini dan jabatan penandatangan ini.

Saat Sekda menekan **Buat mandat**, sistem membuat satu calon surat keluar dengan
status **Menunggu nomor**. Mandat belum memberi nomor surat, belum menandatangani
PDF, dan belum mengirim balasan.

Mandat juga bukan permission permanen. Jika Asisten II dipilih sebagai
penandatangan, kewenangan itu hanya berlaku untuk surat balasan tersebut.

Satu surat masuk dapat mempunyai beberapa mandat. Konsekuensinya, setiap mandat
menjadi satu surat keluar tersendiri dengan:

- nomor surat sendiri;
- PDF final sendiri;
- verifikasi sendiri; dan
- bukti pengiriman sendiri.

### Finalisasi Rencana Balasan

Finalisasi berarti pimpinan menyatakan daftar mandat sudah benar. Setelah
difinalisasi, bahan, usulan, revisi, dan mandat baru tidak dapat ditambahkan.
Petugas baru dapat memberi nomor surat keluar setelah tahap ini.

### Dua Arti Selesai

- **Disposisi selesai**: seluruh Kepala Bagian telah menyelesaikan cabangnya.
- **Balasan terpenuhi**: seluruh mandat aktif telah diterbitkan dan dikirim.

Karena itu, surat dapat selesai secara internal tetapi balasannya masih sedang
disiapkan.

## 2. Pembagian Tanggung Jawab

| Aktor | Tanggung jawab utama |
| --- | --- |
| Pemohon online | Membuat, memperbaiki, mengirim, memantau, dan mengunduh balasan miliknya. |
| Petugas Surat | Memeriksa pengajuan, mencatat surat fisik, memberi nomor surat keluar, dan mengirim/menyerahkan balasan. |
| Kabag Umum | Mengesahkan surat masuk, membuat routing awal, membuat koreksi dokumen resmi sebelum routing, dan memverifikasi PDF balasan. |
| Sekda/Wali Kota penerima | Membuat disposisi ke Asisten, menilai usulan, membuat mandat, dan memfinalisasi rencana balasan. |
| Asisten | Meneruskan disposisi ke Kepala Bagian, menilai bahan bawahannya, dan menyusun usulan balasan. |
| Kepala Bagian | Menangani cabang, menulis jurnal, menyelesaikan tugas, dan menyiapkan bahan teknis. |
| Super-admin | Mengelola role, permission, struktur, penugasan jabatan, dan instruksi disposisi; tidak otomatis boleh membaca surat tanpa Position bisnis. |

## 3. Persiapan Demo

### Akun internal hasil seeder

Semua akun internal berikut menggunakan password demo **password**.

| Jabatan | Email |
| --- | --- |
| Wali Kota | wali.kota@internal.test |
| Sekda | sekda@internal.test |
| Asisten I | asisten.1@internal.test |
| Asisten II | asisten.2@internal.test |
| Asisten III | asisten.3@internal.test |
| Kabag Umum | kabag.umum@internal.test |
| Kabag Kesejahteraan Rakyat | kabag.kesra@internal.test |
| Kabag Organisasi | kabag.organisasi@internal.test |
| Kabag Tata Pemerintahan | kabag.tapem@internal.test |
| Kabag Pembangunan | kabag.pembangunan@internal.test |
| Kabag Ekonomi | kabag.ekonomi@internal.test |
| Kabag Hukum | kabag.hukum@internal.test |
| Kabag Protokoler | kabag.protokoler@internal.test |
| Petugas Surat | petugas.surat@internal.test |

Password demo wajib diganti jika aplikasi dipakai di luar lingkungan lokal.

Gunakan jendela browser/profil berbeda untuk akun publik dan akun internal agar
pergantian aktor tidak membingungkan. Login internal berada di:

    /back-office/login

### Pemeriksaan admin sebelum skenario

Gunakan akun super-admin milik Anda:

1. Buka **Manage Role** dan pastikan role operasional memiliki permission sesuai
   jabatan.
2. Buka **Struktur Organisasi** dan tunjukkan hubungan Sekda, Asisten, dan Kepala
   Bagian.
3. Buka **Penugasan Jabatan** dan pastikan setiap jabatan demo memiliki satu
   pemegang aktif.
4. Buka **Instruksi Disposisi** dan pastikan label seperti **Pelajari dan
   telaah**, **Koordinasikan**, **Siapkan jawaban**, dan **Segera** aktif.
5. Buka **Audit Perubahan Privilege** untuk menunjukkan bahwa perubahan role,
   permission, dan penugasan tercatat.

Catatan presentasi: role menjawab fitur apa yang boleh digunakan, sedangkan
Position menjawab pengguna sedang bertindak sebagai pejabat apa.

### Berkas PDF demo

Siapkan PDF berbeda agar sistem tidak menolak hash yang sama:

- online-surat-v1.pdf
- online-surat-v2-lengkap.pdf
- online-surat-koreksi-resmi.pdf
- bahan-hukum-v1.pdf dan bahan-hukum-v2.pdf
- bahan-ekonomi.pdf
- bahan-organisasi.pdf
- usulan-asisten-1.pdf
- usulan-asisten-2-v1.pdf dan usulan-asisten-2-v2.pdf
- usulan-asisten-3.pdf
- konsolidasi-sekda.pdf
- balasan-online-utama-v1.pdf dan balasan-online-utama-v2.pdf
- balasan-online-koordinasi.pdf
- manual-surat-fisik-v1.pdf dan manual-surat-fisik-v2.pdf
- bahan-kesra.pdf dan bahan-tapem.pdf
- usulan-manual-asisten-1.pdf
- balasan-manual-final.pdf

Semua unggahan harus PDF, maksimal 20 MB. Isi setiap revisi harus sedikit berbeda
agar SHA-256 juga berbeda.

# Skenario 1 - Surat Masuk Online

## Cerita kasus

Forum UMKM Kota Baubau meminta koordinasi pengembangan sentra UMKM, kesiapan
lokasi, dan telaah dasar hukumnya. Surat perlu ditangani oleh beberapa jalur dan
akan menghasilkan dua balasan resmi.

Data contoh:

| Field | Nilai |
| --- | --- |
| Pemohon | Rahmawati Yusuf |
| Email | rahmawati.demo@example.test |
| Instansi | Forum UMKM Kota Baubau |
| Telepon | 081234567890 |
| Nomor surat pengirim | 014/FUMKM/IX/2026 |
| Tanggal surat | Gunakan hari ini atau tanggal sebelumnya |
| Perihal | Permohonan koordinasi pengembangan sentra UMKM Kota Baubau |
| Ringkasan | Permohonan koordinasi lokasi, dukungan program, dan telaah dasar hukum pengembangan sentra UMKM. |

## Tahap 1 - Pemohon membuat pengajuan

Aktor: **Pemohon online**

1. Daftar melalui halaman publik.
2. Buka email verifikasi pada layanan email lokal yang digunakan aplikasi, lalu
   klik tautan verifikasi.
3. Login dan buka **Surat Saya**.
4. Klik **Buat surat**.
5. Isi data contoh dan klik **Simpan dan lanjutkan**.
6. Unggah online-surat-v1.pdf.
7. Buka pratinjau/unduh untuk memastikan PDF benar.
8. Klik **Kirim pengajuan**, kemudian **Ya, kirim**.

Hasil yang harus terlihat:

- status berubah dari DRAFT menjadi SUBMITTED;
- metadata dan PDF terkunci selama pemeriksaan;
- pengajuan hanya terlihat oleh pemilik akun;
- waktu pengiriman ditentukan sistem.

Fitur tambahan autentikasi yang dapat diperagakan dengan akun publik terpisah:

- logout dan login kembali;
- **Lupa kata sandi** dan tautan reset;
- pengaturan profil, MFA/passkey bila sudah dikonfigurasi pada perangkat demo;
- akun publik tidak dapat memasuki halaman back-office.

Untuk memperagakan **Hapus draft**, buat satu draft percobaan terpisah dan hapus
sebelum dikirim. Jangan memakai surat utama karena penghapusan draft bersifat
terminal.

## Tahap 2 - Petugas meminta perbaikan kepada pemohon

Aktor: **Petugas Surat**

1. Login sebagai petugas.surat@internal.test.
2. Buka **Penerimaan Surat**.
3. Cari perihal sentra UMKM dan klik **Periksa**.
4. Pratinjau PDF dan periksa metadata.
5. Biarkan checklist dokumen belum lengkap.
6. Isi catatan:

   > Halaman tanda tangan dan lampiran lokasi belum terbaca lengkap. Mohon
   > unggah kembali surat beserta seluruh lampirannya.

7. Klik **Minta pengirim memperbaiki**.

Hasil:

- status menjadi REVISION_REQUIRED;
- surat hilang dari antrean yang siap diajukan;
- alasan koreksi tercatat;
- versi yang pernah dikirim tidak dihapus.

## Tahap 3 - Pemohon memperbaiki dan mengirim ulang

Aktor: **Pemohon online**

1. Login kembali ke portal publik.
2. Buka surat yang berstatus perlu perbaikan.
3. Klik edit dan perjelas ringkasan bila diperlukan.
4. Ganti dokumen dengan online-surat-v2-lengkap.pdf.
5. Klik **Kirim ulang**.

Hasil:

- status kembali menjadi SUBMITTED;
- dokumen lama tetap menjadi histori;
- dokumen terbaru menjadi dokumen yang diperiksa petugas.

## Tahap 4 - Petugas menyatakan pemeriksaan lengkap

Aktor: **Petugas Surat**

1. Buka kembali surat pada **Penerimaan Surat**.
2. Konfirmasi empat checklist:
   - identitas pengirim dapat diverifikasi;
   - data surat konsisten;
   - PDF terbaca dan lengkap;
   - surat termasuk jalur pimpinan.
3. Isi catatan:

   > Identitas, metadata, dan lampiran telah lengkap. Surat layak diajukan untuk
   > registrasi administratif.

4. Klik **Ajukan ke Kabag Umum**.

Hasil: status menjadi READY_FOR_APPROVAL dan muncul pada menu
**Persetujuan Surat** milik Kabag Umum.

## Tahap 5 - Kabag Umum mengembalikan sekali kepada petugas

Aktor: **Kabag Umum**

Langkah ini sengaja dilakukan untuk memperagakan koreksi internal.

1. Login sebagai kabag.umum@internal.test.
2. Buka **Persetujuan Surat**, lalu buka detail surat.
3. Klik **Kembalikan ke petugas**.
4. Isi alasan:

   > Pastikan nama instansi pada catatan pemeriksaan sama dengan kop surat
   > sebelum diregistrasikan.

Hasil: status menjadi INTERNAL_REVISION_REQUIRED. Pengajuan tidak kembali ke
pemohon karena ini koreksi administrasi internal.

Aktor kembali ke **Petugas Surat**:

1. Buka surat pada **Penerimaan Surat**.
2. Pastikan nama instansi sesuai PDF.
3. Perbarui catatan pemeriksaan.
4. Klik **Ajukan kembali ke Kabag Umum**.

Hasil: status kembali READY_FOR_APPROVAL.

## Tahap 6 - Kabag Umum meregistrasikan surat

Aktor: **Kabag Umum**

1. Buka kembali surat pada **Persetujuan Surat**.
2. Pilih/buat instansi pengirim Forum UMKM Kota Baubau.
3. Isi nomor agenda unik, misalnya:

       0001/SETDA/IX/2026

4. Isi catatan registrasi bila diperlukan.
5. Klik tindakan registrasi dan konfirmasi.

Hasil:

- submission menjadi REGISTERED;
- satu IncomingLetter resmi dibuat;
- dokumen resmi versi 1 dan SHA-256 tercatat;
- surat muncul pada **Buku Surat Masuk**;
- tindakan registrasi tampil pada **Aktivitas Surat**.

Catatan presentasi: persetujuan Kabag Umum mengesahkan administrasi surat, bukan
menyetujui substansi permohonan.

Untuk memperagakan **Tolak surat**, gunakan salinan pengajuan lain yang memang
tidak memenuhi syarat administratif. Kabag Umum mengisi alasan penolakan dan
menetapkan REJECTED. Jangan memakai surat utama karena status REJECTED final
pada MVP.

## Tahap 7 - Koreksi dokumen resmi sebelum routing

Aktor: **Kabag Umum**

Tahap ini memperagakan histori versi dokumen M4.

1. Sebelum surat diarahkan kepada pimpinan, buka **Arsip Dokumen**.
2. Cari nomor agenda 0001/SETDA/IX/2026.
3. Buka histori dokumen.
4. Unggah online-surat-koreksi-resmi.pdf sebagai versi koreksi.
5. Isi alasan:

   > Mengganti hasil scan dengan salinan yang lebih jelas tanpa mengubah isi
   > surat.

Hasil:

- versi baru dibuat tanpa menimpa versi sebelumnya;
- nomor versi dan dokumen yang digantikan terlihat;
- fingerprint, ukuran, pengunggah, dan waktu tercatat;
- preview/download menggunakan akses privat.

Setelah routing dilakukan, koreksi dokumen acuan tidak lagi tersedia pada MVP.

## Tahap 8 - Kabag Umum mengarahkan surat ke Sekda

Aktor: **Kabag Umum**

1. Buka **Routing Surat**.
2. Cari surat dan buka detail.
3. Pilih Sekretaris Daerah sebagai pimpinan tujuan.
4. Isi catatan:

   > Mohon arahan tindak lanjut lintas bagian terkait pengembangan sentra UMKM.

5. Konfirmasi routing.

Hasil:

- surat berubah dari REGISTERED menjadi ROUTED;
- route awal tercatat permanen;
- surat muncul pada **Inbox Pimpinan** milik Sekda.

## Tahap 9 - Sekda membuat disposisi ke tiga Asisten

Aktor: **Sekda**

1. Login sebagai sekda@internal.test.
2. Buka **Inbox Pimpinan** dan pilih surat.
3. Pilih Asisten I, Asisten II, dan Asisten III.
4. Pilih instruksi:
   - Pelajari dan telaah;
   - Koordinasikan;
   - Siapkan jawaban.
5. Isi catatan:

   > Asisten I menelaah aspek hukum, Asisten II mengoordinasikan ekonomi dan
   > pembangunan, Asisten III menyiapkan dukungan administrasi.

6. Konfirmasi disposisi.

Hasil:

- surat menjadi IN_PROGRESS;
- route awal selesai;
- tiga cabang Asisten dibuat secara bersamaan;
- Sekda tidak dapat menunjuk dirinya sendiri.

## Tahap 10 - Setiap Asisten menunjuk Kepala Bagian

Aktor: **Asisten I**

1. Buka **Inbox Disposisi**.
2. Teruskan surat kepada Kabag Hukum.
3. Pilih instruksi **Pelajari dan telaah** serta **Siapkan jawaban**.

Aktor: **Asisten II**

1. Teruskan surat dalam satu tindakan kepada:
   - Kabag Ekonomi; dan
   - Kabag Pembangunan.
2. Pilih instruksi **Koordinasikan** dan **Siapkan jawaban**.

Aktor: **Asisten III**

1. Buka pilihan Kepala Bagian.
2. Perhatikan bahwa Kabag Hukum, Ekonomi, dan Pembangunan yang sudah dipilih
   cabang lain tidak dapat dipilih kembali dan menampilkan siapa Asisten yang
   sudah menugaskannya.
3. Pilih Kabag Organisasi.
4. Teruskan dengan instruksi **Siapkan jawaban**.

Hasil:

- setiap Asisten selesai pada tugas meneruskan;
- empat Kepala Bagian memperoleh cabang terminal yang berbeda;
- satu Kepala Bagian tidak dapat ditunjuk dua Asisten untuk surat yang sama.

## Tahap 11 - Kepala Bagian menangani cabang secara independen

### Kabag Hukum

1. Login sebagai kabag.hukum@internal.test.
2. Buka **Inbox Disposisi** dan detail surat.
3. Klik **Mulai penanganan**.
4. Tambahkan jurnal:

   > Telaah dasar hukum lokasi dan bentuk kerja sama sedang dilakukan.

5. Klik **Selesaikan cabang**.
6. Isi hasil:

   > Telaah selesai. Kerja sama dapat dilanjutkan dengan mencantumkan dasar
   > kewenangan dan status pemanfaatan lokasi.

7. Unggah bahan-hukum-v1.pdf dan catatan bahan.

### Kabag Ekonomi

1. Mulai penanganan.
2. Tambahkan jurnal perkembangan.
3. Selesaikan dengan bahan-ekonomi.pdf.

### Kabag Pembangunan

1. Gunakan **Selesaikan langsung**.
2. Isi hasil penyelesaian tanpa PDF untuk memperagakan bahwa bahan teknis
   bersifat opsional.

Hasil: started_at tetap kosong untuk penyelesaian langsung.

### Kabag Organisasi

1. Mulai penanganan.
2. Tambahkan jurnal.
3. Selesaikan dengan bahan-organisasi.pdf.

Hasil keseluruhan:

- setiap cabang berubah tanpa mengubah cabang lain;
- surat tetap IN_PROGRESS selama masih ada satu cabang belum selesai;
- setelah cabang terakhir selesai, surat menjadi COMPLETED;
- **Ruang Penyusunan Balasan** dibuka sejak cabang terminal pertama selesai;
- audit mulai, jurnal, penyelesaian cabang, dan penyelesaian surat tercatat.

## Tahap 12 - Asisten menyusun usulan balasan

Nama menu saat ini: **Dossier Balasan**.

### Perbaikan bahan Kabag

Aktor: **Asisten I**

1. Buka **Dossier Balasan** dan pilih surat.
2. Klik kartu Kabag Hukum untuk melihat hasil dan PDF.
3. Klik **Kembalikan dokumen**.
4. Isi alasan:

   > Tambahkan rujukan dasar hukum pemanfaatan aset dan perjelas kesimpulan.

Aktor: **Kabag Hukum**

1. Buka surat pada **Dossier Balasan**.
2. Klik **Revisi**.
3. Unggah bahan-hukum-v2.pdf.
4. Isi catatan perubahan.

Hasil: versi 1 tetap tersimpan, versi 2 menjadi versi terbaru.

### Usulan masing-masing Asisten

Setelah semua Kepala Bagian di bawah setiap Asisten selesai:

- Asisten I mengunggah usulan-asisten-1.pdf;
- Asisten II mengunggah usulan-asisten-2-v1.pdf;
- Asisten III mengunggah usulan-asisten-3.pdf.

Setiap unggahan memakai catatan minimal 10 karakter. Sumber bahan bawahannya
terhubung otomatis sebagai riwayat penyusunan.

### Sekda meminta perbaikan usulan

Aktor: **Sekda**

1. Buka **Dossier Balasan**.
2. Pilih usulan Asisten II.
3. Klik **Kembalikan dokumen**.
4. Isi alasan:

   > Gabungkan rekomendasi Bagian Ekonomi dan jadwal kesiapan lokasi dari Bagian
   > Pembangunan.

Aktor: **Asisten II**

1. Buka dokumen yang perlu revisi.
2. Klik **Revisi**.
3. Unggah usulan-asisten-2-v2.pdf dan catatan perubahan.

Hasil: Sekda dapat melihat asal bahan, versi, pembuat, waktu, dan alasan revisi
tanpa mengubah dokumen lama.

## Tahap 13 - Sekda menggabungkan usulan dan membuat mandat

Aktor: **Sekda**

1. Buka **Dossier Balasan**.
2. Klik **Unggah konsolidasi**.
3. Pilih usulan terbaru Asisten I, II, dan III sebagai sumber.
4. Unggah konsolidasi-sekda.pdf.
5. Isi catatan:

   > Gabungan final hasil telaah hukum, ekonomi, pembangunan, dan administrasi.

### Mandat A

Klik **Buat mandat**, lalu isi:

| Field | Nilai |
| --- | --- |
| Dokumen sumber | Konsolidasi eksekutif versi terbaru |
| Penandatangan substantif | Sekretaris Daerah |
| Perihal | Tanggapan atas permohonan pengembangan sentra UMKM Kota Baubau |

Maknanya: satu surat keluar akan diterbitkan berdasarkan konsolidasi dan
penandatangan yang disetujui adalah Sekda.

### Mandat B

Buat mandat kedua:

| Field | Nilai |
| --- | --- |
| Dokumen sumber | Usulan Asisten II versi terbaru |
| Penandatangan substantif | Asisten II |
| Perihal | Undangan koordinasi teknis pengembangan sentra UMKM Kota Baubau |

Maknanya: pemohon akan menerima surat resmi kedua. Wewenang Asisten II hanya
berlaku untuk mandat ini.

### Mandat percobaan yang ditarik

1. Buat mandat ketiga dengan perihal **Pemberitahuan koordinasi tambahan**.
2. Sebelum diberi nomor, buka mandat tersebut pada **Register Surat Keluar**.
3. Klik **Tarik mandat sebelum penomoran**.
4. Isi alasan:

   > Isi mandat sudah digabung ke dalam surat balasan utama.

Hasil: mandat tetap terlihat sebagai histori WITHDRAWN dan tidak diterbitkan.

Kembali ke **Dossier Balasan**, lalu klik **Finalisasi rencana**.

Hasil:

- rencana berubah dari OPEN menjadi FINALIZED;
- Mandat A dan B siap diberi nomor;
- proposal, konsolidasi, dan daftar mandat terkunci;
- mandat yang ditarik tidak ikut menjadi kewajiban penerbitan.

## Tahap 14 - Petugas memberi nomor surat keluar

Aktor: **Petugas Surat**

1. Buka **Register Surat Keluar**.
2. Buka Mandat A.
3. Klik **Berikan nomor resmi**.
4. Isi 0001/BALASAN/SETDA/IX/2026 dan tanggal hari ini.
5. Ulangi untuk Mandat B menggunakan
   0002/BALASAN/SETDA/IX/2026.

Hasil:

- setiap mandat menjadi NUMBER_ASSIGNED;
- nomor harus unik pada tahun yang sama;
- penomoran, aktor, jabatan, dan waktu tercatat.

Catatan presentasi: Petugas mengendalikan nomor dan register, tetapi tidak
mengubah isi substantif balasan.

## Tahap 15 - PDF final ditandatangani di luar sistem dan diunggah

Lakukan penandatanganan di luar aplikasi. Ini bukan TTE tersertifikasi.

Untuk Mandat A, aktor **Petugas Surat**:

1. Klik **Unggah PDF bertanda tangan**.
2. Unggah balasan-online-utama-v1.pdf.
3. Isi catatan kondisi dokumen.

Untuk Mandat B, aktor **Asisten II** sebagai pemilik dokumen sumber:

1. Buka surat pada **Register Surat Keluar**.
2. Unggah balasan-online-koordinasi.pdf.
3. Isi catatan unggahan.

Hasil: kedua surat menjadi SIGNED_DOCUMENT_UPLOADED dan versi PDF tersimpan
secara privat.

## Tahap 16 - Kabag Umum meminta revisi dan memverifikasi

Aktor: **Kabag Umum**

Untuk Mandat A:

1. Buka detail pada **Register Surat Keluar**.
2. Pratinjau PDF.
3. Klik **Minta perbaikan PDF**.
4. Isi:

   > Nomor surat pada halaman pertama kurang terbaca. Unggah scan final yang
   > lebih jelas.

Aktor: **Petugas Surat**

1. Unggah balasan-online-utama-v2.pdf sebagai versi perbaikan.
2. Pastikan versi pertama tetap ada pada jejak dokumen.

Aktor kembali ke **Kabag Umum**:

1. Buka versi terbaru.
2. Klik **Verifikasi dokumen final**.
3. Isi catatan opsional dan klik **Nyatakan sesuai**.
4. Verifikasi Mandat B secara langsung.

Hasil:

- review per versi tercatat;
- versi yang ditolak tidak dihapus;
- kedua surat menjadi ADMIN_VERIFIED;
- verifikasi adalah pemeriksaan administrasi, bukan TTE.

## Tahap 17 - Petugas mempublikasikan balasan online

Aktor: **Petugas Surat**

1. Buka Mandat A dan klik **Publikasikan ke portal**.
2. Konfirmasi pengiriman.
3. Buka Mandat B dan lakukan hal yang sama.

Hasil setelah Mandat A:

- Mandat A menjadi DELIVERED;
- pemohon mendapat notifikasi berisi tautan login tanpa lampiran;
- tracker publik sudah menjadi RESPONSE_AVAILABLE;
- ruang penyusunan belum FULFILLED karena Mandat B masih aktif.

Hasil setelah Mandat B:

- kedua mandat aktif menjadi DELIVERED;
- ruang penyusunan menjadi FULFILLED;
- bukti publikasi tersimpan dan tidak dapat diedit.

## Tahap 18 - Pemohon melihat hasil akhir

Aktor: **Pemohon online**

1. Buka notifikasi email atau login ke portal.
2. Buka detail pengajuan.
3. Lihat tracker:
   - Sedang diproses;
   - Balasan disiapkan;
   - Balasan tersedia.
4. Pastikan terdapat dua kartu balasan resmi.
5. Klik **Pratinjau** dan **Unduh balasan** pada masing-masing kartu.

Hasil:

- hanya pemilik submission yang dapat mengakses kedua PDF;
- pemohon tidak melihat jurnal, bahan Kabag, proposal Asisten, atau catatan
  internal;
- draft balasan tidak pernah dipublikasikan.

# Skenario 2 - Surat Masuk Fisik/Manual

## Cerita kasus

Lembaga Adat Kota Baubau menyerahkan surat fisik mengenai fasilitasi kegiatan
budaya. Pengirim tidak memiliki akun portal dan balasan akan diserahkan
langsung.

Data contoh:

| Field | Nilai |
| --- | --- |
| Instansi | Lembaga Adat Kota Baubau |
| Nama kontak | La Ode Ahmad |
| Email | Kosongkan |
| Telepon | 081298765432 |
| Waktu diterima | Waktu aktual surat diterima, tidak boleh masa depan |
| Nomor surat | 021/LAKB/IX/2026 |
| Tanggal surat | Hari ini atau tanggal sebelumnya |
| Perihal | Permohonan fasilitasi kegiatan budaya Kota Baubau |
| Ringkasan | Permohonan dukungan koordinasi, tempat, dan kehadiran perwakilan pemerintah kota. |

## Tahap 1 - Petugas mencatat surat fisik

Aktor: **Petugas Surat**

1. Buka **Catat Surat Manual**.
2. Isi identitas pengirim, data surat, dan waktu sebenarnya ketika surat
   diterima.
3. Biarkan email kosong untuk menunjukkan bahwa email tidak wajib.
4. Unggah manual-surat-fisik-v1.pdf.
5. Konfirmasi seluruh checklist pemeriksaan.
6. Isi catatan:

   > Surat fisik dan seluruh lampiran telah diterima serta cocok dengan hasil
   > scan.

7. Simpan dan ajukan ke Kabag Umum.

Hasil:

- source tercatat MANUAL;
- submission langsung READY_FOR_APPROVAL;
- tidak ada akun publik yang dibuat;
- screening, dokumen, pencatat, dan audit dibuat dalam satu proses;
- surat belum memiliki nomor agenda resmi.

## Tahap 2 - Kabag mengembalikan scan kepada Petugas

Aktor: **Kabag Umum**

1. Buka **Persetujuan Surat**.
2. Pilih surat manual.
3. Klik **Kembalikan ke petugas**.
4. Isi alasan:

   > Halaman lampiran terakhir terpotong. Lakukan scan ulang sebelum registrasi.

Aktor: **Petugas Surat**

1. Buka kembali surat manual yang perlu diperbaiki.
2. Unggah manual-surat-fisik-v2.pdf.
3. Periksa metadata dan checklist.
4. Klik **Ajukan kembali ke Kabag Umum**.

Hasil:

- versi scan lama tetap tersimpan;
- status kembali READY_FOR_APPROVAL;
- tidak ada tindakan yang harus dilakukan pengirim.

## Tahap 3 - Registrasi dan routing

Aktor: **Kabag Umum**

1. Registrasikan dengan nomor agenda
   0002/SETDA/IX/2026.
2. Buka **Buku Surat Masuk** dan filter sumber **Surat fisik**.
3. Pastikan tanggal diterima memakai waktu surat benar-benar diterima.
4. Buka **Routing Surat** dan arahkan ke Sekda.

Hasil: surat berubah REGISTERED, kemudian ROUTED, dan sumbernya tetap MANUAL.

## Tahap 4 - Disposisi dan penyelesaian cabang

Aktor: **Sekda**

1. Buka **Inbox Pimpinan**.
2. Disposisikan hanya kepada Asisten I.
3. Pilih instruksi **Koordinasikan**, **Hadiri**, dan **Siapkan jawaban**.

Aktor: **Asisten I**

1. Teruskan dalam satu tindakan kepada Kabag Kesra dan Kabag Tata Pemerintahan.

Aktor: **Kabag Kesra**

1. Mulai penanganan.
2. Tambahkan jurnal.
3. Selesaikan dengan bahan-kesra.pdf.

Aktor: **Kabag Tata Pemerintahan**

1. Mulai penanganan.
2. Tambahkan jurnal.
3. Selesaikan dengan bahan-tapem.pdf.

Hasil: setelah kedua cabang selesai, IncomingLetter menjadi COMPLETED.

## Tahap 5 - Penyusunan dan mandat balasan manual

Aktor: **Asisten I**

1. Buka **Dossier Balasan**.
2. Pelajari catatan serta bahan Kabag Kesra dan Tata Pemerintahan.
3. Unggah usulan-manual-asisten-1.pdf.

Aktor: **Sekda**

1. Tinjau usulan Asisten I.
2. Klik **Buat mandat**.
3. Pilih usulan tersebut sebagai sumber.
4. Pilih Sekda sebagai penandatangan.
5. Isi perihal:

   > Tanggapan atas permohonan fasilitasi kegiatan budaya Kota Baubau

6. Klik **Finalisasi rencana**.

Hasil: satu surat keluar berstatus AUTHORIZED siap diterbitkan.

## Tahap 6 - Penerbitan dan penyerahan offline

Aktor: **Petugas Surat**

1. Buka **Register Surat Keluar**.
2. Berikan nomor 0003/BALASAN/SETDA/IX/2026.
3. Unggah balasan-manual-final.pdf setelah ditandatangani di luar sistem.

Aktor: **Kabag Umum**

1. Pratinjau PDF final.
2. Klik **Verifikasi dokumen final**.

Aktor kembali ke **Petugas Surat**:

1. Klik **Catat penyerahan**.
2. Isi:

| Field | Nilai |
| --- | --- |
| Metode | Diserahkan langsung |
| Nama penerima | La Ode Ahmad |
| Waktu penyerahan | Waktu aktual, tidak boleh masa depan |
| Nomor referensi | BAST-003/IX/2026 |
| Catatan | Diterima langsung oleh perwakilan Lembaga Adat. |

Hasil:

- surat keluar menjadi DELIVERED;
- ruang penyusunan menjadi FULFILLED;
- metode, penerima, waktu, dan referensi tersimpan sebagai bukti;
- tidak ada email, akun publik, atau kartu unduhan portal yang dibuat.

# Pemeriksaan Fitur Pendukung Setelah Kedua Skenario

## Buku Surat Masuk

Aktor: Petugas atau Kabag Umum.

- Cari berdasarkan nomor agenda, perihal, atau instansi.
- Filter sumber ONLINE dan MANUAL.
- Filter tanggal/tahun/status.
- Pastikan kedua surat muncul tanpa bercampur dengan surat di luar scope.

## Arsip Dokumen

- Buka histori surat online.
- Bandingkan versi lama dan versi koreksi.
- Periksa nama file, ukuran, waktu, pengunggah, alasan, dan fingerprint.
- Pastikan storage disk/path tidak tampil di UI.

## Register Surat Keluar

- Filter berdasarkan status, sumber, dan tahun.
- Pastikan dua balasan online dan satu balasan manual muncul.
- Pastikan mandat yang ditarik tetap menjadi histori read-only.
- Pastikan dokumen DELIVERED tidak dapat diedit atau dihapus.

## Laporan Periodik

Aktor: Sekda.

1. Pilih periode yang memuat tanggal demo.
2. Uji basis **Diterima**, **Mulai diproses**, dan **Selesai**.
3. Filter sumber ONLINE dan MANUAL.
4. Buka detail surat untuk melihat pohon keputusan vertikal.
5. Klik setiap node Sekda, Asisten, dan Kepala Bagian untuk melihat detail.
6. Ekspor **ringkasan** dan **daftar surat** ke CSV.

Hasil:

- KPI tidak berubah karena pagination;
- pohon menunjukkan hubungan setiap cabang;
- CSV tidak berisi jurnal, completion note, disk/path, email internal, IP,
  assignment ID, atau metadata audit mentah.

Uji batas visibility dengan login bergantian:

- Sekda dapat melihat seluruh pohon;
- Asisten hanya melihat subtree yang menjadi kewenangannya;
- Kepala Bagian hanya melihat cabangnya;
- Kabag Umum memperoleh agregat operasional global tanpa melihat catatan cabang
  lain yang tidak menjadi kewenangannya;
- Petugas tidak memperoleh laporan struktural.

Login juga sebagai Wali Kota dan tunjukkan bahwa Position eksekutif memperoleh
pandangan laporan tingkat kota sesuai scope bisnis, walaupun surat demo utama
diterima dan diputuskan oleh Sekda.

## Aktivitas Surat

Aktor yang memiliki akses.

- Cari kedua nomor agenda.
- Tunjukkan jejak pengajuan, koreksi, registrasi, routing, disposisi, penyelesaian,
  bahan/usulan, mandat, penomoran, review, verifikasi, dan pengiriman.
- Jelaskan bahwa identitas aktor dan Position historis tetap dipertahankan.
- Isi jurnal sensitif tidak disalin ke daftar aktivitas umum.

## Pemeriksaan Batas Keamanan

Gunakan hanya sebagai penutup demo:

1. Login sebagai pemohon lain dan coba buka URL balasan pemohon pertama:
   hasil yang benar adalah 404.
2. Login sebagai Kepala Bagian lain dan coba buka cabang yang bukan miliknya:
   hasil yang benar adalah 404.
3. Login menggunakan akun tanpa permission terkait:
   hasil yang benar adalah 403.
4. Unggah file non-PDF atau lebih dari 20 MB:
   hasil yang benar adalah validasi 422.
5. Coba unggah ulang PDF dengan isi identik pada seri versi yang sama:
   hasil yang benar adalah penolakan duplicate.
6. Coba menyelesaikan cabang yang sudah COMPLETED atau memberi nomor mandat yang
   sudah diproses:
   hasil yang benar adalah konflik workflow 409.

## Pemeriksaan Integritas Teknis

Bagian ini opsional untuk audiens teknis. Jalankan pada terminal dari direktori
aplikasi:

    php artisan documents:verify-integrity --all

Perintah membaca file privat secara streaming dan membandingkan ukuran serta
SHA-256 aktual dengan catatan database. Hasil yang benar adalah seluruh dokumen
yang diperiksa cocok. Gunakan opsi --limit=10 jika demo hanya memerlukan sampel.

# Ringkasan Narasi Presentasi

Gunakan narasi pendek berikut:

> Surat online dibuat sendiri oleh pemohon, sedangkan surat fisik dicatat oleh
> Petugas. Keduanya harus diperiksa dan disahkan Kabag Umum sebelum menjadi surat
> masuk resmi. Surat kemudian diarahkan kepada Sekda, didisposisikan ke Asisten,
> dan diteruskan ke Kepala Bagian. Setiap Kepala Bagian bekerja pada cabangnya
> sendiri. Setelah semua cabang selesai, bahan mereka dirangkum oleh Asisten dan
> diputuskan oleh Sekda. Keputusan Sekda untuk menerbitkan satu dokumen disebut
> mandat balasan. Setiap mandat diberi nomor oleh Petugas, ditandatangani di luar
> sistem, diverifikasi Kabag Umum, lalu dikirim. Pemohon online mengambil
> balasannya melalui portal, sedangkan pengirim surat fisik menerima secara
> offline dengan bukti penyerahan.

## Checklist keberhasilan demo

- [ ] Registrasi, verifikasi email, login, dan reset password publik dipahami.
- [ ] Pengajuan online dapat dikoreksi dan dikirim ulang.
- [ ] Surat manual dapat dicatat tanpa akun dan tanpa email pengirim.
- [ ] Hanya Kabag Umum yang meregistrasikan surat resmi.
- [ ] Histori versi PDF lama tetap utuh.
- [ ] Routing awal dan disposisi mengikuti Position.
- [ ] Beberapa Asisten dan beberapa Kepala Bagian dapat bekerja independen.
- [ ] Kepala Bagian yang sudah dipilih tidak dapat dipilih cabang Asisten lain.
- [ ] Jurnal dan penyelesaian cabang dapat diperagakan.
- [ ] Status surat selesai hanya setelah seluruh cabang terminal selesai.
- [ ] Bahan Kabag, usulan Asisten, revisi, dan konsolidasi pimpinan dipahami.
- [ ] Makna mandat dan finalisasi rencana balasan dipahami.
- [ ] Beberapa mandat menghasilkan beberapa surat keluar.
- [ ] Penarikan mandat sebelum penomoran dapat diperagakan.
- [ ] Penomoran, upload final, permintaan revisi, dan verifikasi dapat diperagakan.
- [ ] Pengiriman online dan bukti penyerahan offline dapat diperagakan.
- [ ] Pemohon hanya dapat melihat balasan final miliknya.
- [ ] Buku masuk, arsip, register keluar, laporan, ekspor, aktivitas, RBAC,
      Position, penugasan, dan instruksi disposisi dapat diperagakan.
