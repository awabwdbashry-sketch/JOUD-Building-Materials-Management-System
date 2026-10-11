<?php
/** تشغيل الترقية من سطر الأوامر:  php database/migrate.php */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('JOUD_NO_MIGRATE', true);
require __DIR__ . '/../includes/bootstrap.php';
foreach (run_migrations() as $l) echo "• $l\n";
