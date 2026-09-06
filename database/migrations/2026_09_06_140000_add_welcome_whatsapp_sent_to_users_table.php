<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'welcome_whatsapp_sent_at')) {
                $table->timestamp('welcome_whatsapp_sent_at')->nullable()->after('signup_user_agent');
            }
            if (! Schema::hasColumn('users', 'welcome_whatsapp_sent_by')) {
                $table->unsignedBigInteger('welcome_whatsapp_sent_by')->nullable()->after('welcome_whatsapp_sent_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'welcome_whatsapp_sent_by')) {
                $table->dropColumn('welcome_whatsapp_sent_by');
            }
            if (Schema::hasColumn('users', 'welcome_whatsapp_sent_at')) {
                $table->dropColumn('welcome_whatsapp_sent_at');
            }
        });
    }
};
