<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $this->dropMysqlTokenableIndexIfExists();
            DB::statement('ALTER TABLE `personal_access_tokens` MODIFY `tokenable_id` CHAR(36) NOT NULL');
            DB::statement('CREATE INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`)');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');
            DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE VARCHAR(36) USING tokenable_id::text');
            DB::statement('CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens (tokenable_type, tokenable_id)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $this->dropMysqlTokenableIndexIfExists();
            DB::statement('ALTER TABLE `personal_access_tokens` MODIFY `tokenable_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('CREATE INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`)');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS personal_access_tokens_tokenable_type_tokenable_id_index');
            DB::statement('ALTER TABLE personal_access_tokens ALTER COLUMN tokenable_id TYPE BIGINT USING NULLIF(tokenable_id, \'\')::BIGINT');
            DB::statement('CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens (tokenable_type, tokenable_id)');
        }
    }

    private function dropMysqlTokenableIndexIfExists(): void
    {
        $indexes = DB::select('SHOW INDEX FROM `personal_access_tokens`');
        $hasIndex = collect($indexes)->contains(function (object $index): bool {
            return (($index->Key_name ?? '') === 'personal_access_tokens_tokenable_type_tokenable_id_index');
        });

        if ($hasIndex) {
            DB::statement('DROP INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens`');
        }
    }
};
