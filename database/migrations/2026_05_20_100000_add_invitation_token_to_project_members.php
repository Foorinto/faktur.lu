<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Idempotent: a previous failed attempt may already have added the columns.
        if (!Schema::hasColumn('project_members', 'invitation_token')) {
            Schema::table('project_members', function (Blueprint $table) {
                $table->string('invitation_token', 64)->nullable()->unique()->after('accepted_at');
                $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');
            });
        }

        // Backfill: any pending collaborator invitation (member_type=collaborator, accepted_at IS NULL)
        // gets a fresh token + 14-day expiry so existing pending invites become clickable.
        $pending = DB::table('project_members')
            ->where('member_type', 'collaborator')
            ->whereNull('accepted_at')
            ->whereNull('invitation_token')
            ->get(['project_id', 'user_id']);

        foreach ($pending as $r) {
            DB::table('project_members')
                ->where('project_id', $r->project_id)
                ->where('user_id', $r->user_id)
                ->update([
                    'invitation_token' => Str::random(48),
                    'invitation_expires_at' => now()->addDays(14),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropUnique(['invitation_token']);
            $table->dropColumn(['invitation_token', 'invitation_expires_at']);
        });
    }
};
