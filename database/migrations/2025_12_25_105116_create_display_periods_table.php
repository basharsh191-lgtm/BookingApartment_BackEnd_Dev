<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('display_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')
                ->constrained('apartment_details')
                ->onDelete('cascade');
            $table->date('display_start_date');
            $table->date('display_end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_periods');
    }
};
