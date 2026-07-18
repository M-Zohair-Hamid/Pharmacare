<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['medicine_id']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('medicine_id')->references('id')->on('medicines')->cascadeOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['medicine_id']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreign('medicine_id')->references('id')->on('medicines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['medicine_id']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('medicine_id')->references('id')->on('medicines')->restrictOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['medicine_id']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreign('medicine_id')->references('id')->on('medicines')->restrictOnDelete();
        });
    }
};
