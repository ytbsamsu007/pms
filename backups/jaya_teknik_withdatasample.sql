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

 Date: 02/10/2026 16:26:52
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
-- Records of activity_log
-- ----------------------------

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
-- Records of adjustment_stok
-- ----------------------------

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
-- Records of adjustment_stok_detail
-- ----------------------------

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
-- Records of backup
-- ----------------------------

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
-- Records of barang
-- ----------------------------
INSERT INTO `barang` VALUES (1, 'BRG001', 1, 1, 2, 'Kawat Las LB-52 3.2mm Kobe Steel', 1, 'PCS', 0, NULL, NULL, NULL, 'Kawat las elektroda untuk lambung kapal & konstruksi berat', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (2, 'BRG002', 1, 1, 1, 'Plat Baja Marine Grade AH36 12mm x 5ft x 20ft', 1, 'UNIT', 0, NULL, NULL, NULL, 'Plat baja standar lambung kapal bersertifikat BKI/LR', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (3, 'BRG003', 1, 1, 1, 'Pipa Seamless Carbon Steel Sch 80 4 Inch', 1, 'PCS', 0, '', '', '', 'Pipa jalur bahan bakar & pendingin kapal', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (4, 'BRG004', 1, 1, 2, 'Mata Bubut Sandvik Coromant CNMG 120408', 1, 'PCS', 0, NULL, NULL, NULL, 'Insert bubut bubut as propeller dan shaft', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (5, 'BRG005', 1, 1, 3, 'Bearing Spherical Roller SKF 22220 EK', 1, 'PCS', 0, NULL, NULL, NULL, 'Bearing poros baling-baling kapal', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (6, 'BRG006', 1, 1, 4, 'Bronze Marine Globe Valve 3 Inch Flange PN16', 1, 'PCS', 0, NULL, NULL, NULL, 'Valve laut tahan korosi air asin', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (7, 'BRG007', 1, 1, 2, 'Mata Bor HSS Morse Taper 25mm Nachi', 1, 'PCS', 0, NULL, NULL, NULL, 'Mata bor mesin radial drill bengkel bubut', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (8, 'BRG008', 1, 1, 4, 'Cat Antifouling Jotun SeaForce 90 20L', 1, 'UNIT', 0, '', '', '', 'Cat bawah lambung kapal pencegah teritip', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (9, 'BRG0009', 3, 5, NULL, 'Imola 500 (2.5 HP)', 1, 'UNIT', 1, '', NULL, NULL, NULL, '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (10, 'BRG0010', 1, 1, 4, 'Marine Steel Plate', 1, 'PCS', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2608-0001', '2026-09-12 10:32:29', 2, 1, 0, 0);
INSERT INTO `barang` VALUES (11, 'BRG0011', 1, 1, 4, 'Cat Marine', 1, 'PCS', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2608-0001', '2026-09-12 10:32:29', 2, 1, 0, 0);
INSERT INTO `barang` VALUES (12, 'BRG0012', 1, 1, 4, 'Tali Baja', 1, 'PCS', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2608-0001', '2026-09-12 10:32:29', 2, 1, 0, 0);
INSERT INTO `barang` VALUES (13, 'BRG0013', 1, 1, 5, 'MT NEMO - MEJA KERJA', 1, 'UNIT', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2608-0003', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (14, 'BRG0014', 159, 10, 5, 'KURSI KANTOR', 1, 'UNIT', 1, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2608-0003', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (15, 'BRG0015', 1, 1, NULL, 'Baja Karbon', 1, 'MTR', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2608-0004', '2026-09-12 10:32:29', 2, 1, 0, 0);
INSERT INTO `barang` VALUES (16, 'BRG0016', 166, 13, 6, 'Kabel LAN CAT 6', 1, 'PCS', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0001', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (17, 'BRG0017', 1, 1, 6, 'HDMI 10m', 1, 'PCS', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0002', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (18, 'BRG0018', 1, 1, 7, 'Pintu Costum', 1, 'SET', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0003', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (19, 'BRG0019', 165, 10, 7, 'Kloset Duduk Dua Bagian CW420J', 1, 'UNIT', 1, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0003', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (20, 'BRG0020', 164, 10, 8, 'Homedoki', 1, 'UNIT', 1, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0004', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (21, 'BRG0021', 101, 23, 2, 'Bosch Impact Drill GSB 600', 1, 'UNIT', 1, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0005', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (22, 'BRG0022', 42, 14, 6, 'Printer EPSON L325', 1, 'UNIT', 0, '', NULL, NULL, 'Ditambahkan otomatis dari Request Order RO-2609-0006', '2026-09-12 10:32:29', 3, 1, 0, 0);
INSERT INTO `barang` VALUES (23, 'BRG0023', 42, 18, NULL, 'Epson XB300', 1, 'UNIT', 1, '', NULL, NULL, '', '2026-09-12 10:32:29', 1, 1, 0, 0);
INSERT INTO `barang` VALUES (24, 'BRG0024', 167, 7, NULL, 'Toyota Alphard 2.5 G', 1, 'UNIT', 1, 'KT 31 JS', NULL, NULL, 'Kendaraan entertaiment bos', '2026-09-12 10:42:16', 1, 1, 1, 25);
INSERT INTO `barang` VALUES (25, 'BRG0025', 167, 7, NULL, 'Lexus X7', 1, 'UNIT', 1, NULL, NULL, NULL, NULL, '2026-09-12 11:54:10', 1, 1, 1, 35);

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
-- Records of barang_hargavendor
-- ----------------------------
INSERT INTO `barang_hargavendor` VALUES (11, 3, 4, 20000, '2026-08-20');
INSERT INTO `barang_hargavendor` VALUES (12, 3, 3, 15000, '2026-08-20');
INSERT INTO `barang_hargavendor` VALUES (13, 11, 4, 250000, '2026-08-22');

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
-- Records of barang_stok
-- ----------------------------
INSERT INTO `barang_stok` VALUES (25, 3, 3, 0);
INSERT INTO `barang_stok` VALUES (26, 3, 2, 0);
INSERT INTO `barang_stok` VALUES (27, 3, 1, 2);
INSERT INTO `barang_stok` VALUES (28, 9, 3, 0);
INSERT INTO `barang_stok` VALUES (29, 9, 2, 0);
INSERT INTO `barang_stok` VALUES (30, 9, 1, 0);
INSERT INTO `barang_stok` VALUES (31, 10, 1, 0);
INSERT INTO `barang_stok` VALUES (32, 10, 2, 0);
INSERT INTO `barang_stok` VALUES (33, 10, 3, 0);
INSERT INTO `barang_stok` VALUES (34, 11, 1, 0);
INSERT INTO `barang_stok` VALUES (35, 11, 2, 0);
INSERT INTO `barang_stok` VALUES (36, 11, 3, 0);
INSERT INTO `barang_stok` VALUES (37, 12, 1, 0);
INSERT INTO `barang_stok` VALUES (38, 12, 2, 0);
INSERT INTO `barang_stok` VALUES (39, 12, 3, 0);
INSERT INTO `barang_stok` VALUES (40, 1, 1, 2);
INSERT INTO `barang_stok` VALUES (41, 1, 3, 10);
INSERT INTO `barang_stok` VALUES (42, 13, 1, 0);
INSERT INTO `barang_stok` VALUES (43, 13, 2, 0);
INSERT INTO `barang_stok` VALUES (44, 13, 3, 0);
INSERT INTO `barang_stok` VALUES (45, 14, 1, 0);
INSERT INTO `barang_stok` VALUES (46, 14, 2, 0);
INSERT INTO `barang_stok` VALUES (47, 14, 3, 5);
INSERT INTO `barang_stok` VALUES (48, 14, 4, 3);
INSERT INTO `barang_stok` VALUES (50, 13, 4, 3);
INSERT INTO `barang_stok` VALUES (51, 15, 1, 0);
INSERT INTO `barang_stok` VALUES (52, 15, 2, 0);
INSERT INTO `barang_stok` VALUES (53, 15, 3, 10);
INSERT INTO `barang_stok` VALUES (54, 15, 4, 0);
INSERT INTO `barang_stok` VALUES (55, 16, 1, 0);
INSERT INTO `barang_stok` VALUES (56, 16, 2, 0);
INSERT INTO `barang_stok` VALUES (57, 16, 3, 0);
INSERT INTO `barang_stok` VALUES (58, 16, 4, 1);
INSERT INTO `barang_stok` VALUES (59, 17, 1, 0);
INSERT INTO `barang_stok` VALUES (60, 17, 2, 1);
INSERT INTO `barang_stok` VALUES (61, 17, 3, 0);
INSERT INTO `barang_stok` VALUES (62, 17, 4, 1);
INSERT INTO `barang_stok` VALUES (63, 18, 1, 0);
INSERT INTO `barang_stok` VALUES (64, 18, 2, 0);
INSERT INTO `barang_stok` VALUES (65, 18, 3, 1);
INSERT INTO `barang_stok` VALUES (66, 18, 4, 0);
INSERT INTO `barang_stok` VALUES (67, 19, 1, 0);
INSERT INTO `barang_stok` VALUES (68, 19, 2, 0);
INSERT INTO `barang_stok` VALUES (69, 19, 3, 0);
INSERT INTO `barang_stok` VALUES (70, 19, 4, 0);
INSERT INTO `barang_stok` VALUES (71, 20, 1, 0);
INSERT INTO `barang_stok` VALUES (72, 20, 2, 0);
INSERT INTO `barang_stok` VALUES (73, 20, 3, 0);
INSERT INTO `barang_stok` VALUES (74, 20, 4, 1);
INSERT INTO `barang_stok` VALUES (75, 21, 1, 0);
INSERT INTO `barang_stok` VALUES (76, 21, 2, 0);
INSERT INTO `barang_stok` VALUES (77, 21, 3, 0);
INSERT INTO `barang_stok` VALUES (78, 21, 4, 0);
INSERT INTO `barang_stok` VALUES (79, 22, 1, 0);
INSERT INTO `barang_stok` VALUES (80, 22, 2, 0);
INSERT INTO `barang_stok` VALUES (81, 22, 3, 0);
INSERT INTO `barang_stok` VALUES (82, 22, 4, 3);
INSERT INTO `barang_stok` VALUES (83, 23, 4, 0);
INSERT INTO `barang_stok` VALUES (84, 23, 3, 0);
INSERT INTO `barang_stok` VALUES (85, 23, 2, 1);
INSERT INTO `barang_stok` VALUES (86, 23, 1, 0);
INSERT INTO `barang_stok` VALUES (89, 24, 4, 2);
INSERT INTO `barang_stok` VALUES (90, 24, 3, 0);
INSERT INTO `barang_stok` VALUES (91, 24, 2, 0);
INSERT INTO `barang_stok` VALUES (92, 24, 1, 0);

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
-- Records of divisi
-- ----------------------------
INSERT INTO `divisi` VALUES (1, 'DIV01', 'Manajemen', 1, 5);
INSERT INTO `divisi` VALUES (2, 'DIV02', 'Logistik & Gudang', 2, NULL);
INSERT INTO `divisi` VALUES (3, 'DIV03', 'Purchasing & Pengadaan', 2, NULL);
INSERT INTO `divisi` VALUES (4, 'DIV04', 'Bengkel Las & Bubut (Mekanik)', 3, NULL);
INSERT INTO `divisi` VALUES (5, 'DIV05', 'IT & Administrasi', 2, 6);
INSERT INTO `divisi` VALUES (6, 'DIV06', 'Finance', 2, 9);

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
-- Records of dokumen
-- ----------------------------

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
-- Records of faktur_po
-- ----------------------------
INSERT INTO `faktur_po` VALUES (7, 'FP26/0915/001', 'INV33192001', 'NPJ0123', '2026-09-15', '2026-09-15', '2026-09-15', 10, '2026-09-25', 19, 13, 6, 4, 'BCA', '81731111350', 'Arena Computer', 4, 5000000, 5000000, 0, 0, 11, 495495, 4504505, 0, 0, 0, 5000000, 'LUNAS', 5000000, 0, NULL, NULL, '', '2026-09-15 16:42:51', '2026-09-16 15:58:23');
INSERT INTO `faktur_po` VALUES (8, 'FP26/0916/001', 'INV021933', 'NPJ0221', '2026-09-16', '2026-09-16', '2026-09-16', 60, '2026-11-15', 18, 14, 9, 4, 'BCA', 'GRAHA TOYOTA SMD', 'Graha Toyota Samarinda', 4, 1500000000, 1500000000, 0, 0, 12, 180000000, 1500000000, 25, 375000000, 0, 2055000000, 'BELUM DIBAYAR', 0, 2055000000, NULL, NULL, '', '2026-09-16 08:53:37', '2026-09-16 15:58:30');
INSERT INTO `faktur_po` VALUES (9, 'FP26/0917/001', 'inv2191910', '1234', '2026-09-17', '2026-09-17', '2026-09-17', 10, '2026-09-27', 20, 15, 6, 4, 'BCA', '81731111350', 'Arena Computer', 4, 325000, 325000, 0, 0, 11, 32207, 292793, 0, 0, 0, 325000, 'LUNAS', 325000, 0, NULL, NULL, '', '2026-09-17 12:02:42', '2026-09-17 12:15:18');

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
-- Records of faktur_po_detail
-- ----------------------------
INSERT INTO `faktur_po_detail` VALUES (16, 7, 23, 1, 1, 0, 1, 'UNIT', 5000000, 0, 5000000, '');
INSERT INTO `faktur_po_detail` VALUES (17, 8, 24, 1, 1, 0, 1, 'UNIT', 1500000000, 0, 1500000000, '');
INSERT INTO `faktur_po_detail` VALUES (18, 9, 20, 1, 1, 0, 1, 'UNIT', 325000, 0, 325000, '');

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
-- Records of info
-- ----------------------------
INSERT INTO `info` VALUES (1, 'Jadwal Maintenance', 'Akan dilakukan maintenance sistem berkala pada hari Sabtu pukul 22:00 - 24:00 WIB.', '', 1, 1, '2026-09-12 08:56:59', '1,2,3,4,5', 1);
INSERT INTO `info` VALUES (2, 'Pemberitahuan Pengadaan Barang Q3', 'Seluruh kepala unit dan mekanik diharapkan mengajukan RO paling lambat akhir bulan ini.', '', 1, 1, '2026-08-22 13:12:22', '3,4', 0);

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
-- Records of jabatan
-- ----------------------------
INSERT INTO `jabatan` VALUES (1, 'JB-001', 'Administrator', 5, 1);
INSERT INTO `jabatan` VALUES (2, 'JB-002', 'Admin Logistik', 2, 3);
INSERT INTO `jabatan` VALUES (3, 'JB-003', 'Kepala Mekanik', 4, 2);
INSERT INTO `jabatan` VALUES (4, 'JB-004', 'Manager Cabang', 1, 1);
INSERT INTO `jabatan` VALUES (5, 'JB-005', 'Staff Purchasing', 3, 3);
INSERT INTO `jabatan` VALUES (6, 'JB-006', 'Staff Finance', 6, 3);
INSERT INTO `jabatan` VALUES (7, 'JB-007', 'Manager Finance', 6, 1);

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
-- Records of karyawan
-- ----------------------------
INSERT INTO `karyawan` VALUES (2, 'KRY002', 3, 'Budi Jaya', 4, '2026-08-19 22:23:08', 1, 'mekanik@jayateknis.com', '081234567891', '$2y$10$leTW0vO08SPhmk1K0Dp97uaE9cQabEjTDnHMwPFg7cMPiQebUGxo.', 2, 1, '1993-08-25', 'Bontang', 1, 1);
INSERT INTO `karyawan` VALUES (3, 'KRY003', 2, 'Agus Hermawan', 2, '2026-08-19 22:23:08', 1, 'dosen.samsu@gmail.com', '081234567892', '$2y$10$.flc709mORDo9MgHEB1cHuhuPuSyg/dCWBo6fQ70h/OLGqzTxRZdq', 2, 1, '2000-12-05', 'Medan', 1, NULL);
INSERT INTO `karyawan` VALUES (4, 'KRY004', 5, 'Siti Khodijah', 3, '2026-08-19 22:23:08', 1, 'sidaw37998@airhemp.com', '085512345678', '$2y$10$gXvuzhGeM6AmUDFDObJ8hOPaUp9BXUfQY77BO3cbV.v6zjRW/2Izu', 2, 1, '2000-01-03', 'Bandung', 2, NULL);
INSERT INTO `karyawan` VALUES (5, 'KRY005', 4, 'Hendra', 1, '2026-08-19 22:23:08', 1, 'tes.manager@gmail.com', '081234567894', '$2y$10$.flc709mORDo9MgHEB1cHuhuPuSyg/dCWBo6fQ70h/OLGqzTxRZdq', 2, 1, NULL, NULL, 1, 1);
INSERT INTO `karyawan` VALUES (6, 'KRY001', 1, 'Bambang Admin', 5, '2026-08-19 22:22:31', 1, 'admin@jayateknis.com', '081234567890', '$2y$10$gXvuzhGeM6AmUDFDObJ8hOPaUp9BXUfQY77BO3cbV.v6zjRW/2Izu', 2, 1, NULL, NULL, NULL, NULL);
INSERT INTO `karyawan` VALUES (7, 'KRY006', 2, 'Seno', 2, '2026-08-26 09:35:59', 1, NULL, NULL, NULL, 2, 0, NULL, NULL, 1, 1);
INSERT INTO `karyawan` VALUES (8, 'KRY007', 6, 'Diana Putri', 6, '2026-08-29 14:02:33', 1, 'lopeki8543@airhemp.com', '081234567899', '$2y$10$GoLgk6w86qjKHbNkGvBrG.9MFSot493s/XtTwpZ5qkGzE/lUsBD7e', 1, 1, '1996-09-21', 'Balikpapan', 2, 1);
INSERT INTO `karyawan` VALUES (9, 'MGR-FIN', 7, 'Farhan Manager Finance', 6, '2026-08-29 14:21:38', 1, 'shem1990@gmail.com', '081298765432', '$2y$10$P.gQplvSM8e4HXzcj0dADOI7lQle6.Cnd1mZhcFOg5B7XQhfzq2fi', 1, 1, NULL, NULL, NULL, NULL);
INSERT INTO `karyawan` VALUES (10, 'KRY010', 2, 'Lina', 2, '2026-09-17 12:26:08', 1, 'ytb.samsu@gmail.com', '085211234456', '$2y$10$RSx.s14mFMze7fyau12fHeG2QRhrKGHZNq59ZCYYIr5H9VupoAJ3.', 0, 1, '2000-05-06', 'Banjarbaru', 2, 3);

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
-- Records of kategori_barang
-- ----------------------------
INSERT INTO `kategori_barang` VALUES (1, 'KAT0001', 'Umum', 1);
INSERT INTO `kategori_barang` VALUES (2, 'KAT0002', 'Besi', 1);
INSERT INTO `kategori_barang` VALUES (4, 'KAT0003', 'Kawat', 0);
INSERT INTO `kategori_barang` VALUES (5, 'KAT0005', 'Kompresor Udara', 1);
INSERT INTO `kategori_barang` VALUES (6, 'KAT0006', 'Elektronik', 1);
INSERT INTO `kategori_barang` VALUES (7, 'KAT007', 'Kendaraan', 1);
INSERT INTO `kategori_barang` VALUES (8, 'KAT008', 'Komputer', 1);
INSERT INTO `kategori_barang` VALUES (9, 'KAT009', 'Laptop', 1);
INSERT INTO `kategori_barang` VALUES (10, 'KAT010', 'Furniture', 1);
INSERT INTO `kategori_barang` VALUES (11, 'KAT011', 'Kabel 2P', 1);
INSERT INTO `kategori_barang` VALUES (12, 'KAT012', 'Kabel 3P', 1);
INSERT INTO `kategori_barang` VALUES (13, 'KAT013', 'Kabel Jaringan', 1);
INSERT INTO `kategori_barang` VALUES (14, 'KAT014', 'Printer Kertas', 1);
INSERT INTO `kategori_barang` VALUES (15, 'KAT015', 'Printer 3D', 1);
INSERT INTO `kategori_barang` VALUES (16, 'KAT016', 'Mesin Produksi', 1);
INSERT INTO `kategori_barang` VALUES (17, 'KAT017', 'Alat Tulis', 1);
INSERT INTO `kategori_barang` VALUES (18, 'KAT018', 'Projector', 1);
INSERT INTO `kategori_barang` VALUES (19, 'KAT019', 'Alat Jaringan', 1);
INSERT INTO `kategori_barang` VALUES (20, 'KAT020', 'Set Kunci', 1);
INSERT INTO `kategori_barang` VALUES (21, 'KAT021', 'Safety Tools', 1);
INSERT INTO `kategori_barang` VALUES (22, 'KAT022', 'Tabung Gas', 1);
INSERT INTO `kategori_barang` VALUES (23, 'KAT0023', 'Bor Elektrik', 1);

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
-- Records of menu_level
-- ----------------------------
INSERT INTO `menu_level` VALUES (1, 1, 'MENU UTAMA', 'Dashboard', 0, NULL, '/admin/dashboard.php', 1, 1, '2026-08-20 19:53:50', 'bi-grid-1x2-fill', 1);
INSERT INTO `menu_level` VALUES (2, 1, 'OPERASIONAL', 'Request Order', 0, NULL, '/admin/pages/request_order/index.php', 1, 1, '2026-09-10 16:51:46', 'bi-file-earmark-text-fill', 2);
INSERT INTO `menu_level` VALUES (3, 1, 'OPERASIONAL', 'Buat RO Baru', 0, NULL, '/admin/pages/request_order/create.php', 1, 0, '2026-08-27 11:46:01', 'bi-plus-circle', 3);
INSERT INTO `menu_level` VALUES (4, 1, 'MASTER DATA', 'Profil Perusahaan', 0, NULL, '/admin/pages/profile/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-buildings', 4);
INSERT INTO `menu_level` VALUES (5, 1, 'MASTER DATA', 'Master Divisi', 0, NULL, '/admin/pages/divisi/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-diagram-3-fill', 5);
INSERT INTO `menu_level` VALUES (6, 1, 'MASTER DATA', 'Master Jabatan', 0, NULL, '/admin/pages/jabatan/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-briefcase-fill', 6);
INSERT INTO `menu_level` VALUES (7, 1, 'MASTER DATA', 'Master Site', 0, NULL, '/admin/pages/site/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-geo-alt-fill', 7);
INSERT INTO `menu_level` VALUES (8, 1, 'MASTER DATA', 'Master Karyawan', 0, NULL, '/admin/pages/user/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-people-fill', 8);
INSERT INTO `menu_level` VALUES (9, 1, 'MASTER DATA', 'Master Vendor', 0, NULL, '/admin/pages/vendor/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-truck', 9);
INSERT INTO `menu_level` VALUES (10, 1, 'MASTER DATA', 'Master Barang', 1, NULL, '#', 1, 1, '2026-08-20 19:53:50', 'bi-boxes', 10);
INSERT INTO `menu_level` VALUES (11, 1, 'MASTER DATA', 'Kategori', 0, 10, '/admin/pages/kategori/index.php', 1, 1, '2026-09-10 16:50:02', 'bi-tags', 1);
INSERT INTO `menu_level` VALUES (12, 1, 'MASTER DATA', 'Merk', 0, 10, '/admin/pages/merk/index.php', 1, 1, '2026-09-10 16:50:12', 'bi-bookmark-star', 2);
INSERT INTO `menu_level` VALUES (13, 1, 'MASTER DATA', 'Barang', 0, 10, '/admin/pages/barang/index.php', 1, 1, '2026-09-10 16:50:27', 'bi-box-seam', 3);
INSERT INTO `menu_level` VALUES (14, 1, 'MASTER DATA', 'Manajemen Menu', 0, 65, '/admin/pages/menu/index.php', 1, 1, '2026-08-22 12:31:34', 'bi-list-check', 12);
INSERT INTO `menu_level` VALUES (15, 2, 'MENU UTAMA', 'Dashboard', 0, NULL, '/admin/dashboard.php', 1, 1, '2026-08-20 19:53:50', 'bi-grid-1x2-fill', 1);
INSERT INTO `menu_level` VALUES (16, 2, 'OPERASIONAL', 'Request Order', 0, NULL, '/admin/pages/request_order/index.php', 1, 1, '2026-09-10 16:53:57', 'bi-file-earmark-text-fill', 2);
INSERT INTO `menu_level` VALUES (17, 2, 'OPERASIONAL', 'Buat RO Baru', 0, NULL, '/admin/pages/request_order/create.php', 1, 0, '2026-08-27 11:46:01', 'bi-plus-circle', 3);
INSERT INTO `menu_level` VALUES (23, 2, 'MASTER DATA', 'Master Vendor', 0, NULL, '/admin/pages/vendor/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-truck', 9);
INSERT INTO `menu_level` VALUES (24, 2, 'MASTER DATA', 'Master Barang', 1, NULL, '#', 1, 1, '2026-09-10 16:57:26', 'bi-boxes', 1);
INSERT INTO `menu_level` VALUES (25, 2, 'MASTER DATA', 'Kategori Barang', 0, 24, '/admin/pages/kategori/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-tags', 1);
INSERT INTO `menu_level` VALUES (26, 2, 'MASTER DATA', 'Merk Barang', 0, 24, '/admin/pages/merk/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-bookmark-star', 2);
INSERT INTO `menu_level` VALUES (27, 2, 'MASTER DATA', 'Katalog Barang', 0, 24, '/admin/pages/barang/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-box-seam', 3);
INSERT INTO `menu_level` VALUES (29, 3, 'MENU UTAMA', 'Dashboard', 0, NULL, '/admin/dashboard.php', 1, 1, '2026-08-20 19:53:50', 'bi-grid-1x2-fill', 1);
INSERT INTO `menu_level` VALUES (30, 3, 'OPERASIONAL', 'Request Order (RO)', 0, NULL, '/admin/pages/request_order/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-file-earmark-text-fill', 2);
INSERT INTO `menu_level` VALUES (31, 3, 'OPERASIONAL', 'Buat RO Baru', 0, NULL, '/admin/pages/request_order/create.php', 1, 0, '2026-08-27 11:46:01', 'bi-plus-circle', 3);
INSERT INTO `menu_level` VALUES (34, 3, 'MASTER DATA', 'Master Jabatan', 0, NULL, '/admin/pages/jabatan/index.php', 0, 0, '2026-08-20 20:10:24', 'bi-briefcase-fill', 6);
INSERT INTO `menu_level` VALUES (35, 3, 'MASTER DATA', 'Master Site', 0, NULL, '/admin/pages/site/index.php', 0, 0, '2026-08-20 20:10:21', 'bi-geo-alt-fill', 7);
INSERT INTO `menu_level` VALUES (36, 3, 'MASTER DATA', 'Master Karyawan', 0, NULL, '/admin/pages/user/index.php', 0, 0, '2026-08-20 20:10:18', 'bi-people-fill', 8);
INSERT INTO `menu_level` VALUES (37, 3, 'MASTER DATA', 'Master Vendor', 0, NULL, '/admin/pages/vendor/index.php', 0, 0, '2026-08-20 20:10:16', 'bi-truck', 9);
INSERT INTO `menu_level` VALUES (38, 3, 'MASTER DATA', 'Master Barang', 1, NULL, '#', 1, 1, '2026-09-04 16:57:37', 'bi-boxes', 10);
INSERT INTO `menu_level` VALUES (39, 3, 'MASTER DATA', 'Kategori Barang', 0, 38, '/admin/pages/kategori/index.php', 0, 0, '2026-08-20 20:10:14', 'bi-tags', 1);
INSERT INTO `menu_level` VALUES (40, 3, 'MASTER DATA', 'Merk Barang', 0, 38, '/admin/pages/merk/index.php', 0, 0, '2026-08-20 20:10:14', 'bi-bookmark-star', 2);
INSERT INTO `menu_level` VALUES (41, 3, 'MASTER DATA', 'Katalog Barang', 0, 38, '/admin/pages/barang/index.php', 1, 1, '2026-09-04 16:57:52', 'bi-box-seam', 3);
INSERT INTO `menu_level` VALUES (42, 3, 'MASTER DATA', 'Manajemen Menu', 0, NULL, '/admin/pages/menu/index.php', 0, 0, '2026-08-20 20:10:13', 'bi-list-check', 11);
INSERT INTO `menu_level` VALUES (43, 4, 'MENU UTAMA', 'Dashboard', 0, NULL, '/admin/dashboard.php', 1, 1, '2026-08-20 19:53:50', 'bi-grid-1x2-fill', 1);
INSERT INTO `menu_level` VALUES (46, 4, 'MASTER DATA', 'Profil Perusahaan', 0, NULL, '/admin/pages/profile/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-buildings', 4);
INSERT INTO `menu_level` VALUES (47, 4, 'MASTER DATA', 'Master Divisi', 0, NULL, '/admin/pages/divisi/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-diagram-3-fill', 5);
INSERT INTO `menu_level` VALUES (48, 4, 'MASTER DATA', 'Master Jabatan', 0, NULL, '/admin/pages/jabatan/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-briefcase-fill', 6);
INSERT INTO `menu_level` VALUES (49, 4, 'MASTER DATA', 'Master Site', 0, NULL, '/admin/pages/site/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-geo-alt-fill', 7);
INSERT INTO `menu_level` VALUES (50, 4, 'MASTER DATA', 'Master Karyawan', 0, NULL, '/admin/pages/user/index.php', 1, 1, '2026-08-20 19:53:50', 'bi-people-fill', 8);
INSERT INTO `menu_level` VALUES (57, 1, 'MASTER DATA', 'Server SMTP', 0, 65, '/admin/pages/smtp/index.php', 1, 1, '2026-08-22 12:33:15', 'bi-envelope-at-fill', 13);
INSERT INTO `menu_level` VALUES (58, 5, 'MENU UTAMA', 'Dashboard', 0, NULL, '/admin/dashboard.php', 1, 1, '2026-08-22 12:17:38', 'bi-grid-1x2-fill', 1);
INSERT INTO `menu_level` VALUES (59, 5, 'MASTER DATA', 'Kategori', 0, 66, '/admin/pages/kategori/index.php', 1, 1, '2026-09-10 16:59:39', 'bi-tags', 6);
INSERT INTO `menu_level` VALUES (60, 5, 'OPERASIONAL', 'Request Order (RO)', 0, NULL, '/admin/pages/request_order/index.php', 1, 1, '2026-08-22 12:17:38', 'bi-file-earmark-text-fill', 2);
INSERT INTO `menu_level` VALUES (61, 5, 'MASTER DATA', 'Merk', 0, 66, '/admin/pages/merk/index.php', 1, 1, '2026-09-10 16:59:46', 'bi-bookmark-star', 7);
INSERT INTO `menu_level` VALUES (63, 5, 'MASTER DATA', 'Barang', 0, 66, '/admin/pages/barang/index.php', 1, 1, '2026-09-10 16:59:57', 'bi-box-seam', 8);
INSERT INTO `menu_level` VALUES (64, 5, 'MASTER DATA', 'Master Vendor', 0, NULL, '/admin/pages/vendor/index.php', 1, 1, '2026-08-22 12:57:03', 'bi-truck', 4);
INSERT INTO `menu_level` VALUES (65, 1, 'MASTER DATA', 'Pengaturan', 1, NULL, '#', 1, 1, '2026-08-22 12:46:31', 'bi-gear-fill', 11);
INSERT INTO `menu_level` VALUES (66, 5, 'MASTER DATA', 'Master Barang', 1, NULL, '#', 1, 1, '2026-08-22 12:57:12', 'bi-boxes', 5);
INSERT INTO `menu_level` VALUES (67, 1, 'MENU UTAMA', 'Pengumuman', 0, NULL, '/admin/pages/info/index.php', 1, 1, '2026-08-22 13:26:44', 'bi-megaphone-fill', 2);
INSERT INTO `menu_level` VALUES (68, 4, 'MENU UTAMA', 'Pengumuman', 0, NULL, '/admin/pages/info/index.php', 1, 1, '2026-08-22 13:27:56', 'bi-megaphone-fill', 2);
INSERT INTO `menu_level` VALUES (69, 3, 'MENU UTAMA', 'Pengumuman', 0, NULL, '/admin/pages/info/index.php', 1, 1, '2026-08-22 13:29:04', 'bi-megaphone-fill', 2);
INSERT INTO `menu_level` VALUES (70, 2, 'MENU UTAMA', 'Pengumuman', 0, NULL, '/admin/pages/info/index.php', 1, 1, '2026-08-22 13:29:29', 'bi-megaphone-fill', 2);
INSERT INTO `menu_level` VALUES (71, 5, 'MENU UTAMA', 'Pengumuman', 0, NULL, '/admin/pages/info/index.php', 1, 1, '2026-08-22 13:30:09', 'bi-megaphone-fill', 2);
INSERT INTO `menu_level` VALUES (72, 1, 'OPERASIONAL', 'Purchase Order', 0, NULL, '/admin/pages/purchase_order/index.php', 1, 1, '2026-09-12 09:00:43', 'bi-cart-check', 3);
INSERT INTO `menu_level` VALUES (76, 5, 'OPERASIONAL', 'Purchase Order (PO)', 0, NULL, '/admin/pages/purchase_order/index.php', 1, 1, '2026-09-12 08:58:53', 'bi-cart-check', 3);
INSERT INTO `menu_level` VALUES (77, 1, 'OPERASIONAL', 'Penerimaan Barang', 0, NULL, '/admin/pages/receiving/index.php', 1, 1, '2026-08-26 09:23:10', 'bi-box-seam', 4);
INSERT INTO `menu_level` VALUES (78, 2, 'OPERASIONAL', 'Penerimaan Barang', 0, NULL, '/admin/pages/receiving/index.php', 1, 1, '2026-08-26 09:23:12', 'bi-box-seam', 4);
INSERT INTO `menu_level` VALUES (82, 1, 'OPERASIONAL', 'PO Outstanding', 0, NULL, 'admin/pages/purchase_order/outstanding.php', 1, 1, '2026-09-10 16:51:05', 'bi-hourglass-split', 8);
INSERT INTO `menu_level` VALUES (86, 5, 'OPERASIONAL', 'PO Outstanding', 0, NULL, 'admin/pages/purchase_order/outstanding.php', 1, 1, '2026-09-10 16:59:04', 'bi-hourglass-split', 5);
INSERT INTO `menu_level` VALUES (87, 1, 'OPERASIONAL', 'Retur PO', 1, NULL, '/admin/pages/retur_po/index.php', 1, 1, '2026-08-27 16:08:34', 'bi-arrow-return-left', 5);
INSERT INTO `menu_level` VALUES (88, 2, 'OPERASIONAL', 'Retur Purchase', 0, NULL, '/admin/pages/retur_po/index.php', 1, 1, '2026-09-10 16:55:55', 'bi-arrow-return-left', 5);
INSERT INTO `menu_level` VALUES (92, 1, 'OPERASIONAL', 'Faktur Purchase', 0, NULL, '/admin/pages/faktur_po/index.php', 1, 1, '2026-09-10 16:52:27', 'bi-receipt-cutoff', 6);
INSERT INTO `menu_level` VALUES (96, 5, 'OPERASIONAL', 'Faktur Purchase', 0, NULL, '/admin/pages/faktur_po/index.php', 1, 1, '2026-09-10 16:59:20', 'bi-receipt-cutoff', 4);
INSERT INTO `menu_level` VALUES (97, 1, 'OPERASIONAL', 'Pembayaran PO', 0, 0, '/admin/pages/pembayaran_po/index.php', 1, 1, '2026-08-29 13:56:27', 'bi-cash-coin', 7);
INSERT INTO `menu_level` VALUES (102, 6, 'OPERASIONAL', 'Pembayaran Purchase', 0, NULL, '/admin/pages/pembayaran_po/index.php', 1, 1, '2026-09-08 10:44:29', 'bi-cash-coin', 2);
INSERT INTO `menu_level` VALUES (103, 1, 'MASTER DATA', 'Atur Timezone', 0, 65, '/admin/pages/timezone/index.php', 1, 1, '2026-09-05 13:56:34', 'bi-clock-history', 14);
INSERT INTO `menu_level` VALUES (104, 1, 'OPERASIONAL', 'Tagihan Jatuh Tempo', 0, NULL, '/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php', 1, 1, '2026-09-14 12:07:14', 'bi bi-clock-history', 10);
INSERT INTO `menu_level` VALUES (106, 6, 'OPERASIONAL', 'Tagihan Jatuh Tempo', 0, NULL, '/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php', 1, 1, '2026-09-08 10:46:34', 'bi bi-clock-history', 1);
INSERT INTO `menu_level` VALUES (107, 7, 'OPERASIONAL', 'Tagihan Jatuh Tempo', 1, NULL, '#', 1, 1, '2026-09-10 16:35:07', 'bi bi-clock-history', 2);
INSERT INTO `menu_level` VALUES (108, 6, 'MENU UTAMA', 'Dashboard', 0, NULL, '/admin/dashboard.php', 1, 1, '2026-09-08 11:09:49', 'bi-grid-1x2-fill', 1);
INSERT INTO `menu_level` VALUES (109, 6, 'MENU UTAMA', 'Pengumuman', 0, NULL, '/admin/pages/info/index.php', 1, 1, '2026-09-08 11:15:12', 'bi-megaphone-fill', 2);
INSERT INTO `menu_level` VALUES (110, 6, 'OPERASIONAL', 'Edit Profil', 0, NULL, 'admin/pages/user/profile.php', 1, 0, '2026-09-08 11:16:43', 'bi bi-person-square', 3);
INSERT INTO `menu_level` VALUES (111, 1, 'MASTER DATA', 'Rekening Bank', 0, NULL, '/admin/pages/rekening_bank/index.php', 1, 1, '2026-09-15 11:01:50', 'bi-credit-card', 17);
INSERT INTO `menu_level` VALUES (113, 3, 'MASTER DATA', 'Rekening Bank', 1, NULL, '/admin/pages/rekening_bank/index.php', 0, 0, '2026-09-08 12:17:24', 'bi-credit-card', 12);
INSERT INTO `menu_level` VALUES (116, 6, 'MASTER DATA', 'Rekening Bank', 0, NULL, '/admin/pages/rekening_bank/index.php', 1, 1, '2026-09-15 10:59:30', 'bi-credit-card', 1);
INSERT INTO `menu_level` VALUES (117, 7, 'MASTER DATA', 'Rekening Bank', 1, NULL, '#', 1, 1, '2026-09-10 16:35:30', 'bi-credit-card', 3);
INSERT INTO `menu_level` VALUES (118, 1, 'LAPORAN', 'Log Aktivitas', 0, NULL, '/admin/pages/activity_log/index.php', 1, 1, '2026-09-19 12:30:13', 'bi-clock-history', 29);
INSERT INTO `menu_level` VALUES (120, 4, 'LAPORAN', 'Log Aktivitas', 0, NULL, '/admin/pages/activity_log/index.php', 1, 1, '2026-09-19 12:30:51', 'bi-clock-history', 12);
INSERT INTO `menu_level` VALUES (124, 1, 'LAPORAN', 'Pengeluaran Bank', 0, NULL, '/admin/pages/laporan/pengeluaran_bank.php', 1, 1, '2026-09-11 09:52:02', 'bi-bank', 24);
INSERT INTO `menu_level` VALUES (125, 4, 'LAPORAN', 'Pengeluaran Bank', 0, NULL, '/admin/pages/laporan/pengeluaran_bank.php', 1, 1, '2026-09-19 12:28:50', 'bi-bank', 9);
INSERT INTO `menu_level` VALUES (126, 5, 'LAPORAN', 'Pengeluaran Bank', 0, NULL, '/admin/pages/laporan/pengeluaran_bank.php', 1, 1, '2026-09-10 16:33:26', 'bi-bank', 13);
INSERT INTO `menu_level` VALUES (127, 6, 'LAPORAN', 'Pengeluaran Bank', 0, NULL, '/admin/pages/laporan/pengeluaran_bank.php', 1, 1, '2026-09-10 16:46:21', 'bi-bank', 4);
INSERT INTO `menu_level` VALUES (128, 7, 'LAPORAN', 'Pengeluaran Bank', 0, NULL, '/admin/pages/laporan/pengeluaran_bank.php', 1, 1, '2026-09-10 16:37:05', 'bi-bank', 8);
INSERT INTO `menu_level` VALUES (129, 1, 'LAPORAN', 'Pembelian Vendor', 0, NULL, '/admin/pages/laporan/pembelian_vendor.php', 1, 1, '2026-09-10 16:27:46', 'bi-shop', 20);
INSERT INTO `menu_level` VALUES (130, 4, 'LAPORAN', 'Pembelian Vendor', 0, NULL, '/admin/pages/laporan/pembelian_vendor.php', 1, 1, '2026-09-19 12:26:38', 'bi-shop', 5);
INSERT INTO `menu_level` VALUES (131, 5, 'LAPORAN', 'Pembelian  Vendor', 0, NULL, '/admin/pages/laporan/pembelian_vendor.php', 1, 1, '2026-09-10 16:33:21', 'bi-shop', 12);
INSERT INTO `menu_level` VALUES (132, 6, 'LAPORAN', 'Pembelian Vendor', 0, NULL, '/admin/pages/laporan/pembelian_vendor.php', 1, 1, '2026-09-10 16:45:48', 'bi-shop', 1);
INSERT INTO `menu_level` VALUES (133, 7, 'LAPORAN', 'Pembelian Vendor', 0, NULL, '/admin/pages/laporan/pembelian_vendor.php', 1, 1, '2026-09-10 16:36:55', 'bi-shop', 5);
INSERT INTO `menu_level` VALUES (134, 1, 'LAPORAN', 'Realisasi Kuantitas', 0, NULL, '/admin/pages/laporan/realisasi_kuantitas.php', 1, 1, '2026-09-10 16:25:04', 'bi-bar-chart-fill', 18);
INSERT INTO `menu_level` VALUES (135, 4, 'LAPORAN', 'Realisasi Kuantitas', 0, NULL, '/admin/pages/laporan/realisasi_kuantitas.php', 1, 1, '2026-09-19 12:26:01', 'bi-bar-chart-fill', 3);
INSERT INTO `menu_level` VALUES (136, 5, 'LAPORAN', 'Realisasi Kuantitas', 0, NULL, '/admin/pages/laporan/realisasi_kuantitas.php', 1, 1, '2026-09-10 16:33:07', 'bi-bar-chart-fill', 11);
INSERT INTO `menu_level` VALUES (139, 1, 'LAPORAN', 'Hutang Vendor', 0, NULL, '/admin/pages/laporan/hutang_vendor.php', 1, 1, '2026-09-11 09:51:55', 'bi-journal-text', 22);
INSERT INTO `menu_level` VALUES (140, 4, 'LAPORAN', 'Hutang Vendor', 0, NULL, '/admin/pages/laporan/hutang_vendor.php', 1, 1, '2026-09-19 12:27:49', 'bi-journal-text', 7);
INSERT INTO `menu_level` VALUES (142, 6, 'LAPORAN', 'Hutang Vendor', 0, NULL, '/admin/pages/laporan/hutang_vendor.php', 1, 1, '2026-09-10 16:46:16', 'bi-journal-text', 3);
INSERT INTO `menu_level` VALUES (143, 7, 'LAPORAN', 'Hutang Vendor', 0, NULL, '/admin/pages/laporan/hutang_vendor.php', 1, 1, '2026-09-10 16:36:27', 'bi-journal-text', 7);
INSERT INTO `menu_level` VALUES (144, 1, 'LAPORAN', 'Purchase Aging', 0, NULL, '/admin/pages/laporan/purchase_aging.php', 1, 1, '2026-09-10 16:25:29', 'bi-hourglass-split', 19);
INSERT INTO `menu_level` VALUES (145, 4, 'LAPORAN', 'Purchase Aging', 0, NULL, '/admin/pages/laporan/purchase_aging.php', 1, 1, '2026-09-19 12:26:17', 'bi-hourglass-split', 4);
INSERT INTO `menu_level` VALUES (146, 5, 'LAPORAN', 'Purchase Aging', 0, NULL, '/admin/pages/laporan/purchase_aging.php', 1, 1, '2026-09-10 16:32:59', 'bi-hourglass-split', 10);
INSERT INTO `menu_level` VALUES (149, 1, 'LAPORAN', 'Pembatalan', 0, NULL, '/admin/pages/laporan/pembatalan_transaksi.php', 1, 1, '2026-09-11 09:51:59', 'bi-x-octagon', 23);
INSERT INTO `menu_level` VALUES (150, 2, 'LAPORAN', 'Pembatalan', 0, NULL, '/admin/pages/laporan/pembatalan_transaksi.php', 1, 1, '2026-09-11 10:18:06', 'bi-x-octagon', 4);
INSERT INTO `menu_level` VALUES (152, 4, 'LAPORAN', 'Pembatalan Transaksi', 0, NULL, '/admin/pages/laporan/pembatalan_transaksi.php', 1, 1, '2026-09-19 12:28:24', 'bi-x-octagon', 8);
INSERT INTO `menu_level` VALUES (153, 5, 'LAPORAN', 'Laporan Pembatalan', 0, NULL, '/admin/pages/laporan/pembatalan_transaksi.php', 1, 1, '2026-09-10 16:33:35', 'bi-x-octagon', 14);
INSERT INTO `menu_level` VALUES (154, 6, 'LAPORAN', 'Pembatalan', 0, NULL, '/admin/pages/laporan/pembatalan_transaksi.php', 1, 1, '2026-09-10 16:46:03', 'bi-x-octagon', 2);
INSERT INTO `menu_level` VALUES (155, 7, 'LAPORAN', 'Pembatalan', 0, NULL, '/admin/pages/laporan/pembatalan_transaksi.php', 1, 1, '2026-09-10 16:36:00', 'bi-x-octagon', 6);
INSERT INTO `menu_level` VALUES (156, 1, 'LAPORAN', 'Data Barang-Site', 0, NULL, '/admin/pages/laporan/barang_site.php', 1, 1, '2026-09-10 16:24:24', 'bi-boxes', 16);
INSERT INTO `menu_level` VALUES (157, 2, 'LAPORAN', 'Data Barang-Site', 0, NULL, '/admin/pages/laporan/barang_site.php', 1, 1, '2026-09-11 10:18:00', 'bi-boxes', 1);
INSERT INTO `menu_level` VALUES (158, 4, 'LAPORAN', 'Data Barang per Site', 0, NULL, '/admin/pages/laporan/barang_site.php', 1, 1, '2026-09-19 12:25:28', 'bi-boxes', 1);
INSERT INTO `menu_level` VALUES (162, 1, 'OPERASIONAL', 'Mutasi Barang', 0, NULL, 'admin/pages/mutasi_barang/index.php', 1, 1, '2026-09-10 16:51:14', 'bi-arrow-left-right', 9);
INSERT INTO `menu_level` VALUES (163, 1, 'LAPORAN', 'Barang Keluar', 0, NULL, 'admin/pages/laporan/barang_keluar.php', 1, 1, '2026-09-10 16:24:46', 'bi-box-arrow-right', 17);
INSERT INTO `menu_level` VALUES (164, 2, 'OPERASIONAL', 'Mutasi Barang', 0, NULL, 'admin/pages/mutasi_barang/index.php', 1, 1, '2026-09-10 16:56:34', 'bi-arrow-left-right', 6);
INSERT INTO `menu_level` VALUES (165, 2, 'LAPORAN', 'Barang Keluar', 0, NULL, 'admin/pages/laporan/barang_keluar.php', 1, 1, '2026-09-11 10:18:04', 'bi-box-arrow-right', 2);
INSERT INTO `menu_level` VALUES (169, 4, 'LAPORAN', 'Barang Keluar', 0, NULL, 'admin/pages/laporan/barang_keluar.php', 1, 1, '2026-09-19 12:25:47', 'bi-box-arrow-right', 2);
INSERT INTO `menu_level` VALUES (174, 7, 'OPERASIONAL', 'Mutasi Barang', 0, NULL, 'admin/pages/mutasi_barang/index.php', 1, 1, '2026-09-10 16:34:50', 'bi-arrow-left-right', 1);
INSERT INTO `menu_level` VALUES (176, 1, 'LAPORAN', 'Rekap Retur PO', 0, NULL, '/admin/pages/laporan/retur_pembelian.php', 1, 1, '2026-09-11 09:51:35', 'bi-arrow-return-left', 21);
INSERT INTO `menu_level` VALUES (177, 2, 'LAPORAN', 'Retur Pembelian', 0, 0, '/admin/pages/laporan/retur_pembelian.php', 1, 1, '2026-09-11 10:18:07', 'bi-arrow-return-left', 3);
INSERT INTO `menu_level` VALUES (178, 3, 'LAPORAN', 'Laporan Retur Pembelian', 0, 0, '/admin/pages/laporan/retur_pembelian.php', 1, 1, '2026-09-11 08:37:22', 'bi-arrow-return-left', 96);
INSERT INTO `menu_level` VALUES (179, 4, 'LAPORAN', 'Rekap Retur Pembelian', 0, NULL, '/admin/pages/laporan/retur_pembelian.php', 1, 1, '2026-09-19 12:27:36', 'bi-arrow-return-left', 6);
INSERT INTO `menu_level` VALUES (180, 5, 'LAPORAN', 'Laporan Retur Pembelian', 0, 0, '/admin/pages/laporan/retur_pembelian.php', 1, 1, '2026-09-11 08:37:22', 'bi-arrow-return-left', 15);
INSERT INTO `menu_level` VALUES (188, 1, 'MASTER DATA', 'Backup & Restore', 0, 65, '/admin/pages/backup/index.php', 1, 1, '2026-09-11 16:07:06', 'bi-database-gear', 15);
INSERT INTO `menu_level` VALUES (189, 2, 'MASTER DATA', 'Backup & Restore', 0, 0, '/admin/pages/backup/index.php', 0, 0, '2026-09-11 11:08:10', 'bi-database-gear', 99);
INSERT INTO `menu_level` VALUES (190, 3, 'MASTER DATA', 'Backup & Restore', 0, 0, '/admin/pages/backup/index.php', 0, 0, '2026-09-11 11:08:10', 'bi-database-gear', 99);
INSERT INTO `menu_level` VALUES (192, 5, 'MASTER DATA', 'Backup & Restore', 0, 0, '/admin/pages/backup/index.php', 0, 0, '2026-09-11 11:08:10', 'bi-database-gear', 99);
INSERT INTO `menu_level` VALUES (193, 6, 'MASTER DATA', 'Backup & Restore', 0, 0, '/admin/pages/backup/index.php', 1, 1, '2026-09-11 11:08:10', 'bi-database-gear', 99);
INSERT INTO `menu_level` VALUES (194, 7, 'MASTER DATA', 'Backup & Restore', 0, 0, '/admin/pages/backup/index.php', 1, 1, '2026-09-11 11:08:10', 'bi-database-gear', 99);
INSERT INTO `menu_level` VALUES (195, 1, 'LAPORAN', 'PPN Masukan', 0, NULL, '/admin/pages/laporan/pajak_masukan.php', 1, 1, '2026-09-14 12:49:09', 'bi-receipt-cutoff', 26);
INSERT INTO `menu_level` VALUES (196, 6, 'LAPORAN', 'PPN Masukan', 0, NULL, '/admin/pages/laporan/pajak_masukan.php', 1, 1, '2026-09-16 16:53:08', 'bi-receipt-cutoff', 6);
INSERT INTO `menu_level` VALUES (197, 7, '', 'Pajak Masukan', 1, 0, '/admin/pages/laporan/pajak_masukan.php', 1, 1, '2026-09-14 11:11:48', 'bi-receipt-cutoff', 9);
INSERT INTO `menu_level` VALUES (198, 1, 'LAPORAN', 'Pengeluaran Lainnya', 0, NULL, '/admin/pages/laporan/pengeluaran_lain.php', 1, 1, '2026-09-14 12:49:23', 'bi-cash-coin', 25);
INSERT INTO `menu_level` VALUES (199, 6, 'LAPORAN', 'Pengeluaran Lainnya', 0, NULL, '/admin/pages/laporan/pengeluaran_lain.php', 1, 1, '2026-09-16 16:52:58', 'bi-cash-coin', 5);
INSERT INTO `menu_level` VALUES (200, 7, '', 'Pengeluaran Lain-lain', 1, 0, '/admin/pages/laporan/pengeluaran_lain.php', 1, 1, '2026-09-14 12:43:31', 'bi-wallet2', 9);
INSERT INTO `menu_level` VALUES (201, 1, 'OPERASIONAL', 'Adjustment Stok', 0, NULL, '/admin/pages/adjustment_stok/index.php', 1, 1, '2026-09-15 10:58:25', 'bi-sliders2', 11);
INSERT INTO `menu_level` VALUES (202, 2, 'OPERASIONAL', 'Adjustment Stok', 0, NULL, '/admin/pages/adjustment_stok/index.php', 1, 1, '2026-09-15 14:12:11', 'bi-sliders2', 15);
INSERT INTO `menu_level` VALUES (203, 3, 'OPERASIONAL', 'Adjustment Stok', 0, NULL, '/admin/pages/adjustment_stok/index.php', 1, 1, '2026-09-14 15:15:09', 'bi-sliders2', 4);
INSERT INTO `menu_level` VALUES (205, 5, '', 'Adjustment Stok', 0, 0, '/admin/pages/adjustment_stok/index.php', 0, 0, '2026-09-14 15:11:39', 'bi-sliders2', 15);
INSERT INTO `menu_level` VALUES (207, 7, '', 'Adjustment Stok', 0, 0, '/admin/pages/adjustment_stok/index.php', 1, 1, '2026-09-14 15:11:39', 'bi-sliders2', 15);
INSERT INTO `menu_level` VALUES (208, 1, 'MASTER DATA', 'Format Penomoran', 0, 65, '/admin/pages/penomoran/index.php', 1, 1, '2026-09-15 10:52:21', 'bi-123', 16);
INSERT INTO `menu_level` VALUES (209, 1, 'OPERASIONAL', 'Arsip Dokumen', 0, NULL, '/admin/pages/dokumen/index.php', 1, 1, '2026-09-16 11:44:10', 'bi-folder2-open', 12);
INSERT INTO `menu_level` VALUES (210, 2, 'OPERASIONAL', 'Arsip Dokumen', 0, NULL, '/admin/pages/dokumen/index.php', 1, 1, '2026-09-16 11:44:10', 'bi-folder2-open', 16);
INSERT INTO `menu_level` VALUES (211, 3, 'OPERASIONAL', 'Arsip Dokumen', 0, NULL, '/admin/pages/dokumen/index.php', 1, 1, '2026-09-16 11:44:10', 'bi-folder2-open', 5);
INSERT INTO `menu_level` VALUES (213, 5, 'OPERASIONAL', 'Arsip Dokumen', 0, NULL, '/admin/pages/dokumen/index.php', 1, 1, '2026-09-16 11:44:10', 'bi-folder2-open', 6);
INSERT INTO `menu_level` VALUES (214, 6, 'OPERASIONAL', 'Arsip Dokumen', 0, NULL, '/admin/pages/dokumen/index.php', 1, 1, '2026-09-16 11:44:10', 'bi-folder2-open', 4);
INSERT INTO `menu_level` VALUES (215, 7, 'OPERASIONAL', 'Arsip Dokumen', 0, NULL, '/admin/pages/dokumen/index.php', 1, 1, '2026-09-16 11:44:10', 'bi-folder2-open', 3);
INSERT INTO `menu_level` VALUES (216, 1, 'OPERASIONAL', 'Pajak Keluaran', 0, 0, '/admin/pages/pajak_keluaran/index.php', 1, 1, '2026-09-16 15:07:22', 'bi-receipt-cutoff', 13);
INSERT INTO `menu_level` VALUES (218, 6, 'OPERASIONAL', 'Pajak Penjualan', 0, NULL, '/admin/pages/pajak_keluaran/index.php', 1, 1, '2026-09-16 16:40:24', 'bi-receipt-cutoff', 5);
INSERT INTO `menu_level` VALUES (219, 7, 'OPERASIONAL', 'Pajak Keluaran', 0, 0, '/admin/pages/pajak_keluaran/index.php', 1, 1, '2026-09-16 15:07:22', 'bi-receipt-cutoff', 4);
INSERT INTO `menu_level` VALUES (220, 1, 'LAPORAN', 'PPN Keluaran', 0, 0, '/admin/pages/laporan/pajak_keluaran.php', 1, 1, '2026-09-16 16:46:38', 'bi-receipt-cutoff', 27);
INSERT INTO `menu_level` VALUES (221, 4, 'LAPORAN', 'PPN Keluaran', 0, NULL, '/admin/pages/laporan/pajak_keluaran.php', 1, 1, '2026-09-19 12:30:35', 'bi-receipt-cutoff', 10);
INSERT INTO `menu_level` VALUES (222, 6, 'LAPORAN', 'PPN Keluaran', 0, NULL, '/admin/pages/laporan/pajak_keluaran.php', 1, 1, '2026-09-16 16:53:14', 'bi-receipt-cutoff', 7);
INSERT INTO `menu_level` VALUES (223, 7, 'LAPORAN', 'PPN Keluaran', 0, 0, '/admin/pages/laporan/pajak_keluaran.php', 1, 1, '2026-09-16 16:46:38', 'bi-receipt-cutoff', 27);
INSERT INTO `menu_level` VALUES (224, 1, 'LAPORAN', 'PPN Masa', 0, 0, '/admin/pages/laporan/ppn_masa.php', 1, 1, '2026-09-16 17:02:39', 'bi-calculator-fill', 28);
INSERT INTO `menu_level` VALUES (225, 4, 'LAPORAN', 'PPN Masa', 0, NULL, '/admin/pages/laporan/ppn_masa.php', 1, 1, '2026-09-19 12:30:42', 'bi-calculator-fill', 11);
INSERT INTO `menu_level` VALUES (226, 6, 'LAPORAN', 'PPN Masa', 0, 0, '/admin/pages/laporan/ppn_masa.php', 1, 1, '2026-09-16 17:02:39', 'bi-calculator-fill', 28);
INSERT INTO `menu_level` VALUES (227, 7, 'LAPORAN', 'PPN Masa', 0, 0, '/admin/pages/laporan/ppn_masa.php', 1, 1, '2026-09-16 17:02:39', 'bi-calculator-fill', 28);

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
-- Records of merk_barang
-- ----------------------------
INSERT INTO `merk_barang` VALUES (1, 'MRK0001', 'Umum', 1);
INSERT INTO `merk_barang` VALUES (2, 'MRK0002', 'Top Cer', 1);
INSERT INTO `merk_barang` VALUES (3, 'MRK0003', 'Lakoni', 1);
INSERT INTO `merk_barang` VALUES (4, 'MRK0004', 'Samsung', 1);
INSERT INTO `merk_barang` VALUES (5, 'MRK0005', 'LG', 1);
INSERT INTO `merk_barang` VALUES (6, 'MRK0006', 'Sony', 1);
INSERT INTO `merk_barang` VALUES (7, 'MRK0007', 'Panasonic', 1);
INSERT INTO `merk_barang` VALUES (8, 'MRK0008', 'Sharp', 1);
INSERT INTO `merk_barang` VALUES (9, 'MRK0009', 'Toshiba', 1);
INSERT INTO `merk_barang` VALUES (10, 'MRK0010', 'Philips', 1);
INSERT INTO `merk_barang` VALUES (11, 'MRK0011', 'Polytron', 1);
INSERT INTO `merk_barang` VALUES (12, 'MRK0012', 'Aqua', 1);
INSERT INTO `merk_barang` VALUES (13, 'MRK0013', 'Sanken', 1);
INSERT INTO `merk_barang` VALUES (14, 'MRK0014', 'Miyako', 1);
INSERT INTO `merk_barang` VALUES (15, 'MRK0015', 'Cosmos', 1);
INSERT INTO `merk_barang` VALUES (16, 'MRK0016', 'Maspion', 1);
INSERT INTO `merk_barang` VALUES (17, 'MRK0017', 'Modena', 1);
INSERT INTO `merk_barang` VALUES (18, 'MRK0018', 'Electrolux', 1);
INSERT INTO `merk_barang` VALUES (19, 'MRK0019', 'Midea', 1);
INSERT INTO `merk_barang` VALUES (20, 'MRK0020', 'Daikin', 1);
INSERT INTO `merk_barang` VALUES (21, 'MRK0021', 'Gree', 1);
INSERT INTO `merk_barang` VALUES (22, 'MRK0022', 'Hitachi', 1);
INSERT INTO `merk_barang` VALUES (23, 'MRK0023', 'Mitsubishi', 1);
INSERT INTO `merk_barang` VALUES (24, 'MRK0024', 'Fujitsu', 1);
INSERT INTO `merk_barang` VALUES (25, 'MRK0025', 'Sanken', 1);
INSERT INTO `merk_barang` VALUES (26, 'MRK0026', 'Ariston', 1);
INSERT INTO `merk_barang` VALUES (27, 'MRK0027', 'Rinnai', 1);
INSERT INTO `merk_barang` VALUES (28, 'MRK0028', 'Shimizu', 1);
INSERT INTO `merk_barang` VALUES (29, 'MRK0029', 'KDK', 1);
INSERT INTO `merk_barang` VALUES (30, 'MRK0030', 'Oxone', 1);
INSERT INTO `merk_barang` VALUES (31, 'MRK0031', 'Maxim', 1);
INSERT INTO `merk_barang` VALUES (32, 'MRK0032', 'Sekai', 1);
INSERT INTO `merk_barang` VALUES (33, 'MRK0033', 'Kirito', 1);
INSERT INTO `merk_barang` VALUES (34, 'MRK0034', 'Advance', 1);
INSERT INTO `merk_barang` VALUES (35, 'MRK0035', 'Polytron', 1);
INSERT INTO `merk_barang` VALUES (36, 'MRK0036', 'TCL', 1);
INSERT INTO `merk_barang` VALUES (37, 'MRK0037', 'Hisense', 1);
INSERT INTO `merk_barang` VALUES (38, 'MRK0038', 'Changhong', 1);
INSERT INTO `merk_barang` VALUES (39, 'MRK0039', 'Coocaa', 1);
INSERT INTO `merk_barang` VALUES (40, 'MRK0040', 'AOC', 1);
INSERT INTO `merk_barang` VALUES (41, 'MRK0041', 'BenQ', 1);
INSERT INTO `merk_barang` VALUES (42, 'MRK0042', 'Epson', 1);
INSERT INTO `merk_barang` VALUES (43, 'MRK0043', 'Canon', 1);
INSERT INTO `merk_barang` VALUES (44, 'MRK0044', 'Nikon', 1);
INSERT INTO `merk_barang` VALUES (45, 'MRK0045', 'Brother', 1);
INSERT INTO `merk_barang` VALUES (46, 'MRK0046', 'Ricoh', 1);
INSERT INTO `merk_barang` VALUES (47, 'MRK0047', 'Kyocera', 1);
INSERT INTO `merk_barang` VALUES (48, 'MRK0048', 'Xiaomi', 1);
INSERT INTO `merk_barang` VALUES (49, 'MRK0049', 'Realme', 1);
INSERT INTO `merk_barang` VALUES (50, 'MRK0050', 'Oppo', 1);
INSERT INTO `merk_barang` VALUES (51, 'MRK0051', 'Vivo', 1);
INSERT INTO `merk_barang` VALUES (52, 'MRK0052', 'Huawei', 1);
INSERT INTO `merk_barang` VALUES (53, 'MRK0053', 'OnePlus', 1);
INSERT INTO `merk_barang` VALUES (54, 'MRK0054', 'Nokia', 1);
INSERT INTO `merk_barang` VALUES (55, 'MRK0055', 'ZTE', 1);
INSERT INTO `merk_barang` VALUES (56, 'MRK0056', 'TP-Link', 1);
INSERT INTO `merk_barang` VALUES (57, 'MRK0057', 'D-Link', 1);
INSERT INTO `merk_barang` VALUES (58, 'MRK0058', 'Tenda', 1);
INSERT INTO `merk_barang` VALUES (59, 'MRK0059', 'Ubiquiti', 1);
INSERT INTO `merk_barang` VALUES (60, 'MRK0060', 'MikroTik', 1);
INSERT INTO `merk_barang` VALUES (61, 'MRK0061', 'Hikvision', 1);
INSERT INTO `merk_barang` VALUES (62, 'MRK0062', 'Dahua', 1);
INSERT INTO `merk_barang` VALUES (63, 'MRK0063', 'Ezviz', 1);
INSERT INTO `merk_barang` VALUES (64, 'MRK0064', 'Imou', 1);
INSERT INTO `merk_barang` VALUES (65, 'MRK0065', 'JBL', 1);
INSERT INTO `merk_barang` VALUES (66, 'MRK0066', 'Yamaha', 1);
INSERT INTO `merk_barang` VALUES (67, 'MRK0067', 'Denon', 1);
INSERT INTO `merk_barang` VALUES (68, 'MRK0068', 'Pioneer', 1);
INSERT INTO `merk_barang` VALUES (69, 'MRK0069', 'Kenwood', 1);
INSERT INTO `merk_barang` VALUES (70, 'MRK0070', 'JVC', 1);
INSERT INTO `merk_barang` VALUES (71, 'MRK0071', 'Bose', 1);
INSERT INTO `merk_barang` VALUES (72, 'MRK0072', 'Harman Kardon', 1);
INSERT INTO `merk_barang` VALUES (73, 'MRK0073', 'Marshall', 1);
INSERT INTO `merk_barang` VALUES (74, 'MRK0074', 'Logitech', 1);
INSERT INTO `merk_barang` VALUES (75, 'MRK0075', 'Razer', 1);
INSERT INTO `merk_barang` VALUES (76, 'MRK0076', 'Acer', 1);
INSERT INTO `merk_barang` VALUES (77, 'MRK0077', 'Asus', 1);
INSERT INTO `merk_barang` VALUES (78, 'MRK0078', 'Lenovo', 1);
INSERT INTO `merk_barang` VALUES (79, 'MRK0079', 'HP', 1);
INSERT INTO `merk_barang` VALUES (80, 'MRK0080', 'Dell', 1);
INSERT INTO `merk_barang` VALUES (81, 'MRK0081', 'MSI', 1);
INSERT INTO `merk_barang` VALUES (82, 'MRK0082', 'Gigabyte', 1);
INSERT INTO `merk_barang` VALUES (83, 'MRK0083', 'ASRock', 1);
INSERT INTO `merk_barang` VALUES (84, 'MRK0084', 'Intel', 1);
INSERT INTO `merk_barang` VALUES (85, 'MRK0085', 'AMD', 1);
INSERT INTO `merk_barang` VALUES (86, 'MRK0086', 'Nvidia', 1);
INSERT INTO `merk_barang` VALUES (87, 'MRK0087', 'Kingston', 1);
INSERT INTO `merk_barang` VALUES (88, 'MRK0088', 'Transcend', 1);
INSERT INTO `merk_barang` VALUES (89, 'MRK0089', 'Sandisk', 1);
INSERT INTO `merk_barang` VALUES (90, 'MRK0090', 'Western Digital', 1);
INSERT INTO `merk_barang` VALUES (91, 'MRK0091', 'Seagate', 1);
INSERT INTO `merk_barang` VALUES (92, 'MRK0092', 'Toshiba Storage', 1);
INSERT INTO `merk_barang` VALUES (93, 'MRK0093', 'TeamGroup', 1);
INSERT INTO `merk_barang` VALUES (94, 'MRK0094', 'Corsair', 1);
INSERT INTO `merk_barang` VALUES (95, 'MRK0095', 'FSP', 1);
INSERT INTO `merk_barang` VALUES (96, 'MRK0096', 'Cooler Master', 1);
INSERT INTO `merk_barang` VALUES (97, 'MRK0097', 'Thermaltake', 1);
INSERT INTO `merk_barang` VALUES (98, 'MRK0098', 'NZXT', 1);
INSERT INTO `merk_barang` VALUES (99, 'MRK0099', 'Deepcool', 1);
INSERT INTO `merk_barang` VALUES (100, 'MRK0100', 'Arctic', 1);
INSERT INTO `merk_barang` VALUES (101, 'MRK0101', 'Bosch', 1);
INSERT INTO `merk_barang` VALUES (102, 'MRK0102', 'Stanley', 1);
INSERT INTO `merk_barang` VALUES (103, 'MRK0103', 'DeWalt', 1);
INSERT INTO `merk_barang` VALUES (104, 'MRK0104', 'Makita', 1);
INSERT INTO `merk_barang` VALUES (105, 'MRK0105', 'Milwaukee', 1);
INSERT INTO `merk_barang` VALUES (106, 'MRK0106', 'Black+Decker', 1);
INSERT INTO `merk_barang` VALUES (107, 'MRK0107', 'Metabo', 1);
INSERT INTO `merk_barang` VALUES (108, 'MRK0108', 'Hitachi Power Tools', 1);
INSERT INTO `merk_barang` VALUES (109, 'MRK0109', 'Hikoki', 1);
INSERT INTO `merk_barang` VALUES (110, 'MRK0110', 'Ryobi', 1);
INSERT INTO `merk_barang` VALUES (111, 'MRK0111', 'Dremel', 1);
INSERT INTO `merk_barang` VALUES (112, 'MRK0112', 'Skil', 1);
INSERT INTO `merk_barang` VALUES (113, 'MRK0113', 'Wera', 1);
INSERT INTO `merk_barang` VALUES (114, 'MRK0114', 'Knipex', 1);
INSERT INTO `merk_barang` VALUES (115, 'MRK0115', 'Bahco', 1);
INSERT INTO `merk_barang` VALUES (116, 'MRK0116', 'Facom', 1);
INSERT INTO `merk_barang` VALUES (117, 'MRK0117', 'Gedore', 1);
INSERT INTO `merk_barang` VALUES (118, 'MRK0118', 'Snap-on', 1);
INSERT INTO `merk_barang` VALUES (119, 'MRK0119', 'Craftsman', 1);
INSERT INTO `merk_barang` VALUES (120, 'MRK0120', 'Tekiro', 1);
INSERT INTO `merk_barang` VALUES (121, 'MRK0121', 'Kenmaster', 1);
INSERT INTO `merk_barang` VALUES (122, 'MRK0122', 'Kenji', 1);
INSERT INTO `merk_barang` VALUES (123, 'MRK0123', 'Krisbow', 1);
INSERT INTO `merk_barang` VALUES (124, 'MRK0124', 'Jakemy', 1);
INSERT INTO `merk_barang` VALUES (125, 'MRK0125', 'Tactix', 1);
INSERT INTO `merk_barang` VALUES (126, 'MRK0126', 'Prohex', 1);
INSERT INTO `merk_barang` VALUES (127, 'MRK0127', 'Multipro', 1);
INSERT INTO `merk_barang` VALUES (128, 'MRK0128', 'Kyoritsu', 1);
INSERT INTO `merk_barang` VALUES (129, 'MRK0129', 'Fluke', 1);
INSERT INTO `merk_barang` VALUES (130, 'MRK0130', 'Hioki', 1);
INSERT INTO `merk_barang` VALUES (131, 'MRK0131', 'Karcher', 1);
INSERT INTO `merk_barang` VALUES (132, 'MRK0132', 'Stihl', 1);
INSERT INTO `merk_barang` VALUES (133, 'MRK0133', 'Husqvarna', 1);
INSERT INTO `merk_barang` VALUES (134, 'MRK0134', 'Honda', 1);
INSERT INTO `merk_barang` VALUES (135, 'MRK0135', 'Yamaha Motor', 1);
INSERT INTO `merk_barang` VALUES (136, 'MRK0136', 'Kawasaki', 1);
INSERT INTO `merk_barang` VALUES (137, 'MRK0137', 'Suzuki', 1);
INSERT INTO `merk_barang` VALUES (138, 'MRK0138', 'Krisbow Professional', 1);
INSERT INTO `merk_barang` VALUES (139, 'MRK0139', 'Informa', 1);
INSERT INTO `merk_barang` VALUES (140, 'MRK0140', 'IKEA', 1);
INSERT INTO `merk_barang` VALUES (141, 'MRK0141', 'Olympic', 1);
INSERT INTO `merk_barang` VALUES (142, 'MRK0142', 'Ligna', 1);
INSERT INTO `merk_barang` VALUES (143, 'MRK0143', 'Chitose', 1);
INSERT INTO `merk_barang` VALUES (144, 'MRK0144', 'Indachi', 1);
INSERT INTO `merk_barang` VALUES (145, 'MRK0145', 'Vinoti', 1);
INSERT INTO `merk_barang` VALUES (146, 'MRK0146', 'Vivere', 1);
INSERT INTO `merk_barang` VALUES (147, 'MRK0147', 'HighPoint', 1);
INSERT INTO `merk_barang` VALUES (148, 'MRK0148', 'Steelcase', 1);
INSERT INTO `merk_barang` VALUES (149, 'MRK0149', 'Herman Miller', 1);
INSERT INTO `merk_barang` VALUES (150, 'MRK0150', 'Stramm', 1);
INSERT INTO `merk_barang` VALUES (151, 'MRK0151', 'Donati', 1);
INSERT INTO `merk_barang` VALUES (152, 'MRK0152', 'Ichiko', 1);
INSERT INTO `merk_barang` VALUES (153, 'MRK0153', 'Savello', 1);
INSERT INTO `merk_barang` VALUES (154, 'MRK0154', 'Chairman', 1);
INSERT INTO `merk_barang` VALUES (155, 'MRK0155', 'Oscar Living', 1);
INSERT INTO `merk_barang` VALUES (156, 'MRK0156', 'Dekoruma', 1);
INSERT INTO `merk_barang` VALUES (157, 'MRK0157', 'Atria', 1);
INSERT INTO `merk_barang` VALUES (158, 'MRK0158', 'Fabelio', 1);
INSERT INTO `merk_barang` VALUES (159, 'MRK0159', 'Informa Furniture', 1);
INSERT INTO `merk_barang` VALUES (160, 'MRK0160', 'Ace Hardware', 1);
INSERT INTO `merk_barang` VALUES (161, 'MRK0161', 'Scandia', 1);
INSERT INTO `merk_barang` VALUES (162, 'MRK0162', 'Spring Air', 1);
INSERT INTO `merk_barang` VALUES (163, 'MRK0163', 'Comforta', 1);
INSERT INTO `merk_barang` VALUES (164, 'MRK0164', 'Homedoki', 1);
INSERT INTO `merk_barang` VALUES (165, 'MRK0165', 'Toto', 1);
INSERT INTO `merk_barang` VALUES (166, 'MRK0166', 'Belden', 1);
INSERT INTO `merk_barang` VALUES (167, 'MRK0167', 'Toyota', 1);

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
-- Records of mutasi_order
-- ----------------------------
INSERT INTO `mutasi_order` VALUES (3, '2026-09-16 10:51:00', 'DI-2609-00001', NULL, 3, 5, 5, 4, 2, '2026-09-16 10:53:11', '2026-09-16 10:53:47', 'DITERIMA SITE TUJUAN', 50000, '');

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
-- Records of mutasi_order_detail
-- ----------------------------
INSERT INTO `mutasi_order_detail` VALUES (1, 3, 23, 1);

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
-- Records of otp_verification
-- ----------------------------
INSERT INTO `otp_verification` VALUES (9, 'users', 1, 'itdev.nuansa@gmail.com', '287216', 'BACKUP_DATABASE', 0, 1, '2026-09-11 15:44:35', '2026-09-11 15:29:35');
INSERT INTO `otp_verification` VALUES (10, 'users', 1, 'itdev.nuansa@gmail.com', '557003', 'BACKUP_DATABASE', 0, 1, '2026-09-15 13:05:18', '2026-09-15 12:50:18');
INSERT INTO `otp_verification` VALUES (11, 'karyawan', 10, 'ytb.samsu@gmail.com', '843637', 'CHANGE_PASSWORD', 0, 1, '2026-09-19 13:18:39', '2026-09-19 12:18:39');

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
-- Records of pajak_keluaran
-- ----------------------------
INSERT INTO `pajak_keluaran` VALUES (3, 8, '2026', '09', 5000000000, '', '2026-09-16 15:35:37', '2026-09-16 15:35:37');

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
-- Records of payment_purchase
-- ----------------------------
INSERT INTO `payment_purchase` VALUES (4, 7, 1, 1, 0, 5000000);
INSERT INTO `payment_purchase` VALUES (6, 9, 1, 0, 0, 325000);

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
-- Records of payment_purchase_detail
-- ----------------------------
INSERT INTO `payment_purchase_detail` VALUES (6, 4, 'PF26/0916/001', '2026-09-16 09:41:23', 8, 9, 'Bank Mandiri', '1400098765432', 'PT JAYA TEKNIK', 2500000, 0, '', 0, '', NULL, 2500000, '2026-09-16 09:41:23', '2026-09-16 09:49:46', '', 'BCA', '81731111350', 'Arena Computer');
INSERT INTO `payment_purchase_detail` VALUES (7, 4, 'PF26/0916/002', '2026-09-16 10:38:59', 8, 9, 'Bank Mandiri', '1400098765432', 'PT JAYA TEKNIK', 2500000, 0, '', 2500, '', NULL, 0, '2026-09-16 10:38:59', NULL, '', 'BCA', '81731111350', 'Arena Computer');
INSERT INTO `payment_purchase_detail` VALUES (10, 6, 'PF26/0917/001', '2026-09-17 12:14:16', 8, 9, 'Bank Mandiri', '1400098765432', 'PT JAYA TEKNIK', 162500, 0, '', 0, '', NULL, 162500, '2026-09-17 12:14:16', NULL, '', 'BCA', '81731111350', 'Arena Computer');
INSERT INTO `payment_purchase_detail` VALUES (11, 6, 'PF26/0917/002', '2026-09-17 12:15:18', 8, 5, 'Bank Mandiri', '1400098765432', 'PT JAYA TEKNIK', 162500, 0, '', 0, '', NULL, 0, '2026-09-17 12:15:18', NULL, '', 'BCA', '81731111350', 'Arena Computer');

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
-- Records of pembatalan_transaksi
-- ----------------------------

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
-- Records of penomoran
-- ----------------------------
INSERT INTO `penomoran` VALUES (3, 'Permintaan Pesanan', 'REQUEST', 2, 5, 'PR/[SHORT_YEAR][MONTH][DAY]/[COUNTER]', '2026-09-15 11:44:02', '2026-09-15 12:55:13', 1);
INSERT INTO `penomoran` VALUES (4, 'Pesanan Pembelian', 'PURCHASE', 2, 4, 'PO/[SHORT_YEAR][MONTH][DAY]-[COUNTER]', '2026-09-15 12:26:16', '2026-09-15 12:55:17', 1);
INSERT INTO `penomoran` VALUES (5, 'Penerimaan', 'RECEIVING', 1, 4, 'PN/[SHORT_YEAR][MONTH][DAY]/[COUNTER]', '2026-09-15 12:29:46', NULL, 1);
INSERT INTO `penomoran` VALUES (6, 'Retur', 'RETUR PO', 1, 4, 'RT/[COUNTER]/[SHORT_YEAR][MONTH][DAY]', '2026-09-15 12:32:27', NULL, 1);
INSERT INTO `penomoran` VALUES (7, 'Faktur Invoice', 'FAKTUR PO', 2, 3, 'FP[SHORT_YEAR]/[MONTH][DAY]/[COUNTER]', '2026-09-15 12:35:26', '2026-09-15 12:40:43', 1);
INSERT INTO `penomoran` VALUES (8, 'Pembayaran', 'PAYMENT PO', 2, 3, 'PF[SHORT_YEAR]/[MONTH][DAY]/[COUNTER]', '2026-09-15 12:39:15', '2026-09-15 12:39:38', 1);
INSERT INTO `penomoran` VALUES (9, 'Mutasi Barang', 'MUTASI BARANG', 2, 5, 'DI-[SHORT_YEAR][MONTH]-[COUNTER]', '2026-09-15 12:44:59', NULL, 1);
INSERT INTO `penomoran` VALUES (10, 'Penyesuaian Stock', 'ADJUSTMENT STOK', 2, 5, 'ADJ-[SHORT_YEAR][MONTH]-[COUNTER]', '2026-09-15 12:46:13', NULL, 1);

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
-- Records of profile
-- ----------------------------
INSERT INTO `profile` VALUES (1, 'PT Jaya Teknik', '031-889900', '081234567890', 'info@jayateknis.com', 'Jl. Ahmad Ardans', '', 'Samarinda', 'Kalimantan Timur', '01.234.567.8-604.000', 'KLU33151', '0123456789012345', 'Asia/Makassar', 0, '2026-09-07 11:12:51', 'images/uploads/company/company_logo_1788750771_f0eedc.png');

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
-- Records of purchase_order
-- ----------------------------
INSERT INTO `purchase_order` VALUES (18, 'PO/260915-0001', '2026-09-15', 4, 4, 9, 'DITERIMA', 'NORMAL', '2026-09-16 08:50:40', '', 'Jl. Perak Timur No. 100, Surabaya', 'Vendor', '2026-09-16 00:00:00', 60, 14, 4, 0, 12, 0, 0, 25);
INSERT INTO `purchase_order` VALUES (19, 'PO/260915-0002', '2026-09-15', 4, 4, 6, 'DITERIMA', 'NORMAL', '2026-09-15 15:21:24', '', 'Jl. Perak Timur No. 100, Surabaya', 'Vendor', '2026-09-16 00:00:00', 10, 13, 4, 1, 11, 0, 0, 0);
INSERT INTO `purchase_order` VALUES (20, 'PO/260917-0001', '2026-09-17', 4, 4, 6, 'DITERIMA', 'NORMAL', '2026-09-17 11:54:34', '', 'Jl. Perak Timur No. 100, Surabaya', 'Vendor', '2026-09-17 00:00:00', 10, 15, 4, 1, 11, 0, 0, 0);
INSERT INTO `purchase_order` VALUES (21, 'PO/261002-0001', '2026-10-02', 4, 4, 6, 'DITERIMA', 'NORMAL', '2026-10-02 09:22:00', '', 'Jl. Perak Timur No. 100, Surabaya', 'Vendor', '2026-10-03 00:00:00', 10, 16, 4, 0, 11, 159420, 0, 0);

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
-- Records of purchase_order_detail
-- ----------------------------
INSERT INTO `purchase_order_detail` VALUES (23, 18, 24, 1, 1500000000, 0, 1500000000, 1, '', 1);
INSERT INTO `purchase_order_detail` VALUES (24, 19, 23, 1, 5000000, 0, 5000000, 1, '', 0);
INSERT INTO `purchase_order_detail` VALUES (25, 20, 20, 1, 325000, 0, 325000, 1, '', 0);
INSERT INTO `purchase_order_detail` VALUES (26, 21, 22, 3, 2657000, 0, 7971000, 1, '', 0);

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
-- Records of receiving_order
-- ----------------------------
INSERT INTO `receiving_order` VALUES (13, 'PN/260915/0001', 19, 3, '2026-09-15 15:21:24', '2026-09-15 00:00:00', '826177299910', NULL, '', 1, 1, '2026-09-15 15:21:24');
INSERT INTO `receiving_order` VALUES (14, 'PN/260916/0001', 18, 3, '2026-09-16 08:50:40', '2026-09-16 00:00:00', 'SRJ09111023', NULL, '', 1, 1, '2026-09-16 08:50:40');
INSERT INTO `receiving_order` VALUES (15, 'PN/260917/0001', 20, 3, '2026-09-17 11:54:34', '2026-09-17 00:00:00', '1234', NULL, '', 1, 1, '2026-09-17 11:54:34');
INSERT INTO `receiving_order` VALUES (16, 'PN/261002/0001', 21, 3, '2026-10-02 09:22:00', '2026-10-02 00:00:00', 'ST123400KS', NULL, '', 1, 1, '2026-10-02 09:22:00');

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
-- Records of receiving_order_detail
-- ----------------------------
INSERT INTO `receiving_order_detail` VALUES (24, 13, '23', 1, 1, 'Kondisi Baik (Passed)');
INSERT INTO `receiving_order_detail` VALUES (25, 14, '24', 1, 1, 'Kondisi Baik (Passed)');
INSERT INTO `receiving_order_detail` VALUES (26, 15, '20', 1, 1, 'Kondisi Baik (Passed)');
INSERT INTO `receiving_order_detail` VALUES (27, 16, '22', 3, 1, 'Kondisi Baik (Passed)');

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
-- Records of rekening_bank
-- ----------------------------
INSERT INTO `rekening_bank` VALUES (1, 'Bank Mandiri', 'PT JAYA TEKNIK', '1400098765432', '2026-09-08 12:21:56', 9);
INSERT INTO `rekening_bank` VALUES (2, 'Bank Central Asia (BCA)', 'JAYA TEKNIK SMD PT', '332519219', '2026-09-08 12:22:44', 5);
INSERT INTO `rekening_bank` VALUES (3, 'Tunai', NULL, NULL, '2026-09-08 14:21:08', 5);

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
-- Records of request_order
-- ----------------------------
INSERT INTO `request_order` VALUES (22, 'PR/260915/00001', '2026-09-15 13:11:36', 2, 4, 'DITERIMA FULL', 'NORMAL', 9, '2026-09-16 08:50:40', 'Kendaraan dinas', 18, 3);
INSERT INTO `request_order` VALUES (23, 'PR/260915/00002', '2026-09-15 13:13:06', 2, 4, 'DITERIMA FULL', 'NORMAL', 6, '2026-09-15 15:21:24', '', 19, 3);
INSERT INTO `request_order` VALUES (24, 'PR/260917/00001', '2026-09-17 10:29:30', 3, 4, 'DISETUJUI LOGISTIK', 'NORMAL', 7, '2026-09-21 12:05:18', '', NULL, 3);
INSERT INTO `request_order` VALUES (25, 'PR/260917/00002', '2026-09-17 10:32:14', 3, 2, 'TERKIRIM', 'NORMAL', 7, '2026-09-17 10:31:02', '', NULL, 3);
INSERT INTO `request_order` VALUES (26, 'PR/260917/00003', '2026-09-17 10:39:34', 3, 4, 'TERKIRIM', 'NORMAL', 5, '2026-09-17 10:38:22', '', NULL, 3);
INSERT INTO `request_order` VALUES (27, 'PR/260917/00004', '2026-09-17 10:38:49', 3, 1, 'TERKIRIM', 'NORMAL', 5, '2026-09-17 10:38:49', '', NULL, 3);
INSERT INTO `request_order` VALUES (28, 'PR/260917/00005', '2026-09-17 10:46:28', 3, 4, 'DITERIMA FULL', 'NORMAL', 6, '2026-09-17 11:54:34', '', 20, 3);
INSERT INTO `request_order` VALUES (29, 'PR/261002/00001', '2026-10-02 09:12:05', 3, 4, 'DITERIMA FULL', 'NORMAL', 6, '2026-10-02 09:22:00', '', 21, 3);

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
-- Records of request_order_detail
-- ----------------------------
INSERT INTO `request_order_detail` VALUES (78, 22, 24, 'BRG0024', 'Toyota Alphard 2.5 G', 1, 'UNIT', 0, 0);
INSERT INTO `request_order_detail` VALUES (79, 23, 23, 'BRG0023', 'Epson XB300', 1, 'UNIT', 0, 0);
INSERT INTO `request_order_detail` VALUES (80, 24, 18, 'BRG0018', 'Pintu Costum', 1, 'SET', 0, 0);
INSERT INTO `request_order_detail` VALUES (81, 25, 18, 'BRG0018', 'Pintu Costum', 1, 'SET', 0, 0);
INSERT INTO `request_order_detail` VALUES (82, 26, 22, 'BRG0022', 'Printer EPSON L325', 1, 'UNIT', 0, 0);
INSERT INTO `request_order_detail` VALUES (83, 27, 22, 'BRG0022', 'Printer EPSON L325', 1, 'UNIT', 0, 0);
INSERT INTO `request_order_detail` VALUES (85, 28, 20, 'BRG0020', 'Homedoki', 1, 'UNIT', 0, 0);
INSERT INTO `request_order_detail` VALUES (87, 29, 22, 'BRG0022', 'Printer EPSON L325', 3, 'UNIT', 0, 0);

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
-- Records of retur_po
-- ----------------------------

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
-- Records of retur_po_detail
-- ----------------------------

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
-- Records of site
-- ----------------------------
INSERT INTO `site` VALUES (1, 'SIT01', 'Galangan Utama Dok 1', 'Bengkel', 'Jl. Pelabuhan Maritim No. 12, Surabaya', NULL, '031-889901', 5, 1);
INSERT INTO `site` VALUES (2, 'SIT02', 'Workshop Bubut & Las Fabrikasi', 'Bengkel', 'Kawasan Industri Gresik Blok B-4', '', '031-889902', 2, 1);
INSERT INTO `site` VALUES (3, 'SIT03', 'Gudang Logistik & Suku Cadang', 'Logistik', 'Jl. Dermaga Barat No. 8, Surabaya', NULL, '031-889903', 5, 1);
INSERT INTO `site` VALUES (4, 'SIT04', 'Kantor Pusat & Operasional', 'Office', 'Jl. Perak Timur No. 100, Surabaya', '', '031-889900', 5, 1);

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
-- Records of smtp_server
-- ----------------------------
INSERT INTO `smtp_server` VALUES (1, 'Gmail SMTP', 'https://myaccount.google.com/apppasswords', 'smtp.gmail.com', '587', 'itdev.nuansa@gmail.com', 'ackrudbkucrkf', '2026-08-22 09:36:07', 100, 35, 1);

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
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'Administrator', 'shem1990@gmail.com', '$2y$10$gXvuzhGeM6AmUDFDObJ8hOPaUp9BXUfQY77BO3cbV.v6zjRW/2Izu', 1, '2026-08-22 11:56:46');

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

-- ----------------------------
-- Records of vendor
-- ----------------------------
INSERT INTO `vendor` VALUES (1, 'VND001', 'PT Baja Maritim Nusantara', 'Kawasan Industri Rungkut Surabaya', NULL, '031-778811', 'Surabaya', 'Bpk. Gunawan', NULL, NULL, NULL, '2026-08-19 22:23:08', '2026-08-20 10:46:23', 'Toko Retail', 'Supplier plat baja & profil kapal', 1, NULL, NULL, 30, 0);
INSERT INTO `vendor` VALUES (2, 'VND002', 'CV Sumber Teknik Las & Gas', 'Jl. Kalianak Barat No. 45, Surabaya', NULL, '031-778822', 'Surabaya', 'Ibu Ratna', NULL, NULL, NULL, '2026-08-19 22:23:08', '2026-08-20 10:46:41', 'Toko Distributor', 'Distributor kawat las & perlengkapan welding', 1, NULL, NULL, 14, 0);
INSERT INTO `vendor` VALUES (3, 'VND003', 'PT Indo Bearing & Seal Sejahtera', 'Jl. Dupak Rukun No. 88, Surabaya', NULL, '031-778833', 'Surabaya', 'Bpk. Tony', NULL, NULL, NULL, '2026-08-19 22:23:08', '2026-09-07 11:45:45', 'Bengkel', 'Bearing propeller & mechanical seal', 1, '7521883991', 'Bank Kaltimtara', 30, 0);
INSERT INTO `vendor` VALUES (4, 'VND004', 'PT Samudera Marine Supply', 'Jl. Tanjung Perak Barat No. 20, Surabaya', NULL, '031-778844', 'Batam', 'Bpk. Eko', NULL, NULL, NULL, '2026-08-19 22:23:08', '2026-08-20 10:54:17', 'Dealer', 'Sparepart mesin diesel kapal & valve', 1, NULL, NULL, 45, 0);
INSERT INTO `vendor` VALUES (5, 'VND005', 'Nuansa Cipta Dharma, PT', 'Jl. Untung Suropati No 35, Kel. Sungai Kunjang', '-0.520035, 117.116561', '0541898912', 'Samarinda', 'Edi Dharmawan', '081178901234 (Direktur)', 'tokonuansa.com', 'tokonuansa@gmail.com', '2026-08-27 11:58:30', NULL, 'Elektronik', '', 1, '99999', 'BCA', 15, 0);
INSERT INTO `vendor` VALUES (6, 'VND006', 'Arena Computer', 'Jl. Bung Tomo Samarinda', 'https://maps.app.goo.gl/h8eF1syqKECC8MgFA', '0541-65313314', 'Samarinda', 'Dimas', '085286812235', '', 'aringo.kom@gmail.com', '2026-09-05 11:35:12', '2026-09-05 11:37:20', 'Komputer', '', 1, '81731111350', 'BCA', 10, 0);
INSERT INTO `vendor` VALUES (7, 'VND007', 'Cici Jaya .CV', 'Jl. P. Antasari No 109', '', '0541-261785', 'Samarinda', 'Bagas', '085233312589', '', 'tokocici2112@gmail.com', '2026-09-07 14:55:17', NULL, 'Bangunan', '', 1, '39851156', 'BNI', 25, 0);
INSERT INTO `vendor` VALUES (8, 'VND008', 'Informa Bigmall', 'Jl. Untung Suropati, Bigmall Lt. 2', '', '0541651234', 'Samarinda', '', '085287871111', 'ruparupa.com', '', '2026-09-08 14:47:07', '2026-09-10 09:26:25', 'Furniture', '', 1, '1150878844', 'BNI', 30, 0);
INSERT INTO `vendor` VALUES (9, 'VND009', 'Graha Toyota Samarinda', 'Jl. Pangeran Antasari No. 22 Air Putih', '', '0541-9120013', 'Samarinda', 'Aris', '085289125555', '', 'sales.toyotasamarinda@gmail.com', '2026-09-12 10:46:04', NULL, 'Dealer Mobil', '', 1, 'GRAHA TOYOTA SMD', 'BCA', 60, 0);

SET FOREIGN_KEY_CHECKS = 1;
