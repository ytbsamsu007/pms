/*
 Navicat Premium Data Transfer

 Source Server         : jaya_teknik
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : jaya_teknik

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 02/10/2026 16:22:20
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for activity_log
-- ----------------------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log`  (
  `id_log` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_karyawan` int NULL DEFAULT NULL,
  `nama_pengguna` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modul` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aksi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_referensi` int NULL DEFAULT NULL,
  `nomor_referensi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_sebelumnya` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `data_sesudahnya` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id_log`) USING BTREE,
  INDEX `idx_karyawan`(`id_karyawan` ASC) USING BTREE,
  INDEX `idx_modul_aksi`(`modul` ASC, `aksi` ASC) USING BTREE,
  INDEX `idx_referensi`(`modul` ASC, `id_referensi` ASC) USING BTREE,
  INDEX `idx_nomor_referensi`(`nomor_referensi` ASC) USING BTREE,
  INDEX `idx_created_at`(`created_at` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 68 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for adjustment_stok
-- ----------------------------
DROP TABLE IF EXISTS `adjustment_stok`;
CREATE TABLE `adjustment_stok`  (
  `id_adjustment` int NOT NULL AUTO_INCREMENT,
  `nomor_adjustment` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_adjustment` datetime NOT NULL,
  `id_site` int NOT NULL,
  `jenis_adjustment` enum('PENAMBAHAN','PENGURANGAN','SET_STOK') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alasan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `status` enum('DRAFT','PENDING','APPROVED','REJECTED','BATAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'DRAFT',
  `id_karyawan` int NOT NULL,
  `id_karyawan_approved` int NULL DEFAULT NULL,
  `tanggal_approved` datetime NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjustment`) USING BTREE,
  UNIQUE INDEX `nomor_adjustment`(`nomor_adjustment` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for adjustment_stok_detail
-- ----------------------------
DROP TABLE IF EXISTS `adjustment_stok_detail`;
CREATE TABLE `adjustment_stok_detail`  (
  `id_adjustment_detail` int NOT NULL AUTO_INCREMENT,
  `id_adjustment` int NOT NULL,
  `id_barang` int NOT NULL,
  `qty_sistem` double NULL DEFAULT 0,
  `qty_fisik` double NULL DEFAULT 0,
  `qty_adjustment` double NULL DEFAULT 0,
  `qty_akhir` double NULL DEFAULT 0,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `harga_satuan` double NULL DEFAULT 0,
  `subtotal_adjustment` double NULL DEFAULT 0,
  PRIMARY KEY (`id_adjustment_detail`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for backup
-- ----------------------------
DROP TABLE IF EXISTS `backup`;
CREATE TABLE `backup`  (
  `id_backup` int NOT NULL AUTO_INCREMENT,
  `email_backup` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_backup` datetime NULL DEFAULT current_timestamp,
  `id_karyawan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `scope` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `lokasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'tersimpan difolder project untuk kebutuhan restore',
  `nama_file` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_karyawan_restore` int NULL DEFAULT NULL COMMENT 'yang melakukan restore',
  `tanggal_restore` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id_backup`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for barang
-- ----------------------------
DROP TABLE IF EXISTS `barang`;
CREATE TABLE `barang`  (
  `id_barang` int NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_merk` int NULL DEFAULT 1,
  `id_kategori` int NULL DEFAULT 1,
  `default_id_vendor` int NULL DEFAULT NULL,
  `nama_barang` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `jenis` tinyint(1) NULL DEFAULT 1 COMMENT '1 = Persediaan , 0 = Jasa',
  `satuan` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PCS',
  `asset` tinyint(1) NULL DEFAULT 0 COMMENT '1 = Asset, 0 = Bukan Asset',
  `serial_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `foto1` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `foto2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `id_karyawan` int NOT NULL,
  `aktif` tinyint NULL DEFAULT 1,
  `PPnBM` tinyint(1) NULL DEFAULT 0 COMMENT '1=mewah , 0 tidak',
  `rate_PPnBM` double NULL DEFAULT 0,
  PRIMARY KEY (`id_barang`) USING BTREE,
  INDEX `idx_barang_nama`(`nama_barang` ASC) USING BTREE,
  INDEX `idx_barang_kode`(`kode_barang` ASC) USING BTREE,
  INDEX `idx_barang_aktif`(`aktif` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for barang_hargavendor
-- ----------------------------
DROP TABLE IF EXISTS `barang_hargavendor`;
CREATE TABLE `barang_hargavendor`  (
  `id_harga` int NOT NULL AUTO_INCREMENT,
  `id_barang` int NULL DEFAULT NULL,
  `id_vendor` int NULL DEFAULT NULL,
  `harga_set` double NULL DEFAULT NULL,
  `berlaku` date NULL DEFAULT NULL,
  PRIMARY KEY (`id_harga`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for barang_stok
-- ----------------------------
DROP TABLE IF EXISTS `barang_stok`;
CREATE TABLE `barang_stok`  (
  `id_stok` int NOT NULL AUTO_INCREMENT,
  `id_barang` int NOT NULL,
  `id_site` int NOT NULL,
  `stok` int NULL DEFAULT NULL,
  PRIMARY KEY (`id_stok`) USING BTREE,
  UNIQUE INDEX `uq_barang_site`(`id_barang` ASC, `id_site` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 95 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for divisi
-- ----------------------------
DROP TABLE IF EXISTS `divisi`;
CREATE TABLE `divisi`  (
  `id_divisi` int NOT NULL AUTO_INCREMENT,
  `kode_divisi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_divisi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `level` tinyint(1) NULL DEFAULT NULL,
  `id_karyawan_headof` int NULL DEFAULT NULL COMMENT 'jadikan approval',
  PRIMARY KEY (`id_divisi`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for dokumen
-- ----------------------------
DROP TABLE IF EXISTS `dokumen`;
CREATE TABLE `dokumen`  (
  `id_dokumen` int NOT NULL AUTO_INCREMENT,
  `tipe_dokumen` enum('REQUEST','PURCHASE','RECEIVING','RETUR PO','FAKTUR PO','PAYMENT PO','MUTASI BARANG','ADJUSTMENT STOK') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nomor_dokumen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_dokumen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_dokumen` date NULL DEFAULT NULL,
  `id_karyawan` int NULL DEFAULT NULL COMMENT 'karyawan yang upload',
  `password_open` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'membuka_file internal',
  `file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'internal url_file',
  `external_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'googledrive',
  `unduh` int NULL DEFAULT 0 COMMENT 'jumlah_unduh',
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_dokumen`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for faktur_po
-- ----------------------------
DROP TABLE IF EXISTS `faktur_po`;
CREATE TABLE `faktur_po`  (
  `id_faktur` int NOT NULL AUTO_INCREMENT,
  `nomor_faktur` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nomor_faktur_vendor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'nomor invoice vendor',
  `nomor_faktur_pajak` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_faktur_pajak` date NULL DEFAULT NULL,
  `tanggal_faktur_vendor` date NOT NULL COMMENT 'tanggal invoice vendor',
  `tanggal_terima_faktur_vendor` date NOT NULL COMMENT 'tanggal invoice datang ke perusahaan/pembeli',
  `term_of_payment` tinyint NOT NULL DEFAULT 0 COMMENT 'default mengikuti purchase_order.term_of_payment',
  `tanggal_jatuh_tempo` date NOT NULL COMMENT 'hitung hari dari term_of_payment',
  `id_po` int NOT NULL,
  `id_rcv` int NOT NULL,
  `id_vendor` int NOT NULL,
  `id_site` int NOT NULL,
  `nama_bank` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nomor_rekening` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `atas_nama_rekening` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_karyawan` int NOT NULL,
  `subtotal_po` double NOT NULL DEFAULT 0,
  `subtotal_diterima` double NOT NULL DEFAULT 0,
  `nilai_retur` double NOT NULL DEFAULT 0,
  `diskon` double NOT NULL DEFAULT 0,
  `rate_pajak` tinyint NOT NULL DEFAULT 0 COMMENT 'PPN',
  `nominal_pajak` double NOT NULL DEFAULT 0 COMMENT 'PPN',
  `dpp` double NOT NULL DEFAULT 0,
  `rate_ppnbm` double NULL DEFAULT NULL,
  `nominal_ppnbm` double NULL DEFAULT NULL,
  `biaya_lain` double NOT NULL DEFAULT 0,
  `total_tagihan` double NOT NULL DEFAULT 0,
  `status` enum('DRAFT','BELUM DIBAYAR','SEBAGIAN DIBAYAR','LUNAS','BATAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'BELUM DIBAYAR',
  `terbayar` double NOT NULL DEFAULT 0,
  `sisa_tagihan` double NOT NULL DEFAULT 0,
  `file_faktur_vendor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `file_faktur_pajak` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_faktur`) USING BTREE,
  UNIQUE INDEX `uk_nomor_faktur`(`nomor_faktur` ASC) USING BTREE,
  INDEX `idx_id_po`(`id_po` ASC) USING BTREE,
  INDEX `idx_id_rcv`(`id_rcv` ASC) USING BTREE,
  INDEX `idx_id_vendor`(`id_vendor` ASC) USING BTREE,
  INDEX `idx_status`(`status` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for faktur_po_detail
-- ----------------------------
DROP TABLE IF EXISTS `faktur_po_detail`;
CREATE TABLE `faktur_po_detail`  (
  `id_faktur_detail` int NOT NULL AUTO_INCREMENT,
  `id_faktur` int NOT NULL,
  `id_barang` int NOT NULL,
  `qty_po` double NOT NULL DEFAULT 0,
  `qty_rcv` double NOT NULL DEFAULT 0,
  `qty_retur` double NOT NULL DEFAULT 0,
  `qty_tagih` double NOT NULL DEFAULT 0,
  `satuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `harga_satuan` double NOT NULL DEFAULT 0,
  `diskon_item` double NOT NULL DEFAULT 0,
  `subtotal` double NOT NULL DEFAULT 0,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_faktur_detail`) USING BTREE,
  INDEX `idx_id_faktur`(`id_faktur` ASC) USING BTREE,
  INDEX `idx_id_barang`(`id_barang` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for info
-- ----------------------------
DROP TABLE IF EXISTS `info`;
CREATE TABLE `info`  (
  `id_info` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `isi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `file` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `aktif` tinyint NULL DEFAULT 1,
  `id_karyawan` int NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `divisi_array` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'terlihat oleh divisi\r\ncontoh input\r\n1,2,3',
  `tampil_login` tinyint(1) NULL DEFAULT 0 COMMENT '1 = ya , 0 tidak',
  PRIMARY KEY (`id_info`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for jabatan
-- ----------------------------
DROP TABLE IF EXISTS `jabatan`;
CREATE TABLE `jabatan`  (
  `id_jabatan` int NOT NULL AUTO_INCREMENT,
  `kode_jabatan` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Auto JB-001',
  `nama_jabatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_divisi` int NULL DEFAULT NULL,
  `level` tinyint(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id_jabatan`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for karyawan
-- ----------------------------
DROP TABLE IF EXISTS `karyawan`;
CREATE TABLE `karyawan`  (
  `id_karyawan` int NOT NULL AUTO_INCREMENT,
  `kode_karyawan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_jabatan` int NULL DEFAULT NULL,
  `nama_karyawan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_divisi` int NOT NULL,
  `tanggal_bergabung` datetime NULL DEFAULT NULL,
  `aktif` tinyint(1) NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `no_handphone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `status_karyawan` tinyint(1) NOT NULL COMMENT 'Magang (Internship), PKWT (Perjanjian Kerja Waktu Tertentu), PKWTT (Perjanjian Kerja Waktu Tidak Tertentu), Pekerja paruh waktu (Part-time), Harian Lepas (Casual Workers), Freelance / Pekerja Lepas, Outsourcing / Alih Daya, Volunteer / Sukarelawan\r\n',
  `login_web` tinyint(1) NULL DEFAULT NULL,
  `tanggal_lahir` date NULL DEFAULT NULL,
  `tempat_lahir` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `jenis_kelamin` tinyint NULL DEFAULT NULL COMMENT '1= Laki-Laki , 0 = Perempuan',
  `id_site` int NULL DEFAULT NULL,
  PRIMARY KEY (`id_karyawan`, `status_karyawan`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for kategori_barang
-- ----------------------------
DROP TABLE IF EXISTS `kategori_barang`;
CREATE TABLE `kategori_barang`  (
  `id_kategori` int NOT NULL AUTO_INCREMENT,
  `kode_kategori` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_kategori` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id_kategori`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for menu_level
-- ----------------------------
DROP TABLE IF EXISTS `menu_level`;
CREATE TABLE `menu_level`  (
  `id_levelmenu` int NOT NULL AUTO_INCREMENT,
  `id_jabatan` int NOT NULL,
  `kategori_menu` enum('MENU UTAMA','OPERASIONAL','MASTER DATA','LAPORAN') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_menu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `is_parent` tinyint(1) NULL DEFAULT 1 COMMENT '1 = ya 0 tidak',
  `id_parent` int NULL DEFAULT NULL COMMENT 'isi jika bukan parent merujuk ke id_levelmenu',
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `akses` tinyint(1) NULL DEFAULT 1 COMMENT '1 = ya 0 tidak',
  `terlihat` tinyint(1) NULL DEFAULT 1 COMMENT '1 = ya 0 tidak',
  `created_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'bi-circle',
  `urutan` int NULL DEFAULT 1,
  PRIMARY KEY (`id_levelmenu`) USING BTREE,
  INDEX `idx_jabatan_urutan`(`id_jabatan` ASC, `urutan` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 228 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for merk_barang
-- ----------------------------
DROP TABLE IF EXISTS `merk_barang`;
CREATE TABLE `merk_barang`  (
  `id_merk` int NOT NULL AUTO_INCREMENT,
  `kode_merk` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_merk` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `aktif` tinyint NULL DEFAULT 1,
  PRIMARY KEY (`id_merk`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 168 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for mutasi_order
-- ----------------------------
DROP TABLE IF EXISTS `mutasi_order`;
CREATE TABLE `mutasi_order`  (
  `id_mutasi` int NOT NULL AUTO_INCREMENT,
  `tanggal_mutasi` datetime NULL DEFAULT NULL,
  `kode_mutasi` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'DI-YYMM-00000',
  `nomor_surat_mutasi` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'opsional',
  `id_karyawan` int NOT NULL COMMENT 'yang menginputkan data',
  `id_karyawan_request` int NOT NULL COMMENT 'yang meminta mutasi',
  `id_karyawan_approved` int NULL DEFAULT NULL COMMENT 'yang menyetujui',
  `id_site_asal` int NULL DEFAULT NULL,
  `id_site_tujuan` int NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('DRAFT','MENUNGGU PERSETUJUAN','DISETUJUI','DITERIMA SITE TUJUAN','DIKIRIM SITE ASAL','BATAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'DRAFT',
  `biaya_operasional` double NULL DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id_mutasi`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for mutasi_order_detail
-- ----------------------------
DROP TABLE IF EXISTS `mutasi_order_detail`;
CREATE TABLE `mutasi_order_detail`  (
  `id_mutasi_detail` int NOT NULL,
  `id_mutasi` int NOT NULL,
  `id_barang` int NULL DEFAULT NULL,
  `qty` double NULL DEFAULT NULL,
  PRIMARY KEY (`id_mutasi_detail`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for otp_verification
-- ----------------------------
DROP TABLE IF EXISTS `otp_verification`;
CREATE TABLE `otp_verification`  (
  `id_otp` int NOT NULL AUTO_INCREMENT,
  `user_type` enum('karyawan','users') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'karyawan',
  `id_user` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `otp_code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'CHANGE_PASSWORD',
  `attempts` int NOT NULL DEFAULT 0,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id_otp`) USING BTREE,
  INDEX `idx_user_action`(`user_type` ASC, `id_user` ASC, `action_type` ASC) USING BTREE,
  INDEX `idx_otp_expires`(`otp_code` ASC, `expires_at` ASC, `is_used` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pajak_keluaran
-- ----------------------------
DROP TABLE IF EXISTS `pajak_keluaran`;
CREATE TABLE `pajak_keluaran`  (
  `id_pajak_keluaran` int NOT NULL AUTO_INCREMENT,
  `id_karyawan` int NULL DEFAULT NULL COMMENT 'yang input',
  `tahun` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bulan` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ppn_keluaran` double NOT NULL DEFAULT 0 COMMENT 'nilai pajak keluaran penjualan / hasil',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pajak_keluaran`) USING BTREE,
  UNIQUE INDEX `uk_pajak_masa`(`tahun` ASC, `bulan` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for payment_purchase
-- ----------------------------
DROP TABLE IF EXISTS `payment_purchase`;
CREATE TABLE `payment_purchase`  (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `id_faktur` int NULL DEFAULT NULL,
  `status_pembayaran` tinyint NULL DEFAULT 0 COMMENT '1=lunas , 0 = belum',
  `jenis_pembayaran` tinyint NULL DEFAULT NULL COMMENT '1=1x bayar, 0=kredit',
  `total_diskon` double NOT NULL DEFAULT 0,
  `total_bayar` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_pembayaran`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for payment_purchase_detail
-- ----------------------------
DROP TABLE IF EXISTS `payment_purchase_detail`;
CREATE TABLE `payment_purchase_detail`  (
  `id_pembayaran_detail` int NOT NULL AUTO_INCREMENT,
  `id_pembayaran` int NOT NULL,
  `kode_pembayaran` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_bayar` datetime NULL DEFAULT NULL,
  `id_karyawan` int NOT NULL COMMENT 'petugas admin yang input',
  `id_karyawan_approved` int NULL DEFAULT NULL,
  `bank_pengirim` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'bank yang mentransfer',
  `norek_pengirim` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `an_pengirim` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'atas nama',
  `nominal_pengiriman` double NULL DEFAULT NULL,
  `nominal_diskon` double NOT NULL DEFAULT 0,
  `keterangan_diskon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `biaya_admin` double NULL DEFAULT NULL,
  `no_ref` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'bukti tranfser berhasil',
  `file_bukti_bayar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `sisa_piutang` double NULL DEFAULT NULL COMMENT 'sisa piutang kredit pembelian',
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `bank_tujuan` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `norek_tujuan` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `an_pengiriman` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran_detail`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pembatalan_transaksi
-- ----------------------------
DROP TABLE IF EXISTS `pembatalan_transaksi`;
CREATE TABLE `pembatalan_transaksi`  (
  `id_pembatalan` int NOT NULL AUTO_INCREMENT,
  `nomor_bap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_dokumen` enum('PO','FAKTUR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_referensi` int NOT NULL,
  `nomor_referensi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `state_tahapan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_vendor` int NULL DEFAULT NULL,
  `nama_vendor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nilai_transaksi` decimal(15, 2) NULL DEFAULT 0.00,
  `kategori_alasan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alasan_detail` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_karyawan_batal` int NULL DEFAULT NULL,
  `nama_karyawan_batal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_batal` datetime NOT NULL,
  `email_notif_sent` tinyint(1) NULL DEFAULT 0,
  `created_at` datetime NULL DEFAULT current_timestamp,
  PRIMARY KEY (`id_pembatalan`) USING BTREE,
  UNIQUE INDEX `nomor_bap`(`nomor_bap` ASC) USING BTREE,
  INDEX `idx_jenis_ref`(`jenis_dokumen` ASC, `id_referensi` ASC) USING BTREE,
  INDEX `idx_nomor_bap`(`nomor_bap` ASC) USING BTREE,
  INDEX `idx_tanggal`(`tanggal_batal` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for penomoran
-- ----------------------------
DROP TABLE IF EXISTS `penomoran`;
CREATE TABLE `penomoran`  (
  `id_nomor` int NOT NULL AUTO_INCREMENT,
  `nama_penomoran` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tipe_transaksi` enum('REQUEST','PURCHASE','RECEIVING','RETUR PO','FAKTUR PO','PAYMENT PO','MUTASI BARANG','ADJUSTMENT STOK') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tipe_penomoran` tinyint(1) NULL DEFAULT 0 COMMENT '0 = tidak reset, 1 reset harian, 2 reset bulanan, 3 reset tahuan',
  `digit_counter` tinyint(1) NULL DEFAULT NULL COMMENT 'misal 3 = 003, 013. misal 5 = 00005, 00010',
  `format` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `id_karyawan` int NOT NULL,
  PRIMARY KEY (`id_nomor`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for profile
-- ----------------------------
DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile`  (
  `id_perusahaan` int NOT NULL,
  `nama` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `telepon1` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `whatsapp` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `alamat_gps` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `kota` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `provinsi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `npwp` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `KLU` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `NITKU` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `timezone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Asia/Makassar',
  `pajak12` tinyint(1) NULL DEFAULT 1,
  `updated_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `picture` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id_perusahaan`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for purchase_order
-- ----------------------------
DROP TABLE IF EXISTS `purchase_order`;
CREATE TABLE `purchase_order`  (
  `id_po` int NOT NULL AUTO_INCREMENT,
  `nomor_po` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'PO-YYMM-0000001',
  `tanggal_po` date NULL DEFAULT NULL,
  `id_karyawan` int NULL DEFAULT NULL COMMENT 'pembuat_purchasing_order',
  `id_site` int NULL DEFAULT NULL,
  `id_vendor` int NOT NULL,
  `status` enum('DRAFT','REVIEW INTERNAL','DISETUJUI INTERNAL','TIDAK DISETUJUI INTERNAL','REVIEW VENDOR','DIPROSES VENDOR','DITERIMA','BATAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `prioritas` enum('NORMAL','URGENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_status` datetime NULL DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `pengiriman` enum('Vendor','Expedisi','Internal') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_pengiriman` datetime NULL DEFAULT NULL,
  `term_of_payment` tinyint NULL DEFAULT 30 COMMENT '0 = C.O.D, >=1 adalah hari',
  `id_receiving` int NULL DEFAULT NULL,
  `id_karyawan_approved` int NULL DEFAULT NULL COMMENT 'yang menyetujui purchasing_order diambil dari karyawan.id_karyawan',
  `total_termasuk_pajak` tinyint NULL DEFAULT NULL COMMENT '1 = ya PPN',
  `pajak` tinyint NULL DEFAULT 0 COMMENT 'PPN',
  `diskon` double NULL DEFAULT 0,
  `total_termasuk_PPnBM` tinyint NULL DEFAULT 0 COMMENT '1 = ya PPnBM',
  `pajak_PPnBM` tinyint NULL DEFAULT NULL COMMENT 'PPnBM rate',
  PRIMARY KEY (`id_po`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for purchase_order_detail
-- ----------------------------
DROP TABLE IF EXISTS `purchase_order_detail`;
CREATE TABLE `purchase_order_detail`  (
  `id_po_detail` int NOT NULL AUTO_INCREMENT,
  `id_po` int NULL DEFAULT NULL,
  `id_barang` int NULL DEFAULT NULL,
  `qty` double NULL DEFAULT NULL,
  `harga` double NULL DEFAULT NULL,
  `diskon` double NULL DEFAULT NULL,
  `subtotal` double NULL DEFAULT NULL,
  `kena_pajak` double NULL DEFAULT NULL COMMENT 'PPN',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `kena_pajak_ppnbm` double NULL DEFAULT NULL,
  PRIMARY KEY (`id_po_detail`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for receiving_order
-- ----------------------------
DROP TABLE IF EXISTS `receiving_order`;
CREATE TABLE `receiving_order`  (
  `id_rcv` int NOT NULL AUTO_INCREMENT,
  `nomor_rcv` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'RI-YYMM-0000001 / nomor SPB',
  `id_po` int NOT NULL,
  `id_karyawan` int NULL DEFAULT NULL COMMENT 'diambil dari karyawan khusus logistik dan jabatannya',
  `tanggal_rcv` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `tanggal_diterima` datetime NULL DEFAULT NULL,
  `nomor_sj` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'nomor surat jalan dari vendor',
  `file_sj` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'hnya nama file + ext',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `status` tinyint(1) NULL DEFAULT NULL COMMENT '1=diterima semua, 0=diterima sebagian',
  `print` tinyint(1) NULL DEFAULT NULL COMMENT '1 = ya',
  `print_date` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id_rcv`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for receiving_order_detail
-- ----------------------------
DROP TABLE IF EXISTS `receiving_order_detail`;
CREATE TABLE `receiving_order_detail`  (
  `id_rcv_detail` int NOT NULL AUTO_INCREMENT,
  `id_rcv` int NULL DEFAULT NULL,
  `id_barang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `qty` int NULL DEFAULT NULL,
  `status_qc` tinyint(1) NULL DEFAULT NULL COMMENT 'Kondisi 1=Baik,0=Rusak ',
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  PRIMARY KEY (`id_rcv_detail`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 28 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for rekening_bank
-- ----------------------------
DROP TABLE IF EXISTS `rekening_bank`;
CREATE TABLE `rekening_bank`  (
  `id_bank` int NOT NULL AUTO_INCREMENT,
  `nama_bank` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `atasnama_rekening` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `no_rekening` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  `id_karyawan` int NULL DEFAULT NULL COMMENT 'pembuat rekening',
  PRIMARY KEY (`id_bank`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for request_order
-- ----------------------------
DROP TABLE IF EXISTS `request_order`;
CREATE TABLE `request_order`  (
  `id_request` int NOT NULL AUTO_INCREMENT,
  `nomor` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `tanggal_ro` datetime NULL DEFAULT current_timestamp,
  `id_karyawan` int NULL DEFAULT NULL,
  `id_site` int NULL DEFAULT NULL,
  `status` enum('DRAFT','TERKIRIM','DISETUJUI LOGISTIK','TIDAK DISETUJUI LOGISTIK','DISETUJUI PURCHASING','TIDAK DISETUJUI PURCHASING','DITERIMA FULL','DITERIMA SEBAGIAN','BATAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'DRAFT',
  `prioritas` enum('NORMAL','URGENT') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'NORMAL',
  `id_vendor` int NULL DEFAULT NULL,
  `tanggal_status` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `id_po` int NULL DEFAULT NULL COMMENT 'Jika sudah terbit jadi po',
  `id_karyawan_approved` int NULL DEFAULT NULL COMMENT 'khusus untuk menyetujui dari mekanik',
  PRIMARY KEY (`id_request`) USING BTREE,
  INDEX `idx_ro_status_tanggal`(`status` ASC, `tanggal_ro` ASC) USING BTREE,
  INDEX `idx_ro_karyawan`(`id_karyawan` ASC) USING BTREE,
  INDEX `idx_ro_site`(`id_site` ASC) USING BTREE,
  INDEX `idx_ro_nomor`(`nomor` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 30 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for request_order_detail
-- ----------------------------
DROP TABLE IF EXISTS `request_order_detail`;
CREATE TABLE `request_order_detail`  (
  `id_request_detail` int NOT NULL AUTO_INCREMENT,
  `id_request` int NOT NULL,
  `id_barang` int NULL DEFAULT NULL,
  `kode_barang` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_barang` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `qty` double NULL DEFAULT NULL,
  `satuan` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PCS',
  `harga` double NULL DEFAULT 0,
  `subtotal` double NULL DEFAULT 0,
  PRIMARY KEY (`id_request_detail`) USING BTREE,
  INDEX `idx_rod_request`(`id_request` ASC) USING BTREE,
  INDEX `idx_rod_barang`(`id_barang` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 88 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for retur_po
-- ----------------------------
DROP TABLE IF EXISTS `retur_po`;
CREATE TABLE `retur_po`  (
  `id_po_retur` int NOT NULL AUTO_INCREMENT,
  `nomor_po_retur` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_karyawan` int NULL DEFAULT NULL COMMENT 'pembuat karyawan logistik',
  `id_karyawan_approved` int NULL DEFAULT NULL COMMENT 'ID Pejabat Penyetuju',
  `tanggal_po_retur` datetime NULL DEFAULT NULL,
  `kompensasi` tinyint(1) NULL DEFAULT 1 COMMENT '1= tukar unit, 0=pengurangan piutang dan barang',
  `id_vendor` int NOT NULL,
  `pic_vendor` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'karyawan vendor yang bertanggung jawab atas retur',
  `id_site` int NULL DEFAULT NULL COMMENT 'tujuan site dikirim kembali',
  `id_po` int NOT NULL,
  `id_rcv` int NOT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `updated_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `total` double NULL DEFAULT NULL COMMENT 'total harga yang di retur',
  `status` enum('DRAFT','MENUNGGU KONFIRMASI VENDOR','DISETUJUI VENDOR','TIDAK DISETUJUI VENDOR','DIKIRIM KE VENDOR','DITERIMA') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'DRAFT',
  `nominal_pajak` double NULL DEFAULT NULL COMMENT 'angka nominal pajak yang dibatalkan',
  `rate_pajak` tinyint NULL DEFAULT 0 COMMENT 'Tarif PPN: 0, 11, atau 12 %',
  `nomor_sj_retur` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nomor_nota_retur_pajak` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `pengiriman_retur` enum('Vendor','Expedisi','Internal') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'Vendor',
  `biaya_retur` double NULL DEFAULT 0,
  PRIMARY KEY (`id_po_retur`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for retur_po_detail
-- ----------------------------
DROP TABLE IF EXISTS `retur_po_detail`;
CREATE TABLE `retur_po_detail`  (
  `id_po_retur_detail` int NOT NULL AUTO_INCREMENT,
  `id_po_retur` int NOT NULL,
  `id_barang` int NOT NULL,
  `qty_retur` double NULL DEFAULT NULL,
  `satuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'PCS',
  `harga_satuan` double NULL DEFAULT 0,
  `subtotal` double NULL DEFAULT 0,
  `alasan_retur` enum('RUSAK_FISIK','CACAT_PRODUKSI','SALAH_SPESIFIKASI','KURANG_PENGIRIMAN','KADALUARSA_EXP') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'RUSAK_FISIK',
  `keterangan_kerusakan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `foto_bukti` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Path / nama file foto',
  `qty_diganti` double NULL DEFAULT 0,
  PRIMARY KEY (`id_po_retur_detail`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for site
-- ----------------------------
DROP TABLE IF EXISTS `site`;
CREATE TABLE `site`  (
  `id_site` int NOT NULL AUTO_INCREMENT,
  `kode_site` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_site` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `jenis_site` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'Pusat, Office, Bengkel, Logistik, Lain Lain',
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `alamat_gps` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `no_hp` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `id_karyawan_headof` int NULL DEFAULT NULL,
  `penyimpanan_stok` tinyint NULL DEFAULT 0 COMMENT '1 = ya, 0 tidak',
  PRIMARY KEY (`id_site`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for smtp_server
-- ----------------------------
DROP TABLE IF EXISTS `smtp_server`;
CREATE TABLE `smtp_server`  (
  `id_stmp` int NOT NULL AUTO_INCREMENT,
  `nama_provider` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `link_provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `stmp_server` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `port` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `user_login` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `limit_harian` int NULL DEFAULT NULL COMMENT '300 perhari',
  `sisa_harian` int NULL DEFAULT NULL,
  `aktif` tinyint NULL DEFAULT 1 COMMENT '1 aktif',
  PRIMARY KEY (`id_stmp`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id_users` int NOT NULL AUTO_INCREMENT,
  `nama_users` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  `created_at` datetime NULL DEFAULT current_timestamp ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_users`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for vendor
-- ----------------------------
DROP TABLE IF EXISTS `vendor`;
CREATE TABLE `vendor`  (
  `id_vendor` int NOT NULL AUTO_INCREMENT,
  `kode_vendor` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_perusahaan` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `gps_alamat` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `no_telepon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `kota` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `person` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `kontak_person` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `website` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp,
  `update_at` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `jenis_vendor` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `aktif` tinyint(1) NULL DEFAULT 1,
  `nomor_rekening` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `nama_bank` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `term_of_payment` int NULL DEFAULT NULL COMMENT 'days',
  `saldo_hutang_terakhir` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_vendor`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
