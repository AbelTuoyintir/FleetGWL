<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'deleted' to every status ENUM that the Auditable trait's softDelete might touch.
     * regions/districts/offices/departments were already fixed in a previous migration,
     * so we skip them here.
     */
    public function up(): void
    {
        $alterations = [
            'vehicle_maintenances' => "ENUM('scheduled','in_progress','completed','cancelled','deleted') DEFAULT 'scheduled'",
            'fuel_logs'            => "ENUM('recorded','verified','rejected','deleted') DEFAULT 'recorded'",
            'documents'            => "ENUM('active','expired','archived','draft','deleted') DEFAULT 'active'",
            'vehicle_submissions'  => "ENUM('pending','processing','completed','failed','deleted') DEFAULT 'pending'",
            'repairs'              => "ENUM('dispatched','in_progress','completed','cancelled','deleted') DEFAULT 'dispatched'",
            'assets'               => "ENUM('active','inactive','maintenance','disposed','deleted') DEFAULT 'active'",
        ];

        foreach ($alterations as $table => $definition) {
            if (\Schema::hasTable($table)) {
                // SQLite doesn't support MODIFY/ENUM alterations. 
                // Since base migrations now include 'deleted', we can skip this on SQLite.
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `status` {$definition}");
                }
            }
        }
    }

    /**
     * Reverse: remove 'deleted' from the ENUMs (move deleted rows to a safe value first).
     */
    public function down(): void
    {
        $originals = [
            'vehicle_maintenances' => ["ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled'", 'cancelled'],
            'fuel_logs'            => ["ENUM('recorded','verified','rejected') DEFAULT 'recorded'", 'rejected'],
            'documents'            => ["ENUM('active','expired','archived','draft') DEFAULT 'active'", 'archived'],
            'vehicle_submissions'  => ["ENUM('pending','processing','completed','failed') DEFAULT 'pending'", 'failed'],
            'repairs'              => ["ENUM('dispatched','in_progress','completed','cancelled') DEFAULT 'dispatched'", 'cancelled'],
            'assets'               => ["ENUM('active','inactive','maintenance','disposed') DEFAULT 'active'", 'disposed'],
        ];

        foreach ($originals as $table => [$definition, $fallback]) {
            if (\Schema::hasTable($table)) {
                DB::table($table)->where('status', 'deleted')->update(['status' => $fallback]);
                DB::statement("ALTER TABLE `{$table}` MODIFY `status` {$definition}");
            }
        }
    }
};
