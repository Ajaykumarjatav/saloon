<?php

use App\Support\SignupDevice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'signup_device')) {
                $table->string('signup_device', 32)->nullable()->after('last_login_at');
            }
            if (! Schema::hasColumn('users', 'signup_user_agent')) {
                $table->string('signup_user_agent', 500)->nullable()->after('signup_device');
            }
        });

        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $firstAgents = DB::table('audit_logs as a')
            ->join(DB::raw('(
                SELECT user_id, MIN(occurred_at) AS first_at
                FROM audit_logs
                WHERE user_id IS NOT NULL
                  AND user_agent IS NOT NULL
                  AND user_agent != \'\'
                GROUP BY user_id
            ) as first_log'), function ($join) {
                $join->on('a.user_id', '=', 'first_log.user_id')
                    ->on('a.occurred_at', '=', 'first_log.first_at');
            })
            ->whereNotNull('a.user_agent')
            ->where('a.user_agent', '!=', '')
            ->select('a.user_id', 'a.user_agent')
            ->get()
            ->unique('user_id');

        foreach ($firstAgents as $row) {
            $ua = substr((string) $row->user_agent, 0, 500);
            DB::table('users')
                ->where('id', $row->user_id)
                ->whereNull('signup_device')
                ->update([
                    'signup_device' => SignupDevice::labelFromUserAgent($ua),
                    'signup_user_agent' => $ua,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'signup_user_agent')) {
                $table->dropColumn('signup_user_agent');
            }
            if (Schema::hasColumn('users', 'signup_device')) {
                $table->dropColumn('signup_device');
            }
        });
    }
};
