<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $legacy = DB::table('helpdesk_records')->where('kind', 'departments');
        DB::table('departments')->insertUsing(
            ['id', 'name', 'description', 'settings', 'is_active', 'created_at', 'updated_at'],
            (clone $legacy)->whereNotIn('id', DB::table('departments')->select('id'))
                ->select('id', 'name', 'description', 'settings', 'is_active', 'created_at', 'updated_at'),
        );

        if (DB::table('tickets')->whereNotNull('department_id')
            ->whereNotIn('department_id', DB::table('departments')->select('id'))->exists()) {
            throw new RuntimeException('Some tickets reference IDs that are not departments. Correct these references before migrating.');
        }

        $foreignKey = collect(Schema::getForeignKeys('tickets'))->firstWhere('columns', ['department_id']);
        if ($foreignKey && $foreignKey['foreign_table'] === 'helpdesk_records') {
            Schema::table('tickets', fn (Blueprint $table) => $table->dropForeign(['department_id']));
        }
        if (($foreignKey['foreign_table'] ?? null) !== 'departments') {
            Schema::table('tickets', fn (Blueprint $table) => $table->foreign('department_id')
                ->references('id')->on('departments')->nullOnDelete());
        }

        $legacy->delete();
    }

    public function down(): void
    {
        throw new RuntimeException('Restore a pre-migration backup to reverse the department move.');
    }
};
