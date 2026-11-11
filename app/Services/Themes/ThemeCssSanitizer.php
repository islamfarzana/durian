<?php

namespace App\Services\Themes;

class ThemeCssSanitizer
{
    public function sanitize(?string $css): string
    {
        if ($css === null || trim($css) === '') {
            return '';
        }

        $css = str_replace("\0", '', $css);
        $css = preg_replace('/@import\b[^;]*;?/i', '', $css) ?? '';
        $css = preg_replace('/expression\s*\(/i', '', $css) ?? '';
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? '';
        $css = preg_replace('/behavior\s*:/i', '', $css) ?? '';
        $css = preg_replace('/-moz-binding\s*:/i', '', $css) ?? '';
        $css = preg_replace('/url\s*\(\s*([\'"]?)(?!data:image\/(?:png|jpeg|gif|webp)|\/)[^)]+\)/i', 'url()', $css) ?? '';
        $css = str_replace(['<', '>', '{!', '!}'], '', $css);

        return mb_substr(trim($css), 0, 20000);
    }
}
