<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void { Schema::create('ticket_number_sequences',function(Blueprint $table){$table->unsignedInteger('id')->primary();$table->unsignedBigInteger('next_number')->default(1);}); DB::table('ticket_number_sequences')->insert(['id'=>1,'next_number'=>1]); }
 public function down(): void { Schema::dropIfExists('ticket_number_sequences'); }
};
