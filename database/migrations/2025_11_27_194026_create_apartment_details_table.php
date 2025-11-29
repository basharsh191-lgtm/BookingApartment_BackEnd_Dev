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
        Schema::create('apartment_details', function (Blueprint $table) {
            $table->id('apartment_id');
            $table->foreignId('owner_id')->constrained('users');
            $table->string('apartment_description');
            $table->string('image');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('governorate', 50);
            $table->decimal('area');
            $table->decimal('price');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_details');
    }
};
