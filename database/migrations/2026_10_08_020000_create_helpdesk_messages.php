<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('helpdesk_messages',function(Blueprint $t) {
            $t->id(); $t->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $t->string('message_key')->unique(); $t->string('direction',10)->default('out');
            $t->string('recipient'); $t->string('subject'); $t->text('body');
            $t->string('status',20)->default('pending')->index(); $t->unsignedInteger('attempts')->default(0);
            $t->text('error')->nullable(); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('helpdesk_messages'); }
};
