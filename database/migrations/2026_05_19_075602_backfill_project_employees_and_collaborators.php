<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FEAT-081 — Backfill data migration:
 * 1. Auto-attach all active employees to all existing (non-archived) projects
 * 2. Mark existing project_members rows with member_type='collaborator' (default already)
 *    and copy invited_at = created_at for clarity
 *
 * Idempotent: re-run safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. For each org, attach each active employee to each of their non-archived projects
        $orgUserIds = DB::table('projects')
            ->where('is_archived', false)
            ->distinct()
            ->pluck('user_id');

        $now = now();
        $inserts = [];

        foreach ($orgUserIds as $userId) {
            $projectIds = DB::table('projects')
                ->where('user_id', $userId)
                ->where('is_archived', false)
                ->pluck('id');

            $employeeIds = DB::table('employees')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->pluck('id');

            foreach ($projectIds as $projectId) {
                foreach ($employeeIds as $employeeId) {
                    // Skip if already exists (idempotent)
                    $exists = DB::table('project_employees')
                        ->where('project_id', $projectId)
                        ->where('employee_id', $employeeId)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $inserts[] = [
                        'project_id' => $projectId,
                        'employee_id' => $employeeId,
                        'active' => true,
                        'added_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Batch insert every 500 to avoid memory issues
                    if (count($inserts) >= 500) {
                        DB::table('project_employees')->insert($inserts);
                        $inserts = [];
                    }
                }
            }
        }

        if (! empty($inserts)) {
            DB::table('project_employees')->insert($inserts);
        }

        // 2. Backfill invited_at on existing project_members rows
        DB::table('project_members')
            ->whereNull('invited_at')
            ->update([
                'member_type' => 'collaborator',
                'invited_at' => DB::raw('created_at'),
                'accepted_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        // No down: data backfill, not recreatable
    }
};
