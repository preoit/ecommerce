<?php

namespace App\Support\Content;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class RichTextSanitizer
{
    public function sanitize(?string $html, bool $allowRelativeImages = false): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $config = (new HtmlSanitizerConfig)->allowSafeElements();
        if ($allowRelativeImages) {
            $config = $config->allowRelativeMedias()->allowMediaSchemes(['http', 'https']);
        }
        $sanitizer = new HtmlSanitizer($config);
        $sanitized = trim($sanitizer->sanitize($html));

        return $sanitized === '' ? null : $sanitized;
    }
}
