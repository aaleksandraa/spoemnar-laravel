<?php

namespace Tests\Feature\Backup;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    private string $sqliteFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $tempDirectory = storage_path('framework/testing/sqlite');
        if (!is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0755, true);
        }

        $this->sqliteFilePath = $tempDirectory.'/backup_command_test.sqlite';
        if (is_file($this->sqliteFilePath)) {
            unlink($this->sqliteFilePath);
        }

        $pdo = new \PDO('sqlite:'.$this->sqliteFilePath);
        $pdo->exec('CREATE TABLE notes (id INTEGER PRIMARY KEY AUTOINCREMENT, body TEXT NOT NULL)');
        $pdo->exec("INSERT INTO notes (body) VALUES ('backup smoke test')");
        $pdo = null;

        config([
            'database.connections.backup_sqlite' => [
                'driver' => 'sqlite',
                'database' => $this->sqliteFilePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->sqliteFilePath)) {
            unlink($this->sqliteFilePath);
        }

        parent::tearDown();
    }

    public function test_database_backup_is_created_on_primary_disk(): void
    {
        Storage::fake('backup-primary');

        config([
            'backup.enabled' => true,
            'backup.connection' => 'backup_sqlite',
            'backup.primary.disk' => 'backup-primary',
            'backup.primary.path' => 'db-backups',
            'backup.secure_copy.enabled' => false,
            'backup.retention_days' => 30,
        ]);

        $this->artisan('db:backup --connection=backup_sqlite')
            ->assertExitCode(0);

        $files = Storage::disk('backup-primary')->files('db-backups');
        $compressedFiles = array_values(array_filter($files, static fn (string $file): bool => str_ends_with($file, '.sqlite.gz')));

        $this->assertCount(1, $compressedFiles);

        $backupFile = $compressedFiles[0];
        Storage::disk('backup-primary')->assertExists($backupFile);
        Storage::disk('backup-primary')->assertExists("{$backupFile}.sha256");

        $compressed = Storage::disk('backup-primary')->get($backupFile);
        $this->assertNotEmpty($compressed);

        $decompressed = gzdecode($compressed);
        $this->assertNotFalse($decompressed);
        $this->assertStringStartsWith('SQLite format 3', $decompressed);

        $checksum = trim(Storage::disk('backup-primary')->get("{$backupFile}.sha256"));
        $this->assertSame(hash('sha256', $compressed), $checksum);
    }

    public function test_database_backup_is_replicated_to_secure_disk_when_enabled(): void
    {
        Storage::fake('backup-primary');
        Storage::fake('backup-secure');

        config([
            'backup.enabled' => true,
            'backup.connection' => 'backup_sqlite',
            'backup.primary.disk' => 'backup-primary',
            'backup.primary.path' => 'db-backups',
            'backup.secure_copy.enabled' => true,
            'backup.secure_copy.disk' => 'backup-secure',
            'backup.secure_copy.path' => 'secure/db-backups',
            'backup.retention_days' => 30,
        ]);

        $this->artisan('db:backup --connection=backup_sqlite')
            ->assertExitCode(0);

        $primaryCompressed = array_values(array_filter(
            Storage::disk('backup-primary')->files('db-backups'),
            static fn (string $file): bool => str_ends_with($file, '.sqlite.gz')
        ));
        $secureCompressed = array_values(array_filter(
            Storage::disk('backup-secure')->files('secure/db-backups'),
            static fn (string $file): bool => str_ends_with($file, '.sqlite.gz')
        ));

        $this->assertCount(1, $primaryCompressed);
        $this->assertCount(1, $secureCompressed);

        $primaryBlob = Storage::disk('backup-primary')->get($primaryCompressed[0]);
        $secureBlob = Storage::disk('backup-secure')->get($secureCompressed[0]);
        $this->assertSame($primaryBlob, $secureBlob);
    }

    public function test_old_backups_are_removed_based_on_retention_days(): void
    {
        Storage::fake('backup-primary');

        config([
            'backup.enabled' => true,
            'backup.connection' => 'backup_sqlite',
            'backup.primary.disk' => 'backup-primary',
            'backup.primary.path' => 'db-backups',
            'backup.secure_copy.enabled' => false,
            'backup.retention_days' => 3,
        ]);

        Storage::disk('backup-primary')->put('db-backups/old.sqlite.gz', 'old-backup');
        Storage::disk('backup-primary')->put('db-backups/old.sqlite.gz.sha256', 'old-checksum');
        Storage::disk('backup-primary')->put('db-backups/recent.sqlite.gz', 'recent-backup');
        Storage::disk('backup-primary')->put('db-backups/recent.sqlite.gz.sha256', 'recent-checksum');

        $oldBackupPath = Storage::disk('backup-primary')->path('db-backups/old.sqlite.gz');
        $oldChecksumPath = Storage::disk('backup-primary')->path('db-backups/old.sqlite.gz.sha256');
        $recentBackupPath = Storage::disk('backup-primary')->path('db-backups/recent.sqlite.gz');

        touch($oldBackupPath, now()->subDays(10)->timestamp);
        touch($oldChecksumPath, now()->subDays(10)->timestamp);
        touch($recentBackupPath, now()->subDay()->timestamp);

        $this->artisan('db:backup --connection=backup_sqlite')
            ->assertExitCode(0);

        Storage::disk('backup-primary')->assertMissing('db-backups/old.sqlite.gz');
        Storage::disk('backup-primary')->assertMissing('db-backups/old.sqlite.gz.sha256');
        Storage::disk('backup-primary')->assertExists('db-backups/recent.sqlite.gz');
    }
}

