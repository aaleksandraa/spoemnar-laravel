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
        Schema::create('memorial_candles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('memorial_id');
            $table->uuid('user_id')->nullable();
            $table->string('display_name')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('visitor_hash', 64)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['memorial_id', 'status', 'expires_at']);
            $table->index(['memorial_id', 'created_at']);

            $table->foreign('memorial_id')
                ->references('id')
                ->on('memorials')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memorial_candles');
    }
};
