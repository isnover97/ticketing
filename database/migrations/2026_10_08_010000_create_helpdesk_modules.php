<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('helpdesk_records', function (Blueprint $t) {
            $t->id(); $t->string('kind', 40)->index(); $t->string('name');
            $t->text('description')->nullable(); $t->json('settings')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::table('tickets', function (Blueprint $t) {
            foreach (['department_id', 'team_id', 'sla_id', 'help_topic_id'] as $column) {
                $t->foreignId($column)->nullable()->constrained('helpdesk_records')->nullOnDelete();
            }
            $t->timestamp('due_at')->nullable()->index();
            $t->json('custom_fields')->nullable();
            $t->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('locked_until')->nullable();
        });
        Schema::table('comments', fn (Blueprint $t) => $t->boolean('is_internal')->default(false));
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('organization_id')->nullable()->constrained('helpdesk_records')->nullOnDelete();
        });
        Schema::create('helpdesk_tasks', function (Blueprint $t) {
            $t->id(); $t->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title'); $t->text('description')->nullable();
            $t->string('status', 20)->default('open')->index(); $t->timestamp('due_at')->nullable(); $t->timestamps();
        });
        Schema::create('ticket_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action'); $t->text('description'); $t->timestamps();
        });
        Schema::create('ticket_attachments', function (Blueprint $t) {
            $t->id(); $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained(); $t->string('name'); $t->string('path');
            $t->string('mime'); $t->unsignedBigInteger('size'); $t->boolean('is_internal')->default(false); $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments'); Schema::dropIfExists('ticket_events'); Schema::dropIfExists('helpdesk_tasks');
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('organization_id'));
        Schema::table('comments', fn (Blueprint $t) => $t->dropColumn('is_internal'));
        Schema::table('tickets', function (Blueprint $t) {
            foreach (['department_id','team_id','sla_id','help_topic_id','locked_by'] as $column) $t->dropConstrainedForeignId($column);
            $t->dropColumn(['due_at','custom_fields','locked_until']);
        });
        Schema::dropIfExists('helpdesk_records');
    }
};
