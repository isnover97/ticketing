<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$db = new PDO('sqlite:'.__DIR__.'/database.sqlite');
foreach (['users', 'categories', 'tickets', 'comments'] as $table) {
    try { echo $table.': '.$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn().PHP_EOL; }
    catch (Throwable $e) { echo $table.': unavailable'.PHP_EOL; }
}
