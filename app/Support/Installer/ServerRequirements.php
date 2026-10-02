<?php

namespace App\Support\Installer;

class ServerRequirements
{
    /** @return array{items: array<int, array{label: string, value: string, passed: bool}>, passed: bool} */
    public function check(): array
    {
        $items = [[
            'label' => 'PHP version',
            'value' => PHP_VERSION.' (requires '.config('installer.required_php', '8.3.0').'+)',
            'passed' => version_compare(PHP_VERSION, (string) config('installer.required_php', '8.3.0'), '>='),
        ]];

        foreach ((array) config('installer.required_extensions', []) as $extension) {
            $items[] = [
                'label' => strtoupper(str_replace('_', ' ', $extension)).' extension',
                'value' => extension_loaded($extension) ? 'Available' : 'Missing',
                'passed' => extension_loaded($extension),
            ];
        }

        $items = [
            ...$items,
            $this->pathCheck('Composer dependencies', base_path('vendor/autoload.php'), true),
            $this->pathCheck('Compiled frontend assets', public_path('build/manifest.json'), true),
            $this->pathCheck('Storage directory', storage_path(), false),
            $this->pathCheck('Installer private storage', storage_path('app/private'), false),
            $this->pathCheck('Bootstrap cache directory', base_path('bootstrap/cache'), false),
            [
                'label' => '.env configuration',
                'value' => $this->environmentWritable() ? 'Writable' : 'Not writable',
                'passed' => $this->environmentWritable(),
            ],
        ];

        return [
            'items' => $items,
            'passed' => collect($items)->every(fn (array $item): bool => $item['passed']),
        ];
    }

    /** @return array{label: string, value: string, passed: bool} */
    private function pathCheck(string $label, string $path, bool $file): array
    {
        $exists = $file ? is_file($path) : is_dir($path);
        $passed = $exists && ($file || is_writable($path));

        return [
            'label' => $label,
            'value' => ! $exists ? 'Missing' : ($passed ? ($file ? 'Available' : 'Writable') : 'Not writable'),
            'passed' => $passed,
        ];
    }

    private function environmentWritable(): bool
    {
        $path = base_path('.env');

        return is_file($path) ? is_writable($path) : is_writable(base_path());
    }
}
