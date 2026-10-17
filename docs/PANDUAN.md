# Panduan Penggunaan Aplikasi School Book

## Data Sekolah
- Isi seluruh field (alamat, kecamatan, kab/kota, provinsi, kode pos, kontak, telepon, kepala sekolah) pada master Sekolah; data tersimpan lengkap.
- Sekolah dapat dikaitkan ke Zona untuk harga per zona.

## Pajak (DPP / PPN / PPh)
- Barang lainnya/jasa: DPP = harga × 100/111, PPN = 12% × DPP, PPh = 2% × DPP.
- Buku: bebas PPN, PPh = 0,5% × harga.
- Rincian ditampilkan di nota pesanan, invoice, dan kartu Nilai.

## Diskon Pesanan
- Diskon header dalam persen (0–100); diskon per baris tetap nominal Rp.

## Klasifikasi Buku
- Het, Reguler, Depag, Banpem, Alcom (master Buku).

## Harga per Zona
- Zona 3, 4, 5 (hardcode). Harga jual per zona diisi di master Buku.
- Pada buat pesanan pilih Zona Harga; harga item mengikuti zona, tetap bisa diubah.

## Nota Pesanan
- Cetak dari detail pesanan / pengiriman. Memuat item (kode, judul, jenis Buku/Barang Lainnya), total, subtotal, diskon %, DPP/PPN/PPh, status LUNAS/BELUM LUNAS.

## Daftar Hutang (Barang Belum Dikirim)
- Invoice lunas tetapi pesanan belum terkirim penuh. Menu di bawah Invoice.

## Pinjaman Tunai
- CRUD master Pinjaman Tunai; nomor otomatis PIN-xxxxxx.

## Pencarian Dropdown
- Dropdown dengan lebih dari 20 opsi memiliki kolom pencarian.
- Item pesanan dan penerimaan barang memiliki filter pencarian barang.