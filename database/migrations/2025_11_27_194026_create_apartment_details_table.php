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
                $table->id();
                $table->foreignId('owner_id')->constrained('users');
                $table->text('apartment_description');
                $table->integer('floorNumber');
                $table->integer('roomNumber');
                $table->boolean('free_wifi');
                $table->date('available_from');
                $table->date('available_to')->nullable();
                $table->enum('status', ['available', 'not_available'])->default('available');
                $table->string('governorate', 50);
                $table->string('city');
                $table->float('area');
                $table->decimal('price',10,2);
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_details');
    }
};
