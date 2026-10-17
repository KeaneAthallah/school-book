# Panduan Penggunaan Aplikasi School Book

Panduan ini menjelaskan langkah-langkah penggunaan aplikasi untuk pengguna harian.

## 1. Login
1. Buka aplikasi di browser.
2. Masukkan email dan password.
3. Klik **Masuk**.
4. Setelah berhasil, Anda masuk ke **Dashboard**.

Akun default:
- Admin: admin@sekolah.test / password
- Operator Pesanan: operator@sekolah.test / password
- Gudang: gudang@sekolah.test / password
- Keuangan: keuangan@sekolah.test / password
- Sales: sales@sekolah.test / password

## 2. Dashboard
- Menampilkan ringkasan pesanan, stok, invoice, dan piutang.
- Kartu **Pesanan Lunas Belum Dikirim** menampilkan invoice yang sudah lunas tetapi barang belum terkirim.

## 3. Master Data

### 3.1 Sekolah
Menu: Master Data > Sekolah.
Langkah:
1. Klik **Tambah**.
2. Isi NPSN, Nama Sekolah (wajib), Alamat, Kecamatan, Kab/Kota, Provinsi, Kode Pos, Kontak, Telepon, Email, Kepala Sekolah.
3. Pilih Sales/PIC dan Status.
4. Klik **Simpan**.

### 3.2 Buku
Menu: Master Data > Buku.
Field utama: Kode, Judul, Penerbit, Kategori, Kelas, Mapel, Klasifikasi (Het/Reguler/Depag/Banpem/Alcom), Harga Jual, Harga Pokok, Stok Minimum, Lokasi Rak.
Harga per zona (Zona 3, 4, 5) diisi pada form buku.

### 3.3 Barang Lainnya
Menu: Master Data > Barang Lainnya.
Field: Kode, Nama Barang, Harga Jual, Harga Pokok, Stok Minimum, Lokasi Rak, Nama Toko, Satuan, Status.

### 3.4 Zona (hardcode)
Zona yang digunakan: Zona 3, Zona 4, Zona 5. Harga per zona diisi pada master Buku.

### 3.5 Pinjaman Tunai
Menu: Master Data > Pinjaman Tunai.
Field: Nama (wajib), Nama Sekolah, Tgl Pinjaman (wajib), Tgl Janji Bayar, Nilai Pinjaman (wajib), Keterangan.
Nomor pinjaman dibuat otomatis.

## 4. Buat Pesanan
Menu: Pesanan > Buat Pesanan.
Langkah:
1. Pilih Tanggal, Sekolah, Sumber Pesanan.
2. (Opsional) Isi Referensi Eksternal, Diskon Pesanan (%), Catatan.
3. Pada **Item Pesanan**:
   - Klik **Tambah Item** untuk menambah baris.
   - Pilih **Zona Harga** (Zona 3/4/5) agar harga item mengikuti zona.
   - Gunakan kotak pencarian untuk mencari barang.
   - Pilih Barang, isi Qty, Harga, Diskon.
4. Klik **Simpan Pesanan**.

## 5. Diskon Pesanan
- Diskon header dalam persen (0–100).
- Diskon per baris tetap nominal Rp.

## 6. Pajak (DPP / PPN / PPh)
- Barang lainnya/jasa:
  - DPP = harga × 100/111
  - PPN = 12% × DPP
  - PPh = 2% × DPP
- Buku:
  - Bebas PPN
  - PPh = 0,5% × harga
- Rincian ditampilkan di nota pesanan, invoice, dan kartu Nilai.

## 7. Nota Pesanan (Cetak)
- Dari detail pesanan klik **Cetak Nota**.
- Isi: header perusahaan, info sekolah, item (kode, judul, jenis Buku/Barang Lainnya), subtotal, diskon %, DPP, PPN, PPh, total, status LUNAS/BELUM LUNAS, status invoice.

## 8. Penerimaan Barang (Gudang)
Menu: Stok & Gudang > Barang Masuk.
- Pilih Penerbit, Gudang, Tanggal.
- Tambah item barang, qty, harga pokok.
- Simpan, lalu konfirmasi untuk menambah stok.

## 9. Picking & Packing
Menu: Picking & Packing.
- Proses picking dan packing sesuai daftar.

## 10. Pengiriman
Menu: Pengiriman.
- Buat pengiriman dari pesanan.
- Konfirmasi dan kirim.
- Cetak surat jalan.

## 11. Invoice
Menu: Invoice.
- Terbitkan invoice dari pengiriman/pesanan.
- Cetak invoice dengan rincian DPP/PPN/PPh.

## 12. Daftar Hutang (Barang Belum Dikirim)
Menu: Piutang > Barang Belum Dikirim.
- Menampilkan invoice yang sudah lunas tetapi pesanannya belum terkirim penuh.

## 13. Pembayaran
Menu: Pembayaran.
- Catat pembayaran dan alokasikan ke invoice.
- Verifikasi pembayaran.

## 14. Rebate / Cashback
Menu: Rebate / Cashback.
- Ajukan, setujui, bayarkan rebate dengan bukti foto.

## 15. Laporan
Menu: Laporan.
- Lihat dan ekspor laporan.

## 16. Tips
- Dropdown dengan lebih dari 20 opsi memiliki kolom pencarian.
- Gunakan filter pada daftar untuk mempercepat pencarian.