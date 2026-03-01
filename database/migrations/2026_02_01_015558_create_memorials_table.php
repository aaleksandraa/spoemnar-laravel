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
        Schema::create('memorials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->date('death_date');
            $table->string('birth_place')->nullable();
            $table->string('death_place')->nullable();
            $table->text('biography')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index('slug');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memorials');
    }
};
