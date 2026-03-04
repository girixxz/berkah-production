# BERKAH PRODUCTION (STGR) — Dokumentasi Lengkap Struktur Project

> **Framework:** Laravel 12 + Blade + Tailwind CSS v4 + Alpine.js + Turbo  
> **PHP:** ^8.2 | **Node:** Vite 7 | **Database:** MySQL  
> **Local Dev:** Laravel Herd (`berkah-production.test`)

---

## DAFTAR ISI

1. [Struktur Folder](#1-struktur-folder)
2. [Migrasi Database](#2-migrasi-database)
3. [Model dan Relasi](#3-model-dan-relasi)
4. [Controller](#4-controller)
5. [Routes](#5-routes)
6. [Hak Akses & Middleware](#6-hak-akses--middleware)
7. [Views & Styling](#7-views--styling)
8. [Frontend Stack](#8-frontend-stack)
9. [Konfigurasi & Dependencies](#9-konfigurasi--dependencies)
10. [Seeders](#10-seeders)
11. [ER Diagram (Text)](#11-er-diagram-text)

---

## 1. STRUKTUR FOLDER

```
berkah-production/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php          # Login/Logout + role-based redirect
│   │   │   ├── Employee/
│   │   │   │   └── TaskController.php            # Employee task view & mark-done
│   │   │   ├── Finance/
│   │   │   │   ├── InternalTransferController.php # Transfer antar saldo (cash ↔ transfer)
│   │   │   │   └── LoanCapitalController.php      # Pinjaman modal + cicilan
│   │   │   ├── Main/
│   │   │   │   ├── ManageFinanceDataController.php  # Index halaman master data finance
│   │   │   │   ├── ManageProductsController.php     # Index halaman master data produk
│   │   │   │   ├── ManageUsersSalesController.php   # Index halaman users & sales
│   │   │   │   ├── ManageWorkOrderDataController.php # Index halaman master data WO
│   │   │   │   └── SalesController.php              # CRUD sales (tim penjualan)
│   │   │   ├── Owner/
│   │   │   │   └── DashboardController.php       # Owner dashboard + chart data API
│   │   │   ├── CalendarController.php            # Kalender produksi (create/deadline/stage)
│   │   │   ├── ChainClothController.php          # CRUD master kain rantai
│   │   │   ├── Controller.php                    # Base controller (abstract)
│   │   │   ├── CustomerController.php            # CRUD customer + location API
│   │   │   ├── CuttingPatternController.php      # CRUD master pola potong
│   │   │   ├── FinishingController.php           # CRUD master finishing
│   │   │   ├── HighlightController.php           # Highlight order (WIP/Finished)
│   │   │   ├── ManageTaskController.php          # PM task management (stage dates & status)
│   │   │   ├── MaterialCategoryController.php    # CRUD master kategori bahan
│   │   │   ├── MaterialReportController.php      # Laporan pembelian bahan
│   │   │   ├── MaterialSizeController.php        # CRUD master ukuran bahan
│   │   │   ├── MaterialSleeveController.php      # CRUD master lengan bahan
│   │   │   ├── MaterialSupplierController.php    # CRUD master supplier
│   │   │   ├── MaterialTextureController.php     # CRUD master tekstur bahan
│   │   │   ├── NeckOverdeckController.php        # CRUD master overdeck leher
│   │   │   ├── OperationalListController.php     # CRUD master daftar operasional
│   │   │   ├── OperationalReportController.php   # Laporan operasional
│   │   │   ├── OrderController.php               # CRUD order + invoice + PDF (1337 baris)
│   │   │   ├── OrderReportController.php         # Laporan order per periode + locking
│   │   │   ├── PaymentController.php             # Pembayaran + approve/reject (owner)
│   │   │   ├── PaymentHistoryController.php      # Riwayat pembayaran
│   │   │   ├── PlasticPackingController.php      # CRUD master plastik packing
│   │   │   ├── PrintInkController.php            # CRUD master tinta print
│   │   │   ├── ProductCategoryController.php     # CRUD master kategori produk
│   │   │   ├── RibSizeController.php             # CRUD master ukuran rib
│   │   │   ├── SalaryReportController.php        # Laporan gaji karyawan
│   │   │   ├── SalesController.php               # Search sales (API)
│   │   │   ├── ServiceController.php             # CRUD master jasa tambahan
│   │   │   ├── SewingLabelController.php         # CRUD master label jahit
│   │   │   ├── ShippingOrderController.php       # Daftar pengiriman order
│   │   │   ├── SideSplitController.php           # CRUD master belahan samping
│   │   │   ├── StickerController.php             # CRUD master stiker
│   │   │   ├── SupportPartnerController.php      # CRUD master support partner
│   │   │   ├── SupportPartnerReportController.php # Laporan partner pendukung
│   │   │   ├── UnderarmOverdeckController.php    # CRUD master overdeck ketiak
│   │   │   ├── UserController.php                # CRUD user accounts
│   │   │   ├── UserProfileController.php         # Manajemen profil & gaji karyawan
│   │   │   └── WorkOrderController.php           # Work order (6 section) + PDF (1081 baris)
│   │   └── Middleware/
│   │       └── RoleMiddleware.php                # Role-based access control
│   ├── Models/                                    # 54 Eloquent Models
│   │   ├── Balance.php
│   │   ├── ChainCloth.php, City.php, Customer.php, CuttingPattern.php
│   │   ├── DesignVariant.php, District.php
│   │   ├── EmployeeSalary.php, ExtraService.php
│   │   ├── Finishing.php
│   │   ├── InternalTransfer.php, Invoice.php
│   │   ├── LoanCapital.php, LoanRepayment.php
│   │   ├── MaterialCategory.php, MaterialSize.php, MaterialSleeve.php
│   │   ├── MaterialSupplier.php, MaterialTexture.php
│   │   ├── NeckOverdeck.php
│   │   ├── OperationalList.php, OperationalReport.php
│   │   ├── Order.php, OrderItem.php, OrderMaterialReport.php
│   │   ├── OrderPartnerReport.php, OrderReport.php, OrderStage.php
│   │   ├── Payment.php, PlasticPacking.php, PrintInk.php
│   │   ├── ProductCategory.php, ProductionStage.php, Province.php
│   │   ├── ReportPeriod.php, RibSize.php
│   │   ├── SalaryReport.php, SalarySystem.php, Sale.php
│   │   ├── Service.php, SewingLabel.php, SideSplit.php, Sticker.php
│   │   ├── SupportPartner.php
│   │   ├── UnderarmOverdeck.php, User.php, UserProfile.php, Village.php
│   │   ├── WorkOrder.php, WorkOrderCutting.php, WorkOrderPacking.php
│   │   ├── WorkOrderPrinting.php, WorkOrderPrintingPlacement.php
│   │   └── WorkOrderSewing.php
│   └── Providers/
├── bootstrap/
│   ├── app.php                                    # Middleware alias 'role' → RoleMiddleware
│   └── providers.php
├── config/                                        # Standard Laravel config
├── database/
│   ├── migrations/                                # 53 migration files
│   ├── seeders/
│   │   └── DatabaseSeeder.php                     # 11 seeders
│   └── factories/
├── public/
│   ├── build/                                     # Vite compiled assets
│   └── images/
├── resources/
│   ├── css/
│   │   └── app.css                                # Tailwind v4 @theme (custom color palette)
│   ├── js/
│   │   ├── app.js                                 # Turbo + NProgress + Alpine.js
│   │   ├── bootstrap.js                           # Axios setup
│   │   └── charts/
│   │       └── order-trend.js                     # ApexCharts
│   └── views/
│       ├── auth.blade.php                         # Login page
│       ├── layouts/
│       │   └── app.blade.php                      # Layout utama (sidebar + navbar + content)
│       ├── partials/
│       │   ├── navbar.blade.php                   # Top navbar (hamburger + links + notif + user)
│       │   └── sidebar.blade.php                  # Sidebar per role (397 baris)
│       ├── components/
│       │   ├── charts/                            # 5 chart Blade components
│       │   │   ├── order-trend-chart.blade.php
│       │   │   ├── product-sales-chart.blade.php
│       │   │   ├── customer-trend-chart.blade.php
│       │   │   ├── customer-province-chart.blade.php
│       │   │   └── order-by-sales-table.blade.php
│       │   ├── icons/                             # 16 SVG icon components
│       │   ├── sidebar-menu/
│       │   │   ├── main-menu.blade.php
│       │   │   └── sub-menu.blade.php
│       │   ├── custom-pagination.blade.php
│       │   ├── nav-locate.blade.php
│       │   ├── select-form.blade.php
│       │   ├── select-search.blade.php
│       │   └── toast-notif.blade.php
│       └── pages/
│           ├── admin/
│           │   ├── dashboard.blade.php
│           │   ├── customers.blade.php
│           │   ├── customers/show.blade.php
│           │   ├── delivery-orders.blade.php
│           │   ├── orders/
│           │   │   ├── index.blade.php
│           │   │   ├── create.blade.php
│           │   │   ├── edit.blade.php
│           │   │   ├── show.blade.php
│           │   │   └── invoice-pdf.blade.php
│           │   ├── payment-history.blade.php
│           │   ├── shipping-orders/index.blade.php
│           │   └── work-orders/
│           │       ├── index.blade.php
│           │       ├── manage.blade.php
│           │       └── partials/
│           │           ├── form-body.blade.php
│           │           ├── pdf-template.blade.php
│           │           └── show-modal.blade.php
│           ├── employee/
│           │   ├── dashboard.blade.php
│           │   ├── task.blade.php
│           │   └── view-work-order.blade.php
│           ├── finance/
│           │   ├── dashboard.blade.php
│           │   ├── internal-transfer/index.blade.php
│           │   ├── loan-capital/
│           │   │   ├── index.blade.php
│           │   │   └── repayment-history.blade.php
│           │   └── report/
│           │       ├── order-list/
│           │       │   ├── index.blade.php
│           │       │   └── partials/
│           │       ├── material.blade.php
│           │       ├── support-partner.blade.php
│           │       ├── operational.blade.php
│           │       └── salary.blade.php
│           ├── owner/
│           │   ├── dashboard.blade.php
│           │   └── manage-data/
│           │       ├── products.blade.php
│           │       ├── work-orders.blade.php
│           │       ├── finance.blade.php
│           │       ├── users.blade.php
│           │       ├── user-profile.blade.php
│           │       └── sales.blade.php
│           ├── pm/
│           │   ├── dashboard.blade.php
│           │   └── manage-task.blade.php
│           ├── calendar.blade.php
│           ├── highlights.blade.php
│           └── profile.blade.php
├── routes/
│   ├── web.php                                    # Semua route (300+ baris)
│   └── console.php
├── scripts/                                       # Deployment scripts (bash)
├── storage/
│   └── app/private/                               # Private uploaded images
│       ├── orders/
│       ├── payments/
│       ├── work-orders/
│       ├── internal-transfers/
│       ├── loan-capitals/
│       ├── material-reports/
│       ├── partner-reports/
│       ├── operational-reports/
│       └── salary-reports/
├── tests/
├── docs/                                          # Deployment & support docs
├── composer.json
├── package.json
└── vite.config.js
```

---

## 2. MIGRASI DATABASE

### 2.1 Daftar Tabel (53 migrasi → ~40+ tabel)

#### Core / Auth
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 1 | `2024_01_01_000000` | `sessions` | Session store |
| 2 | `2024_01_01_000001` | `users` | User accounts |
| 3 | `2025_10_28_154644` | `cache`, `cache_locks` | Cache store |
| 4 | `2025_12_30_170000` | *(alter)* `users` | Tambah kolom `gender` |
| 5 | `2026_01_11_023808` | *(alter)* `users` | Role: owner/admin/finance/pm/employee + status + migrasi profil |

#### User Profiles & Salary
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 6 | `2026_01_11_023805` | `user_profiles` | Data profil karyawan (fullname, phone, gender, birth_date, dll) |
| 7 | `2026_01_11_023806` | `salary_systems` | Sistem gaji: monthly_1x, monthly_2x, project_3x |
| 8 | `2026_01_11_023807` | `employee_salaries` | Relasi user ↔ salary_system |

#### Master Data Produk
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 9 | `2024_01_01_000002` | `sales` | Data tim sales |
| 10 | `2024_01_01_000003` | `product_categories` | Kategori produk (t-shirt, hoodie, dll) |
| 11 | `2024_01_01_000004` | `material_categories` | Kategori bahan |
| 12 | `2024_01_01_000005` | `material_textures` | Tekstur bahan |
| 13 | `2024_01_01_000006` | `material_sleeves` | Jenis lengan |
| 14 | `2024_01_01_000007` | `material_sizes` | Ukuran + extra_price |
| 15 | `2024_01_01_000009` | `services` | Jasa tambahan |

#### Customer & Location
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 16 | `2024_01_01_000014` | `customers` | Data customer |
| 17 | `2025_12_26_210638` | *(alter)* `customers` | Tambah `birth_date` |
| — | *(via API)* | `provinces`, `cities`, `districts`, `villages` | Data wilayah Indonesia (emsifa API) |

#### Order System
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 18 | `2024_01_01_000015` | `orders` | Order utama (customer, produk, status, dll) |
| 19 | `2024_01_01_000016` | `design_variants` | Varian desain per order |
| 20 | `2024_01_01_000017` | `order_items` | Detail item (variant × sleeve × size × qty × price) |
| 21 | `2024_01_01_000018` | `extra_services` | Jasa tambahan per order |
| 22 | `2024_01_01_000019` | `invoices` | Invoice (1:1 dengan order) |
| 23 | `2024_01_01_000020` | `payments` | Pembayaran + status (pending/approved/rejected) |
| 24 | `2025_12_18_210616` | *(alter)* `design_variants` | Tambah `color` |
| 25 | `2025_12_18_210625` | *(alter)* `orders` | Hapus `product_color` |
| 26 | `2026_01_17_183035` | *(alter)* `orders` | Tambah `report_status`, `report_date` |

#### Production / Work Order
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 27 | `2024_01_01_000021` | `production_stages` | Master tahap produksi |
| 28 | `2024_01_01_000022` | `order_stages` | Progress tahap per order |
| 29 | `2025_11_04_085217` | **6 tabel**: `work_orders`, `work_order_cuttings`, `work_order_printings`, `work_order_printing_placements`, `work_order_sewings`, `work_order_packings` | Work order lengkap |

#### Master Data Work Order
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 30-40 | `2024_01_01_000023–000033` | `cutting_patterns`, `chain_cloths`, `rib_sizes`, `print_inks`, `finishings`, `neck_overdecks`, `underarm_overdecks`, `side_splits`, `sewing_labels`, `plastic_packings`, `stickers` | Master data WO (11 tabel lookup) |

#### Finance & Balance
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 41 | `2026_01_17_190738` | `balances` | Saldo per periode (transfer + cash) |
| 42 | `2026_01_17_190748` | `loan_capitals` | Pinjaman modal |
| 43 | `2026_01_17_190748` | `loan_repayments` | Cicilan pinjaman |
| 44 | `2026_01_18_065151` | `internal_transfers` | Transfer internal (cash ↔ transfer ↔ withdraw) |

#### Finance Reports
| # | Nama File | Tabel | Deskripsi |
|---|-----------|-------|-----------|
| 45 | `2026_01_11_073211` | `material_suppliers` | Master supplier bahan |
| 46 | `2026_01_11_073219` | `support_partners` | Master partner pendukung |
| 47 | `2026_01_13_000001` | `operational_lists` | Master daftar operasional (fix_cost_1/2, printing_supply) |
| 48 | `2026_01_20_000000` | `report_periods` | Periode laporan + lock_status |
| 49 | `2026_01_24_000000` | `order_reports` | Laporan order per periode |
| 50 | `2026_01_27_160558` | `order_material_reports` | Laporan pembelian bahan per order |
| 51 | `2026_02_03_100000` | `order_partner_reports` | Laporan support partner per order |
| 52 | `2026_02_12_100000` | `operational_reports` | Laporan operasional |
| 53 | `2026_02_23_100000` | `salary_reports` | Laporan gaji karyawan |

### 2.2 Skema Tabel Final (Setelah Semua Migrasi)

#### `users` (final)
| Kolom | Tipe | Constraint |
|-------|------|-----------|
| id | bigint unsigned AI | PK |
| username | string(100) | UNIQUE |
| password | string | hashed |
| role | enum: owner, admin, finance, pm, employee | |
| status | enum: active, inactive | default: active |
| remember_token | string(100) | nullable |
| timestamps | | |

#### `orders` (final)
| Kolom | Tipe | Constraint |
|-------|------|-----------|
| id | bigint unsigned AI | PK |
| priority | enum: normal, high | default: normal |
| customer_id | FK → customers | CASCADE |
| sales_id | FK → sales | CASCADE |
| order_date, deadline | timestamp | |
| product_category_id | FK → product_categories | CASCADE |
| material_category_id | FK → material_categories | CASCADE |
| material_texture_id | FK → material_textures | CASCADE |
| notes | text | nullable |
| shipping_type | enum: pickup, delivery | |
| shipping_status | enum: pending, shipped | default: pending |
| shipping_date | timestamp | nullable |
| total_qty | integer | default: 0 |
| subtotal, discount, grand_total | decimal(12,2) | |
| production_status | enum: pending, wip, finished, cancelled | default: pending |
| wip_date, finished_date, cancelled_date | timestamp | nullable |
| work_order_status | enum: pending, created | default: pending |
| report_status | enum: pending, reported | default: pending |
| report_date | date | nullable |
| img_url | string(255) | nullable |
| timestamps | | |

#### `invoices`
| Kolom | Tipe | Constraint |
|-------|------|-----------|
| id | bigint unsigned AI | PK |
| order_id | FK → orders | UNIQUE, CASCADE |
| invoice_no | string(50) | UNIQUE (INV-STGR-XXXX) |
| total_bill, amount_paid, amount_due | decimal(12,2) | |
| status | enum: unpaid, dp, paid | default: unpaid |
| notes | text | nullable |
| timestamps | | |

#### `balances`
| Kolom | Tipe | Constraint |
|-------|------|-----------|
| id | bigint unsigned AI | PK |
| period_start, period_end | date | |
| total_balance | decimal(12,2) | |
| transfer_balance | decimal(12,2) | |
| cash_balance | decimal(12,2) | |
| timestamps | | |

---

## 3. MODEL DAN RELASI

### 3.1 Diagram Relasi Utama

```
User ──HasOne──→ UserProfile
User ──HasOne──→ EmployeeSalary ──BelongsTo──→ SalarySystem

Customer ──HasMany──→ Order
Sale ──HasMany──→ Order

Order ──BelongsTo──→ Customer, Sale, ProductCategory, MaterialCategory, MaterialTexture
Order ──HasMany──→ DesignVariant, OrderItem, ExtraService, OrderStage, WorkOrder, OrderReport
Order ──HasOne──→ Invoice

DesignVariant ──BelongsTo──→ Order
DesignVariant ──HasMany──→ OrderItem
DesignVariant ──HasOne──→ WorkOrder

OrderItem ──BelongsTo──→ Order, DesignVariant, MaterialSleeve, MaterialSize
ExtraService ──BelongsTo──→ Order, Service

Invoice ──HasMany──→ Payment, OrderReport
Payment ──BelongsTo──→ Invoice

OrderStage ──BelongsTo──→ Order, ProductionStage
ProductionStage ──HasMany──→ OrderStage

WorkOrder ──BelongsTo──→ Order, DesignVariant
WorkOrder ──HasOne──→ WorkOrderCutting, WorkOrderPrinting, WorkOrderPrintingPlacement, WorkOrderSewing, WorkOrderPacking

WorkOrderCutting ──BelongsTo──→ CuttingPattern, ChainCloth, RibSize
WorkOrderPrinting ──BelongsTo──→ PrintInk, Finishing
WorkOrderSewing ──BelongsTo──→ NeckOverdeck, UnderarmOverdeck, SideSplit, SewingLabel
WorkOrderPacking ──BelongsTo──→ PlasticPacking, Sticker

Balance ──HasMany──→ LoanCapital, OperationalReport
LoanCapital ──HasMany──→ LoanRepayment
LoanRepayment ──BelongsTo──→ LoanCapital, Balance
InternalTransfer ──BelongsTo──→ Balance

OrderReport ──BelongsTo──→ Order, Invoice
OrderReport ──HasMany──→ OrderMaterialReport, OrderPartnerReport
OrderMaterialReport ──BelongsTo──→ Balance, OrderReport, MaterialSupplier
OrderPartnerReport ──BelongsTo──→ Balance, OrderReport, SupportPartner
OperationalReport ──BelongsTo──→ Balance
SalaryReport ──BelongsTo──→ Balance, EmployeeSalary

Province ──HasMany──→ City
City ──HasMany──→ District
District ──HasMany──→ Village
```

### 3.2 Detail Semua Model (54 model)

#### Core Models

| Model | Tabel | Fillable | Relasi Utama |
|-------|-------|----------|--------------|
| **User** | users | username, password, role, status | HasOne: UserProfile, EmployeeSalary |
| **UserProfile** | user_profiles | user_id, fullname, phone_number, gender, birth_date, work_date, dress_size, address | BelongsTo: User |
| **EmployeeSalary** | employee_salaries | user_id, salary_system_id | BelongsTo: User, SalarySystem |
| **SalarySystem** | salary_systems | type_name | HasMany: EmployeeSalary |

#### Order Models

| Model | Tabel | Fillable | Relasi Utama |
|-------|-------|----------|--------------|
| **Order** | orders | priority, customer_id, sales_id, order_date, deadline, product_category_id, material_category_id, material_texture_id, notes, shipping_type/status/date, total_qty, subtotal, discount, grand_total, production_status, work_order_status, report_status, report_date, img_url | BelongsTo: Customer, Sale, ProductCategory, MaterialCategory, MaterialTexture; HasMany: DesignVariant, OrderItem, ExtraService, OrderStage, WorkOrder, OrderReport; HasOne: Invoice |
| **DesignVariant** | design_variants | order_id, design_name, color | BelongsTo: Order; HasMany: OrderItem; HasOne: WorkOrder |
| **OrderItem** | order_items | order_id, design_variant_id, sleeve_id, size_id, qty, unit_price, subtotal | BelongsTo: Order, DesignVariant, MaterialSleeve, MaterialSize |
| **ExtraService** | extra_services | order_id, service_id, price | BelongsTo: Order, Service |
| **Invoice** | invoices | order_id, invoice_no, total_bill, amount_paid, amount_due, status, notes | BelongsTo: Order; HasMany: Payment, OrderReport |
| **Payment** | payments | invoice_id, paid_at, payment_method, payment_type, amount, status, notes, img_url | BelongsTo: Invoice |
| **Customer** | customers | customer_name, phone, birth_date, province_id, city_id, district_id, village_id, address | HasMany: Order |
| **Sale** | sales | sales_name, phone | HasMany: Order |

#### Production Models

| Model | Tabel | Fillable | Relasi Utama |
|-------|-------|----------|--------------|
| **ProductionStage** | production_stages | stage_name | HasMany: OrderStage |
| **OrderStage** | order_stages | order_id, stage_id, start_date, deadline, status | BelongsTo: Order, ProductionStage |
| **WorkOrder** | work_orders | order_id, design_variant_id, mockup_img_url, status | BelongsTo: Order, DesignVariant; HasOne: Cutting/Printing/PrintingPlacement/Sewing/Packing |
| **WorkOrderCutting** | work_order_cuttings | work_order_id, cutting_pattern_id, chain_cloth_id, rib_size_id, custom_size_chart_img_url, notes | BelongsTo: WorkOrder, CuttingPattern, ChainCloth, RibSize |
| **WorkOrderPrinting** | work_order_printings | work_order_id, print_ink_id, finishing_id, detail_img_url, notes | BelongsTo: WorkOrder, PrintInk, Finishing |
| **WorkOrderPrintingPlacement** | work_order_printing_placements | work_order_id, detail_img_url, notes | BelongsTo: WorkOrder |
| **WorkOrderSewing** | work_order_sewings | work_order_id, neck_overdeck_id, underarm_overdeck_id, side_split_id, sewing_label_id, detail_img_url, notes | BelongsTo: WorkOrder, NeckOverdeck, UnderarmOverdeck, SideSplit, SewingLabel |
| **WorkOrderPacking** | work_order_packings | work_order_id, plastic_packing_id, sticker_id, hangtag_img_url, notes | BelongsTo: WorkOrder, PlasticPacking, Sticker |

#### Finance Models

| Model | Tabel | Fillable | Relasi Utama |
|-------|-------|----------|--------------|
| **Balance** | balances | period_start, period_end, total_balance, transfer_balance, cash_balance | HasMany: LoanCapital, OperationalReport |
| **InternalTransfer** | internal_transfers | transfer_date, balance_id, transfer_type, amount, notes, proof_img | BelongsTo: Balance |
| **LoanCapital** | loan_capitals | balance_id, loan_date, amount, remaining_amount, payment_method, proof_img, status, notes | BelongsTo: Balance; HasMany: LoanRepayment |
| **LoanRepayment** | loan_repayments | loan_id, balance_id, paid_date, amount, payment_method, proof_img, notes | BelongsTo: LoanCapital, Balance |
| **ReportPeriod** | report_periods | period_start, period_end, lock_status | — |

#### Report Models

| Model | Tabel | Fillable | Relasi Utama |
|-------|-------|----------|--------------|
| **OrderReport** | order_reports | period_start, period_end, order_id, invoice_id, product_type, note | BelongsTo: Order, Invoice; HasMany: OrderMaterialReport, OrderPartnerReport |
| **OrderMaterialReport** | order_material_reports | balance_id, order_report_id, purchase_date, purchase_type, material_name, material_supplier_id, amount, notes, payment_method, proof_img, proof_img2, report_status | BelongsTo: Balance, OrderReport, MaterialSupplier |
| **OrderPartnerReport** | order_partner_reports | balance_id, order_report_id, service_date, service_type, service_name, support_partner_id, amount, notes, payment_method, proof_img, proof_img2, report_status | BelongsTo: Balance, OrderReport, SupportPartner |
| **OperationalReport** | operational_reports | balance_id, operational_date, operational_type, category, operational_name, amount, notes, payment_method, proof_img, proof_img2, report_status | BelongsTo: Balance |
| **SalaryReport** | salary_reports | balance_id, salary_date, employee_salary_id, payment_sequence, amount, notes, payment_method, proof_img, report_status | BelongsTo: Balance, EmployeeSalary |

#### Master Data / Lookup Models (12 model, tanpa relasi)

| Model | Tabel | Fillable |
|-------|-------|----------|
| ProductCategory | product_categories | product_name, sort_order |
| MaterialCategory | material_categories | material_name, sort_order |
| MaterialTexture | material_textures | texture_name, sort_order |
| MaterialSleeve | material_sleeves | sleeve_name, sort_order |
| MaterialSize | material_sizes | size_name, extra_price, sort_order |
| Service | services | service_name, sort_order |
| CuttingPattern | cutting_patterns | name, sort_order |
| ChainCloth | chain_cloths | name, sort_order |
| RibSize | rib_sizes | name, sort_order |
| PrintInk | print_inks | name, sort_order |
| Finishing | finishings | name, sort_order |
| NeckOverdeck | neck_overdecks | name, sort_order |
| UnderarmOverdeck | underarm_overdecks | name, sort_order |
| SideSplit | side_splits | name, sort_order |
| SewingLabel | sewing_labels | name, sort_order |
| PlasticPacking | plastic_packings | name, sort_order |
| Sticker | stickers | name, sort_order |
| MaterialSupplier | material_suppliers | supplier_name, notes, sort_order |
| SupportPartner | support_partners | partner_name, notes, sort_order |
| OperationalList | operational_lists | category, list_name, sort_order |

#### Location Models (API-driven)

| Model | Tabel | Relasi |
|-------|-------|--------|
| Province | provinces | HasMany: City, Customer |
| City | cities | BelongsTo: Province; HasMany: District, Customer |
| District | districts | BelongsTo: City; HasMany: Village, Customer |
| Village | villages | BelongsTo: District; HasMany: Customer |

### 3.3 Custom Methods & Accessors

| Model | Method/Accessor | Fungsi |
|-------|-----------------|--------|
| Order | `checkAndUpdateProductionStatus()` | Auto-update status jika semua stage done |
| OrderReport | `isLocked()` | Cek apakah periode laporan terkunci |
| WorkOrder | `isComplete()` | Cek apakah semua 5 sub-section lengkap |
| ReportPeriod | `isLocked()`, `isDraft()` | Cek status lock periode |
| OrderStage | `getStatusAttribute()` | Auto-upgrade pending → in_progress jika tanggal tercapai |
| InternalTransfer | `getTransferTypeDisplayAttribute()` | Format display transfer type |
| InternalTransfer | `getPeriodAttribute()` | Get period string dari balance |
| MaterialCategory, MaterialSize, MaterialSleeve, MaterialTexture, ProductCategory | `getNameAttribute()` | Map field spesifik ke `name` via `$appends` |

---

## 4. CONTROLLER

### 4.1 Ringkasan Per Kelompok

#### Auth (1 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `Auth\LoginController` | showLoginForm, login, logout | Login dengan role-based redirect |

#### Order & Customer (3 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `OrderController` | index, create, store, show, edit, update, destroy, cancel, moveToShipping, moveToReport, downloadInvoice, getCustomerLocation, serveOrderImage | Order CRUD + invoice PDF + workflow (1337 baris) |
| `CustomerController` | index, show, store, update, destroy, getProvinces, getCities, getDistricts, getVillages | Customer CRUD + cascading location API |
| `ShippingOrderController` | index | Daftar order yang di-shipping |

#### Payment (2 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `PaymentController` | store, getPaymentsByInvoice, destroy, approve, reject, serveImage, getPendingCount, getPendingList | Payment workflow + owner approval |
| `PaymentHistoryController` | index | Riwayat pembayaran dengan filter |

#### Work Order (1 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `WorkOrderController` | index, manage, store, update, finalize, downloadPdf, serve*Image (6) | Work order 6-section + PDF (1081 baris) |

#### Production & Task (3 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `ManageTaskController` | index, updateStage, updateStageStatus | PM: kelola stage produksi |
| `Employee\TaskController` | index, markAsDone, viewWorkOrder | Employee: lihat & selesaikan task |
| `CalendarController` | index | Kalender produksi (3 mode) |

#### Finance Reports (5 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `OrderReportController` | index, togglePeriodLock, update, toggleLock, destroy | Laporan order per periode |
| `MaterialReportController` | index, store, storeExtra, update, destroy, toggleReportStatus, +5 API | Laporan bahan (dual image) |
| `SupportPartnerReportController` | index, store, storeExtra, update, destroy, toggleReportStatus, +5 API | Laporan partner (mirror MaterialReport) |
| `OperationalReportController` | index, store, storeExtra, update, destroy, extractFromOperationLists, +3 API | Laporan operasional |
| `SalaryReportController` | index, extract, update, destroy, serveImage | Laporan gaji |

#### Finance Other (2 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `Finance\InternalTransferController` | index, store, serveImage | Transfer dana cash ↔ transfer |
| `Finance\LoanCapitalController` | index, store, update, storeRepayment, repaymentHistory, findBalanceByPeriod, serveImage, serveRepaymentImage | Pinjaman modal + cicilan |

#### Owner Dashboard (1 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `Owner\DashboardController` | index, getOrderTrendData, getProductSalesData, getCustomerTrendData, getCustomerProvinceData | Dashboard + 4 chart API endpoint |

#### Shared Features (1 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `HighlightController` | index | Highlight WIP/Finished orders |

#### Master Data Pages (5 controller)
| Controller | Methods | Deskripsi |
|-----------|---------|-----------|
| `Main\ManageProductsController` | index | Agregasi 6 master data produk |
| `Main\ManageWorkOrderDataController` | index | Agregasi 11 master data WO |
| `Main\ManageFinanceDataController` | index | Agregasi supplier, partner, operational list |
| `Main\ManageUsersSalesController` | index | Agregasi users & sales |
| `Main\SalesController` | index, store, update, destroy | CRUD sales |

#### Master Data CRUD (21 controller dengan pola identik)

Semua mengikuti pattern yang sama:
- `store()` — validasi + create + auto sort_order
- `update()` — validasi + update + Cache::forget()
- `destroy()` — delete + reorder + Cache::forget()
- Redirect ke halaman manage-data parent

| Controller | Model | Cache Key |
|-----------|-------|-----------|
| ProductCategoryController | ProductCategory | product_categories |
| MaterialCategoryController | MaterialCategory | material_categories |
| MaterialTextureController | MaterialTexture | material_textures |
| MaterialSleeveController | MaterialSleeve | material_sleeves |
| MaterialSizeController | MaterialSize | material_sizes |
| ServiceController | Service | services |
| CuttingPatternController | CuttingPattern | cutting_patterns |
| ChainClothController | ChainCloth | chain_cloths |
| RibSizeController | RibSize | rib_sizes |
| PrintInkController | PrintInk | print_inks |
| FinishingController | Finishing | finishings |
| NeckOverdeckController | NeckOverdeck | neck_overdecks |
| UnderarmOverdeckController | UnderarmOverdeck | underarm_overdecks |
| SideSplitController | SideSplit | side_splits |
| SewingLabelController | SewingLabel | sewing_labels |
| PlasticPackingController | PlasticPacking | plastic_packings |
| StickerController | Sticker | stickers |
| MaterialSupplierController | MaterialSupplier | — |
| SupportPartnerController | SupportPartner | — |
| OperationalListController | OperationalList | — |
| UserController | User + UserProfile | — |
| UserProfileController | User + EmployeeSalary | — |

### 4.2 Fitur Umum Antar Controller

| Fitur | Detail |
|-------|--------|
| **Image Compression** | GD library, >200KB → JPEG quality 80%, resize proportional |
| **Private Image Serving** | Storage::disk('local'), path: `private/...`, served via controller method |
| **Balance Management** | Auto-deduct/restore saldo (transfer/cash) pada setiap CRUD report |
| **Cache** | Master data di-cache via `Cache::rememberForever()`, invalidasi saat mutasi |
| **AJAX Support** | Deteksi `$request->ajax()` → return JSON fragment, else return full Blade view |
| **Pagination** | 10-25 per halaman, dengan custom Blade component |
| **Sort Order** | Master data otomatis reorder saat create/delete |

---

## 5. ROUTES

### 5.1 Route Map Lengkap

```
GET  /                          → redirect /login
GET  /login                     → LoginController@showLoginForm (+ auto-redirect jika sudah login)
POST /                          → LoginController@login
POST /logout                    → LoginController@logout
GET  /test                      → test string

[auth middleware]
├── GET  highlights              → HighlightController@index
├── GET  calendar                → CalendarController@index
├── GET  profile                 → pages.profile
│
├── [role:owner] /owner/
│   ├── GET  dashboard                          → Owner\DashboardController@index
│   ├── GET  payment-history                    → PaymentHistoryController@index
│   ├── PATCH payments/{payment}/approve        → PaymentController@approve
│   ├── PATCH payments/{payment}/reject         → PaymentController@reject
│   ├── GET  payments/pending-count             → PaymentController@getPendingCount    [JSON]
│   ├── GET  payments/pending-list              → PaymentController@getPendingList     [JSON]
│   ├── /manage-data/products/
│   │   ├── GET  /                              → ManageProductsController@index
│   │   ├── RESOURCE product-categories         → ProductCategoryController (store/update/destroy)
│   │   ├── RESOURCE material-categories        → MaterialCategoryController
│   │   ├── RESOURCE material-textures          → MaterialTextureController
│   │   ├── RESOURCE material-sleeves           → MaterialSleeveController
│   │   ├── RESOURCE material-sizes             → MaterialSizeController
│   │   └── RESOURCE services                   → ServiceController
│   ├── /manage-data/work-orders/
│   │   ├── GET  /                              → ManageWorkOrderDataController@index
│   │   └── RESOURCE × 11                       → CuttingPattern/ChainCloth/RibSize/PrintInk/Finishing/
│   │                                             NeckOverdeck/UnderarmOverdeck/SideSplit/SewingLabel/
│   │                                             PlasticPacking/Sticker Controllers
│   ├── /manage-data/finance/
│   │   ├── GET  /                              → ManageFinanceDataController@index
│   │   ├── RESOURCE material-suppliers         → MaterialSupplierController
│   │   ├── RESOURCE support-partners           → SupportPartnerController
│   │   └── RESOURCE operational-lists          → OperationalListController
│   ├── /manage-data/users/                     → UserController (index/store/update/destroy)
│   ├── /manage-data/sales/                     → Main\SalesController (index/store/update/destroy)
│   ├── /manage-data/user-profile/              → UserProfileController (index/update)
│   └── /dashboard/chart/
│       ├── GET order-trend                     → DashboardController@getOrderTrendData    [JSON]
│       ├── GET product-sales                   → DashboardController@getProductSalesData  [JSON]
│       ├── GET customer-trend                  → DashboardController@getCustomerTrendData [JSON]
│       └── GET customer-province               → DashboardController@getCustomerProvinceData [JSON]
│
├── [role:admin] /admin/
│   ├── GET  dashboard                          → pages.admin.dashboard
│   ├── GET  work-orders                        → WorkOrderController@index
│   ├── GET  work-orders/{order}/manage         → WorkOrderController@manage
│   ├── POST work-orders                        → WorkOrderController@store (throttle:10,1)
│   ├── PUT  work-orders/{workOrder}            → WorkOrderController@update (throttle:10,1)
│   ├── GET  work-orders/{order}/finalize       → WorkOrderController@finalize
│   ├── GET  customers/{customer}               → CustomerController@show
│   └── RESOURCE customers                      → CustomerController
│
├── [role:admin,finance] /admin/
│   ├── RESOURCE orders                         → OrderController (full CRUD)
│   ├── PATCH orders/{order}/cancel             → OrderController@cancel
│   ├── PATCH orders/{order}/move-to-shipping   → OrderController@moveToShipping
│   ├── PATCH orders/{order}/move-to-report     → OrderController@moveToReport
│   ├── GET  orders/{order}/invoice/download    → OrderController@downloadInvoice
│   ├── POST payments                           → PaymentController@store
│   ├── DELETE payments/{payment}               → PaymentController@destroy
│   ├── GET  invoices/{invoice}/payments        → PaymentController@getPaymentsByInvoice [JSON]
│   ├── GET  shipping-orders                    → ShippingOrderController@index
│   ├── GET  report-orders                      → OrderReportController@index
│   ├── PATCH report-orders/{}/toggle-lock      → OrderReportController@toggleLock
│   ├── DELETE report-orders/{}                 → OrderReportController@destroy
│   ├── GET  payment-history                    → PaymentHistoryController@index
│   └── GET  customers/api/provinces|cities|districts|villages → CustomerController@getLocation [JSON]
│
├── [role:finance,owner] /finance/
│   ├── GET  dashboard                          → pages.finance.dashboard
│   ├── /report/
│   │   ├── GET  order-list                     → OrderReportController@index
│   │   ├── POST order-list/toggle-period-lock  → OrderReportController@togglePeriodLock
│   │   ├── PATCH order-list/{}/toggle-lock     → OrderReportController@toggleLock
│   │   ├── PATCH order-list/{}/update          → OrderReportController@update
│   │   ├── DELETE order-list/{}                → OrderReportController@destroy
│   │   ├── GET/POST material/*                 → MaterialReportController (12 routes)
│   │   ├── GET/POST support-partner/*          → SupportPartnerReportController (12 routes)
│   │   ├── GET/POST operational/*              → OperationalReportController (10 routes)
│   │   └── GET/POST salary/*                   → SalaryReportController (5 routes)
│   ├── GET/POST internal-transfer              → Finance\InternalTransferController (3 routes)
│   └── GET/POST loan-capital/*                 → Finance\LoanCapitalController (8 routes)
│
├── [role:pm,admin,owner,finance] /pm/
│   ├── GET  dashboard                          → pages.pm.dashboard
│   ├── GET  manage-task                        → ManageTaskController@index
│   ├── POST manage-task/update-stage           → ManageTaskController@updateStage [JSON]
│   └── POST manage-task/update-stage-status    → ManageTaskController@updateStageStatus [JSON]
│
├── [role:employee,admin,pm,finance] /employee/
│   ├── GET  dashboard                          → pages.employee.dashboard
│   ├── GET  task                               → Employee\TaskController@index
│   ├── POST task/mark-done                     → Employee\TaskController@markAsDone [JSON]
│   └── GET  task/work-order/{order}            → Employee\TaskController@viewWorkOrder
│
├── [all authenticated] Shared Image/File Routes
│   ├── GET  admin/work-orders/{}/download-pdf  → WorkOrderController@downloadPdf
│   ├── GET  payments/{}/image                  → PaymentController@serveImage
│   ├── GET  orders/{}/image                    → OrderController@serveOrderImage
│   ├── GET  work-orders/{}/mockup-image        → WorkOrderController@serveMockupImage
│   ├── GET  work-orders/cutting/{}/image       → WorkOrderController@serveCuttingImage
│   ├── GET  work-orders/printing/{}/image      → WorkOrderController@servePrintingImage
│   ├── GET  work-orders/placement/{}/image     → WorkOrderController@servePlacementImage
│   ├── GET  work-orders/sewing/{}/image        → WorkOrderController@serveSewingImage
│   ├── GET  work-orders/packing/{}/image       → WorkOrderController@servePackingImage
│   └── GET  customers/{}/location              → OrderController@getCustomerLocation [JSON]
```

### 5.2 Total Routes: ~120+ route definitions

---

## 6. HAK AKSES & MIDDLEWARE

### 6.1 Middleware Stack

```php
// bootstrap/app.php
$middleware->alias([
    'role' => \App\Http\Middleware\RoleMiddleware::class,
]);
```

### 6.2 RoleMiddleware Logic

```
1. Cek login → redirect ke /login jika belum
2. Ambil role user (string dari kolom `role`)
3. Owner = SUPER ADMIN → bypass semua pengecekan
4. Jika role user TIDAK termasuk daftar roles yang diizinkan → abort(403)
```

### 6.3 Role Matrix

| Role | Akses Menu |
|------|-----------|
| **owner** | SEMUA (super admin bypass) — Dashboard, Manage Data, Admin, Finance, PM, Employee |
| **admin** | Admin (Dashboard, Orders, Work Orders, Shipping, Payment History, Customers) |
| **finance** | Finance (Reports, Internal Transfer, Loan Capital) + Admin (Orders, Shipping, Payment History) + PM (Task Manager) + Employee (Task) |
| **pm** | PM (Dashboard, Task Manager) |
| **employee** | Employee (Dashboard, Task) |

### 6.4 Route Group × Role Mapping

| Route Prefix | Middleware | Roles yang Bisa Akses |
|-------------|-----------|----------------------|
| `/owner/*` | `role:owner` | owner |
| `/admin/*` (admin-only) | `role:admin` | owner, admin |
| `/admin/*` (shared) | `role:admin,finance` | owner, admin, finance |
| `/finance/*` | `role:finance,owner` | owner, finance |
| `/pm/*` | `role:pm,admin,owner,finance` | owner, admin, finance, pm |
| `/employee/*` | `role:employee,admin,pm,finance` | owner, admin, finance, pm, employee |
| `/highlights`, `/calendar`, `/profile` | `auth` | semua yang login |
| Shared image routes | `auth` | semua yang login |

### 6.5 Fitur Khusus Per Role

| Role | Fitur Eksklusif |
|------|----------------|
| **owner** | Approve/Reject payment, Lock periode laporan, Manage semua master data, Dashboard analytics + charts, Notification bell payment pending |
| **admin** | Buat/Edit/Delete order, Buat work order, Manage customer, Finalize work order |
| **finance** | Buat laporan (material, partner, operational, salary), Internal transfer, Loan capital, Toggle report status (draft ↔ fixed) |
| **pm** | Update stage dates & status, Monitor semua task produksi |
| **employee** | Mark task as done, Lihat work order (read-only) |

---

## 7. VIEWS & STYLING

### 7.1 Layout System

```
layouts/app.blade.php
├── <head>
│   ├── CSRF meta tag
│   ├── @vite(['css/app.css', 'js/app.js'])
│   └── Custom styles (x-cloak, NProgress)
├── <body x-data="{ sidebarOpen, userPreference, init(), toggleSidebar() }">
│   ├── SIDEBAR (XL+: push content, <XL: overlay + dark backdrop)
│   │   └── @include('partials.sidebar')
│   ├── MAIN CONTENT
│   │   ├── @include('partials.navbar')
│   │   └── <main> @yield('content') </main>
│   ├── <x-toast-notif />
│   ├── @stack('modals')
│   ├── @stack('scripts')
│   └── [Owner only] Notification polling script (60s interval)
```

### 7.2 Sidebar Navigation (per role)

| Section | Visible For | Menu Items |
|---------|------------|------------|
| **MENU (Owner)** | owner | Dashboard, Manage Data (Products, Master WO, Finance, Users Account, User Profile, Sales Data) |
| **ADMIN** | owner, admin | Dashboard*, Orders, Work Orders, Shipping Orders, Payment History, Customers |
| **FINANCE** | owner, finance | Dashboard*, Report (Order List, Material, Support Partner, Operational, Salary), Internal Transfer, Loan Capital |
| **PRODUCT MANAGER** | owner, admin, pm | Dashboard*, Task Manager |
| **EMPLOYEE** | owner, admin, pm, finance, employee | Dashboard*, Task |

*\*Dashboard hanya muncul untuk role asli (bukan owner)*

### 7.3 Halaman Views

#### Admin Pages
| View | Deskripsi |
|------|-----------|
| `pages.admin.dashboard` | Dashboard admin (static) |
| `pages.admin.orders.index` | Daftar order + filter (status, tanggal, search) |
| `pages.admin.orders.create` | Form buat order (multi-design, nested items) |
| `pages.admin.orders.edit` | Form edit order |
| `pages.admin.orders.show` | Detail order (invoice, payment, stages, work orders, location) |
| `pages.admin.orders.invoice-pdf` | Template PDF invoice (DomPDF) |
| `pages.admin.customers` | Daftar customer + CRUD modal |
| `pages.admin.customers.show` | Detail customer + riwayat order |
| `pages.admin.payment-history` | Riwayat pembayaran + filter |
| `pages.admin.shipping-orders.index` | Daftar shipping (pickup/delivery) |
| `pages.admin.work-orders.index` | Daftar work order per order |
| `pages.admin.work-orders.manage` | Form work order (6 section per design variant) |
| `pages.admin.work-orders.partials.pdf-template` | Template PDF work order |
| `pages.admin.work-orders.partials.form-body` | Partial form body WO |
| `pages.admin.work-orders.partials.show-modal` | Modal preview WO |

#### Owner Pages
| View | Deskripsi |
|------|-----------|
| `pages.owner.dashboard` | Dashboard owner (stats + 4 chart) |
| `pages.owner.manage-data.products` | Master data produk (6 tabel) |
| `pages.owner.manage-data.work-orders` | Master data WO (11 tabel) |
| `pages.owner.manage-data.finance` | Master data finance (supplier, partner, operational list) |
| `pages.owner.manage-data.users` | Manajemen user accounts |
| `pages.owner.manage-data.user-profile` | Profil & gaji karyawan |
| `pages.owner.manage-data.sales` | Data sales |

#### Finance Pages
| View | Deskripsi |
|------|-----------|
| `pages.finance.dashboard` | Dashboard finance (static) |
| `pages.finance.report.order-list.index` | Laporan order per periode (4 tab product type) |
| `pages.finance.report.material` | Laporan pembelian bahan |
| `pages.finance.report.support-partner` | Laporan support partner |
| `pages.finance.report.operational` | Laporan operasional (4 kategori) |
| `pages.finance.report.salary` | Laporan gaji |
| `pages.finance.internal-transfer.index` | Transfer internal |
| `pages.finance.loan-capital.index` | Pinjaman modal |
| `pages.finance.loan-capital.repayment-history` | Riwayat cicilan |

#### PM Pages
| View | Deskripsi |
|------|-----------|
| `pages.pm.dashboard` | Dashboard PM (static) |
| `pages.pm.manage-task` | Task manager (stage dates, status) |

#### Employee Pages
| View | Deskripsi |
|------|-----------|
| `pages.employee.dashboard` | Dashboard employee (static) |
| `pages.employee.task` | Daftar task hari ini per stage |
| `pages.employee.view-work-order` | Detail work order (read-only) |

#### Shared Pages
| View | Deskripsi |
|------|-----------|
| `auth` | Halaman login |
| `pages.highlights` | Highlight WIP/Finished orders |
| `pages.calendar` | Kalender produksi (3 mode) |
| `pages.profile` | Profil user |

### 7.4 Blade Components

| Component | Deskripsi |
|-----------|-----------|
| `<x-toast-notif />` | Toast notification (success, error, warning) |
| `<x-sidebar-menu.main-menu>` | Menu item sidebar (active state detection) |
| `<x-sidebar-menu.sub-menu>` | Sub-menu sidebar |
| `<x-custom-pagination>` | Custom pagination component |
| `<x-nav-locate>` | Breadcrumb-like navigation |
| `<x-select-form>` | Styled select dropdown |
| `<x-select-search>` | Searchable select dropdown |
| `<x-icons.*>` | 16 SVG icon components |
| `<x-charts.*>` | 5 chart components (ApexCharts) |

### 7.5 Styling (Tailwind CSS v4)

**Color Palette:**
```css
--color-primary: #56ba9f        /* Hijau teal — brand utama */
--color-primary-light: #c6ecdb
--color-primary-dark: #49a08a

--color-gray-solid: #cecece
--color-gray-light: #f0f0f0     /* Background utama */
--color-gray-dark: #8c8c8c

--color-font-base: #374151      /* Text utama */
--color-font-muted: #9ca3af     /* Text muted */

--color-alert-success: #059669  /* Hijau */
--color-alert-warning: #d97706  /* Kuning/Oranye */
--color-alert-danger: #dc2626   /* Merah */
```

**Font:** Inter (sans-serif)

**Responsive Breakpoints:**
- `< xl (1280px)`: Sidebar overlay + dark backdrop
- `≥ xl`: Sidebar push content (collapsible)
- User preference disimpan di `localStorage`

---

## 8. FRONTEND STACK

### 8.1 JavaScript Libraries

| Library | Versi | Fungsi |
|---------|-------|--------|
| **Turbo** (@hotwired/turbo) | ^8.0 | SPA-like navigation tanpa full reload |
| **Alpine.js** | ^3.15 | Reactive UI (sidebar toggle, modals, dropdowns) |
| **ApexCharts** | ^5.3 | Dashboard charts (line, bar, donut, treemap) |
| **NProgress** | ^0.2 | Loading progress bar di top |
| **Axios** | ^1.11 | HTTP client (AJAX requests) |

### 8.2 Build Tool

- **Vite 7** dengan `laravel-vite-plugin` dan `@tailwindcss/vite`
- Entry points: `resources/css/app.css` + `resources/js/app.js`
- Dev server: `berkah-production.test:5173` (TLS via Herd)

### 8.3 Chart Components

| Chart | Tipe | Data Source |
|-------|------|------------|
| Order Trend | Line | `owner.dashboard.chart.order-trend` |
| Product Sales | Bar | `owner.dashboard.chart.product-sales` |
| Customer Trend | Line | `owner.dashboard.chart.customer-trend` |
| Customer Province | Treemap | `owner.dashboard.chart.customer-province` |
| Order by Sales | Table | Inline data dari DashboardController |

---

## 9. KONFIGURASI & DEPENDENCIES

### 9.1 composer.json (PHP Dependencies)

| Package | Fungsi |
|---------|--------|
| `laravel/framework ^12.0` | Core framework |
| `barryvdh/laravel-dompdf ^3.1` | PDF generation (invoice, work order) |
| `blade-ui-kit/blade-heroicons ^2.6` | Heroicons SVG components |
| `laravel/tinker ^2.10` | REPL debugging |

**Dev:**
| Package | Fungsi |
|---------|--------|
| `pestphp/pest ^4.0` | Testing framework |
| `laravel/pail ^1.2` | Log viewer |
| `laravel/pint ^1.24` | Code formatter |
| `laravel/sail ^1.41` | Docker development |

### 9.2 package.json (Node Dependencies)

| Package | Fungsi |
|---------|--------|
| `tailwindcss ^4.1` | CSS framework |
| `vite ^7.0` | Build tool |
| `@hotwired/turbo ^8.0` | SPA-like navigation |
| `alpinejs ^3.15` | Reactive UI |
| `apexcharts ^5.3` | Charts |
| `nprogress ^0.2` | Progress bar |
| `axios ^1.11` | HTTP client |

### 9.3 Key Configurations

| Config File | Setting Penting |
|-------------|----------------|
| `bootstrap/app.php` | Middleware alias `role` → `RoleMiddleware` |
| `config/filesystems.php` | Local disk untuk private image storage |
| `vite.config.js` | TLS detection `berkah-production.test`, HMR config |
| `resources/css/app.css` | Tailwind v4 `@theme` custom colors |
| `.env` | Database, mail, app URL, session config |

---

## 10. SEEDERS

### DatabaseSeeder.php — Urutan Seeding

| # | Seeder | Deskripsi |
|---|--------|-----------|
| 1 | `UserSeeder` | User default (owner, admin, finance, pm, employee) |
| 2 | `SalarySystemSeeder` | 3 tipe gaji (monthly_1x, monthly_2x, project_3x) |
| 3 | `SaleSeeder` | Data sales awal |
| 4 | `ProductSeeder` | Kategori produk, bahan, tekstur, lengan, ukuran, jasa |
| 5 | `ProductionStageSeeder` | Tahap produksi (PO/Material, Cutting, Printing, Sewing, dll) |
| 6 | `MaterialSupplierSeeder` | Data supplier bahan |
| 7 | `SupportPartnerSeeder` | Data support partner |
| 8 | `OperationalListSeeder` | Master daftar operasional |
| 9 | `WorkOrderDataSeeder` | Master data WO (cutting pattern, chain cloth, dll) |
| 10 | `CustomerSeeder` | Data customer dummy |
| 11 | `WorkOrderDataSeeder` | *(duplicate call)* |

---

## 11. ER DIAGRAM (TEXT)

```
┌─────────────────────────────────────────────────────────────────┐
│                        AUTHENTICATION                            │
│  ┌──────┐ 1───1 ┌─────────────┐                                │
│  │ User │───────│ UserProfile  │                                │
│  └──┬───┘       └─────────────┘                                │
│     │ 1───1 ┌────────────────┐ N───1 ┌──────────────┐         │
│     └───────│EmployeeSalary  │───────│ SalarySystem  │         │
│             └────────────────┘       └──────────────┘         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                         ORDER SYSTEM                             │
│                                                                  │
│ ┌──────────┐ 1───N ┌───────┐ N───1 ┌─────────────────┐        │
│ │ Customer │───────│ Order │───────│ ProductCategory   │        │
│ └──────────┘       │       │───────│ MaterialCategory  │        │
│ ┌──────┐ 1───N    │       │───────│ MaterialTexture   │        │
│ │ Sale │──────────│       │       └─────────────────┘        │
│ └──────┘          └───┬───┘                                    │
│                       │                                          │
│      ┌────────────────┼─────────────────────────┐               │
│      │ 1-N            │ 1-N            │ 1-1    │ 1-N           │
│      ▼                ▼                ▼        ▼               │
│ ┌──────────┐   ┌───────────┐   ┌─────────┐ ┌──────────┐       │
│ │DesignVar │   │ExtraService│   │ Invoice │ │OrderStage│       │
│ └────┬─────┘   └───────────┘   └────┬────┘ └──────────┘       │
│      │ 1-N              1-N         │                           │
│      ▼                  ▼            │                           │
│ ┌───────────┐   ┌──────────┐        │                           │
│ │ OrderItem │   │ Payment  │◄───────┘                           │
│ └───────────┘   └──────────┘                                    │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       WORK ORDER SYSTEM                          │
│                                                                  │
│ Order 1───N ┌───────────┐                                       │
│ DesignVar───│ WorkOrder │                                       │
│             └─────┬─────┘                                       │
│    ┌──────────────┼──────────────────────────────┐              │
│    │ 1-1   │ 1-1      │ 1-1       │ 1-1   │ 1-1               │
│    ▼       ▼          ▼           ▼       ▼                     │
│ ┌──────┐┌────────┐┌───────────┐┌───────┐┌────────┐            │
│ │Cutting││Printing││PrintPlace ││Sewing ││Packing │            │
│ └──┬───┘└───┬────┘└───────────┘└───┬───┘└───┬────┘            │
│    │        │                       │        │                   │
│ FK: CuttingPattern  FK: PrintInk    FK: NeckOverdeck  FK: PlasticPacking│
│     ChainCloth          Finishing       UnderarmOD        Sticker       │
│     RibSize                             SideSplit                       │
│                                         SewingLabel                     │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       FINANCE SYSTEM                             │
│                                                                  │
│ ┌─────────┐ 1───N ┌─────────────┐ 1───N ┌───────────────┐     │
│ │ Balance │───────│ LoanCapital │───────│ LoanRepayment │     │
│ └────┬────┘       └─────────────┘       └───────────────┘     │
│      │ 1-N                                                      │
│      ├──→ InternalTransfer                                      │
│      ├──→ OperationalReport                                     │
│      ├──→ SalaryReport                                          │
│      ├──→ OrderMaterialReport                                   │
│      └──→ OrderPartnerReport                                    │
│                                                                  │
│ ┌──────────────┐ 1───N ┌───────────────────┐                   │
│ │ OrderReport  │───────│OrderMaterialReport │                   │
│ │              │───────│OrderPartnerReport  │                   │
│ └──────────────┘       └───────────────────┘                   │
│                                                                  │
│ ┌──────────────┐                                                │
│ │ ReportPeriod │ — Lock/Unlock periode laporan                  │
│ └──────────────┘                                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## RINGKASAN STATISTIK

| Metrik | Jumlah |
|--------|--------|
| Total Controller Files | 42 |
| Total Model Files | 54 |
| Total Migration Files | 53 |
| Total Database Tables | ~40+ |
| Total Blade Views | ~45+ |
| Total Blade Components | ~28 |
| Total Route Definitions | ~120+ |
| Total Lines of Controller Code | ~10,500+ |
| User Roles | 5 (owner, admin, finance, pm, employee) |
| Seeder Classes | 11 |
| JS Libraries | 5 (Turbo, Alpine, ApexCharts, NProgress, Axios) |
| CSS Framework | Tailwind CSS v4 |
| PDF Generator | DomPDF |
| Image Handling | GD Compression (>200KB → JPEG 80%) |

---

*Dokumentasi ini di-generate pada 4 Maret 2026 berdasarkan analisis lengkap seluruh source code project.*
