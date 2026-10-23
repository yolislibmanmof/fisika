-- =====================================================
-- DATABASE FINAL v2 : fisika  (struktur dulu, isi kemudian)
-- Program Studi Fisika | PHP 8+ | Laragon 8.2
-- =====================================================
DROP DATABASE IF EXISTS fisika;
CREATE DATABASE fisika DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE fisika;

-- ================= STRUKTUR TABEL =================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE settings (
  id INT PRIMARY KEY DEFAULT 1,
  site_name VARCHAR(150) NOT NULL DEFAULT 'Program Studi Fisika',
  site_tagline VARCHAR(150) DEFAULT 'Departemen Fisika FMIPA',
  prodi_name VARCHAR(150) DEFAULT 'FISIKA',
  fakultas VARCHAR(150) DEFAULT 'Fakultas Matematika dan Ilmu Pengetahuan Alam',
  kampus VARCHAR(150) DEFAULT 'Universitas Nusantara',
  logo VARCHAR(255) DEFAULT '',
  favicon VARCHAR(255) DEFAULT '',
  email VARCHAR(100) DEFAULT '',
  telepon VARCHAR(50) DEFAULT '',
  alamat TEXT,
  facebook VARCHAR(255) DEFAULT '#',
  instagram VARCHAR(255) DEFAULT '#',
  youtube VARCHAR(255) DEFAULT '#',
  linkedin VARCHAR(255) DEFAULT '#',
  badge_hero VARCHAR(150) DEFAULT '',
  hero_title VARCHAR(255) DEFAULT '',
  hero_subtitle VARCHAR(255) DEFAULT '',
  akreditasi VARCHAR(50) DEFAULT 'UNGGUL',
  meta_description TEXT,
  footer_text VARCHAR(255) DEFAULT '',
  updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE heroes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipe ENUM('image','video') NOT NULL DEFAULT 'image',
  file VARCHAR(255) NOT NULL DEFAULT '',
  judul VARCHAR(255) DEFAULT '',
  subtitle VARCHAR(255) DEFAULT '',
  badge VARCHAR(150) DEFAULT '',
  urutan INT DEFAULT 1,
  aktif TINYINT DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(100) NOT NULL,
  url VARCHAR(255) NOT NULL DEFAULT '',
  parent_id INT DEFAULT 0,
  urutan INT DEFAULT 1,
  aktif TINYINT DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(150) NOT NULL UNIQUE,
  judul VARCHAR(200) NOT NULL,
  konten LONGTEXT,
  gambar VARCHAR(255) DEFAULT '',
  urutan INT DEFAULT 1,
  status TINYINT DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE sambutan (
  id INT PRIMARY KEY DEFAULT 1,
  nama VARCHAR(150) DEFAULT '',
  jabatan VARCHAR(150) DEFAULT '',
  foto VARCHAR(255) DEFAULT '',
  isi TEXT,
  tanda_tangan VARCHAR(150) DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE fitur (
  id INT AUTO_INCREMENT PRIMARY KEY,
  icon VARCHAR(100) DEFAULT 'fa-solid fa-atom',
  judul VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  urutan INT DEFAULT 1,
  aktif TINYINT DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(100) NOT NULL,
  nilai INT DEFAULT 0,
  suffix VARCHAR(10) DEFAULT '+',
  icon VARCHAR(100) DEFAULT 'fa-solid fa-users',
  urutan INT DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE berita (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(250) NOT NULL,
  slug VARCHAR(250) NOT NULL UNIQUE,
  kategori VARCHAR(100) DEFAULT 'Kegiatan',
  isi LONGTEXT,
  gambar VARCHAR(255) DEFAULT '',
  penulis VARCHAR(100) DEFAULT 'Admin',
  tanggal DATE DEFAULT NULL,
  views INT DEFAULT 0,
  status TINYINT DEFAULT 1,
  dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE galeri (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  kategori VARCHAR(100) DEFAULT 'Dokumentasi',
  file VARCHAR(255) DEFAULT '',
  tipe ENUM('image','video') DEFAULT 'image',
  tanggal DATE DEFAULT NULL,
  urutan INT DEFAULT 1
) ENGINE=InnoDB;

-- ================= DATA AWAL =================
INSERT INTO settings (id, site_name, site_tagline, prodi_name, fakultas, kampus, logo, favicon, email, telepon, alamat, facebook, instagram, youtube, linkedin, badge_hero, hero_title, hero_subtitle, akreditasi, meta_description, footer_text) VALUES
(1,'Program Studi Fisika','Departemen Fisika FMIPA','FISIKA','Fakultas Matematika dan Ilmu Pengetahuan Alam','Universitas Nusantara','','','fisika@kampus.ac.id','(021) 555-0188','Gedung FMIPA Lantai 2, Jl. Pendidikan No. 1, Indonesia','https://facebook.com','https://instagram.com','https://youtube.com','https://linkedin.com','PRODI FISIKA - TERAKREDITASI UNGGUL','Mencetak Ilmuwan & Inovator Fisika Masa Depan','Program Studi Fisika — Terakreditasi UNGGUL','UNGGUL','Website resmi Program Studi Fisika: pendidikan, riset, dan inovasi sains.','© 2026 Program Studi Fisika. Hak Cipta Dilindungi Undang-Undang.');

INSERT INTO heroes (tipe, file, judul, subtitle, badge, urutan, aktif) VALUES
('image','https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1600&q=60','Mencetak Ilmuwan & Inovator Fisika Masa Depan','Program Studi Fisika — Terakreditasi UNGGUL','PRODI FISIKA - TERAKREDITASI UNGGUL',1,1),
('image','https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1600&q=60','Riset, Inovasi, dan Dedikasi untuk Sains','Laboratorium lengkap dan dosen berpengalaman','SAINS UNTUK NEGERI',2,1),
('image','https://images.unsplash.com/photo-1507413245164-6160d8298b31?auto=format&fit=crop&w=1600&q=60','Membangun Generasi Berbasis Data & Fenomena','Kurikulum adaptif industri dan riset terapan','PENDIDIKAN BERKUALITAS',3,1);

INSERT INTO menus (label, url, parent_id, urutan, aktif) VALUES
('Beranda','index.php',0,1,1),
('Profil','',0,2,1),
('Akademik','',0,3,1),
('Berita','berita.php',0,4,1),
('Galeri','galeri.php',0,5,1),
('Kontak','page.php?slug=kontak',0,6,1);
INSERT INTO menus (label, url, parent_id, urutan, aktif) VALUES
('Sejarah','page.php?slug=sejarah',2,1,1),
('Visi & Misi','page.php?slug=visi-misi',2,2,1),
('Struktur Organisasi','page.php?slug=struktur-organisasi',2,3,1),
('Kurikulum','page.php?slug=kurikulum',3,1,1),
('Laboratorium','page.php?slug=laboratorium',3,2,1),
('Penelitian','page.php?slug=penelitian',3,3,1);

INSERT INTO pages (slug, judul, konten, gambar, urutan, status) VALUES
('sejarah','Sejarah Program Studi','<p>Program Studi Fisika berdiri sebagai bagian dari Fakultas Matematika dan Ilmu Pengetahuan Alam dengan komitmen mengembangkan pendidikan sains berkualitas. Sejak didirikan, prodi ini terus bertransformasi menjadi pusat unggulan riset fisika terapan.</p><p>Perjalanan panjang kami ditandai dengan pembukaan laboratorium fisika modern, kerja sama industri, serta publikasi ilmiah bereputasi internasional.</p>','https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=60',1,1),
('visi-misi','Visi & Misi','<h3>Visi</h3><p>Menjadi program studi fisika unggul di tingkat nasional dan internasional dalam pendidikan, riset, dan pengabdian masyarakat berbasis teknologi pada tahun 2030.</p><h3>Misi</h3><ul><li>Menyelenggarakan pendidikan fisika berkualitas dan adaptif.</li><li>Mengembangkan riset inovatif berbasis fenomena fisika terapan.</li><li>Menjalin kerja sama industri dan institusi global.</li><li>Menghasilkan lulusan berintegritas dan kompeten.</li></ul>','',2,1),
('struktur-organisasi','Struktur Organisasi','<p>Struktur organisasi Program Studi Fisika terdiri dari Ketua Program Studi, Sekretaris Program Studi, Koordinator Laboratorium, Koordinator Penelitian, serta staf dosen dan tenaga kependidikan yang profesional.</p>','',3,1),
('kurikulum','Kurikulum','<p>Kurikulum disusun berbasis Outcome Based Education (OBE) dengan mata kuliah inti: Mekanika, Elektromagnetika, Fisika Kuantum, Termodinamika, Fisika Komputasi, serta mata kuliah pilihan berbasis riset dan industri.</p>','',4,1),
('laboratorium','Laboratorium','<p>Prodi Fisika memiliki laboratorium Fisika Dasar, Elektronika & Instrumentasi, Optika & Fotonika, Material, serta Komputasi Fisika yang mendukung praktikum dan riset mahasiswa.</p>','https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1200&q=60',5,1),
('penelitian','Penelitian','<p>Dosen dan mahasiswa aktif melakukan penelitian pada bidang material maju, fisika medis, komputasi kuantum, dan energi terbarukan dengan pendanaan nasional maupun internasional.</p>','',6,1),
('kontak','Kontak Kami','<p>Gedung FMIPA Lantai 2, Jl. Pendidikan No. 1, Indonesia.</p><p>Telepon: (021) 555-0188 — Email: fisika@kampus.ac.id</p><p>Layanan: Senin–Jumat, 08.00–16.00 WIB.</p>','',7,1);

INSERT INTO sambutan (id, nama, jabatan, foto, isi, tanda_tangan) VALUES
(1,'Dr. Rizky Pratama, M.Si.','Ketua Program Studi Fisika','https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=800&q=60','Assalamu\'alaikum warahmatullahi wabarakatuh, salam sejahtera bagi kita semua. Selamat datang di website resmi Program Studi Fisika. Kami berkomitmen menghadirkan pendidikan sains yang unggul, riset yang berdampak, serta lingkungan akademik yang kolaboratif. Semoga website ini menjadi jendela informasi yang bermanfaat bagi mahasiswa, mitra, dan masyarakat luas.','Dr. Rizky Pratama, M.Si.');

INSERT INTO fitur (icon, judul, deskripsi, urutan, aktif) VALUES
('fa-solid fa-atom','Riset & Inovasi','Penelitian fisika terapan berkaliber internasional dengan publikasi bereputasi.',1,1),
('fa-solid fa-flask','Laboratorium Modern','Enam laboratorium lengkap: dasar, elektronika, optika, material, dan komputasi.',2,1),
('fa-solid fa-user-graduate','Dosen Berkualitas','Tenaga pengajar berpendidikan S2 dan S3 dari perguruan tinggi dalam serta luar negeri.',3,1),
('fa-solid fa-satellite','Fasilitas Teknologi','Instrumentasi presisi tinggi dan komputasi awan untuk simulasi fenomena fisika.',4,1),
('fa-solid fa-book-open','Kurikulum Adaptif','Kurikulum OBE yang selaras dengan kebutuhan industri dan perkembangan sains.',5,1),
('fa-solid fa-handshake','Kerja Sama Mitra','Kolaborasi riset dan magang dengan industri, rumah sakit, dan institusi global.',6,1);

INSERT INTO stats (label, nilai, suffix, icon, urutan) VALUES
('Mahasiswa Aktif',480,'+','fa-solid fa-users',1),
('Dosen & Peneliti',32,'+','fa-solid fa-chalkboard-user',2),
('Publikasi Ilmiah',120,'+','fa-solid fa-book',3),
('Mitra Kerja Sama',25,'+','fa-solid fa-handshake',4);

INSERT INTO berita (judul, slug, kategori, isi, gambar, penulis, tanggal, views, status) VALUES
('Tim Fisika Raih Pendanaan Riset Nasional 2026','tim-fisika-raih-pendanaan-riset-nasional-2026','Prestasi','<p>Tim peneliti Program Studi Fisika berhasil memperoleh pendanaan riset nasional untuk kajian material maju berbasis nanoteknologi. Penelitian ini akan berlangsung selama dua tahun bersama mitra industri.</p>','https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=1200&q=60','Humas','2026-09-14',128,1),
('Workshop Fisika Komputasi untuk Mahasiswa Baru','workshop-fisika-komputasi-untuk-mahasiswa-baru','Kegiatan','<p>Prodi Fisika menyelenggarakan workshop pemrograman Python dan simulasi numerik bagi mahasiswa baru nhằm memperkuat kompetensi komputasi sejak dini.</p>','https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1200&q=60','Humas','2026-08-22',96,1),
('Kuliah Tamu: Fisika Medis di Era Kecerdasan Buatan','kuliah-tamu-fisika-medis-di-era-kecerdasan-buatan','Akademik','<p>Praktisi fisika medis dari rumah sakit mitra memberikan kuliah tamu mengenai pemanfaatan kecerdasan buatan dalam pencitraan diagnostik.</p>','https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=60','Humas','2026-07-30',74,1);

INSERT INTO galeri (judul, kategori, file, tipe, tanggal, urutan) VALUES
('Praktikum Optika & Fotonika','Laboratorium','https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=900&q=60','image','2026-09-01',1),
('Diskusi Riset Material Maju','Riset','https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=900&q=60','image','2026-08-15',2),
('Kegiatan Field Study Mahasiswa','Kegiatan','https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=60','image','2026-07-19',3),
('Simulasi Komputasi Kuantum','Laboratorium','https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=60','image','2026-06-11',4),
('Seminar Nasional Fisika 2026','Kegiatan','https://images.unsplash.com/photo-1507413245164-6160d8298b31?auto=format&fit=crop&w=900&q=60','image','2026-05-08',5),
('Wisuda & Pelepasan Lulusan','Kegiatan','https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=900&q=60','image','2026-04-02',6);