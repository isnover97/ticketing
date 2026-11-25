<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('users',function(Blueprint $t) {
        $t->string('ticket_scope',20)->default('all'); $t->json('department_ids')->nullable();
        $t->foreignId('access_profile_id')->nullable()->constrained('helpdesk_records')->nullOnDelete();
    }); }
    public function down(): void { Schema::table('users',function(Blueprint $t) {
        $t->dropConstrainedForeignId('access_profile_id'); $t->dropColumn(['ticket_scope','department_ids']);
    }); }
};
