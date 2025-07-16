<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Symfony\Component\Process\Process;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore {--filename=}';
    protected $description = 'Restore Laravel app and DB from a Spatie-style backup ZIP';

    public function handle(): int
    {
        $filename = $this->option('filename');

        if (!$filename) {
            $this->error('❌ Please provide a --filename=');
            return 1;
        }

        $backupPath = storage_path("app/laravel-backups/{$filename}");

        if (!file_exists($backupPath)) {
            $this->error("❌ Backup file not found at: {$backupPath}");
            return 1;
        }

        $this->info("📦 Extracting backup ZIP...");
        $restorePath = storage_path('app/backup-restore-temp');
        File::deleteDirectory($restorePath);
        File::makeDirectory($restorePath);

        $zip = new ZipArchive;
        if ($zip->open($backupPath) === true) {
            $zip->extractTo($restorePath);
            $zip->close();
        } else {
            $this->error("❌ Failed to extract ZIP file.");
            return 1;
        }

        // Restore DB
        $sqlDump = collect(File::files("{$restorePath}/db-dumps"))
            ->first(fn ($file) => str_ends_with($file->getFilename(), '.sql'));

        if (!$sqlDump) {
            $this->error("❌ No .sql file found in db-dumps/");
            return 1;
        }

        $this->info("🧠 Restoring database from: " . $sqlDump->getFilename());

        $dbHost = config('database.connections.mysql.host');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');

        $process = Process::fromShellCommandline(
            "mysql -h $dbHost -u $dbUser -p$dbPass $dbName < " . $sqlDump->getRealPath()
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("❌ DB Restore Failed: " . $process->getErrorOutput());
            return 1;
        }

        $this->info("✅ Database restored successfully.");

        // Optionally restore files
        if (File::exists("{$restorePath}/.env")) {
            File::copy("{$restorePath}/.env", base_path('.env'));
            $this->info("📄 Restored .env file.");
        }

        if (File::exists("{$restorePath}/var/www/html/biss/storage")) {
            File::copyDirectory("{$restorePath}/var/www/html/biss/storage", storage_path());
            $this->info("📁 Restored storage/ folder.");
        }


        $this->info("🎉 Restore complete!");

        return 0;
    }
}
