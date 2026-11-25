<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('helpdesk_api_keys',function(Blueprint $t) {
        $t->id(); $t->string('name'); $t->string('key_hash',64)->unique(); $t->foreignId('user_id')->constrained();
        $t->json('scopes'); $t->string('allowed_ip',45)->nullable(); $t->timestamp('expires_at')->nullable();
        $t->timestamp('last_used_at')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
    }); }
    public function down(): void { Schema::dropIfExists('helpdesk_api_keys'); }
};
