<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return; // Only applicable to MySQL/MariaDB
        }

        // Drop existing FK, make owner_id nullable, clean orphans, and re-add FK with ON DELETE SET NULL
        DB::statement('ALTER TABLE charities DROP FOREIGN KEY charities_owner_id_foreign');
        DB::statement('ALTER TABLE charities MODIFY owner_id BIGINT UNSIGNED NULL');
        DB::statement('UPDATE charities c LEFT JOIN users u ON u.id = c.owner_id SET c.owner_id = NULL WHERE c.owner_id IS NOT NULL AND u.id IS NULL');
        DB::statement('ALTER TABLE charities ADD CONSTRAINT charities_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return;
        }

        // Revert FK to RESTRICT; keep column nullable if NULLs exist to avoid failure
        DB::statement('ALTER TABLE charities DROP FOREIGN KEY charities_owner_id_foreign');
        // If there are no NULLs, you may choose to make it NOT NULL again (skipped for safety)
        DB::statement('ALTER TABLE charities ADD CONSTRAINT charities_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT');
    }
};
