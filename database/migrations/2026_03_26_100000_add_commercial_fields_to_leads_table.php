<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('operational_priority', 20)->default('low_priority')->after('commercial_status');
            $table->string('primary_problem', 500)->nullable()->after('operational_priority');
            $table->string('sales_angle', 500)->nullable()->after('primary_problem');
            $table->string('recommended_channel', 20)->default('none')->after('sales_angle');
            $table->string('quick_tip', 500)->nullable()->after('recommended_channel');
            $table->text('commercial_notes')->nullable()->after('quick_tip');
            $table->timestamp('last_contacted_at')->nullable()->after('commercial_notes');
            $table->timestamp('next_follow_up_at')->nullable()->after('last_contacted_at');

            $table->index('operational_priority');
            $table->index('recommended_channel');
            $table->index('last_contacted_at');
            $table->index('next_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['operational_priority']);
            $table->dropIndex(['recommended_channel']);
            $table->dropIndex(['last_contacted_at']);
            $table->dropIndex(['next_follow_up_at']);

            $table->dropColumn([
                'operational_priority',
                'primary_problem',
                'sales_angle',
                'recommended_channel',
                'quick_tip',
                'commercial_notes',
                'last_contacted_at',
                'next_follow_up_at',
            ]);
        });
    }
};
