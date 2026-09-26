# 🌐 Sistem Manajemen Aset & Layanan Jaringan TI

[![Laravel](https://img.shields.io/badge/Framework-Laravel%2012-FF2D20?style=flat-square\&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square\&logo=php)](https://php.net)
[![TailwindCSS](https://img.shields.io/badge/UI-TailwindCSS%20%26%20Bootstrap-06B6D4?style=flat-square\&logo=tailwindcss)](https://tailwindcss.com)
[![Live Demo](https://img.shields.io/badge/Live%20Demo-demo.mydiskom.my.id-0EA2BC?style=flat-square\&logo=google-chrome)](https://demo.mydiskom.my.id/)
[![License](https://img.shields.io/badge/License-MIT-green.style=flat-square)](LICENSE)

> **Aplikasi Manajemen Aset** adalah sistem informasi manajemen inventarisasi perangkat jaringan, penanganan tiket aduan perbaikan, generator Surat SST (Serah Terima Perangkat) berbasis PDF, dan audit trail pergerakan aset (*Asset Flow*) yang dirancang khusus untuk instansi pemerintahan (DISKOMINFO) dan organisasi berskala luas.

🔗 **Akses Live Demo**: https://demo.mydiskom.my.id/

---

## 📌 Gambaran Umum (System Overview)

Dalam operasional infrastruktur jaringan pemerintahan/perusahaan, tantangan utama yang sering dihadapi adalah ketidakjelasan stok perangkat di gudang, kesulitan melacak posisi alat yang sedang aktif terpasang di lapangan, serta ketiadaan audit trail dokumen fisik penyerahan barang.

**Aplikasi Manajemen Aset** hadir memberikan solusi terintegrasi melalui siklus pengelolaan aset end-to-end:

1. **Inventarisasi Gudang (*Stored Device*)**: Pengelolaan stok barang yang tersimpan di vault/gudang.
2. **Perangkat Terpasang (*Deployment Device*)**: Pemetaan lokasi & visualisasi peta GIS perangkat aktif di kantor instansi/klien.
3. **Dokumen Legalitas (*Surat SST*)**: Generator otomatis Berita Acara Serah Terima Perangkat format PDF resmi.
4. **Audit Trail (*Asset Flow*)**: Pelacakan riwayat kronologis mutasi fisik perangkat dari gudang hingga penarikan alat rusak.
5. **Tiket Layanan & Disposisi**: Formulir aduan gangguan jaringan dan permohonan pemasangan baru bagi pengguna.

---

## 👥 Peran Pengguna (Multi-Role Privileges)

Sistem ini mendukung pembagian hak akses terintegrasi (*Multi-Role Access Control*):

* 👑 **Master (Super Admin / Kepala Dinas)**:

  * Pemantauan statistik global seluruh aset & tiket aduan secara real-time.
  * Pengawasan aktivitas operasional dan evaluasi laporan bulanan.

* 🛡️ **Admin (Teknisi / Operator)**:

  * Pengelolaan stok barang gudang (*Stored Device*) & pendaftaran jenis perangkat.
  * Memproses dan menyetujui tiket permohonan dari pengguna.
  * Penerbitan Surat Berita Acara SST & pencetakan dokumen PDF.
  * Pencatatan pergerakan mutasi alat (*Asset Flow*) & pemetaan GIS lokasi perangkat terpasang.

* 👤 **User (Instansi / Klien Pelapor)**:

  * Pengajuan tiket aduan kendala jaringan atau permohonan alat baru.
  * Percakapan pesan *Live Chat* dengan admin teknis.
  * Pemantauan status perbaikan dan riwayat penyerahan alat ke instansi masing-masing.

---

## 🚀 Fitur & Modul Utama

### 1. 📦 Vault / Stored Device (Stok Gudang)

Mengelola seluruh stok perangkat cadangan (Router MikroTik, Switch Cisco, Access Point Ruijie, Kabel Fiber Optic, dll) yang tersimpan di gudang penyimpanan. Dilengkapi dengan kalkulasi otomatis pemakaian roll kabel (dalam meter) dan unit pcs.

### 2. 📡 Deployment Device & Peta GIS

Mencatat dan memetakan koordinat lokasi perangkat yang telah disetujui dan aktif beroperasi di lokasi instansi/klien. Dilengkapi integrasi peta interaktif (Leaflet GIS) untuk mempermudah pemantauan sebaran infrastruktur secara visual.

### 3. 📝 Generator Surat SST (Serah Terima Perangkat)

Fitur otomatisasi pencetakan Berita Acara Serah Terima (SST) perangkat lengkap dengan kop surat resmi, rincian daftar barang, tanda tangan digital/fisik, dan pengarsipan berkas PDF yang siap diunduh atau dicetak.

### 4. 🔄 Asset Flow (Audit Trail Mutasi Perangkat)

Setiap pergerakan fisik perangkat—baik penyerahan awal dari gudang ke klien, pemindahan antar instansi, maupun pengembalian alat rusak kembali ke gudang—direkam secara permanen sebagai jejak audit kronologis yang valid.

### 5. 🎫 Tiket Aduan & Layanan TI

Pusat penanganan aduan gangguan dan permohonan layanan. Tiket yang disetujui dapat langsung ditindaklanjuti menjadi transaksi penyerahan barang dan pencetakan Surat SST.

### 6. 📊 General Reports (Export PDF & Excel)

Modul pelaporan yang memungkinkan ekspor rekapitulasi data stok gudang, sebaran alat aktif, dokumen surat, dan audit trail transaksi ke dalam format PDF dan Excel.

---

## 🛠️ Arsitektur & Tech Stack

* **Backend**: [Laravel 12](https://laravel.com), PHP 8.2+, Eloquent ORM.
* **Frontend**: Blade Templating, [Tailwind CSS](https://tailwindcss.com), Bootstrap 5, AblePro Admin / HeroBiz UI Kit.
* **PDF & Export Engine**: `barryvdh/laravel-dompdf` & `maatwebsite/excel`.
* **Peta GIS**: Leaflet.js Map Integration.
* **Database**: MySQL / SQLite.

---

## 💻 Panduan Instalasi Lokal (Local Setup)

1. **Clone Repositori**:

   ```bash
   git clone https://github.com/Cakrava/manajemen-aset.git
   cd manajemen-aset
   ```

2. **Instalasi Dependensi PHP & JS**:

   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi Database**:

   ```bash
   php artisan migrate --seed
   ```

5. **Menjalankan Server Lokal**:

   ```bash
   php artisan serve
   ```

   Akses aplikasi di browser pada http://127.0.0.1:8000.

---

## 🌐 Akses Live Demo Online

Pengujian versi demo interaktif dapat langsung diakses pada domain resmi:

* **URL Demo**: https://demo.mydiskom.my.id/
* **Fitur Demo**: Dilengkapi dengan *1-Click Multi-Role Switcher*, *Tour Interaktif*, dan *Data Mockup Auto-Reset*.

---

## 📄 Lisensi

Proyek ini dirilis di bawah lisensi [MIT License](LICENSE).
