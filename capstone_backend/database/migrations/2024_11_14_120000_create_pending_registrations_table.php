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
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password'); // Hashed password
            $table->string('role')->default('donor');
            $table->string('verification_code', 6);
            $table->string('verification_token');
            $table->timestamp('expires_at');
            $table->integer('attempts')->default(0);
            $table->integer('resend_count')->default(0);
            $table->json('registration_data')->nullable(); // For storing additional registration data
            $table->timestamps();

            // Add index for faster lookups
            $table->index('email');
            $table->index('verification_code');
            $table->index('verification_token');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
