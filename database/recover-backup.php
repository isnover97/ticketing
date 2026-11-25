<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$source = new PDO('sqlite:'.__DIR__.'/database.sqlite');
Illuminate\Support\Facades\DB::transaction(function () use ($source) {
    foreach (['users', 'categories', 'tickets', 'comments'] as $table) {
        if (Illuminate\Support\Facades\DB::table($table)->exists()) {
            throw new RuntimeException('Recovery stopped: destination is not empty: '.$table);
        }
        $rows = $source->query('SELECT * FROM '.$table)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) Illuminate\Support\Facades\DB::table($table)->insert($row);
        echo $table.': restored '.count($rows).PHP_EOL;
    }
});
