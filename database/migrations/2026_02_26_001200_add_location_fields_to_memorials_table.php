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
        Schema::table('memorials', function (Blueprint $table) {
            $table->foreignId('birth_country_id')
                ->nullable()
                ->after('death_date')
                ->constrained('countries')
                ->nullOnDelete();
            $table->foreignId('birth_place_id')
                ->nullable()
                ->after('birth_country_id')
                ->constrained('places')
                ->nullOnDelete();
            $table->foreignId('death_country_id')
                ->nullable()
                ->after('birth_place')
                ->constrained('countries')
                ->nullOnDelete();
            $table->foreignId('death_place_id')
                ->nullable()
                ->after('death_country_id')
                ->constrained('places')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memorials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('birth_country_id');
            $table->dropConstrainedForeignId('birth_place_id');
            $table->dropConstrainedForeignId('death_country_id');
            $table->dropConstrainedForeignId('death_place_id');
        });
    }
};

