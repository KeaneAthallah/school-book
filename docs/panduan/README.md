# Panduan Pengguna ARKARA PM — Sumber & Cara Membangun Ulang

Direktori ini memuat seluruh bahan buku panduan: sumber HTML yang dapat disunting,
tangkapan layar asli, skrip penangkap gambar, dan skrip pembangun PDF.

Hasil akhir: `docs/Panduan-Pengguna-School-Book-Sales-Monitoring-System.pdf`

---

## Isi Direktori

```
docs/panduan/
├── book/                      Sumber HTML kanonik (dapat disunting)
│   ├── css/book.css           Gaya cetak A4 (Paged.js)
│   ├── fonts/                 Font merek (Instrument Sans)
│   ├── 00-cover.html          Sampul + kolofon
│   ├── 01-frontmatter.html    Kata Pengantar, Tentang Buku, Daftar Isi, Daftar Gambar
│   ├── 10-part1.html          Bagian I  — Kenal Aplikasi
│   ├── 20-part2.html          Bagian II — Memulai
│   ├── 30-part3.html          Bagian III — Peran & Izin
│   ├── 40-part4a-master.html  Bagian IV — Dashboard & data induk
│   ├── 42-part4b-operasional.html  Bagian IV — Transaksi & keuangan
│   ├── 50-part5.html          Bagian V  — Alur kerja lengkap
│   ├── 60-part6.html          Bagian VI — Laporan
│   ├── 70-part7.html          Bagian VII — Pemecahan masalah
│   ├── 80-part8.html          Bagian VIII — Keamanan & praktik baik
│   └── 99-appendix.html       Lampiran A–H
├── screens/                   Tangkapan layar asli + inventory.md + mutations.md
├── build/render.mjs           Penggabung + pencetak PDF
├── scripts/
│   ├── capture.mjs            Penangkap tangkapan layar (Playwright + Edge)
│   ├── qa_images.py           Pemeriksa gambar (Pillow)
│   ├── qa_pdf.py              Pemeriksa PDF (PyMuPDF)
│   └── check_html.py          Pemeriksa struktur HTML
├── book-rendered.html         Hasil gabungan (dihasilkan otomatis, jangan disunting)
├── build-report.json          Ringkasan build terakhir
├── coverage.md                Daftar periksa cakupan halaman → bagian buku
├── QA.md                      Laporan pemeriksaan mutu
└── package.json               Hanya untuk dependensi build (pagedjs)
```

---

## Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| PHP + aplikasi ARKARA PM | `vendor/` sudah terpasang; basis data `db_school` terisi. |
| Node.js | Dipakai untuk `capture.mjs` dan `render.mjs`. |
| `playwright-core` | Diambil dari `node_modules` aplikasi utama (versi 1.64.1). Tidak perlu mengunduh peramban: skrip memakai **Microsoft Edge** yang sudah terpasang (`channel: 'msedge'`). |
| `pagedjs` | Dipasang lokal di direktori ini (tercatat pada `package.json`). |
| Python 3 + Pillow + PyMuPDF | Hanya untuk skrip pemeriksaan mutu. |

Penyiapan sekali saja:

```bash
cd docs/panduan
npm install
```

---

## Membangun Ulang

### 1. Jalankan aplikasi

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Bila berkas `public/hot` ada sementara peladen pengembangan Vite tidak berjalan, hapus dulu
agar halaman memakai aset hasil bangun:

```bash
del public\hot        # Windows
rm public/hot         # Unix
```

### 2. Pastikan data contoh tersedia

Tangkapan layar hanya bermakna bila basis data berisi data contoh. Periksa dulu:

```bash
mysql -h 127.0.0.1 -u root -p db_school -e "SELECT COUNT(*) FROM orders;"
```

Bila jumlahnya nol (misalnya setelah menjalankan `DemoDataResetSeeder`), isi ulang
sebelum mengambil gambar:

```bash
php artisan db:seed --class=DashboardDemoSeeder
```

Tanpa langkah ini, `capture.mjs` akan menimpa tangkapan layar yang baik dengan
halaman daftar yang kosong.

### 3. Ambil tangkapan layar (pilihan — hanya bila antarmuka berubah)

```bash
node docs/panduan/scripts/capture.mjs
```

