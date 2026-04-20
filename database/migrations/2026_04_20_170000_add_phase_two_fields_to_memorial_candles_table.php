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
        Schema::table('memorial_candles', function (Blueprint $table) {
            $table->text('message')->nullable()->after('display_name');
            $table->string('candle_type', 20)->default('memory')->after('visitor_hash');
            $table->boolean('is_premium')->default(false)->after('candle_type');

            $table->index(['memorial_id', 'candle_type', 'status'], 'memorial_candles_type_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memorial_candles', function (Blueprint $table) {
            $table->dropIndex('memorial_candles_type_status_idx');
            $table->dropColumn(['message', 'candle_type', 'is_premium']);
        });
    }
};
