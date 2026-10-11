-- JOUD v3 — مخطط التثبيت (استورده في قاعدة جديدة عبر phpMyAdmin). للترقية من نسخة قديمة لا تستورد هذا الملف: افتح النظام وسيرقّي نفسه تلقائيًا.
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS joud_building CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE joud_building;
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `entity` varchar(60) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entity` (`entity`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `cancel_reason` varchar(255) DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_date` (`expense_date`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  KEY `cancelled_by` (`cancelled_by`),
  KEY `idx_exp_status_date` (`status`,`expense_date`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` enum('sale','purchase','adjustment','return','purchase_cancel') NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `qty_before` decimal(14,3) DEFAULT NULL,
  `qty_after` decimal(14,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_mov_ref` (`type`,`ref_id`),
  CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `inventory_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `payment_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `payment_allocations_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `code` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `type` enum('sale','customer','reversal') NOT NULL DEFAULT 'sale',
  `reversal_of` int(11) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_date` date NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `payment_date` (`payment_date`),
  KEY `user_id` (`user_id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `idx_pay_method` (`payment_method_id`,`payment_date`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `label` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `sku` varchar(60) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit_id` int(11) NOT NULL,
  `cost_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `base_price` decimal(14,2) NOT NULL,
  `min_price` decimal(14,2) NOT NULL,
  `stock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `min_stock` decimal(14,3) NOT NULL DEFAULT 0.000,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `name` (`name`),
  KEY `category_id` (`category_id`),
  KEY `unit_id` (`unit_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `cost` decimal(14,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `ref` varchar(60) DEFAULT NULL,
  `total` decimal(14,2) NOT NULL,
  `paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `pay_status` enum('paid','partial','credit') NOT NULL DEFAULT 'credit',
  `refunded` decimal(14,2) NOT NULL DEFAULT 0.00,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `cancel_reason` varchar(255) DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_purch_supplier` (`supplier_id`),
  KEY `idx_purch_status_date` (`status`,`created_at`),
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `label` varchar(80) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `qty` decimal(14,3) NOT NULL,
  `base_price` decimal(14,2) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(14,2) DEFAULT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `override_by` int(11) DEFAULT NULL,
  `override_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  KEY `override_by` (`override_by`),
  KEY `idx_items_product` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_items_ibfk_3` FOREIGN KEY (`override_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) DEFAULT NULL,
  `client_token` char(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  `discount_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL,
  `paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `due` decimal(14,2) NOT NULL DEFAULT 0.00,
  `pay_status` enum('paid','partial','credit') NOT NULL DEFAULT 'paid',
  `status` enum('completed','cancelled') NOT NULL DEFAULT 'completed',
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `refunded` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_token` (`client_token`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `created_at` (`created_at`),
  KEY `customer_id` (`customer_id`,`status`),
  KEY `user_id` (`user_id`),
  KEY `cancelled_by` (`cancelled_by`),
  KEY `idx_sales_status_date` (`status`,`created_at`),
  KEY `idx_sales_user` (`user_id`,`created_at`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `settings` (
  `k` varchar(60) NOT NULL,
  `v` text DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `type` enum('initial','payment','reversal') NOT NULL DEFAULT 'payment',
  `reversal_of` int(11) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_date` date NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sp_supplier` (`supplier_id`,`payment_date`),
  KEY `idx_sp_purchase` (`purchase_id`),
  KEY `idx_sp_method` (`payment_method_id`,`payment_date`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_payments_ibfk_2` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  CONSTRAINT `supplier_payments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `supplier_payments_ibfk_4` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `roles` VALUES (1,'admin','المدير',1,'2026-10-04 22:11:19');
INSERT INTO `roles` VALUES (2,'cashier','الكاشير',1,'2026-10-04 22:11:19');
INSERT INTO `permissions` VALUES (1,'view_products','عرض المنتجات');
INSERT INTO `permissions` VALUES (2,'create_product','إضافة منتج');
INSERT INTO `permissions` VALUES (3,'edit_product','تعديل منتج');
INSERT INTO `permissions` VALUES (4,'delete_product','حذف منتج');
INSERT INTO `permissions` VALUES (5,'view_sales','عرض المبيعات');
INSERT INTO `permissions` VALUES (6,'create_sale','إنشاء بيع');
INSERT INTO `permissions` VALUES (7,'cancel_sale','إلغاء فاتورة');
INSERT INTO `permissions` VALUES (8,'override_min_price','البيع تحت الحد الأدنى');
INSERT INTO `permissions` VALUES (9,'credit_sale','البيع الآجل');
INSERT INTO `permissions` VALUES (10,'view_inventory','عرض المخزون');
INSERT INTO `permissions` VALUES (11,'manage_inventory','إدارة المخزون');
INSERT INTO `permissions` VALUES (12,'view_reports','عرض التقارير');
INSERT INTO `permissions` VALUES (13,'manage_users','إدارة المستخدمين والأدوار');
INSERT INTO `permissions` VALUES (14,'manage_settings','الإعدادات');
INSERT INTO `permissions` VALUES (15,'manage_catalog','التصنيفات والوحدات');
INSERT INTO `permissions` VALUES (16,'manage_suppliers','إدارة الموردين');
INSERT INTO `permissions` VALUES (17,'manage_customers','إدارة العملاء');
INSERT INTO `permissions` VALUES (18,'receive_customer_payment','تسجيل دفعات العملاء');
INSERT INTO `permissions` VALUES (19,'view_purchases','عرض المشتريات');
INSERT INTO `permissions` VALUES (20,'create_purchase','إنشاء شراء');
INSERT INTO `permissions` VALUES (21,'manage_expenses','إدارة المصروفات');
INSERT INTO `permissions` VALUES (22,'cancel_expense','إلغاء مصروف');
INSERT INTO `permissions` VALUES (23,'view_audit','عرض سجل العمليات');
INSERT INTO `permissions` VALUES (24,'manage_payment_methods','إدارة طرق الدفع');
INSERT INTO `permissions` VALUES (25,'view_analytics','عرض تحليلات لوحة التحكم');
INSERT INTO `permissions` VALUES (26,'view_all_sales','عرض فواتير جميع الكاشيرية');
INSERT INTO `permissions` VALUES (27,'view_sales_reports','تقارير المبيعات والدفعات');
INSERT INTO `permissions` VALUES (28,'cancel_purchase','إلغاء فاتورة شراء');
INSERT INTO `permissions` VALUES (29,'delete_records','حذف السجلات (عملاء، موردون، تصنيفات…)');
INSERT INTO `permissions` VALUES (30,'manage_roles','إدارة الأدوار والصلاحيات');
INSERT INTO `permissions` VALUES (31,'pay_supplier','تسجيل دفعات للموردين');
INSERT INTO `role_permissions` VALUES (1,1);
INSERT INTO `role_permissions` VALUES (1,2);
INSERT INTO `role_permissions` VALUES (1,3);
INSERT INTO `role_permissions` VALUES (1,4);
INSERT INTO `role_permissions` VALUES (1,5);
INSERT INTO `role_permissions` VALUES (1,6);
INSERT INTO `role_permissions` VALUES (1,7);
INSERT INTO `role_permissions` VALUES (1,8);
INSERT INTO `role_permissions` VALUES (1,9);
INSERT INTO `role_permissions` VALUES (1,10);
INSERT INTO `role_permissions` VALUES (1,11);
INSERT INTO `role_permissions` VALUES (1,12);
INSERT INTO `role_permissions` VALUES (1,13);
INSERT INTO `role_permissions` VALUES (1,14);
INSERT INTO `role_permissions` VALUES (1,15);
INSERT INTO `role_permissions` VALUES (1,16);
INSERT INTO `role_permissions` VALUES (1,17);
INSERT INTO `role_permissions` VALUES (1,18);
INSERT INTO `role_permissions` VALUES (1,19);
INSERT INTO `role_permissions` VALUES (1,20);
INSERT INTO `role_permissions` VALUES (1,21);
INSERT INTO `role_permissions` VALUES (1,22);
INSERT INTO `role_permissions` VALUES (1,23);
INSERT INTO `role_permissions` VALUES (1,24);
INSERT INTO `role_permissions` VALUES (1,25);
INSERT INTO `role_permissions` VALUES (1,26);
INSERT INTO `role_permissions` VALUES (1,27);
INSERT INTO `role_permissions` VALUES (1,28);
INSERT INTO `role_permissions` VALUES (1,29);
INSERT INTO `role_permissions` VALUES (1,30);
INSERT INTO `role_permissions` VALUES (1,31);
INSERT INTO `role_permissions` VALUES (2,1);
INSERT INTO `role_permissions` VALUES (2,5);
INSERT INTO `role_permissions` VALUES (2,6);
INSERT INTO `role_permissions` VALUES (2,9);
INSERT INTO `role_permissions` VALUES (2,17);
INSERT INTO `role_permissions` VALUES (2,18);
INSERT INTO `role_permissions` VALUES (2,27);
INSERT INTO `categories` VALUES (1,'كهربائيات',1);
INSERT INTO `categories` VALUES (2,'سباكة',1);
INSERT INTO `categories` VALUES (3,'إنارة',1);
INSERT INTO `categories` VALUES (4,'مواد بناء',1);
INSERT INTO `categories` VALUES (5,'أدوات',1);
INSERT INTO `units` VALUES (1,'قطعة',1);
INSERT INTO `units` VALUES (2,'كرتونة',1);
INSERT INTO `units` VALUES (3,'متر',1);
INSERT INTO `units` VALUES (4,'كيلو',1);
INSERT INTO `units` VALUES (5,'طن',1);
INSERT INTO `units` VALUES (6,'جوال',1);
INSERT INTO `payment_methods` VALUES (1,'كاش','cash',1,'2026-10-04 22:11:19','2026-10-04 22:11:19');
INSERT INTO `payment_methods` VALUES (2,'بنك','bank',1,'2026-10-04 22:11:19','2026-10-04 22:11:19');
INSERT INTO `expense_categories` VALUES (1,'كهرباء',1);
INSERT INTO `expense_categories` VALUES (2,'نقل',1);
INSERT INTO `expense_categories` VALUES (3,'صيانة',1);
INSERT INTO `expense_categories` VALUES (4,'رواتب',1);
INSERT INTO `expense_categories` VALUES (5,'مصروفات تشغيل',1);
INSERT INTO `expense_categories` VALUES (6,'أخرى',1);
INSERT INTO `settings` VALUES ('address','');
INSERT INTO `settings` VALUES ('company_name','جود لمواد البناء');
INSERT INTO `settings` VALUES ('currency','ج.س');
INSERT INTO `settings` VALUES ('email','');
INSERT INTO `settings` VALUES ('invoice_footer','شكرًا لتعاملكم معنا');
INSERT INTO `settings` VALUES ('invoice_prefix','INV');
INSERT INTO `settings` VALUES ('invoice_seq','0');
INSERT INTO `settings` VALUES ('logo','');
INSERT INTO `settings` VALUES ('phone','');
INSERT INTO `settings` VALUES ('tax_no','');
INSERT INTO settings(k,v) VALUES('schema_version','3') ON DUPLICATE KEY UPDATE v='3';
SET FOREIGN_KEY_CHECKS=1;
