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
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('verification_type'); // 'sign_up', 'reset_password', 'change_email'
            $table->string('code', 6); // 6-digit verification code
            $table->string('email'); // Email address to verify
            $table->unsignedBigInteger('user_id')->nullable(); // User ID (nullable for pre-registration verification)
            $table->timestamp('expires_at'); // Expiration time for the code
            $table->boolean('is_used')->default(false); // Whether the code has been used
            $table->json('metadata')->nullable(); // Additional data (e.g., new email for change_email)
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['email', 'verification_type']);
            $table->index(['code', 'verification_type']);
            $table->index('expires_at');
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
