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
        Schema::create('hero_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('hero_title');
            $table->string('hero_subtitle');
            $table->string('hero_image_url')->nullable();
            $table->string('cta_button_text');
            $table->string('cta_button_link');
            $table->string('secondary_button_text');
            $table->string('secondary_button_link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_settings');
    }
};
