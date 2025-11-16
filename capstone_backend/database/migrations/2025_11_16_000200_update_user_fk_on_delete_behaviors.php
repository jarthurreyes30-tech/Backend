<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return;
        }
        $pairs = [
            ['table' => 'charity_documents', 'column' => 'uploaded_by', 'nullable' => true, 'onDelete' => 'SET NULL'],
            ['table' => 'charity_documents', 'column' => 'verified_by', 'nullable' => true, 'onDelete' => 'SET NULL'],
            ['table' => 'donations', 'column' => 'donor_id', 'nullable' => true, 'onDelete' => 'SET NULL'],
            ['table' => 'support_tickets', 'column' => 'assigned_to', 'nullable' => true, 'onDelete' => 'SET NULL'],
            ['table' => 'account_retrieval_requests', 'column' => 'reviewed_by', 'nullable' => true, 'onDelete' => 'SET NULL'],
            ['table' => 'refund_requests', 'column' => 'reviewed_by', 'nullable' => true, 'onDelete' => 'SET NULL'],
        ];
        foreach ($pairs as $p) {
            $constraint = DB::selectOne(<<<SQL
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = '{$p['table']}'
                  AND COLUMN_NAME = '{$p['column']}'
                  AND REFERENCED_TABLE_NAME = 'users'
            SQL);
            if ($constraint && isset($constraint->CONSTRAINT_NAME)) {
                $fk = $constraint->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE {$p['table']} DROP FOREIGN KEY `{$fk}`");
            }
            if ($p['nullable']) {
                DB::statement("ALTER TABLE {$p['table']} MODIFY {$p['column']} BIGINT UNSIGNED NULL");
            }
            DB::statement("ALTER TABLE {$p['table']} ADD CONSTRAINT `{$p['table']}_{$p['column']}_foreign` FOREIGN KEY (`{$p['column']}`) REFERENCES `users`(`id`) ON DELETE {$p['onDelete']}");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return;
        }
        // No-op safe down; leave improved FKs in place
    }
};
