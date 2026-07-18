<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (Schema::hasColumn('medicines', 'sku')) {
                $table->dropUnique('medicines_sku_unique');
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('medicines', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (!Schema::hasColumn('medicines', 'sku')) {
                $table->string('sku', 100)->nullable()->after('manufacturer');
            }
            if (!Schema::hasColumn('medicines', 'reorder_level')) {
                $table->integer('reorder_level')->default(10)->after('quantity');
            }
        });
    }
};
