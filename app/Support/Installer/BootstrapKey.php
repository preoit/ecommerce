<?php

namespace App\Support\Installer;

use Illuminate\Support\Facades\File;
use Throwable;

class BootstrapKey
{
    public function activate(): void
    {
        if (filled(config('app.key'))) {
            return;
        }

        config([
            'app.key' => $this->loadOrCreate(),
            'installer.key_is_temporary' => true,
        ]);
    }

    private function loadOrCreate(): string
    {
        $path = (string) config('installer.bootstrap_key_file');

        try {
            File::ensureDirectoryExists(dirname($path));
            $handle = @fopen($path, 'c+');

            if ($handle === false) {
                throw new \RuntimeException('Installer key storage is not writable.');
            }

            try {
                if (! flock($handle, LOCK_EX)) {
                    throw new \RuntimeException('Installer key storage could not be locked.');
                }

                rewind($handle);
                $existing = trim((string) stream_get_contents($handle));
                if (preg_match('/^base64:[A-Za-z0-9+\/]{43}=$/', $existing)) {
                    return $existing;
                }

                $key = 'base64:'.base64_encode(random_bytes(32));
                if (! ftruncate($handle, 0) || ! rewind($handle) || fwrite($handle, $key.PHP_EOL) === false) {
                    throw new \RuntimeException('Installer key could not be saved.');
                }
                fflush($handle);

                return $key;
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } catch (Throwable $exception) {
            report($exception);

            return 'base64:'.base64_encode(random_bytes(32));
        }
    }
}
