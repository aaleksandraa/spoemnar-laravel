<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup
        {--connection= : Database connection name}
        {--keep-days= : Number of days to keep backups}';

    protected $description = 'Create a compressed database backup and optionally replicate it to secure storage';

    public function handle(): int
    {
        if (!config('backup.enabled', true)) {
            $this->warn('Database backup is disabled via DB_BACKUP_ENABLED.');

            return self::SUCCESS;
        }

        $connection = $this->resolveConnectionName();
        $connectionConfig = config("database.connections.{$connection}");

        if (!is_array($connectionConfig)) {
            $this->error("Database connection [{$connection}] is not configured.");

            return self::FAILURE;
        }

        $driver = (string) ($connectionConfig['driver'] ?? '');
        if ($driver === '') {
            $this->error("Database connection [{$connection}] does not define a driver.");

            return self::FAILURE;
        }

        $keepDays = $this->resolveKeepDays();
        if ($keepDays < 1) {
            $this->error('Retention must be at least 1 day.');

            return self::FAILURE;
        }

        $timestamp = now()->format('Ymd_His');
        $dumpExtension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $fileName = "{$connection}_{$timestamp}.{$dumpExtension}.gz";

        $primaryDisk = (string) config('backup.primary.disk', 'local');
        $primaryPath = $this->normalizePath((string) config('backup.primary.path', 'backups/database'));
        if ($primaryPath === '') {
            throw new RuntimeException('DB_BACKUP_PATH cannot be empty.');
        }
        $primaryRelativeFile = $this->joinPath($primaryPath, $fileName);

        $tmpDir = storage_path('app/private/tmp/db-backups');
        $tmpDumpPath = $this->joinLocalPath($tmpDir, "{$connection}_{$timestamp}.{$dumpExtension}");
        $tmpCompressedPath = $tmpDumpPath.'.gz';

        $this->ensureDirectoryExists($tmpDir);

        try {
            $this->createDump($driver, $connectionConfig, $tmpDumpPath);
            $this->compressFile($tmpDumpPath, $tmpCompressedPath);

            $checksum = hash_file('sha256', $tmpCompressedPath);
            if (!is_string($checksum) || $checksum === '') {
                throw new RuntimeException('Unable to compute backup checksum.');
            }

            $this->storeBackup($primaryDisk, $primaryRelativeFile, $tmpCompressedPath, $checksum);
            $this->line("Primary backup saved to [{$primaryDisk}] {$primaryRelativeFile}");

            if ((bool) config('backup.secure_copy.enabled', false)) {
                $secureDisk = (string) config('backup.secure_copy.disk', '');
                $securePath = $this->normalizePath((string) config('backup.secure_copy.path', 'backups/database'));

                if ($secureDisk === '') {
                    throw new RuntimeException('Secure copy is enabled, but DB_BACKUP_SECURE_DISK is not configured.');
                }
                if ($securePath === '') {
                    throw new RuntimeException('Secure copy is enabled, but DB_BACKUP_SECURE_PATH is empty.');
                }

                $secureRelativeFile = $this->joinPath($securePath, $fileName);
                $this->storeBackup($secureDisk, $secureRelativeFile, $tmpCompressedPath, $checksum);
                $this->line("Secure backup replicated to [{$secureDisk}] {$secureRelativeFile}");

                $removedFromSecure = $this->cleanupOldBackups($secureDisk, $securePath, $keepDays);
                if ($removedFromSecure > 0) {
                    $this->line("Removed {$removedFromSecure} old secure backup(s).");
                }
            }

            $removedFromPrimary = $this->cleanupOldBackups($primaryDisk, $primaryPath, $keepDays);
            if ($removedFromPrimary > 0) {
                $this->line("Removed {$removedFromPrimary} old primary backup(s).");
            }

            $this->info("Database backup completed successfully for [{$connection}] at ".now()->toDateTimeString().'.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($tmpDumpPath);
            @unlink($tmpCompressedPath);
        }
    }

    private function resolveConnectionName(): string
    {
        $optionConnection = $this->option('connection');
        if (is_string($optionConnection) && $optionConnection !== '') {
            return $optionConnection;
        }

        $configuredConnection = config('backup.connection');
        if (is_string($configuredConnection) && $configuredConnection !== '') {
            return $configuredConnection;
        }

        return (string) config('database.default');
    }

    private function resolveKeepDays(): int
    {
        $option = $this->option('keep-days');
        if (is_string($option) && $option !== '') {
            return max(0, (int) $option);
        }

        return max(0, (int) config('backup.retention_days', 30));
    }

    private function createDump(string $driver, array $connectionConfig, string $outputPath): void
    {
        match ($driver) {
            'pgsql' => $this->dumpPostgres($connectionConfig, $outputPath),
            'mysql', 'mariadb' => $this->dumpMysql($connectionConfig, $outputPath),
            'sqlite' => $this->dumpSqlite($connectionConfig, $outputPath),
            default => throw new RuntimeException("Backup for driver [{$driver}] is not supported."),
        };
    }

    private function dumpPostgres(array $connectionConfig, string $outputPath): void
    {
        $database = (string) ($connectionConfig['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('PostgreSQL database name is missing.');
        }

        $binary = (string) config('backup.tools.pg_dump_binary', 'pg_dump');

        $command = [
            $binary,
            '--format=plain',
            '--encoding=UTF8',
            '--no-owner',
            '--no-acl',
            "--file={$outputPath}",
        ];

        $host = (string) ($connectionConfig['host'] ?? '');
        if ($host !== '') {
            $command[] = "--host={$host}";
        }

        $port = (string) ($connectionConfig['port'] ?? '');
        if ($port !== '') {
            $command[] = "--port={$port}";
        }

        $username = (string) ($connectionConfig['username'] ?? '');
        if ($username !== '') {
            $command[] = "--username={$username}";
        }

        $command[] = $database;

        $env = null;
        $password = (string) ($connectionConfig['password'] ?? '');
        if ($password !== '') {
            $env = ['PGPASSWORD' => $password];
        }

        $this->runProcess($command, $env);
    }

    private function dumpMysql(array $connectionConfig, string $outputPath): void
    {
        $database = (string) ($connectionConfig['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('MySQL database name is missing.');
        }

        $binary = (string) config('backup.tools.mysqldump_binary', 'mysqldump');

        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            "--result-file={$outputPath}",
        ];

        $host = (string) ($connectionConfig['host'] ?? '');
        if ($host !== '') {
            $command[] = "--host={$host}";
        }

        $port = (string) ($connectionConfig['port'] ?? '');
        if ($port !== '') {
            $command[] = "--port={$port}";
        }

        $username = (string) ($connectionConfig['username'] ?? '');
        if ($username !== '') {
            $command[] = "--user={$username}";
        }

        $charset = (string) ($connectionConfig['charset'] ?? '');
        if ($charset !== '') {
            $command[] = "--default-character-set={$charset}";
        }

        $command[] = $database;

        $env = null;
        $password = (string) ($connectionConfig['password'] ?? '');
        if ($password !== '') {
            $env = ['MYSQL_PWD' => $password];
        }

        $this->runProcess($command, $env);
    }

    private function dumpSqlite(array $connectionConfig, string $outputPath): void
    {
        $database = (string) ($connectionConfig['database'] ?? '');
        if ($database === '' || $database === ':memory:') {
            throw new RuntimeException('SQLite in-memory database cannot be backed up with this command.');
        }

        $resolvedPath = $this->resolveSqlitePath($database);
        if (!is_file($resolvedPath)) {
            throw new RuntimeException("SQLite database file not found at [{$resolvedPath}].");
        }

        if (!copy($resolvedPath, $outputPath)) {
            throw new RuntimeException('Failed to copy SQLite database file.');
        }
    }

    private function runProcess(array $command, ?array $env = null): void
    {
        $timeout = max(60, (int) config('backup.timeout_seconds', 1200));
        $process = new Process($command, base_path(), $env);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function compressFile(string $sourcePath, string $targetPath): void
    {
        $inputHandle = fopen($sourcePath, 'rb');
        if ($inputHandle === false) {
            throw new RuntimeException("Cannot open source dump file [{$sourcePath}].");
        }

        $gzipHandle = gzopen($targetPath, 'wb9');
        if ($gzipHandle === false) {
            fclose($inputHandle);
            throw new RuntimeException("Cannot create compressed dump file [{$targetPath}].");
        }

        try {
            while (!feof($inputHandle)) {
                $chunk = fread($inputHandle, 1024 * 1024);
                if ($chunk === false) {
                    throw new RuntimeException('Failed reading source dump file.');
                }

                if ($chunk === '') {
                    continue;
                }

                if (gzwrite($gzipHandle, $chunk) === false) {
                    throw new RuntimeException('Failed writing compressed backup chunk.');
                }
            }
        } finally {
            fclose($inputHandle);
            gzclose($gzipHandle);
        }
    }

    private function storeBackup(string $disk, string $relativeFilePath, string $localCompressedPath, string $checksum): void
    {
        $stream = fopen($localCompressedPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Cannot read compressed backup [{$localCompressedPath}].");
        }

        try {
            Storage::disk($disk)->put($relativeFilePath, $stream, [
                'visibility' => 'private',
            ]);
        } finally {
            fclose($stream);
        }

        Storage::disk($disk)->put("{$relativeFilePath}.sha256", $checksum.PHP_EOL, [
            'visibility' => 'private',
        ]);
    }

    private function cleanupOldBackups(string $disk, string $directory, int $keepDays): int
    {
        $cutoff = now()->subDays($keepDays)->timestamp;
        $deleted = 0;
        $files = Storage::disk($disk)->files($directory);

        foreach ($files as $file) {
            if (!preg_match('/\.(sql|sqlite)\.gz$/', $file)) {
                continue;
            }

            $lastModified = Storage::disk($disk)->lastModified($file);
            if ($lastModified >= $cutoff) {
                continue;
            }

            if (Storage::disk($disk)->delete($file)) {
                $deleted++;
            }

            Storage::disk($disk)->delete("{$file}.sha256");
        }

        return $deleted;
    }

    private function resolveSqlitePath(string $database): string
    {
        if ($this->isAbsolutePath($database)) {
            return $database;
        }

        return database_path($database);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create temporary directory [{$directory}].");
        }
    }

    private function normalizePath(string $path): string
    {
        return trim($path, '/');
    }

    private function joinPath(string $left, string $right): string
    {
        $left = trim($left, '/');
        $right = trim($right, '/');

        if ($left === '') {
            return $right;
        }

        return "{$left}/{$right}";
    }

    private function joinLocalPath(string $left, string $right): string
    {
        return rtrim($left, '\\/').DIRECTORY_SEPARATOR.ltrim($right, '\\/');
    }
}
