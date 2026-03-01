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
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique(); // Supports IPv4 and IPv6
            $table->string('reason')->nullable();
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable(); // NULL = permanent block
            $table->boolean('is_auto_blocked')->default(false);
            $table->integer('malicious_request_count')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('ip_address');
            $table->index('expires_at');
            $table->index(['ip_address', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
