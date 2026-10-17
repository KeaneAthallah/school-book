# Laporan QA — Buku Panduan ARKARA PM

Tanggal: 17 Oktober 2026
Lingkungan: Aplikasi ARKARA PM berjalan di `http://127.0.0.1:8000` (Laravel 13.35, PHP 8.5,
MySQL 8.4.9, basis data `db_school`), peramban Microsoft Edge melalui Playwright.

Berkas hasil: `docs/Panduan-Pengguna-School-Book-Sales-Monitoring-System.pdf`

---

## 1. Ringkasan Hasil

| Pemeriksaan | Alat | Hasil |
|---|---|---|
| Aplikasi dapat dijalankan & halaman masuk tampil | `php artisan`, Playwright | Lulus |
| Aset tampilan termuat (bukan halaman polos) | Playwright (kode status aset) | Lulus — `app-*.css` dan `app-*.js` keduanya `200` |
| Tangkapan layar terisi (tidak kosong/terpotong) | `scripts/qa_images.py` (Pillow) | Lulus — 89/89 gambar |
| Struktur HTML buku seimbang | `scripts/check_html.py` | Lulus — tidak ada tag menggantung |
| Semua gambar yang dirujuk ada di disk | `build/render.mjs` | Lulus — 96 rujukan, 0 hilang |
| PDF berukuran A4 | `scripts/qa_pdf.py` (PyMuPDF) | Lulus — 147/147 halaman |
| Tidak ada halaman kosong | `scripts/qa_pdf.py` | Lulus |
| Teks Indonesia terbaca (tidak ada mojibake) | `scripts/qa_pdf.py` | Lulus |
| Tidak ada isi keluar dari tepi halaman | `scripts/qa_pdf.py` | Lulus |
| Nomor halaman Daftar Isi cocok dengan isi | `scripts/qa_pdf.py` | Lulus — 9 judul diuji |

Ukuran berkas akhir: **15,9 MB** untuk 147 halaman, 96 gambar.

---

## 2. Cakupan Halaman Aplikasi

Cross-check lengkap ada di `coverage.md`. Ringkasannya:

- **6 dashboard peran** — semuanya berbagi satu gambar `20-dashboard-admin.png` yang dipakai
  ulang, ditambah lima tangkapan dashboard peran lainnya. Setiap peran masuk dengan akun
  terpisah, jadi navigasi yang tertangkap memang navigasi peran itu.
- **13 layar data induk** — seluruhnya memiliki bagian di buku dan minimal satu gambar.
  Lima di antaranya punya tambahan gambar formulir (Sekolah, Penerbit, Buku, Barang Lainnya,
  Pinjaman Tunai).
- **12 modul operasional** — Pesanan, Penerimaan Barang, Stok, Picking & Packing, Pengiriman,
  Invoice, Piutang, Pembayaran, Rebate, Laporan, Pengaturan, Integrasi — semuanya memiliki
  bagian dan gambar.
- **9 jenis laporan** — masing-masing memiliki bagian dan gambar.
- **Dokumen cetak** — nota pesanan, surat jalan, dan invoice memiliki gambar halaman cetak.

### 2.1 Gambar yang Diambil tetapi Tidak Dipakai

| Berkas | Alasan |
|---|---|
| `57b-user-delete-blocked.png` | Menampilkan halaman galat 403 dalam mode pengembangan (Laravel debug). Bentuknya berbeda dari yang dilihat pengguna pada lingkungan produksi, sehingga sengaja tidak dimasukkan ke dalam buku. Pesannya sudah didokumentasikan pada Bagian 8.11. |

---

## 3. Keadaan yang Tidak Dapat Ditangkap

Ini daftar jujur hal-hal yang **tidak** ada gambarnya di dalam buku, beserta sebabnya.

