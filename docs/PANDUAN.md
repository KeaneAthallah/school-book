# Panduan Penggunaan Aplikasi School Book

Dokumen ini menjelaskan seluruh fitur aplikasi secara detail agar pengguna dapat bekerja tanpa kebingungan.

---

## 1. Login dan Akun

### 1.1 Cara Login
1. Buka aplikasi di browser.
2. Masukkan **Email** dan **Password**.
3. Klik **Masuk**.
4. Jika berhasil, Anda masuk ke Dashboard.

### 1.2 Akun Default
- Admin: admin@sekolah.test / password
- Operator Pesanan: operator@sekolah.test / password
- Gudang: gudang@sekolah.test / password
- Keuangan: keuangan@sekolah.test / password
- Sales: sales@sekolah.test / password

### 1.3 Jika Tidak Bisa Login
- Pastikan email dan password benar.
- Pastikan database sudah dimigrasi dan di-seed.
- Jalankan `php artisan migrate --force` dan `php artisan db:seed --force` bila perlu.
- Restart server aplikasi.

---

## 2. Dashboard
Dashboard menampilkan:
- Ringkasan pesanan
- Stok
- Invoice
- Piutang
- Kartu **Pesanan Lunas Belum Dikirim** (invoice lunas tetapi barang belum terkirim penuh)

---

## 3. Master Data

### 3.1 Sekolah
Menu: **Master Data > Sekolah**

Field:
- NPSN: nomor pokok sekolah
- Nama Sekolah (wajib)
- Alamat
- Kecamatan
- Kabupaten/Kota
- Provinsi
- Kode Pos
- Kontak
- Telepon
- Email
- Kepala Sekolah
- Sales/PIC
- Zona
- Status (Aktif/Nonaktif)

Langkah tambah:
1. Klik **Tambah Sekolah**.
2. Isi semua field yang diperlukan.
3. Pilih Sales/PIC dan Status.
4. Klik **Simpan**.

Langkah ubah:
1. Klik **Ubah** pada baris sekolah.
2. Ubah data.
3. Klik **Simpan**.

Langkah hapus:
1. Klik **Hapus**.
2. Konfirmasi.

### 3.2 Penerbit
Menu: **Master Data > Penerbit**
Field: Nama, Kontak, Telepon, Email, Alamat, Logo, Status.

### 3.3 Buku
Menu: **Master Data > Buku**

Field:
- Kode Buku (wajib)
- ISBN
- Judul (wajib)
- Penerbit (wajib)
- Kategori
- Kelas
- Mata Pelajaran
- Klasifikasi: Het, Reguler, Depag, Banpem, Alcom
- Harga Jual (wajib)
- Harga Pokok
- Stok Minimum
- Lokasi Rak
- Status

Harga per Zona:
- Zona 3, Zona 4, Zona 5.
- Isi harga masing-masing zona.
- Jika dikosongkan, sistem memakai harga jual dasar.

### 3.4 Barang Lainnya
Menu: **Master Data > Barang Lainnya**

Field:
- Kode Barang (wajib)
- Nama Barang (wajib)
- Harga Jual (wajib)
- Harga Pokok
- Stok Minimum
- Lokasi Rak
- Nama Toko
- Satuan
- Status

### 3.5 Kategori Buku
Menu: **Master Data > Kategori Buku**
Field: Nama Kategori, Deskripsi.

### 3.6 Gudang
Menu: **Master Data > Gudang**
Field: Kode, Nama Gudang, Alamat, Status.

### 3.7 Sumber Pesanan
Menu: **Master Data > Sumber Pesanan**
Field: Kode, Nama, Tipe, Aktif.

### 3.8 Kurir
Menu: **Master Data > Kurir**
Field: Kode, Nama Kurir, Telepon, Status.

### 3.9 Metode Kirim
Menu: **Master Data > Metode Kirim**
Field: Nama Metode, Kurir, Biaya, Status.

### 3.10 Metode Bayar
Menu: **Master Data > Metode Bayar**
Field: Kode, Nama, Status.

### 3.11 Aturan Rebate
Menu: **Master Data > Aturan Rebate**
Field: Nama Aturan, Penerbit, Tipe, Nilai, Minimum Invoice, Wajib Lunas, Status.

### 3.12 Konfigurasi
Menu: **Master Data > Konfigurasi**
Field: Grup, Kunci, Nilai.

### 3.13 Pinjaman Tunai
Menu: **Master Data > Pinjaman Tunai**

Field:
- Nama (wajib)
- Nama Sekolah
- Tgl Pinjaman (wajib)
- Tgl Janji Bayar
- Nilai Pinjaman (wajib)
- Keterangan

Nomor pinjaman dibuat otomatis (PIN-xxxxxx).

---

## 4. Pesanan

### 4.1 Daftar Pesanan
Menu: **Pesanan > Nota Pesanan**
- Filter berdasarkan pencarian, status, dan sumber.
- Klik nomor pesanan untuk melihat detail.

### 4.2 Buat Pesanan
Menu: **Pesanan > Buat Pesanan**

