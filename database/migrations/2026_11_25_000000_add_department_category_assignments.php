<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        Schema::create('category_department', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'department_id']);
        });

        $categories = DB::table('categories')->pluck('id')->flip();
        $departments = DB::table('departments')->pluck('id')->flip();
        foreach (DB::table('helpdesk_records')->where('kind', 'topics')->where('is_active', true)->cursor() as $topic) {
            $settings = json_decode($topic->settings ?? '{}', true);
            $category = (int) ($settings['category_id'] ?? 0);
            $department = (int) ($settings['department_id'] ?? 0);
            if ($categories->has($category) && $departments->has($department)) {
                DB::table('category_department')->insertOrIgnore(['category_id' => $category, 'department_id' => $department]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_department');
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
