# Purchase Operational Management System

Aplikasi manajemen pembelian (Purchasing) dan operasional terintegrasi yang dirancang khusus untuk memenuhi kebutuhan *End-to-End* perusahaan (PT Jaya Teknik). Sistem ini mengelola alur permintaan barang dari lapangan hingga pelunasan pembayaran oleh departemen keuangan.

## 🌟 Fitur Utama

Sistem ini terdiri dari beberapa modul utama yang saling terhubung:

- **🔐 Multi-Role Access:** Hak akses berjenjang mulai dari Admin, Mekanik, Logistik, Purchasing, Finance, hingga Manager.
- **📦 Manajemen Inventory:** Pemantauan batas stok minimum (Minimum Stock), pergerakan barang, dan pengelolaan multi-site/gudang.
- **📝 Request Order (RO):** Sistem pengajuan kebutuhan material/suku cadang dari lapangan dengan validasi gudang dan *approval* manajerial.
- **🛒 Purchase Order (PO):** Pembuatan pesanan ke pihak ketiga (Vendor) dengan kalkulasi nilai transaksi otomatis (Pajak, Diskon, Ongkir).
- **🚛 Penerimaan & Retur (Receiving):** Pencatatan penerimaan fisik barang (termasuk parsial) yang otomatis mengupdate stok secara *real-time*.
- **💰 Finance & Account Payable:** Pencatatan hutang berbasis akrual, pelunasan bertahap, dan riwayat kas keluar.

## 💻 Tech Stack

- **Backend:** PHP (Native/Procedural dengan arsitektur berbasis API untuk beberapa modul)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **UI Framework:** Bootstrap 5
- **Database:** MySQL / MariaDB

## 🚀 Cara Instalasi

1. **Persiapan Server Lokal:**
   Pastikan Anda sudah menginstal XAMPP, WAMP, atau *web server* sejenis yang mendukung PHP dan MySQL.

2. **Clone Repositori:**
   Buka terminal/Command Prompt, arahkan ke folder `htdocs` (jika menggunakan XAMPP), lalu jalankan:
   ```bash
   git clone https://github.com/username-anda/JT_Purchase.git
   ```

3. **Konfigurasi Database:**
   - Buka phpMyAdmin (biasanya di `http://localhost/phpmyadmin`).
   - Buat database baru (misalnya `jt_purchase_db`).
   - *Import* file SQL bawaan (jika tersedia di folder `/database` atau `/sql`) ke dalam database yang baru dibuat.
   - Buka file konfigurasi di `config/config.php` (atau file serupa yang mengatur koneksi DB) dan sesuaikan detail koneksi database Anda:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'jt_purchase_db');
     ```

4. **Jalankan Aplikasi:**
   Buka *browser* dan akses URL: 
   ```
   http://localhost/JT_Purchase
   ```

## 🔒 Fitur Keamanan Tambahan
- **Session Management:** Proteksi akses berlapis untuk mencegah manipulasi URL antar-*role*.
- **Relational Data Protection:** Fitur *Anti-Hapus* untuk master data (Karyawan, Barang, Vendor) yang sudah pernah digunakan dalam transaksi guna menjaga integritas laporan.

---
*© 2026 Samsu Bahri - All Rights Reserved*
