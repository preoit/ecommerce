<?php

namespace App\Support\Content;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class RichTextSanitizer
{
    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)->allowSafeElements());
        $sanitized = trim($sanitizer->sanitize($html));

        return $sanitized === '' ? null : $sanitized;
    }
}
