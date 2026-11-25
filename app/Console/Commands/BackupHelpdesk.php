<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BackupHelpdesk extends Command
{
    protected $signature = 'helpdesk:backup';

    protected $description = 'Save a private JSON snapshot of database rows before maintenance';

    public function handle(): int
    {
        $snapshot = ['created_at' => now()->toIso8601String(), 'connection' => config('database.default'), 'tables' => []];
        DB::transaction(function () use (&$snapshot) {
            $schema = DB::connection()->getDriverName() === 'sqlite' ? 'main' : DB::connection()->getDatabaseName();
            foreach (Schema::getTables($schema) as $table) {
                $name = $table['name'];
                $snapshot['tables'][$name] = DB::table($name)->get()->map(fn ($row) => (array) $row)->all();
            }
        });
        $path = 'backups/helpdesk-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
        if (! Storage::disk('local')->put($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            $this->error('Backup write failed.');

            return self::FAILURE;
        }
        $this->info('Private data snapshot saved: storage/app/private/'.$path);

        return self::SUCCESS;
    }
}