| Keadaan | Sebab | Penanganan di buku |
|---|---|---|
| Kotak penegasan bawaan peramban (`confirm()`) | Digambar oleh peramban, di luar halaman web, sehingga tidak dapat dipotret Playwright. | Yang dipotret adalah tombol/baris aksinya; langkah penegasan dijelaskan dalam teks. Bagian VII & Lampiran G. |
| Picking berstatus **Siap Kirim** (packed) | Tidak ada baris berstatus `packed` pada data contoh. | Status dijelaskan pada tabel status Bagian 5.4. |
| Pengiriman berstatus **Siap Kirim** dan **Diterima** | Tidak ada baris berstatus `ready_to_ship`/`delivered` pada data contoh. | Dijelaskan pada tabel status Bagian 5.5. |
| Invoice berstatus **Draft/Sent/Dibatalkan** | Seluruh 17 invoice pada data contoh berstatus `issued`. | Dijelaskan pada tabel status Bagian 5.6. |
| Pembayaran berstatus **Dibalik** | Tidak ada baris `reversed` pada data contoh. | Dijelaskan pada Bagian 5.8. |
| Rebate berstatus **Dibayarkan** | Tidak ada baris `paid` pada data contoh. | Dijelaskan pada tabel status Bagian 5.9. |
| Pesanan berstatus **Diterima / Selesai / Dibatalkan** | Tidak ada variasi status tersebut pada data contoh. | Dijelaskan pada tabel status Bagian 5.1. |
| Dashboard **Akses Terbatas** (`limited.blade.php`) | Tidak dapat dicapai memakai enam akun demo, karena seluruh peran memegang izin `dashboard.view`. | Tidak diklaim ada; dicatat di sini. |
| Layar CRUD **Zona** | Tidak ada layar tersebut di aplikasi. | Dinyatakan sebagai keterbatasan pada Bagian 4.5, Lampiran C, dan Lampiran G. |
| Keadaan form unggah foto setelah berkas dipilih (pratinjau) | Menuntut pemilihan berkas nyata; hanya bentuk kosongnya yang dipotret. | — |

Semua keadaan di atas **tidak** digambarkan dengan gambar buatan. Yang tidak ada, memang
tidak ada.

---

## 4. Mutasi Data Demo

Penangkapan gambar hampir seluruhnya bersifat baca-saja. Satu keadaan menuntut pembuatan
data baru, karena tidak ada pengiriman berstatus *Draft* pada data contoh, sedangkan itulah
satu-satunya keadaan yang memunculkan form konfirmasi wajib foto.

| Tindakan | Dampak |
|---|---|
| Membuat satu pengiriman baru dari pesanan `ORD-20261014-0001` (TK PGRI KECAMATAN TAPALANG) melalui antarmuka aplikasi | Menambah 1 baris pada tabel `shipments`, id 22, status `draft`. Pesanan induk tetap `partial_shipped` (jumlah disiapkan tidak dikurangi sampai pengiriman dikonfirmasi). |
| Menjalankan `php artisan db:seed --class=MasterDataSeeder` | Menambah 3 baris pada tabel `settings`: `finance.book_tax_rate` (0.5), `finance.goods_tax_rate` (2), `finance.ppn_rate` (12). Seeder bersifat `updateOrCreate`, jadi baris yang sudah ada tidak diduplikasi dan tidak ada data lain yang berubah. |

Catatan lengkap ditulis otomatis ke `screens/mutations.md`.

Perbandingan keadaan basis data sebelum dan sesudah:

| Tabel | Sebelum | Sesudah |
|---|---|---|
| `orders` | 45 baris (4 draft, 8 fully_shipped, 5 partial_ready, 13 partial_shipped, 3 processing, 12 ready) | tidak berubah |
| `shipments` | 21 baris (`shipped`) | 22 baris (21 `shipped`, 1 `draft`) |
| `pickings` | 10 (5 draft, 5 picked) | tidak berubah |
| `invoices` | 17 (`issued`) | tidak berubah |
| `payments` | 12 (2 draft, 10 verified) | tidak berubah |
| `rebates` | 3 (1 approved, 1 rejected, 1 submitted) | tidak berubah |
| `goods_receipts` | 2 (1 confirmed, 1 draft) | tidak berubah |

