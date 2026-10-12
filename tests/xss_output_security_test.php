<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require __DIR__ . '/../includes/functions.php';
$checks = 0;
function xss_assert(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}
try {
    foreach (['" autofocus onfocus="/*inert*/', "' onmouseover='/*inert*/", '</textarea><script>/*inert*/</script>',
        '<svg onload="/*inert*/">', '&quot; onerror=&quot;/*inert*/', "bad\xC3\x28"] as $probe) {
        $encoded = e($probe);
        xss_assert($encoded !== '' && !str_contains($encoded, '<') && !str_contains($encoded, '>'), 'Text encoded with invalid UTF-8 substitution');
        foreach (['src','href','value','data-probe','title','alt'] as $attribute) {
            $html = '<div ' . $attribute . '="' . $encoded . '"></div>';
            $document = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($html, LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $div = $document->getElementsByTagName('div')->item(0);
            xss_assert($div->attributes->length === 1 && $div->hasAttribute($attribute), 'Quotes/entities cannot escape ' . $attribute);
        }
    }
    foreach (['javascript:/*inert*/','JaVaScRiPt:/*inert*/','data:text/html,INERT','vbscript:INERT','//evil.invalid',
        "java\nscript:INERT", '/\\evil.invalid','https://user:pass@example.invalid','not a URL'] as $url) {
        xss_assert(safe_content_url($url) === '', 'Unsafe URL rejected');
        xss_assert(content_link_url($url) === '#', 'Unsafe link falls back harmlessly');
        xss_assert(video_embed_url($url) === '', 'Unsafe iframe source rejected');
    }
    foreach (['https://example.invalid/video','http://example.invalid/path?q=one&other=two','/profil','#section','berita/valid-slug','?category=valid'] as $url) {
        xss_assert(safe_content_url($url) === $url, 'Allowed URL preserved');
    }
    // The video field is now YouTube-only: IDs must be 11 characters and unknown hosts fail closed.
    xss_assert(video_embed_url('https://www.youtube.com/watch?v=AbC_123-xYz') === 'https://www.youtube.com/embed/AbC_123-xYz', 'Existing YouTube embed works');
    xss_assert(video_embed_url('https://youtu.be/AbC_123-xYz') === 'https://www.youtube.com/embed/AbC_123-xYz', 'Existing short YouTube URL works');
    xss_assert(video_embed_url('https://youtube.com.evil.invalid/watch?v=abc') === '', 'Lookalike host rejected');
    xss_assert(video_embed_url('https://www.youtube.com/watch?v[]=abc') === '', 'Array query value rejected without type error');
    echo 'PASS: ' . $checks . ' XSS context/URL checks.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
