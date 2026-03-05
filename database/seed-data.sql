-- ============================================================
-- SEED DATA untuk tabel baru setelah migration
-- Jalankan di VPS setelah: php artisan migrate --force
-- Command: mysql -u root -p berkah_production < database/seed-data.sql
-- ============================================================

-- ============================================================
-- 1. SALARY SYSTEMS
-- ============================================================
INSERT INTO `salary_systems` (`type_name`, `created_at`, `updated_at`) VALUES
('monthly_1x', NOW(), NOW()),
('monthly_2x', NOW(), NOW()),
('project_3x', NOW(), NOW());

-- ============================================================
-- 2. OPERATIONAL LISTS
-- ============================================================

-- Fix Cost 1
INSERT INTO `operational_lists` (`category`, `list_name`, `sort_order`, `created_at`, `updated_at`) VALUES
('fix_cost_1', 'Idul Fitri', 1, NOW(), NOW()),
('fix_cost_1', 'Entertaiment', 2, NOW(), NOW()),
('fix_cost_1', 'Label', 3, NOW(), NOW()),
('fix_cost_1', 'Pajak', 4, NOW(), NOW()),
('fix_cost_1', 'Sewa Alat', 5, NOW(), NOW());

-- Fix Cost 2
INSERT INTO `operational_lists` (`category`, `list_name`, `sort_order`, `created_at`, `updated_at`) VALUES
('fix_cost_2', 'Biznet', 1, NOW(), NOW()),
('fix_cost_2', 'Sampah', 2, NOW(), NOW()),
('fix_cost_2', 'MBG Siang', 3, NOW(), NOW()),
('fix_cost_2', 'MBG Sore', 4, NOW(), NOW()),
('fix_cost_2', 'MBG Lembur', 5, NOW(), NOW()),
('fix_cost_2', 'Listrik', 6, NOW(), NOW()),
('fix_cost_2', 'Bonus', 7, NOW(), NOW()),
('fix_cost_2', 'Lembur Jahit STGR', 8, NOW(), NOW());

-- Printing Supply
INSERT INTO `operational_lists` (`category`, `list_name`, `sort_order`, `created_at`, `updated_at`) VALUES
('printing_supply', 'Base Plasma', 1, NOW(), NOW()),
('printing_supply', 'Tinta Ecoplast', 2, NOW(), NOW()),
('printing_supply', 'Kertas HVS', 3, NOW(), NOW()),
('printing_supply', 'Tinta Alfasol', 4, NOW(), NOW()),
('printing_supply', 'Gas', 5, NOW(), NOW()),
('printing_supply', 'Screen Opener', 6, NOW(), NOW()),
('printing_supply', 'Activator', 7, NOW(), NOW()),
('printing_supply', 'Ordoles', 8, NOW(), NOW()),
('printing_supply', 'Eksklusif', 9, NOW(), NOW()),
('printing_supply', 'Solasi', 10, NOW(), NOW()),
('printing_supply', 'Kertas Roti', 11, NOW(), NOW()),
('printing_supply', 'Tinta Printer', 12, NOW(), NOW()),
('printing_supply', 'Baiclin', 13, NOW(), NOW()),
('printing_supply', 'Ulano', 14, NOW(), NOW()),
('printing_supply', 'Obat Film', 15, NOW(), NOW());