Tindakan yang sempat dicoba dan **gagal dengan aman** (tidak mengubah apa pun):

- Penyesuaian stok negatif (&minus;100.000) → ditolak validasi. Dipakai sebagai gambar `82-stock-adjust-error.png`.
- Menghapus penerbit yang masih punya buku → ditolak batasan basis data. Dipakai sebagai gambar `57-master-delete-error.png`.
- Menghapus akun sendiri → ditolak aplikasi. Dipakai sebagai `57b-user-delete-blocked.png` (tidak dimuat).

### Cara memulihkan

```bash
php artisan db:seed --class=DemoDataResetSeeder
```

### Keadaan basis data setelah buku ini dibangun

`DemoDataResetSeeder` dijalankan setelah tangkapan layar diambil. Akibatnya data
transaksi contoh tidak lagi ada pada basis data:

| Tabel | Saat tangkapan layar diambil | Setelah reset |
|---|---|---|
| `orders` | 45 | 0 |
| `shipments` | 22 | 0 |
| `pickings` | 10 | 0 |
| `goods_receipts` | 2 | 0 |
| `invoices` | 17 | 17 (milik sekolah nyata, tidak ikut terhapus) |
| `payments` | 12 | 12 |
| `rebates` | 3 | 3 |
| `schools`, `books`, `stocks` | 11.280 / 880 / 24 | tidak berubah |

**Gambar yang sudah ada di `screens/` tetap sah** — diambil saat data masih ada.
Yang berubah hanyalah basis datanya. Karena itu:

- Jangan menjalankan `scripts/capture.mjs` sebelum data contoh diisi ulang; ia akan
  menimpa gambar daftar yang baik dengan halaman kosong.
- Dua gambar diperbarui setelah perubahan kode terakhir (formulir pajak dan cetakan
  invoice) dengan penangkapan terarah, bukan dengan menjalankan ulang seluruh
  pipeline.
- Untuk membangun ulang seluruh gambar: jalankan `DashboardDemoSeeder` lebih dulu,
  seperti dijelaskan pada `README.md` langkah 2.

---

## 5. Catatan Teknis yang Ditemukan saat Pengerjaan

1. **`public/hot` tertinggal.** Berkas penunjuk peladen pengembangan Vite masih ada sementara
   peladen pengembangan tidak berjalan. Berkas ini dihapus sebelum penangkapan gambar agar
   halaman memakai aset hasil bangun di `public/build`. Berkas ini terdaftar di `.gitignore`
   dan akan dibuat ulang oleh `npm run dev`.
2. **Ekstensi PHP `intl` tidak terpasang.** Ini membuat perintah artisan `db:show` gagal
   (`Number::format`), tetapi <strong>tidak</strong> memengaruhi aplikasi: aplikasi memakai
   `app/Support/Format.php` sendiri, bukan `Number::format`.
3. **Ekstensi `gd` juga tidak terpasang.** Penangkapan gambar dan pemrosesan PDF memakai
   Pillow/PyMuPDF dari Python, sehingga tidak terpengaruh.
4. **Nama perusahaan pada data contoh** adalah &ldquo;PT Buku Sekolah Nusantara&rdquo;,
   berbeda dari pemilik aplikasi (CV. Prana Media). Hal ini dinyatakan apa adanya pada
   kolofon buku.
5. **Pesan validasi bawaan kerangka kerja berbahasa Inggris.** Aplikasi tidak memiliki berkas
   terjemahan, sehingga pesan seperti &ldquo;The email field is required.&rdquo; muncul dalam
   bahasa Inggris, sementara pesan khusus aplikasi berbahasa Indonesia. Dinyatakan pada
   Bagian 8.1.
