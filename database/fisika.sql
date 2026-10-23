-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 23, 2026 at 04:26 AM
-- Server version: 5.7.39
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fisika`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `user_name` varchar(100) NOT NULL DEFAULT 'tamu',
  `action` varchar(60) NOT NULL,
  `detail` varchar(500) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `ua` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `user_name`, `action`, `detail`, `ip`, `ua`, `created_at`) VALUES
(1, 1, 'Administrator', 'login_success', 'Masuk sebagai admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-23 04:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id` int(11) NOT NULL,
  `judul` varchar(250) NOT NULL,
  `slug` varchar(250) NOT NULL,
  `kategori` varchar(100) DEFAULT 'Kegiatan',
  `isi` longtext,
  `gambar` varchar(255) DEFAULT '',
  `penulis` varchar(100) DEFAULT 'Admin',
  `tanggal` date DEFAULT NULL,
  `views` int(11) DEFAULT '0',
  `status` tinyint(4) DEFAULT '1',
  `dibuat` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `judul`, `slug`, `kategori`, `isi`, `gambar`, `penulis`, `tanggal`, `views`, `status`, `dibuat`) VALUES
(1, 'Tim Fisika Raih Pendanaan Riset Nasional 2026', 'tim-fisika-raih-pendanaan-riset-nasional-2026', 'Prestasi', '<p>Tim peneliti Program Studi Fisika berhasil memperoleh pendanaan riset nasional untuk kajian material maju berbasis nanoteknologi. Penelitian ini akan berlangsung selama dua tahun bersama mitra industri.</p>', 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=60', 'Humas', '2026-09-14', 128, 1, '2026-10-22 12:04:27'),
(2, 'Workshop Fisika Komputasi untuk Mahasiswa Baru', 'workshop-fisika-komputasi-untuk-mahasiswa-baru', 'Kegiatan', '<p>Prodi Fisika menyelenggarakan workshop pemrograman Python dan simulasi numerik bagi mahasiswa baru nhằm memperkuat kompetensi komputasi sejak dini.</p>', 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1200&q=60', 'Humas', '2026-08-22', 98, 1, '2026-10-22 12:04:27'),
(3, 'Kuliah Tamu: Fisika Medis di Era Kecerdasan Buatan', 'kuliah-tamu-fisika-medis-di-era-kecerdasan-buatan', 'Akademik', '<p>Praktisi fisika medis dari rumah sakit mitra memberikan kuliah tamu mengenai pemanfaatan kecerdasan buatan dalam pencitraan diagnostik.</p>', 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=60', 'Humas', '2026-07-30', 74, 1, '2026-10-22 12:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL DEFAULT '',
  `blocked_until` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `fitur`
--

CREATE TABLE `fitur` (
  `id` int(11) NOT NULL,
  `icon` varchar(100) DEFAULT 'fa-solid fa-atom',
  `judul` varchar(150) NOT NULL,
  `deskripsi` text,
  `urutan` int(11) DEFAULT '1',
  `aktif` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `fitur`
--

INSERT INTO `fitur` (`id`, `icon`, `judul`, `deskripsi`, `urutan`, `aktif`) VALUES
(1, 'fa-solid fa-atom', 'Riset & Inovasi', 'Penelitian fisika terapan berkaliber internasional dengan publikasi bereputasi.', 1, 1),
(2, 'fa-solid fa-flask', 'Laboratorium Modern', 'Enam laboratorium lengkap: dasar, elektronika, optika, material, dan komputasi.', 2, 1),
(3, 'fa-solid fa-user-graduate', 'Dosen Berkualitas', 'Tenaga pengajar berpendidikan S2 dan S3 dari perguruan tinggi dalam serta luar negeri.', 3, 1),
(4, 'fa-solid fa-satellite', 'Fasilitas Teknologi', 'Instrumentasi presisi tinggi dan komputasi awan untuk simulasi fenomena fisika.', 4, 1),
(5, 'fa-solid fa-book-open', 'Kurikulum Adaptif', 'Kurikulum OBE yang selaras dengan kebutuhan industri dan perkembangan sains.', 5, 1),
(6, 'fa-solid fa-handshake', 'Kerja Sama Mitra', 'Kolaborasi riset dan magang dengan industri, rumah sakit, dan institusi global.', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `kategori` varchar(100) DEFAULT 'Dokumentasi',
  `file` varchar(255) DEFAULT '',
  `tipe` enum('image','video') DEFAULT 'image',
  `tanggal` date DEFAULT NULL,
  `urutan` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `galeri`
--

INSERT INTO `galeri` (`id`, `judul`, `kategori`, `file`, `tipe`, `tanggal`, `urutan`) VALUES
(1, 'Praktikum Optika & Fotonika', 'Laboratorium', 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=900&q=60', 'image', '2026-09-01', 1),
(2, 'Diskusi Riset Material Maju', 'Riset', 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=900&q=60', 'image', '2026-08-15', 2),
(3, 'Kegiatan Field Study Mahasiswa', 'Kegiatan', 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=60', 'image', '2026-07-19', 3),
(4, 'Simulasi Komputasi Kuantum', 'Laboratorium', 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=60', 'image', '2026-06-11', 4),
(5, 'Seminar Nasional Fisika 2026', 'Kegiatan', 'https://images.unsplash.com/photo-1507413245164-6160d8298b31?auto=format&fit=crop&w=900&q=60', 'image', '2026-05-08', 5),
(6, 'Wisuda & Pelepasan Lulusan', 'Kegiatan', 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=900&q=60', 'image', '2026-04-02', 6);

-- --------------------------------------------------------

--
-- Table structure for table `heroes`
--

CREATE TABLE `heroes` (
  `id` int(11) NOT NULL,
  `tipe` enum('image','video') NOT NULL DEFAULT 'image',
  `file` varchar(255) NOT NULL DEFAULT '',
  `judul` varchar(255) DEFAULT '',
  `subtitle` varchar(255) DEFAULT '',
  `badge` varchar(150) DEFAULT '',
  `urutan` int(11) DEFAULT '1',
  `aktif` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `heroes`
--

INSERT INTO `heroes` (`id`, `tipe`, `file`, `judul`, `subtitle`, `badge`, `urutan`, `aktif`) VALUES
(1, 'image', 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1600&q=60', 'Mencetak Ilmuwan & Inovator Fisika Masa Depan', 'Program Studi Fisika — Terakreditasi UNGGUL', 'PRODI FISIKA - TERAKREDITASI UNGGUL', 1, 1),
(2, 'image', 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1600&q=60', 'Riset, Inovasi, dan Dedikasi untuk Sains', 'Laboratorium lengkap dan dosen berpengalaman', 'SAINS UNTUK NEGERI', 2, 1),
(3, 'image', 'https://images.unsplash.com/photo-1507413245164-6160d8298b31?auto=format&fit=crop&w=1600&q=60', 'Membangun Generasi Berbasis Data & Fenomena', 'Kurikulum adaptif industri dan riset terapan', 'PENDIDIKAN BERKUALITAS', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `parent_id` int(11) DEFAULT '0',
  `urutan` int(11) DEFAULT '1',
  `aktif` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `label`, `url`, `parent_id`, `urutan`, `aktif`) VALUES
(1, 'Beranda', 'index.php', 0, 1, 1),
(2, 'Profil', '', 0, 2, 1),
(3, 'Akademik', '', 0, 3, 1),
(4, 'Berita', 'berita.php', 0, 4, 1),
(5, 'Galeri', 'galeri.php', 0, 5, 1),
(6, 'Kontak', 'page.php?slug=kontak', 0, 6, 1),
(7, 'Sejarah', 'page.php?slug=sejarah', 2, 1, 1),
(8, 'Visi & Misi', 'page.php?slug=visi-misi', 2, 2, 1),
(9, 'Struktur Organisasi', 'page.php?slug=struktur-organisasi', 2, 3, 1),
(10, 'Kurikulum', 'page.php?slug=kurikulum', 3, 1, 1),
(11, 'Laboratorium', 'page.php?slug=laboratorium', 3, 2, 1),
(12, 'Penelitian', 'page.php?slug=penelitian', 3, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `konten` longtext,
  `gambar` varchar(255) DEFAULT '',
  `urutan` int(11) DEFAULT '1',
  `status` tinyint(4) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `slug`, `judul`, `konten`, `gambar`, `urutan`, `status`) VALUES
(1, 'sejarah', 'Sejarah Program Studi', '<p>Program Studi Fisika berdiri sebagai bagian dari Fakultas Matematika dan Ilmu Pengetahuan Alam dengan komitmen mengembangkan pendidikan sains berkualitas. Sejak didirikan, prodi ini terus bertransformasi menjadi pusat unggulan riset fisika terapan.</p><p>Perjalanan panjang kami ditandai dengan pembukaan laboratorium fisika modern, kerja sama industri, serta publikasi ilmiah bereputasi internasional.</p>', 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=60', 1, 1),
(2, 'visi-misi', 'Visi & Misi', '<h3>Visi</h3><p>Menjadi program studi fisika unggul di tingkat nasional dan internasional dalam pendidikan, riset, dan pengabdian masyarakat berbasis teknologi pada tahun 2030.</p><h3>Misi</h3><ul><li>Menyelenggarakan pendidikan fisika berkualitas dan adaptif.</li><li>Mengembangkan riset inovatif berbasis fenomena fisika terapan.</li><li>Menjalin kerja sama industri dan institusi global.</li><li>Menghasilkan lulusan berintegritas dan kompeten.</li></ul>', '', 2, 1),
(3, 'struktur-organisasi', 'Struktur Organisasi', '<p>Struktur organisasi Program Studi Fisika terdiri dari Ketua Program Studi, Sekretaris Program Studi, Koordinator Laboratorium, Koordinator Penelitian, serta staf dosen dan tenaga kependidikan yang profesional.</p>', '', 3, 1),
(4, 'kurikulum', 'Kurikulum', '<p>Kurikulum disusun berbasis Outcome Based Education (OBE) dengan mata kuliah inti: Mekanika, Elektromagnetika, Fisika Kuantum, Termodinamika, Fisika Komputasi, serta mata kuliah pilihan berbasis riset dan industri.</p>', '', 4, 1),
(5, 'laboratorium', 'Laboratorium', '<p>Prodi Fisika memiliki laboratorium Fisika Dasar, Elektronika & Instrumentasi, Optika & Fotonika, Material, serta Komputasi Fisika yang mendukung praktikum dan riset mahasiswa.</p>', 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1200&q=60', 5, 1),
(6, 'penelitian', 'Penelitian', '<p>Dosen dan mahasiswa aktif melakukan penelitian pada bidang material maju, fisika medis, komputasi kuantum, dan energi terbarukan dengan pendanaan nasional maupun internasional.</p>', '', 6, 1),
(7, 'kontak', 'Kontak Kami', '<p>Gedung FMIPA Lantai 2, Jl. Pendidikan No. 1, Indonesia.</p><p>Telepon: (021) 555-0188 — Email: fisika@kampus.ac.id</p><p>Layanan: Senin–Jumat, 08.00–16.00 WIB.</p>', '', 7, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sambutan`
--

CREATE TABLE `sambutan` (
  `id` int(11) NOT NULL DEFAULT '1',
  `nama` varchar(150) DEFAULT '',
  `jabatan` varchar(150) DEFAULT '',
  `foto` varchar(255) DEFAULT '',
  `isi` text,
  `tanda_tangan` varchar(150) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sambutan`
--

INSERT INTO `sambutan` (`id`, `nama`, `jabatan`, `foto`, `isi`, `tanda_tangan`) VALUES
(1, 'Dr. Rizky Pratama, M.Si.', 'Ketua Program Studi Fisika', 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=800&q=60', 'Assalamu\'alaikum warahmatullahi wabarakatuh, salam sejahtera bagi kita semua. Selamat datang di website resmi Program Studi Fisika. Kami berkomitmen menghadirkan pendidikan sains yang unggul, riset yang berdampak, serta lingkungan akademik yang kolaboratif. Semoga website ini menjadi jendela informasi yang bermanfaat bagi mahasiswa, mitra, dan masyarakat luas.', 'Dr. Rizky Pratama, M.Si.');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL DEFAULT '1',
  `site_name` varchar(150) NOT NULL DEFAULT 'Program Studi Fisika',
  `site_tagline` varchar(150) DEFAULT 'Departemen Fisika FMIPA',
  `prodi_name` varchar(150) DEFAULT 'FISIKA',
  `fakultas` varchar(150) DEFAULT 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
  `kampus` varchar(150) DEFAULT 'Universitas Nusantara',
  `logo` varchar(255) DEFAULT '',
  `favicon` varchar(255) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `telepon` varchar(50) DEFAULT '',
  `alamat` text,
  `facebook` varchar(255) DEFAULT '#',
  `instagram` varchar(255) DEFAULT '#',
  `youtube` varchar(255) DEFAULT '#',
  `linkedin` varchar(255) DEFAULT '#',
  `badge_hero` varchar(150) DEFAULT '',
  `hero_title` varchar(255) DEFAULT '',
  `hero_subtitle` varchar(255) DEFAULT '',
  `akreditasi` varchar(50) DEFAULT 'UNGGUL',
  `meta_description` text,
  `footer_text` varchar(255) DEFAULT '',
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `site_name`, `site_tagline`, `prodi_name`, `fakultas`, `kampus`, `logo`, `favicon`, `email`, `telepon`, `alamat`, `facebook`, `instagram`, `youtube`, `linkedin`, `badge_hero`, `hero_title`, `hero_subtitle`, `akreditasi`, `meta_description`, `footer_text`, `updated`) VALUES
(1, 'Program Studi Fisika', 'Departemen Fisika FMIPA', 'FISIKA', 'Fakultas Matematika dan Ilmu Pengetahuan Alam', 'Universitas Nusantara', '', '', 'fisika@kampus.ac.id', '(021) 555-0188', 'Gedung FMIPA Lantai 2, Jl. Pendidikan No. 1, Indonesia', 'https://facebook.com', 'https://instagram.com', 'https://youtube.com', 'https://linkedin.com', 'PRODI FISIKA - TERAKREDITASI UNGGUL', 'Mencetak Ilmuwan & Inovator Fisika Masa Depan', 'Program Studi Fisika — Terakreditasi UNGGUL', 'UNGGUL', 'Website resmi Program Studi Fisika: pendidikan, riset, dan inovasi sains.', '© 2026 Program Studi Fisika. Hak Cipta Dilindungi Undang-Undang.', '2026-10-22 12:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `stats`
--

CREATE TABLE `stats` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `nilai` int(11) DEFAULT '0',
  `suffix` varchar(10) DEFAULT '+',
  `icon` varchar(100) DEFAULT 'fa-solid fa-users',
  `urutan` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stats`
--

INSERT INTO `stats` (`id`, `label`, `nilai`, `suffix`, `icon`, `urutan`) VALUES
(1, 'Mahasiswa Aktif', 480, '+', 'fa-solid fa-users', 1),
(2, 'Dosen & Peneliti', 32, '+', 'fa-solid fa-chalkboard-user', 2),
(3, 'Publikasi Ilmiah', 120, '+', 'fa-solid fa-book', 3),
(4, 'Mitra Kerja Sama', 25, '+', 'fa-solid fa-handshake', 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','editor') NOT NULL DEFAULT 'admin',
  `dibuat` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `dibuat`) VALUES
(1, 'Administrator', 'admin', '$2y$10$q355JVae99ZthuEulxi3ROPp5V4OK5ztiUMWkv7iHQUgUciH4frPK', 'admin', '2026-10-22 12:04:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip` (`ip`);

--
-- Indexes for table `fitur`
--
ALTER TABLE `fitur`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `heroes`
--
ALTER TABLE `heroes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`),
  ADD KEY `idx_time` (`attempted_at`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `sambutan`
--
ALTER TABLE `sambutan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fitur`
--
ALTER TABLE `fitur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `heroes`
--
ALTER TABLE `heroes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stats`
--
ALTER TABLE `stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
