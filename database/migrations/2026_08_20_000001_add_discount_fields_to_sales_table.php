<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Bill subtotal before discount (i.e. what total_amount used to mean).
            $table->decimal('subtotal', 12, 2)->default(0)->after('customer_name');
            // Percentage entered at checkout, e.g. 5 for 5%.
            $table->decimal('discount_percent', 5, 2)->default(0)->after('subtotal');
            // Rupee amount the discount worked out to, stored so history/receipts
            // never need to recompute it later even if percent rounding rules change.
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
        });

        // Backfill existing rows: subtotal = total_amount, no discount.
        DB::table('sales')->update([
            'subtotal' => DB::raw('total_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_percent', 'discount_amount']);
        });
    }
};