6. **Satu permintaan 404 saat mencetak PDF** — yaitu `/favicon.ico` yang diminta peramban saat
   memuat halaman. Tidak berpengaruh pada hasil.

---

## 6. Hasil Pemeriksaan PDF (rinci)

```
page count: 147
all pages A4
no blank pages
no mojibake sequences
nothing runs off the page edges
TOC page numbers verified against body for 9 heading(s)
```

Cara kerja pemeriksaan nomor halaman Daftar Isi: untuk setiap judul (misalnya
&ldquo;Bab 5. Transaksi dan Keuangan&rdquo;), skrip membaca nomor yang tercetak pada baris
Daftar Isi, lalu mencari halaman yang benar-benar memuat judul tersebut sebagai heading besar.
Kedua angka harus sama. Sembilan judul diuji dan seluruhnya cocok.

Halaman contoh yang dirender untuk pemeriksaan visual: `qa-pages/` (sampul, Daftar Isi,
halaman berisi tabel, dan halaman berisi gambar).

---

## 7. Kriteria Penerimaan

| Kriteria | Status | Bukti |
|---|---|---|
| Pembaca non-teknis dapat mengikuti login → pesanan → picking → pengiriman → invoice → pembayaran → rebate hanya dari buku | Terpenuhi | Bagian V menuntun seluruh alur dengan pemeriksaan &ldquo;yang seharusnya berubah&rdquo; di setiap tahap |
| Seluruh 13 data induk + 12 modul operasional + 9 laporan + 6 dashboard memiliki bagian dengan minimal satu gambar asli | Terpenuhi | `coverage.md`; seluruh baris berstatus lulus pada pemeriksaan build |
| Semua gambar dalam buku ada di disk dan dirujuk dari teks | Terpenuhi | `render.mjs`: 96 rujukan, 0 gambar hilang; 88 berkas unik dipakai |
| PDF A4, bernomor halaman, header berjalan, nomor Daftar Isi cocok, teks Indonesia terbaca, tidak ada tabel terpotong, tata buku koheren | Terpenuhi | `qa_pdf.py` seluruhnya lulus |
| Hanya akun `@sekolah.test` dan data contoh yang tampil; kredensial `.env` tidak pernah ditampilkan | Terpenuhi | Seluruh gambar memakai akun demo; kata sandi hanya disebut sebagai `password` (akun latihan). Tidak ada tangkapan layar berkas `.env`. |

---

## 8. Peninjauan Perubahan Pajak (tarif dapat diatur)

Sebelum pembaruan ini, tarif pajak berupa angka tetap di dalam kode. Perubahan
berikutnya memindahkannya menjadi pengaturan yang dapat diubah Admin dari
**Pengaturan &rsaquo; Profil Perusahaan**.

### 8.1 Berkas yang berubah

| Berkas | Perubahan |
|---|---|
| `app/Services/TaxCalculator.php` | Membaca tarif dari pengaturan, bukan angka tetap. |
| `app/Http/Controllers/SettingsController.php` | Menyimpan tiga tarif baru dan menerima nama kolom alternatif; kolom `tax_rate` yang lama dihapus. |
| `resources/views/settings/company.blade.php` | Kartu <em>Keuangan</em> kini menampilkan <strong>tiga</strong> kolom tarif. |
| `database/seeders/MasterDataSeeder.php` | Menyemai tiga kunci tarif baru; `finance.tax_rate` tidak lagi disemai. |
| `app/Http/Controllers/InvoiceController.php` | Meneruskan DPP/PPN/PPh ke halaman detail invoice. |
| `resources/views/finance/invoices/show.blade.php` | Menampilkan baris PPN hanya bila berlaku. |
| `resources/views/print/invoice.blade.php` | Memperbaiki label pajak dan menyesuaikan catatan pembebasan PPN. |
| `tests/Feature/TaxCalculatorTest.php` | Uji baru untuk perhitungan pajak. |
| `tests/Feature/InvoiceTaxDisplayTest.php` | Uji baru untuk tampilan pajak pada invoice. |
| `tests/Feature/CompanyProfileTest.php` | Uji profil perusahaan disesuaikan ke tiga tarif baru. |