- 6 peran masuk secara terpisah, jadi setiap dashboard tertangkap dengan navigasinya sendiri.
- Hasil ditulis ke `screens/` beserta `screens/inventory.md` yang memetakan setiap gambar ke
  rute, peran, dan bagian buku.
- **Bersifat baca-saja secara bawaan.** Satu keadaan (form konfirmasi pengiriman) menuntut
  pembuatan dokumen baru; itu hanya dijalankan bila diminta:

  ```bash
  # Windows
  set CAPTURE_MUTATIONS=1 && node docs/panduan/scripts/capture.mjs
  # Unix
  CAPTURE_MUTATIONS=1 node docs/panduan/scripts/capture.mjs
  ```

  Mutasi dicatat ke `screens/mutations.md` dan dapat dipulihkan dengan
  `php artisan db:seed --class=DemoDataResetSeeder`.

### 4. Bangun PDF

```bash
node docs/panduan/build/render.mjs
```

Skrip ini:

1. menggabungkan seluruh berkas `book/*.html` menurut urutan nama;
2. menomori setiap gambar menurut urutan kemunculan dan membangkitkan **Daftar Gambar**;
3. memeriksa bahwa setiap gambar yang dirujuk benar-benar ada di disk;
4. menyajikan `docs/panduan/` melalui HTTP sementara, lalu mencetak memakai Edge + Paged.js
   sehingga Daftar Isi dan Daftar Gambar memperoleh nomor halaman sungguhan.

Hasil: PDF di `docs/` dan `build-report.json`.

### 5. Periksa mutu

```bash
python docs/panduan/scripts/qa_images.py    # gambar terisi, tidak kosong
python docs/panduan/scripts/check_html.py   # struktur HTML seimbang
python docs/panduan/scripts/qa_pdf.py       # A4, nomor halaman, mojibake, luberan
```

---

## Menyunting Isi Buku

- **Teks:** sunting berkas `book/*.html`, lalu jalankan ulang `render.mjs`. Nomor gambar
  diberikan otomatis mengikuti urutan, jadi Anda boleh menambah atau memindahkan gambar
  tanpa menghitung ulang apa pun.
- **Daftar Isi:** entri dan tautannya ada di `book/01-frontmatter.html`. Nomor halaman diisi
  otomatis oleh Paged.js melalui `target-counter`, jadi tidak perlu diisi tangan.
- **Daftar Gambar:** dibangkitkan otomatis. Cukup tandai tempatnya dengan
  `<!--DAFTAR-GAMBAR-->` (sudah ada di front matter).
- **Gambar:** letakkan di `screens/`, lalu rujuk dari satu berkas bab memakai
  `src="../screens/nama-berkas.png"`. Skrip build menulis ulang jalurnya saat menggabungkan.
- **Gaya:** seluruh tampilan diatur di `book/css/book.css`.

### Menambah gambar

```html
<figure class="fig" id="fig-nama-unik">
  <img src="../screens/nama-berkas.png" alt="Keterangan singkat">
  <figcaption>Keterangan yang muncul pada Daftar Gambar.</figcaption>
</figure>
```

Setiap gambar perlu `id` yang unik agar dapat ditautkan dari Daftar Gambar. Kelas tambahan:
`class="fig sempit"` untuk gambar yang lebih kecil, `class="fig full"` untuk lebar penuh.

---

## Cara Kerja Bagian Cetak

Gaya cetak memakai **Paged.js** untuk hal-hal yang tidak didukung peramban saat mencetak:

- `@page` bernama (`sampul`, `pembuka`, `dokumen`) untuk sampul dan halaman pembuka bab;
- header berjalan (`string-set: chapter content()`) yang menampilkan bab yang sedang dibaca;
- `target-counter(attr(href), page)` untuk nomor halaman di Daftar Isi dan Daftar Gambar.

Karena itu PDF tidak dapat dihasilkan hanya dengan mencetak peramban biasa — harus melalui
`build/render.mjs`.

---

## Berkas yang Dihasilkan Otomatis

Jangan disunting secara manual — akan tertimpa pada build berikutnya:

- `book-rendered.html`
- `build-report.json`
- `screens/inventory.md`
- `screens/mutations.md`
- `qa-pages/` (halaman contoh hasil pemeriksaan)