Langkah:
1. Isi **Tanggal**.
2. Pilih **Sekolah**.
3. Pilih **Sumber Pesanan**.
4. (Opsional) Isi **Referensi Eksternal**.
5. Isi **Diskon Pesanan (%)** jika ada.
6. Isi **Catatan** jika perlu.
7. Pada bagian **Item Pesanan**:
   - Klik **Tambah Item** untuk menambah baris.
   - Pilih **Zona Harga** (Zona 3/4/5).
   - Ketik pada kolom barang untuk mencari.
   - Pilih barang dari daftar.
   - Isi **Qty**.
   - Harga akan terisi otomatis sesuai zona; dapat diubah.
   - Isi **Diskon** per baris jika ada.
8. Klik **Simpan Pesanan**.

### 4.3 Ubah Pesanan
- Hanya pesanan yang masih dapat diubah.
- Langkah sama seperti buat pesanan.

### 4.4 Detail Pesanan
Menampilkan:
- Informasi pesanan
- Item
- Pengiriman
- Invoice
- Backorder

### 4.5 Konfirmasi Pesanan
- Klik **Konfirmasi** pada detail pesanan.
- Sistem mengecek stok dan membuat backorder jika kurang.

### 4.6 Batalkan Pesanan
- Klik **Batalkan** jika pesanan belum selesai.

---

## 5. Diskon
- Diskon header dalam persen (0–100).
- Diskon per baris dalam nominal Rp.
- Sistem menghitung diskon header otomatis.

---

## 6. Pajak
### 6.1 Rumus
Barang lainnya/jasa:
- DPP = harga × 100/111
- PPN = 12% × DPP
- PPh = 2% × DPP

Buku:
- Bebas PPN
- PPh = 0,5% × harga

### 6.2 Tampilan
- Rincian DPP, PPN, PPh ditampilkan pada:
  - Nota Pesanan
  - Invoice
  - Kartu Nilai di detail pesanan

---

## 7. Nota Pesanan (Cetak)
- Dari detail pesanan klik **Cetak Nota**.
- Isi:
  - Header perusahaan
  - Info sekolah
  - Item: kode, judul, jenis (Buku/Barang Lainnya/Jasa)
  - Subtotal
  - Diskon %
  - DPP
  - PPN
  - PPh
  - Total
  - Status bayar: LUNAS / BELUM LUNAS
  - Status invoice

---

## 8. Penerimaan Barang (Gudang)
Menu: **Stok & Gudang > Barang Masuk**

Langkah:
1. Pilih Penerbit.
2. Pilih Gudang.
3. Pilih Tanggal.
4. Tambah item:
   - Cari barang
   - Isi Qty
   - Isi Harga Pokok
5. Isi Catatan (opsional).
6. Klik **Simpan**.
7. Konfirmasi penerimaan untuk menambah stok.

---

## 9. Stok
Menu: **Stok & Gudang > Stok Barang**
- Melihat stok per barang.
- Penyesuaian stok.

Menu: **Stok & Gudang > Mutasi Stok**
- Riwayat pergerakan stok.

---

## 10. Picking & Packing
Menu: **Picking & Packing > Daftar Picking**

Langkah:
1. Buat picking dari pesanan.
2. Buka detail picking.
3. Isi jumlah **Diambil** per item.
4. Klik **Simpan Hasil Picking** untuk menyimpan progress.
5. Klik **Simpan & Selesai Packing** untuk langsung packing dan lanjut ke picking berikutnya.

---

## 11. Pengiriman
Menu: **Pengiriman > Daftar Pengiriman**

Langkah:
1. Buat pengiriman dari pesanan.
2. Isi data pengiriman.
3. Konfirmasi pengiriman.
4. Setelah terkirim, tandai terkirim.
5. Cetak surat jalan.

---

## 12. Invoice
Menu: **Invoice**

Langkah:
1. Terbitkan invoice dari pengiriman/pesanan.
2. Buka detail invoice.
3. Cetak invoice.

Rincian invoice:
- Subtotal
- Diskon
- DPP
- PPN
- PPh
- Total

---

## 13. Daftar Hutang (Barang Belum Dikirim)
Menu: **Piutang > Barang Belum Dikirim**

Menampilkan invoice yang sudah lunas tetapi pesanannya belum terkirim penuh.

Kolom:
- No. Invoice
- No. Pesanan
- Sekolah
- Tgl Bayar
- Tgl Janji Kirim
- Nilai

---

## 14. Pembayaran
Menu: **Pembayaran**

Langkah:
1. Catat pembayaran.
2. Alokasikan ke invoice.
3. Verifikasi pembayaran.

---

## 15. Rebate / Cashback
Menu: **Rebate / Cashback**

Langkah:
1. Ajukan rebate.
2. Setujui rebate.
3. Bayarkan rebate dengan bukti foto.

---

## 16. Laporan
Menu: **Laporan**
- Lihat laporan.
- Ekspor laporan.

---

## 17. Tips Penggunaan
- Dropdown dengan lebih dari 20 opsi memiliki kotak pencarian.
- Pada item pesanan/penerimaan, ketik nama barang untuk mencari.
- Gunakan filter pada daftar untuk mempercepat pencarian.
- Jika data tidak tersimpan, pastikan field wajib terisi.

---

## 18. Pemecahan Masalah
### Tidak bisa login
- Cek email/password.
- Jalankan migrasi dan seeder.
- Restart server.

### Data tidak tersimpan
- Pastikan field wajib terisi.
- Cek koneksi database.

### Harga tidak sesuai zona
- Pastikan zona dipilih pada buat pesanan.
- Pastikan harga zona diisi pada master buku.