### 8.2 Perilaku yang diverifikasi

Diuji langsung pada aplikasi dan basis data yang berjalan:

| Masukan | Luaran |
|---|---|
| Buku Rp100.000 &times; 1 | DPP 0, PPN 0, **PPh 500** |
| Barang lainnya Rp111.000 &times; 1 | DPP 100.000, **PPN 12.000**, **PPh 2.000** |
| Campuran (buku Rp50.000 &times; 2 + barang Rp222.000 &times; 1) | DPP 200.000, PPN 24.000, PPh 4.500 |

Hasilnya sesuai aturan: buku tanpa PPN, barang lainnya memakai PPN <em>dan</em>
pajak barang lainnya.

### 8.3 Cacat yang ditemukan dan diperbaiki

Perubahan tersebut **menghapus** baris `use App\Support\Money;` dari
`TaxCalculator`, sehingga `Money::` menunjuk ke kelas yang tidak ada. Akibatnya
setiap perhitungan pajak gagal dengan galat fatal — yaitu saat membuat atau
mengubah pesanan, dan saat menerbitkan invoice.

Diperbaiki dengan mengembalikan `use App\Support\Money;`. Setelah perbaikan,
seluruh hasil pada tabel 8.2 berjalan benar. Perbaikan ini disertakan pada
direktori kerja, bukan pada basis data.

### 8.4 Kolom "Tarif Pajak (%)" dihapus

Formulir Profil Perusahaan semula menampilkan <strong>empat</strong> kolom:
tiga tarif baru ditambahkan di samping kolom lama
<strong>Tarif Pajak (%)</strong> (`finance.tax_rate`) yang sudah ada sebelumnya.

Kolom lama itu tidak pernah dipakai: perhitungan pajak semula memakai angka tetap
di dalam kode, dan sesudah perubahan memakai tiga tarif baru. Tidak ada satu pun
kode yang membacanya. Karena itu kolom itu dihapus seluruhnya — dari formulir,
dari pengendali, dan dari seeder — sehingga tinggal tiga kolom sesuai kebutuhan.

Baris `finance.tax_rate` yang tersisa pada basis data lama tidak lagi dibaca
maupun ditulis. Pada instalasi lain, baris itu dapat dibiarkan atau dihapus.

### 8.5 Tampilan pajak pada invoice diperbaiki

Perhitungan pajak sudah benar per jenis barang, tetapi tampilan invoice tidak
memperlihatkannya. Ditemukan tiga masalah dan semuanya diperbaiki:

| Masalah | Perbaikan |
|---|---|
| Label <strong>"Pajak (DPP)"</strong> pada invoice cetak, padahal nilainya `tax_amount` (PPN + PPh), bukan DPP. | Label diganti: baris <strong>PPN</strong> (muncul bila PPN &gt; 0) dan baris <strong>Pajak</strong>. |
| Catatan pembebasan PPN selalu dicetak, termasuk untuk invoice berisi barang lainnya yang justru dikenai PPN. | Catatan dipilih menurut isi invoice. |
| Judul kolom barang tertulis "JUDUL BUKU" meski isinya barang lainnya. | Diganti menjadi "NAMA BARANG". |

Halaman detail invoice juga kini menampilkan baris <strong>PPN</strong> bila
PPN memang berlaku. DPP tidak ditampilkan sebagai baris tersendiri karena ia
bukan nilai yang ditambahkan ke total, melainkan hanya dasar perhitungan.

Ketiga hal ini dijaga oleh `tests/Feature/InvoiceTaxDisplayTest.php`.

### 8.6 Catatan untuk pembaca buku

