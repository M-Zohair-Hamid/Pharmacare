<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('refunds_enabled')->default(false)->after('low_stock_threshold');
            $table->integer('refund_window_days')->nullable()->after('refunds_enabled');
            $table->string('receipt_paper_width', 10)->default('80')->after('refund_window_days');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['refunds_enabled', 'refund_window_days', 'receipt_paper_width']);
        });
    }
};
