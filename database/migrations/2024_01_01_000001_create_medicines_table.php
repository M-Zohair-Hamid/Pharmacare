<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('generic_name', 255)->nullable();
            $table->string('category', 100)->default('General');
            $table->string('medicine_type', 50)->default('Tablet');
            $table->string('manufacturer', 255)->nullable();
            $table->string('sku', 100)->unique();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->timestamp('expiry_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