- Mengubah tarif hanya memengaruhi dokumen yang dihitung setelah perubahan
  disimpan, karena nilai pajak tersimpan pada dokumen saat dibuat.
- Pengendali juga menuliskan beberapa kunci alternatif (misalnya
  `finance.tax_book_rate`, `finance.book_tax`, `finance.pajak_buku`, dan
  sejenisnya) dengan nilai yang sama. Kunci-kunci itu akan tampak pada daftar
  Konfigurasi; buku hanya menyebutkan kunci utamanya.
- Seluruh 17 invoice pada data contoh hanya berisi buku, sehingga tidak ada
  invoice barang lainnya yang dapat dipotret. Perilaku cabang barang lainnya
  diverifikasi melalui pengujian, bukan melalui gambar.

---

## 9. Harga Jual Buku per Zona

### 9.1 Yang berubah

- Formulir **Buku** mengganti satu kolom "Harga Jual" dengan tiga: **Harga Jual
  Zona 3**, **Zona 4**, dan **Zona 5**. Minimal satu wajib diisi.
- Kolom **Zona** dihapus dari formulir **Sekolah**.
- Zona 3, 4, dan 5 disemai oleh `MasterDataSeeder`; tabel `zonas` sebelumnya kosong.
- Formulir pesanan kini mengirim `zona_prices` untuk setiap barang, sehingga
  pemilih **Zona Harga** benar-benar mengubah harga. Sebelumnya data itu tidak
  pernah dikirim, sehingga pemilih zona tidak berpengaruh.

### 9.2 Cara penyimpanan

| Isian | Disimpan ke |
|---|---|
| Harga Jual Zona 3 | `product_prices`, zona berkode `Z3` |
| Harga Jual Zona 4 | `product_prices`, zona berkode `Z4` |
| Harga Jual Zona 5 | `product_prices`, zona berkode `Z5` |

`products.price` mengikuti harga zona pertama yang terisi (Zona 3 → 4 → 5).
Kolom itu tetap diperlukan karena daftar Buku dan cadangan harga di sisi peladen
membacanya.

Mengosongkan sebuah kolom menghapus harga zona tersebut. Saat menyunting buku
lama yang belum punya harga zona, harga lamanya ditampilkan pada kolom Zona 3
agar tidak hilang tanpa sengaja.

### 9.3 Verifikasi

- 5 pengujian baru di `tests/Feature/BookZonePriceTest.php`.
- Diperiksa langsung pada aplikasi: formulir Buku memuat tepat tiga input
  `price_zona_3/4/5` tanpa input `price`; formulir Sekolah tidak memuat
  `zona_id`; pemilih Zona Harga berisi Zona 3, 4, 5 dan data `zona_prices`
  terkirim ke halaman pesanan.

### 9.4 Catatan

- Karena zona tidak lagi melekat pada sekolah, `OrderService` tidak dapat lagi
  menyimpulkan zona dari sekolah. Harga zona dipilih per pesanan lewat pemilih
  Zona Harga; bila tidak dipilih, dipakai `products.price`.
- Kolom `schools.zona_id` tetap ada di basis data tetapi tidak lagi diisi dari
  formulir. Tidak ada sekolah pada data contoh yang memilikinya.
- Karena data transaksi contoh sudah direset (lihat §4), tidak ada pesanan nyata
  untuk menguji pemilihan zona secara visual. Perilaku ini dijaga oleh pengujian.

---

## 10. Cara Mengulang Seluruh Pemeriksaan

```bash
# 1. jalankan aplikasi
php artisan serve --host=127.0.0.1 --port=8000

# 2. ambil ulang tangkapan layar (baca-saja) + periksa
node docs/panduan/scripts/capture.mjs
python docs/panduan/scripts/qa_images.py

# 3. bangun PDF
node docs/panduan/build/render.mjs

# 4. periksa PDF
python docs/panduan/scripts/check_html.py
python docs/panduan/scripts/qa_pdf.py
```
