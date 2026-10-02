<?php

namespace App\Support\Installer;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class InstallationState
{
    private bool $installed = false;

    public function hasLock(): bool
    {
        return File::isFile($this->lockPath());
    }

    public function isInstalled(): bool
    {
        if ($this->installed || $this->hasLock()) {
            return $this->installed = true;
        }

        if (app()->environment('testing') && ! config('installer.testing_uninstalled')) {
            return true;
        }

        if (config('installer.key_is_temporary') || blank(config('app.key'))) {
            return false;
        }

        try {
            $legacyInstallation = Schema::hasTable('migrations') && Schema::hasTable('users');
            if ($legacyInstallation && ! app()->environment('testing')) {
                $this->writeLock(['source' => 'existing-installation']);
            }

            return $this->installed = $legacyInstallation;
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string, mixed> $database */
    public function begin(array $database): void
    {
        $this->writeJson($this->progressPath(), [
            'fingerprint' => $this->fingerprint($database),
            'started_at' => now()->toIso8601String(),
        ]);
    }

    /** @param array<string, mixed> $database */
    public function canResume(array $database): bool
    {
        if (! File::isFile($this->progressPath())) {
            return false;
        }

        $progress = json_decode((string) File::get($this->progressPath()), true);

        return is_array($progress)
            && hash_equals((string) ($progress['fingerprint'] ?? ''), $this->fingerprint($database));
    }

    public function markInstalled(): void
    {
        $this->writeLock(['source' => 'web-installer']);
        File::delete($this->progressPath());
        $this->installed = true;
    }

    private function writeLock(array $extra): void
    {
        $this->writeJson($this->lockPath(), [
            'installed_at' => now()->toIso8601String(),
            'app_version' => app()->version(),
            ...$extra,
        ]);
    }

    /** @param array<string, mixed> $database */
    private function fingerprint(array $database): string
    {
        return hash('sha256', implode('|', [
            strtolower(trim((string) ($database['db_host'] ?? ''))),
            (string) ($database['db_port'] ?? ''),
            strtolower(trim((string) ($database['db_database'] ?? ''))),
            trim((string) ($database['db_username'] ?? '')),
        ]));
    }

    private function writeJson(string $path, array $payload): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL, true);
    }

    private function lockPath(): string
    {
        return (string) config('installer.lock_file', storage_path('app/private/installed.json'));
    }

    private function progressPath(): string
    {
        return (string) config('installer.progress_file', storage_path('app/private/installing.json'));
    }
}
