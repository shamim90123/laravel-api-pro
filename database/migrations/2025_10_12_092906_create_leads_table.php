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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Basic lead details
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->text('link')->nullable();
            $table->string('item_id')->nullable();

            // Product interest flags (true/false)
            $table->string('sams_pay')->nullable();
            $table->string('sams_manage')->nullable();
            $table->string('sams_platform')->nullable();
            $table->string('sams_pay_client_management')->nullable();

            // Booking and comments
            $table->string('booked_demo')->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
