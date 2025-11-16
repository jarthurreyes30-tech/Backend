<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the status enum to include 'paused' (MySQL/MariaDB only)
        $driver = \DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            \DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'published', 'paused', 'closed', 'archived') DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values (MySQL/MariaDB only)
        $driver = \DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            \DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'published', 'closed', 'archived') DEFAULT 'draft'");
        }
    }
};
