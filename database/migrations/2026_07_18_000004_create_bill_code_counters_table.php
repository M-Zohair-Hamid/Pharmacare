<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_code_counters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('next_value')->default(1);
        });

        // Seed the counter, continuing from the highest existing bill_code if any sales exist.
        $maxCode = DB::table('sales')->max('bill_code');
        $start = $maxCode ? ((int) $maxCode) + 1 : 1;

        DB::table('bill_code_counters')->insert([
            'id' => 1,
            'next_value' => $start,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_code_counters');
    }
};
