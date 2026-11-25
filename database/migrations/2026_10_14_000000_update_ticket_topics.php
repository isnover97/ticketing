<?php

use App\Support\TicketTopics;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(fn () => TicketTopics::install(legacyDepartments: true));
    }

    public function down(): void
    {
        // Preserve catalog records referenced by existing tickets.
    }
};
