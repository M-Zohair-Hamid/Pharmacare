<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->integer('tablets_per_box')->nullable()->after('medicine_type');
            $table->decimal('box_price', 12, 2)->nullable()->after('tablets_per_box');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn(['tablets_per_box', 'box_price']);
        });
    }
};
