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
        Schema::create('memorial_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('memorial_id');
            $table->string('youtube_url');
            $table->string('title')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

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
        Schema::dropIfExists('memorial_videos');
    }
};
