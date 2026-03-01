<?php

use App\Support\MediaUrl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeColumnValues('memorials', 'profile_image_url');
        $this->normalizeColumnValues('memorial_images', 'image_url');
    }

    public function down(): void
    {
        // One-way data normalization.
    }

    private function normalizeColumnValues(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $rows = DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->get();

        foreach ($rows as $row) {
            $currentValue = is_string($row->{$column}) ? $row->{$column} : null;
            if ($currentValue === null) {
                continue;
            }

            $normalized = MediaUrl::normalize($currentValue);
            if ($normalized === null || $normalized === $currentValue) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update([$column => $normalized]);
        }
    }
};

