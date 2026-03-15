# DOKUMENTASI APLIKASI — BERKAH DIGITAL PRODUCTION

> **Judul Tugas Akhir:** RANCANG BANGUN APLIKASI MANAJEMEN PRODUKSI DI STRONGER MANUFACTURE  
> **Nama Aplikasi:** Berkah Digital Production  
> **Framework:** Laravel 12 + Blade + Tailwind CSS v4 + Alpine.js + Turbo  
> **Bahasa:** PHP 8.2 | **Database:** MySQL 8.0

---

## DAFTAR ISI

1. [Gambaran Umum Aplikasi](#1-gambaran-umum-aplikasi)
2. [Alur Aplikasi (Application Flow)](#2-alur-aplikasi-application-flow)
3. [5 Role Akun Pengguna](#3-5-role-akun-pengguna)
4. [Halaman per Halaman (Page by Page)](#4-halaman-per-halaman-page-by-page)
   - [4.1 Halaman Login](#41-halaman-login)
   - [4.2 Owner — Dashboard & Kelola Data](#42-owner--dashboard--kelola-data)
   - [4.3 Admin — Order & Work Order](#43-admin--order--work-order)
   - [4.4 Finance — Laporan Keuangan](#44-finance--laporan-keuangan)
   - [4.5 PM — Manajemen Tugas Produksi](#45-pm--manajemen-tugas-produksi)
   - [4.6 Employee — Pelaksanaan Tugas](#46-employee--pelaksanaan-tugas)
   - [4.7 Halaman Bersama (Shared Pages)](#47-halaman-bersama-shared-pages)
5. [Fitur-Fitur Utama](#5-fitur-fitur-utama)
6. [Arsitektur Sistem](#6-arsitektur-sistem)
7. [Database & Model](#7-database--model)
8. [Hak Akses per Role (Matrix)](#8-hak-akses-per-role-matrix)
9. [Alur Proses Bisnis Lengkap](#9-alur-proses-bisnis-lengkap)

---

## 1. Gambaran Umum Aplikasi

**Berkah Digital Production** adalah aplikasi manajemen produksi berbasis web yang dirancang khusus untuk industri konveksi garmen. Aplikasi ini mengelola seluruh siklus produksi mulai dari penerimaan pesanan, pelaksanaan produksi, manajemen keuangan, hingga pengiriman dan pelaporan.

### Tujuan Aplikasi

- Mengdigitalisasi proses manajemen produksi yang sebelumnya manual
- Memberikan visibilitas real-time atas status setiap pesanan dan work order
- Mengelola keuangan perusahaan secara terpusat (pembayaran, laporan, kas, pinjaman)
- Memfasilitasi koordinasi antar-departemen (Admin, Finance, PM, Employee)
- Menyediakan analytics dan dashboard untuk Owner dalam pengambilan keputusan

### Komponen Utama

| Komponen | Detail |
|----------|--------|
| Backend | Laravel 12.0 (PHP 8.2) |
| Frontend | Blade Template + Tailwind CSS 4.x + Alpine.js 3.15 + Turbo 8.0 |
| Database | MySQL 8.0 (54 model, 53 tabel) |
| Charts | ApexCharts 5.3 |
| PDF Export | DomPDF 3.1 |
| Build Tool | Vite 7.x |

---

## 2. Alur Aplikasi (Application Flow)

### 2.1 Alur Umum Login & Navigasi

```
[Pengguna membuka aplikasi]
        │
        ▼
[Halaman Login (/login)]
        │
        ▼
[Autentikasi Kredensial]
        │
   ┌────┴────┐
   │ Gagal   │ Berhasil
   ▼         ▼
[Error]  [Redirect berdasarkan Role]
         │
   ┌─────┼──────────────────────┐
   ▼     ▼     ▼      ▼         ▼
[Owner][Admin][Finance][PM] [Employee]
   │     │       │      │       │
   ▼     ▼       ▼      ▼       ▼
[/owner  /admin  /finance /pm   /employee
/dashboard] [dashboard] ...
```

### 2.2 Alur Siklus Pesanan (Order Lifecycle)

```
1. ADMIN membuat Order (data customer, produk, harga)
        │
        ▼
2. ADMIN membuat Work Order (detail produksi dari Order)
        │
        ▼
3. Work Order masuk ke 6 TAHAPAN PRODUKSI:
   ┌─────────────────────────────────┐
   │ CUTTING → PRINTING → PLACEMENT  │
   │ → SEWING → PACKING (→ DONE)     │
   └─────────────────────────────────┘
        │
        ▼
4. PM memantau & mengupdate tanggal/status setiap tahap
        │
        ▼
5. EMPLOYEE menjalankan tugas & mark-done setiap tahap
        │
        ▼
6. ADMIN memindahkan Order ke SHIPPING (setelah produksi selesai)
        │
        ▼
7. ADMIN memindahkan Order ke REPORTING
        │
        ▼
8. FINANCE mengelola Laporan Order (lock per periode)
        │
        ▼
9. FINANCE mencatat Laporan Material / Partner / Operasional / Gaji
        │
        ▼
10. OWNER melihat analytics & menyetujui/menolak pembayaran
```

### 2.3 Alur Pembayaran (Payment Flow)

```
ADMIN mencatat Pembayaran dari Customer
        │
        ▼
Payment berstatus "PENDING"
        │
        ▼
OWNER melihat daftar pending payment
        │
   ┌────┴────┐
   │ Approve │ Reject
   ▼         ▼
[APPROVED] [REJECTED]
   │
   ▼
Saldo/piutang customer diperbarui otomatis
```

### 2.4 Alur Laporan Keuangan (Finance Reporting Flow)

```
ADMIN menyelesaikan Order → pindah ke Report
        │
        ▼
FINANCE melihat Order Report
        │
        ▼
FINANCE mencatat:
  ├─ Laporan Pembelian Material (bahan baku)
  ├─ Laporan Support Partner (jasa outsource)
  ├─ Laporan Operasional (biaya operasional)
  └─ Laporan Gaji (penggajian karyawan)
        │
        ▼
FINANCE mengunci periode laporan (lock period)
        │
        ▼
Data laporan terkunci — tidak bisa diedit lagi
```

---

## 3. 5 Role Akun Pengguna

Aplikasi menggunakan sistem **Role-Based Access Control (RBAC)**. Setiap pengguna memiliki satu role yang menentukan halaman dan fitur yang dapat diakses.

### 3.1 Owner (Super Admin)

**Deskripsi:** Pemilik usaha / super admin dengan akses penuh ke seluruh fitur aplikasi.

**Hak Akses:**
- Mengakses semua halaman semua role (bypass middleware)
- Melihat dashboard analytics bisnis (grafik trend order, penjualan, customer)
- Menyetujui atau menolak pembayaran yang diajukan Admin
- Mengelola **Master Data Produk** (kategori produk, bahan, tekstur, lengan, ukuran, jasa)
- Mengelola **Master Data Work Order** (pola potong, kain rantai, ukuran rib, tinta, finishing, overdeck, label, packing, stiker)
- Mengelola **Master Data Finance** (supplier material, support partner, daftar operasional)
- Mengelola **Akun Pengguna** (CRUD user dengan role)
- Mengelola **Tim Sales**
- Mengelola **Profil & Gaji Karyawan**
- Melihat riwayat pembayaran seluruh order

**Redirect Login:** `/owner/dashboard`

---

### 3.2 Admin (Operasional)

**Deskripsi:** Staf operasional yang mengelola order, produksi, customer, dan pembayaran.

**Hak Akses:**
- Dashboard ringkasan operasional
- CRUD lengkap **Order Customer** (buat, edit, lihat, hapus, batalkan, pindah status)
- Download **Invoice PDF** per order
- CRUD **Work Order** (buat dari order, kelola 6 tahapan produksi)
- CRUD **Customer** (dengan data lokasi lengkap: provinsi, kota, kecamatan, kelurahan)
- Catat dan hapus **Pembayaran**
- Lihat **Riwayat Pembayaran**
- Lihat **Shipping Orders** (order yang sudah dikirim)
- Kelola **Report Orders** (lock/unlock, hapus)

**Redirect Login:** `/admin/dashboard`

---

### 3.3 Finance (Keuangan)

**Deskripsi:** Staf keuangan yang mengelola seluruh laporan keuangan perusahaan.

**Hak Akses:**
- Dashboard ringkasan keuangan (balance, piutang, pengeluaran)
- **Laporan Order** per periode (view, update, lock/unlock)
- **Laporan Pembelian Material** (catat, edit, hapus, upload bukti, toggle status)
- **Laporan Support Partner** (mitra outsource — jahit, bordir, dll.)
- **Laporan Operasional** (biaya listrik, transport, dll.)
- **Laporan Gaji Karyawan** (ekstrak data gaji, edit, hapus)
- **Kas Internal** (transfer antar rekening kas, upload bukti)
- **Pinjaman Modal** (catat pinjaman, cicilan, riwayat)
- Cek saldo per periode

**Redirect Login:** `/finance/dashboard`

---

### 3.4 PM — Project Manager (Manajer Produksi)

**Deskripsi:** Manajer produksi yang memantau dan mengupdate jadwal/status setiap tahap work order.

**Hak Akses:**
- Dashboard ringkasan tugas produksi
- Melihat semua Work Order beserta tahapannya
- Mengupdate **tanggal deadline** setiap tahap produksi
- Mengupdate **status penyelesaian** setiap tahap (selesai/belum)

**Redirect Login:** `/pm/dashboard`

---

### 3.5 Employee (Karyawan)

**Deskripsi:** Karyawan produksi yang menjalankan tugas dan menandai tugas selesai.

**Hak Akses:**
- Dashboard ringkasan tugas personal
- Melihat daftar tugas/work order yang ditugaskan
- Menandai tugas sebagai **selesai (mark done)**
- Melihat detail work order

**Redirect Login:** `/employee/dashboard`

---

## 4. Halaman per Halaman (Page by Page)

### 4.1 Halaman Login

| Item | Detail |
|------|--------|
| **URL** | `/login` |
| **Akses** | Publik (belum login) |
| **File View** | `resources/views/auth.blade.php` |

**Fitur:**
- Form login dengan username/email dan password
- Validasi kredensial
- Redirect otomatis ke dashboard sesuai role setelah berhasil login
- Tampilan brand "Berkah Digital Production"

---

### 4.2 Owner — Dashboard & Kelola Data

#### 4.2.1 Owner Dashboard

| Item | Detail |
|------|--------|
| **URL** | `/owner/dashboard` |
| **Controller** | `Owner\DashboardController@index` |
| **File View** | `resources/views/pages/owner/dashboard.blade.php` |

**Konten Dashboard:**
- **Statistik ringkasan:** total order, total pendapatan, customer aktif, work order berjalan
- **Grafik Trend Order** — jumlah order per bulan (line chart)
- **Grafik Penjualan Produk** — produk terlaris per periode (bar chart)
- **Grafik Trend Customer** — pertumbuhan customer baru (area chart)
- **Grafik Sebaran Customer per Provinsi** — distribusi geografis (donut chart)
- **Daftar Pending Payment** — pembayaran menunggu persetujuan

**API Chart Endpoints:**
- `GET /owner/dashboard/chart/order-trend`
- `GET /owner/dashboard/chart/product-sales`
- `GET /owner/dashboard/chart/customer-trend`
- `GET /owner/dashboard/chart/customer-province`

---

#### 4.2.2 Riwayat Pembayaran (Owner)

| Item | Detail |
|------|--------|
| **URL** | `/owner/payment-history` |
| **Controller** | `PaymentHistoryController` |

**Fitur:**
- Melihat seluruh riwayat pembayaran dari semua order
- Filter dan sorting pembayaran
- **Approve** pembayaran (`PATCH /owner/payments/{payment}/approve`)
- **Reject** pembayaran (`PATCH /owner/payments/{payment}/reject`)
- Lihat bukti transfer/pembayaran
- Badge pending count di navbar

---

#### 4.2.3 Kelola Data Master — Produk

| Item | Detail |
|------|--------|
| **URL** | `/owner/manage-data/products` |
| **Controller** | `Main\ManageProductsController` |
| **File View** | `resources/views/pages/owner/manage-data/products.blade.php` |

**Master Data yang Dikelola (CRUD):**
| Data Master | Keterangan |
|-------------|-----------|
| Kategori Produk | Jenis produk (kaos, jaket, dll.) |
| Kategori Bahan | Jenis bahan material |
| Tekstur Bahan | Cotton, polyester, dll. |
| Lengan Bahan | Panjang pendek, dll. |
| Ukuran Bahan | S, M, L, XL, dll. |
| Jasa Tambahan | Layanan tambahan (bordir, sablon, dll.) |

---

#### 4.2.4 Kelola Data Master — Work Order

| Item | Detail |
|------|--------|
| **URL** | `/owner/manage-data/work-orders` |
| **File View** | `resources/views/pages/owner/manage-data/work-orders.blade.php` |

**Master Data yang Dikelola (CRUD):**
| Data Master | Keterangan |
|-------------|-----------|
| Pola Potong | Jenis pola pemotongan kain |
| Kain Rantai | Jenis kain rantai |
| Ukuran Rib | Ukuran bagian rib |
| Tinta Print | Jenis tinta sablon |
| Finishing | Jenis penyelesaian akhir |
| Overdeck Leher | Jenis overdeck bagian leher |
| Overdeck Ketiak | Jenis overdeck bagian ketiak |
| Belahan Samping | Jenis belahan samping |
| Label Jahit | Jenis label yang dijahit |
| Plastik Packing | Jenis plastik kemasan |
| Stiker | Jenis stiker produk |

---

#### 4.2.5 Kelola Data Master — Finance

| Item | Detail |
|------|--------|
| **URL** | `/owner/manage-data/finance` |
| **File View** | `resources/views/pages/owner/manage-data/finance.blade.php` |

**Master Data yang Dikelola (CRUD):**
| Data Master | Keterangan |
|-------------|-----------|
| Supplier Material | Pemasok bahan baku |
| Support Partner | Mitra outsource (konveksi lain, bordir, dll.) |
| Daftar Operasional | Item biaya operasional (listrik, air, dll.) |

---

#### 4.2.6 Kelola Pengguna (Users)

| Item | Detail |
|------|--------|
| **URL** | `/owner/manage-data/users` |
| **File View** | `resources/views/pages/owner/manage-data/users.blade.php` |

**Fitur:**
- Melihat daftar semua pengguna
- Tambah pengguna baru (nama, email, password, role)
- Edit data pengguna
- Hapus pengguna
- Role yang tersedia: `owner`, `admin`, `finance`, `pm`, `employee`

---

#### 4.2.7 Kelola Tim Sales

| Item | Detail |
|------|--------|
| **URL** | `/owner/manage-data/sales` |
| **File View** | `resources/views/pages/owner/manage-data/sales.blade.php` |

**Fitur:**
- CRUD data tim sales/penjual
- Data sales digunakan saat pembuatan order (referensi sales yang membawa order)

---

#### 4.2.8 Kelola Profil & Gaji Karyawan

| Item | Detail |
|------|--------|
| **URL** | `/owner/manage-data/user-profile` |
| **File View** | `resources/views/pages/owner/manage-data/user-profile.blade.php` |

**Fitur:**
- Melihat profil detail setiap karyawan
- Edit profil karyawan (nama lengkap, jabatan, kontak)
- Mengatur sistem gaji karyawan (nominal gaji pokok, dll.)

---

### 4.3 Admin — Order & Work Order

#### 4.3.1 Admin Dashboard

| Item | Detail |
|------|--------|
| **URL** | `/admin/dashboard` |
| **File View** | `resources/views/pages/admin/dashboard.blade.php` |

**Konten:**
- Ringkasan order aktif, order pending, total customer
- Shortcut ke menu utama (buat order, work order, customers)
- Statistik pendapatan dan piutang

---

#### 4.3.2 Daftar Order (Orders Index)

| Item | Detail |
|------|--------|
| **URL** | `/admin/orders` |
| **File View** | `resources/views/pages/admin/orders/index.blade.php` |

**Fitur:**
- Tabel daftar semua order dengan filter status
- Status order: `active`, `shipping`, `report`, `cancelled`
- Aksi cepat: lihat, edit, cancel, pindah status
- Tombol "Buat Order Baru"
- Pagination

---

#### 4.3.3 Buat Order Baru (Create Order)

| Item | Detail |
|------|--------|
| **URL** | `/admin/orders/create` (via form) |
| **File View** | `resources/views/pages/admin/orders/create.blade.php` |

**Data yang Diisi:**
- Pilih/buat Customer
- Pilih Sales yang membawa order
- Tanggal order dan deadline
- **Detail Item Order:**
  - Kategori produk, bahan, tekstur, lengan, ukuran
  - Jumlah (qty) per ukuran
  - Harga satuan
  - Jasa tambahan (extra services)
- Catatan/keterangan tambahan
- Upload gambar referensi desain
- Total harga otomatis terhitung

---

#### 4.3.4 Detail Order (Show Order)

| Item | Detail |
|------|--------|
| **URL** | `/admin/orders/{order}` |
| **File View** | `resources/views/pages/admin/orders/show.blade.php` |

**Konten:**
- Informasi lengkap order (customer, sales, tanggal, status)
- Tabel item order dengan rincian harga
- Ringkasan invoice (total tagihan, sudah dibayar, sisa piutang)
- Daftar riwayat pembayaran dengan status (pending/approved/rejected)
- Form tambah pembayaran baru
- Tombol download invoice PDF

---

#### 4.3.5 Edit Order

| Item | Detail |
|------|--------|
| **URL** | `/admin/orders/{order}/edit` |
| **File View** | `resources/views/pages/admin/orders/edit.blade.php` |

**Fitur:** Sama dengan Create Order, prefill data yang sudah ada.

---

#### 4.3.6 Daftar Work Order (Work Orders Index)

| Item | Detail |
|------|--------|
| **URL** | `/admin/work-orders` |
| **File View** | `resources/views/pages/admin/work-orders/index.blade.php` |

**Fitur:**
- Tabel semua work order dengan status produksi
- Tampilan progres tahapan: Cutting, Printing, Placement, Sewing, Packing
- Filter berdasarkan status atau nama order
- Tombol "Buat Work Order"

---

#### 4.3.7 Kelola Work Order (Manage Work Order)

| Item | Detail |
|------|--------|
| **URL** | `/admin/work-orders/{order}/manage` |
| **File View** | `resources/views/pages/admin/work-orders/manage.blade.php` |

**6 Tahapan Produksi yang Dikelola:**

| No | Tahap | Konten |
|----|-------|--------|
| 1 | **Cutting (Pemotongan)** | Pola potong, kain rantai, ukuran rib, warna, kuantitas, upload gambar |
| 2 | **Printing (Sablon)** | Tinta print, jenis sablon, upload gambar mockup |
| 3 | **Placement (Penempatan Desain)** | Posisi desain, ukuran desain, upload gambar layout |
| 4 | **Sewing (Jahit)** | Label jahit, overdeck, belahan, finishing, upload gambar |
| 5 | **Packing (Pengemasan)** | Plastik packing, stiker, metode packing, upload gambar |
| 6 | **Design Variants** | Varian desain produk |

**Fitur tambahan:**
- Setiap tahap bisa upload hingga 7 gambar
- Download Work Order sebagai **PDF** (`GET /admin/work-orders/{workOrder}/download-pdf`)
- Status tahap: pending / in_progress / done

---

#### 4.3.8 Daftar Customer (Customers Index)

| Item | Detail |
|------|--------|
| **URL** | `/admin/customers` |
| **File View** | `resources/views/pages/admin/customers.blade.php` |

**Fitur:**
- Tabel semua customer dengan info ringkas
- Tambah customer baru
- Pencarian customer
- Link ke detail customer

---

#### 4.3.9 Detail Customer (Customer Show)

| Item | Detail |
|------|--------|
| **URL** | `/admin/customers/{customer}` |
| **File View** | `resources/views/pages/admin/customers/show.blade.php` |

**Konten:**
- Informasi lengkap customer (nama, kontak, alamat lengkap s.d. kelurahan)
- Riwayat semua order dari customer tersebut
- Total transaksi customer
- Status piutang

---

#### 4.3.10 Shipping Orders

| Item | Detail |
|------|--------|
| **URL** | `/admin/shipping-orders` |
| **File View** | `resources/views/pages/admin/shipping-orders/index.blade.php` |

**Konten:**
- Daftar order yang sudah dipindahkan ke status "Shipping"
- Informasi pengiriman (customer, tanggal, detail produk)
- Aksi pindahkan ke "Report"

---

#### 4.3.11 Riwayat Pembayaran (Admin)

| Item | Detail |
|------|--------|
| **URL** | `/admin/payment-history` |
| **File View** | `resources/views/pages/admin/payment-history.blade.php` |

**Fitur:**
- Melihat semua pembayaran yang telah dicatat
- Filter berdasarkan status (pending, approved, rejected)
- Catat pembayaran baru dari form order show
- Lihat bukti pembayaran

---

#### 4.3.12 Report Orders (Admin)

Halaman untuk mengelola order yang sudah masuk ke tahap laporan.

| Item | Detail |
|------|--------|
| **URL** | `/admin/report-orders` |

**Fitur:**
- Daftar order dalam status "Report"
- Toggle lock/unlock laporan order
- Hapus laporan order

---

### 4.4 Finance — Laporan Keuangan

#### 4.4.1 Finance Dashboard

| Item | Detail |
|------|--------|
| **URL** | `/finance/dashboard` |
| **File View** | `resources/views/pages/finance/dashboard.blade.php` |

**Konten:**
- Ringkasan saldo kas (cash dan transfer)
- Ringkasan piutang aktif
- Ringkasan pengeluaran bulan ini (material, partner, operasional)
- Ringkasan gaji yang sudah dibayarkan
- Ringkasan pinjaman modal yang aktif

---

#### 4.4.2 Laporan Order

| Item | Detail |
|------|--------|
| **URL** | `/finance/report/order-list` |
| **File View** | `resources/views/pages/finance/report/order-list/index.blade.php` |

**Fitur:**
- Melihat daftar order yang sudah masuk ke tahap report, dikelompokkan per periode
- Update data laporan order (harga final, catatan)
- Lock/unlock laporan per periode (data tidak bisa diubah setelah dikunci)
- Toggle lock per baris laporan
- Hapus laporan order

---

#### 4.4.3 Laporan Pembelian Material

| Item | Detail |
|------|--------|
| **URL** | `/finance/report/material` |
| **File View** | `resources/views/pages/finance/report/material.blade.php` |

**Fitur:**
- Mencatat pembelian bahan baku per order (dikaitkan ke order report)
- Pilih supplier, kuantitas, harga satuan
- Upload bukti pembelian (foto/scan nota)
- Toggle status material (sudah dibayar/belum)
- Edit dan hapus catatan
- Catat pembelian ekstra (di luar order tertentu)
- Cek status periode (apakah bisa diinput atau sudah dikunci)

---

#### 4.4.4 Laporan Support Partner

| Item | Detail |
|------|--------|
| **URL** | `/finance/report/support-partner` |
| **File View** | `resources/views/pages/finance/report/support-partner.blade.php` |

**Fitur:**
- Mencatat pembayaran ke mitra outsource (bordir, jahit khusus, dll.)
- Dikaitkan ke order report tertentu
- Upload bukti pembayaran
- Toggle status pembayaran partner
- Edit dan hapus catatan

---

#### 4.4.5 Laporan Operasional

| Item | Detail |
|------|--------|
| **URL** | `/finance/report/operational` |
| **File View** | `resources/views/pages/finance/report/operational.blade.php` |

**Fitur:**
- Mencatat biaya operasional (listrik, air, internet, transportasi, dll.)
- Pilih item dari daftar operasional master
- Ekstrak otomatis dari daftar operasional yang sudah ada
- Edit dan hapus catatan
- Cek status periode

---

#### 4.4.6 Laporan Gaji Karyawan

| Item | Detail |
|------|--------|
| **URL** | `/finance/report/salary` |
| **File View** | `resources/views/pages/finance/report/salary.blade.php` |

**Fitur:**
- Melihat laporan gaji per periode
- Ekstrak data gaji karyawan berdasarkan data profil & sistem gaji
- Edit nominal gaji (penyesuaian bonus/potongan)
- Hapus catatan gaji
- Ringkasan total pengeluaran gaji per periode

---

#### 4.4.7 Kas Internal (Internal Transfer)

| Item | Detail |
|------|--------|
| **URL** | `/finance/internal-transfer` |
| **File View** | `resources/views/pages/finance/internal-transfer/index.blade.php` |

**Fitur:**
- Mencatat transfer antar rekening kas perusahaan (cash ↔ transfer bank)
- Upload bukti transfer
- Riwayat semua transfer kas
- Saldo per jenis kas (cash dan transfer)

---

#### 4.4.8 Pinjaman Modal (Loan Capital)

| Item | Detail |
|------|--------|
| **URL** | `/finance/loan-capital` |
| **File View** | `resources/views/pages/finance/loan-capital/index.blade.php` |

**Fitur:**
- Mencatat pinjaman modal yang masuk
- Upload bukti pinjaman
- Mencatat cicilan/pembayaran kembali pinjaman
- Riwayat semua cicilan
- Saldo pinjaman yang masih harus dibayar
- Melihat bukti cicilan

---

### 4.5 PM — Manajemen Tugas Produksi

#### 4.5.1 PM Dashboard

| Item | Detail |
|------|--------|
| **URL** | `/pm/dashboard` |
| **File View** | `resources/views/pages/pm/dashboard.blade.php` |

**Konten:**
- Ringkasan work order aktif
- Jumlah tahapan selesai vs. berjalan
- Work order yang mendekati deadline

---

#### 4.5.2 Kelola Tugas (Manage Task)

| Item | Detail |
|------|--------|
| **URL** | `/pm/manage-task` |
| **File View** | `resources/views/pages/pm/manage-task.blade.php` |

**Fitur:**
- Melihat semua work order beserta 6 tahapan produksinya
- Mengupdate **tanggal mulai dan selesai** setiap tahap
- Mengupdate **status penyelesaian** setiap tahap (selesai/belum selesai)
- Filter dan pencarian work order
- Tampilan progres visual per tahap

---

### 4.6 Employee — Pelaksanaan Tugas

#### 4.6.1 Employee Dashboard

| Item | Detail |
|------|--------|
| **URL** | `/employee/dashboard` |
| **File View** | `resources/views/pages/employee/dashboard.blade.php` |

**Konten:**
- Tugas yang sedang berjalan (assigned)
- Tugas yang sudah selesai
- Ringkasan kinerja personal

---

#### 4.6.2 Daftar Tugas (Task List)

| Item | Detail |
|------|--------|
| **URL** | `/employee/task` |
| **File View** | `resources/views/pages/employee/task.blade.php` |

**Fitur:**
- Melihat semua work order/tugas yang ditugaskan
- Status tugas: pending / in_progress / done
- Tombol **Mark Done** untuk menandai tugas selesai
- Filter berdasarkan status

---

#### 4.6.3 Detail Work Order (Employee View)

| Item | Detail |
|------|--------|
| **URL** | `/employee/task/work-order/{order}` |
| **File View** | `resources/views/pages/employee/view-work-order.blade.php` |

**Konten:**
- Detail lengkap work order yang ditugaskan
- Instruksi produksi per tahap (cutting, printing, sewing, dll.)
- Gambar referensi per tahap
- Status setiap tahap

---

### 4.7 Halaman Bersama (Shared Pages)

#### 4.7.1 Kalender Produksi (Calendar)

| Item | Detail |
|------|--------|
| **URL** | `/calendar` |
| **File View** | `resources/views/pages/calendar.blade.php` |
| **Akses** | Semua role yang sudah login |

**Fitur:**
- Kalender visual untuk melihat jadwal produksi
- Menampilkan tanggal pembuatan order, deadline, dan status tahap
- Navigasi bulan/minggu
- Color coding berdasarkan status (on-time, mendekati deadline, terlambat)

---

#### 4.7.2 Highlights

| Item | Detail |
|------|--------|
| **URL** | `/highlights` |
| **File View** | `resources/views/pages/highlights.blade.php` |
| **Akses** | Semua role yang sudah login |

**Konten:**
- Daftar order/work order yang sedang berjalan (WIP — Work in Progress)
- Order yang baru selesai
- Statistik ringkas progres produksi
- Visual card per work order

---

#### 4.7.3 Profil Pengguna (User Profile)

| Item | Detail |
|------|--------|
| **URL** | `/profile` |
| **File View** | `resources/views/pages/profile.blade.php` |
| **Akses** | Semua role yang sudah login |

**Fitur:**
- Melihat dan mengedit profil diri sendiri
- Ganti foto profil
- Ganti password
- Data diri: nama, jabatan, kontak

---

## 5. Fitur-Fitur Utama

### 5.1 Manajemen Order

| Fitur | Keterangan |
|-------|-----------|
| CRUD Order | Buat, baca, edit, hapus pesanan customer |
| Multi-item Order | Satu order bisa memiliki banyak item produk berbeda |
| Extra Services | Jasa tambahan per order (bordir, label custom, dll.) |
| Status Tracking | Order berstatus: active → shipping → report (atau cancelled) |
| Invoice PDF | Generate dan download invoice dalam format PDF |
| Gambar Order | Upload gambar referensi desain dari customer |
| Cancel Order | Pembatalan order dengan alasan |

### 5.2 Manajemen Work Order (Produksi)

| Fitur | Keterangan |
|-------|-----------|
| Buat dari Order | Work Order dibuat berdasarkan Order yang sudah ada |
| 6 Tahap Produksi | Cutting → Printing → Placement → Sewing → Packing |
| Upload Gambar | Upload hingga 7 gambar per tahap (bukti foto produksi) |
| PDF Work Order | Download work order dalam format PDF |
| Status Tahap | Setiap tahap punya status independen |
| Varian Desain | Dukung multiple design variant dalam satu work order |

### 5.3 Manajemen Customer

| Fitur | Keterangan |
|-------|-----------|
| CRUD Customer | Kelola data customer lengkap |
| Alamat Lengkap | Integrasi API wilayah Indonesia (provinsi → kabupaten → kecamatan → kelurahan) |
| Riwayat Order | Semua riwayat order per customer |
| Status Piutang | Monitoring piutang customer real-time |

### 5.4 Manajemen Pembayaran

| Fitur | Keterangan |
|-------|-----------|
| Catat Pembayaran | Admin mencatat pembayaran dari customer |
| Bukti Transfer | Upload foto bukti pembayaran |
| Approval Workflow | Pembayaran harus disetujui Owner sebelum sah |
| Status Pembayaran | Pending → Approved / Rejected |
| Riwayat Lengkap | Semua history pembayaran per order |
| Pending Counter | Badge real-time di navbar untuk payment pending |

### 5.5 Manajemen Keuangan (Finance)

| Fitur | Keterangan |
|-------|-----------|
| Laporan Order | Rekap keuangan per order/periode |
| Laporan Material | Catat pembelian bahan baku dengan bukti |
| Laporan Partner | Catat pembayaran mitra outsource |
| Laporan Operasional | Catat biaya operasional bulanan |
| Laporan Gaji | Penggajian karyawan per periode |
| Kas Internal | Transfer antar rekening kas perusahaan |
| Pinjaman Modal | Kelola pinjaman dan cicilan |
| Period Locking | Kunci laporan per periode agar tidak bisa diubah |

### 5.6 Manajemen Tugas (Task Management)

| Fitur | Keterangan |
|-------|-----------|
| Assign Task | PM mengatur tahapan work order |
| Update Progress | PM update tanggal dan status setiap tahap |
| Mark Done | Employee tandai tugas selesai |
| View Detail | Employee lihat instruksi produksi lengkap |

### 5.7 Analytics & Dashboard

| Fitur | Keterangan |
|-------|-----------|
| Trend Order | Grafik jumlah order per bulan |
| Penjualan Produk | Grafik produk terlaris |
| Trend Customer | Pertumbuhan customer baru |
| Sebaran Geografis | Distribusi customer per provinsi |
| Kalender Produksi | Jadwal visual semua work order |
| Highlights | Sorotan order WIP dan yang baru selesai |

### 5.8 Master Data Management (Owner)

| Fitur | Keterangan |
|-------|-----------|
| 6 Master Data Produk | Kategori, bahan, tekstur, lengan, ukuran, jasa |
| 11 Master Data WO | Pola, kain, rib, tinta, finishing, overdeck, label, packing, stiker |
| 3 Master Data Finance | Supplier, partner, operasional |
| Manajemen User | CRUD akun pengguna dengan role |
| Manajemen Sales | CRUD tim penjualan |
| Profil & Gaji | Kelola profil dan sistem gaji karyawan |

---

## 6. Arsitektur Sistem

### 6.1 Arsitektur Aplikasi (MVC + RBAC)

```
┌─────────────────────────────────────────────────────┐
│                    CLIENT (Browser)                  │
│     Blade Template + Tailwind CSS + Alpine.js        │
│                   (Turbo SPA-like)                   │
└──────────────────────────┬──────────────────────────┘
                           │ HTTP Request
┌──────────────────────────▼──────────────────────────┐
│                   WEB SERVER (Nginx)                 │
└──────────────────────────┬──────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────┐
│                   LARAVEL 12 (PHP 8.2)               │
│  ┌────────────┐  ┌──────────────┐  ┌─────────────┐  │
│  │   Routes   │→│  Middleware  │→│ Controllers  │  │
│  │ (web.php)  │  │(RoleMiddle-  │  │ (38 files)  │  │
│  └────────────┘  │  ware.php)  │  └──────┬──────┘  │
│                  └──────────────┘         │          │
│                                    ┌──────▼──────┐  │
│                                    │   Models    │  │
│                                    │ (54 models) │  │
│                                    └──────┬──────┘  │
└───────────────────────────────────────────┼─────────┘
                                            │
┌───────────────────────────────────────────▼─────────┐
│                   MySQL 8.0 Database                 │
│                    (53 tables)                       │
└─────────────────────────────────────────────────────┘
```

### 6.2 Struktur Controller

```
app/Http/Controllers/
├── Auth/
│   └── LoginController.php        → Login, logout, role-based redirect
├── Employee/
│   └── TaskController.php         → Employee tasks
├── Finance/
│   ├── DashboardController.php    → Finance dashboard
│   ├── InternalTransferController.php
│   └── LoanCapitalController.php
├── Main/
│   ├── ManageFinanceDataController.php
│   ├── ManageProductsController.php
│   ├── ManageUsersSalesController.php
│   ├── ManageWorkOrderDataController.php
│   └── SalesController.php
├── Owner/
│   └── DashboardController.php    → Owner dashboard + chart APIs
├── CalendarController.php
├── CustomerController.php
├── HighlightController.php
├── ManageTaskController.php       → PM task management
├── MaterialReportController.php
├── OperationalReportController.php
├── OrderController.php            → 1337 baris (CRUD order)
├── OrderReportController.php
├── PaymentController.php
├── PaymentHistoryController.php
├── SalaryReportController.php
├── ShippingOrderController.php
├── SupportPartnerReportController.php
├── UserController.php
├── UserProfileController.php
├── WorkOrderController.php        → 1081 baris (6 tahap produksi)
└── [18 Master Data Controllers]   → CRUD data master
```

### 6.3 Middleware RBAC

```php
// RoleMiddleware.php
// Owner = bypass semua cek (super admin)
// Role lain: cek apakah role user ada di daftar role yang diizinkan

Route::middleware('role:admin')          → hanya admin
Route::middleware('role:admin,finance')  → admin atau finance
Route::middleware('role:finance,owner')  → finance atau owner
Route::middleware('role:pm,admin,...')   → pm, admin, dll.
```

---

## 7. Database & Model

### 7.1 Entity Relationship Diagram (Simplified)

```
USERS ────────────── USER_PROFILES
  │                       │
  │ (sales)           SALARY_SYSTEM
  │                   EMPLOYEE_SALARIES
  ▼                   SALARY_REPORTS
SALES

CUSTOMERS ──────── ORDERS ─────────── ORDER_ITEMS
                      │                    │
                      │               EXTRA_SERVICES
                      │
                   INVOICES ────── PAYMENTS
                      │
                   ORDER_STAGES ── PRODUCTION_STAGES
                      │
                   ORDER_REPORTS ─ ORDER_MATERIAL_REPORTS
                                 ─ ORDER_PARTNER_REPORTS
                                 ─ OPERATIONAL_REPORTS
                                 ─ SALARY_REPORTS

ORDERS ──────── WORK_ORDERS ──── WORK_ORDER_CUTTINGS
                    │         ├── WORK_ORDER_PRINTINGS
                    │         │      └── WO_PRINTING_PLACEMENTS
                    │         ├── WORK_ORDER_SEWINGS
                    │         ├── WORK_ORDER_PACKINGS
                    │         └── DESIGN_VARIANTS

MATERIAL_SUPPLIERS ── ORDER_MATERIAL_REPORTS
SUPPORT_PARTNERS ──── ORDER_PARTNER_REPORTS
OPERATIONAL_LISTS ─── OPERATIONAL_REPORTS

BALANCES ─── INTERNAL_TRANSFERS
BALANCES ─── LOAN_CAPITALS ─── LOAN_REPAYMENTS

REPORT_PERIODS
PROVINCES ─ CITIES ─ DISTRICTS ─ VILLAGES (Location)
```

### 7.2 Tabel Utama Database

| Tabel | Fungsi | Kolom Penting |
|-------|--------|--------------|
| `users` | Akun pengguna | id, name, email, password, role |
| `user_profiles` | Profil karyawan | user_id, position, phone |
| `customers` | Data customer | name, email, phone, address, province_id |
| `orders` | Pesanan | customer_id, status, total, deadline |
| `order_items` | Item dalam pesanan | order_id, product details, qty, price |
| `invoices` | Invoice pembayaran | order_id, total, paid, remaining |
| `payments` | Pembayaran | invoice_id, amount, status, proof_image |
| `work_orders` | Work order produksi | order_id, status |
| `work_order_cuttings` | Tahap cutting | work_order_id, pattern, details, images |
| `work_order_printings` | Tahap printing | work_order_id, ink, details, images |
| `work_order_sewings` | Tahap sewing | work_order_id, label, overdeck, images |
| `work_order_packings` | Tahap packing | work_order_id, packing_type, images |
| `order_reports` | Laporan order | order_id, period, status, locked |
| `order_material_reports` | Laporan material | order_report_id, supplier_id, amount |
| `balances` | Saldo kas | type (cash/transfer), amount |
| `loan_capitals` | Pinjaman modal | amount, lender, date, proof |
| `loan_repayments` | Cicilan pinjaman | loan_id, amount, date |
| `internal_transfers` | Transfer kas | from_type, to_type, amount |
| `salary_reports` | Laporan gaji | user_id, period, amount |

---

## 8. Hak Akses per Role (Matrix)

| Fitur / Halaman | Owner | Admin | Finance | PM | Employee |
|-----------------|:-----:|:-----:|:-------:|:--:|:--------:|
| Dashboard khusus role | ✅ | ✅ | ✅ | ✅ | ✅ |
| Semua halaman (bypass) | ✅ | ❌ | ❌ | ❌ | ❌ |
| CRUD Order | ✅ | ✅ | ❌ | ❌ | ❌ |
| Download Invoice PDF | ✅ | ✅ | ❌ | ❌ | ❌ |
| CRUD Work Order | ✅ | ✅ | ❌ | ❌ | ❌ |
| Kelola 6 Tahap Produksi | ✅ | ✅ | ❌ | ❌ | ❌ |
| CRUD Customer | ✅ | ✅ | ❌ | ❌ | ❌ |
| Catat Pembayaran | ✅ | ✅ | ❌ | ❌ | ❌ |
| Approve/Reject Payment | ✅ | ❌ | ❌ | ❌ | ❌ |
| Laporan Order | ✅ | ❌ | ✅ | ❌ | ❌ |
| Laporan Material | ✅ | ❌ | ✅ | ❌ | ❌ |
| Laporan Partner | ✅ | ❌ | ✅ | ❌ | ❌ |
| Laporan Operasional | ✅ | ❌ | ✅ | ❌ | ❌ |
| Laporan Gaji | ✅ | ❌ | ✅ | ❌ | ❌ |
| Kas Internal | ✅ | ❌ | ✅ | ❌ | ❌ |
| Pinjaman Modal | ✅ | ❌ | ✅ | ❌ | ❌ |
| Period Locking | ✅ | ❌ | ✅ | ❌ | ❌ |
| Update Tahap (date/status) | ✅ | ❌ | ❌ | ✅ | ❌ |
| Lihat Work Order (detail) | ✅ | ✅ | ✅ | ✅ | ✅ |
| Mark Done Task | ✅ | ❌ | ❌ | ❌ | ✅ |
| Master Data Produk | ✅ | ❌ | ❌ | ❌ | ❌ |
| Master Data Work Order | ✅ | ❌ | ❌ | ❌ | ❌ |
| Master Data Finance | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manajemen User | ✅ | ❌ | ❌ | ❌ | ❌ |
| Manajemen Sales | ✅ | ❌ | ❌ | ❌ | ❌ |
| Profil & Gaji Karyawan | ✅ | ❌ | ❌ | ❌ | ❌ |
| Analytics/Charts | ✅ | ❌ | ❌ | ❌ | ❌ |
| Kalender Produksi | ✅ | ✅ | ✅ | ✅ | ✅ |
| Highlights | ✅ | ✅ | ✅ | ✅ | ✅ |
| Profil Diri Sendiri | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 9. Alur Proses Bisnis Lengkap

### 9.1 Siklus Hidup Order Lengkap

```
[1. PEMBUATAN ORDER]
Admin Login → Buka menu Orders → Buat Order Baru
  → Pilih Customer (atau buat baru)
  → Pilih Sales penanggung jawab
  → Isi item produk (kategori, bahan, ukuran, qty, harga)
  → Tambahkan extra services jika ada
  → Submit → Order terbuat (status: active)
  → Invoice otomatis terbuat

[2. PEMBUATAN WORK ORDER]
Admin → Menu Work Orders → Buat Work Order dari Order
  → Isi detail setiap tahap produksi:
    • Cutting: pola potong, bahan, kain rantai, ukuran rib
    • Printing: tinta, jenis sablon, warna
    • Placement: posisi dan ukuran desain
    • Sewing: label, overdeck leher/ketiak, belahan, finishing
    • Packing: plastik, stiker, metode packing
  → Upload gambar referensi tiap tahap
  → Simpan Work Order

[3. PELAKSANAAN PRODUKSI]
PM Login → Kelola Tugas → Update tanggal dan status tiap tahap

Employee Login → Daftar Tugas → Lihat instruksi → Kerjakan
  → Selesai → Klik Mark Done

[4. PENGIRIMAN]
Admin → Order selesai produksi → Move to Shipping
  → Order berpindah ke Shipping Orders

[5. PENCATATAN PEMBAYARAN]
Admin → Detail Order → Tambah Pembayaran
  → Isi jumlah, upload bukti → Submit (status: PENDING)

Owner Login → Payment History → Lihat pending payment
  → Review bukti → Approve atau Reject

[6. PELAPORAN KEUANGAN]
Admin → Move Order to Report (setelah pengiriman)

Finance Login → Laporan Order → Update data laporan
Finance → Laporan Material → Catat pembelian bahan
Finance → Laporan Partner → Catat biaya mitra
Finance → Laporan Operasional → Catat biaya operasional
Finance → Laporan Gaji → Ekstrak dan catat gaji
Finance → Lock Periode → Kunci laporan agar final

[7. MONITORING (OWNER)]
Owner Login → Dashboard → Lihat semua chart analytics
Owner → Payment History → Approve/reject pembayaran
Owner → Manage Data → Kelola semua master data
```

### 9.2 Alur Pembayaran Detail

```
Pembayaran dibuat oleh Admin
         │
         ▼
Status: PENDING (menunggu approval)
         │
         ▼
Owner mendapat notifikasi (badge di navbar)
         │
         ▼
Owner melihat bukti transfer
         │
    ┌────┴────────┐
    │ APPROVE     │ REJECT
    ▼             ▼
[APPROVED]    [REJECTED]
    │
    ▼
Invoice diperbarui:
  • Total terbayar bertambah
  • Sisa piutang berkurang
```

### 9.3 Alur Tahapan Produksi Detail

```
Work Order dibuat
         │
         ▼
┌────────────────────────────────────────┐
│  TAHAP 1: CUTTING (Pemotongan)         │
│  • PM set tanggal target               │
│  • Employee kerjakan dan upload foto   │
│  • Employee mark done                  │
└─────────────────┬──────────────────────┘
                  │
                  ▼
┌────────────────────────────────────────┐
│  TAHAP 2: PRINTING (Sablon)            │
│  • PM set tanggal target               │
│  • Employee kerjakan dan upload foto   │
│  • Employee mark done                  │
└─────────────────┬──────────────────────┘
                  │
                  ▼
┌────────────────────────────────────────┐
│  TAHAP 3: PLACEMENT (Penempatan Desain)│
│  • PM set tanggal target               │
│  • Employee kerjakan dan upload foto   │
│  • Employee mark done                  │
└─────────────────┬──────────────────────┘
                  │
                  ▼
┌────────────────────────────────────────┐
│  TAHAP 4: SEWING (Penjahitan)          │
│  • PM set tanggal target               │
│  • Employee kerjakan dan upload foto   │
│  • Employee mark done                  │
└─────────────────┬──────────────────────┘
                  │
                  ▼
┌────────────────────────────────────────┐
│  TAHAP 5: PACKING (Pengemasan)         │
│  • PM set tanggal target               │
│  • Employee kerjakan dan upload foto   │
│  • Employee mark done                  │
└─────────────────┬──────────────────────┘
                  │
                  ▼
         Work Order SELESAI
```

---

*Dokumentasi ini dibuat sebagai bahan referensi Tugas Akhir mahasiswa.*  
*Aplikasi: **Berkah Digital Production** | Nama Project: **berkah-production***
