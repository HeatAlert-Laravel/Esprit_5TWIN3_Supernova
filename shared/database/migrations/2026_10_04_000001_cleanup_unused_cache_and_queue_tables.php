<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// HeatAlert uses file cache and the sync queue; only database sessions are kept.
return new class extends Migration {
    public function up(): void
    {
        foreach (['cache_locks', 'cache', 'failed_jobs', 'job_batches', 'jobs'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    // Intentionally empty: the tables are unused infrastructure. Their original definitions stay in
    // the 0001_01_01_000001/000002 migrations, so rolling back this cleanup is not needed.
    public function down(): void
    {
    }
};
