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
        Schema::create('memorial_translations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('memorial_id');
            $table->string('locale', 5);
            $table->string('birth_place')->nullable();
            $table->string('death_place')->nullable();
            $table->text('biography')->nullable();
            $table->timestamps();

            $table->unique(['memorial_id', 'locale']);
            $table->index('locale');

            $table->foreign('memorial_id')
                ->references('id')
                ->on('memorials')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memorial_translations');
    }
};
