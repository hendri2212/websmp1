<?php

function render_view(string $view, array $data = [], string $layout = 'site'): void
{
    $viewPath = APP_ROOT . '/app/views/' . $view;
    if (!in_array($layout, ['site', 'admin'], true)
        || !file_is_within_directory($viewPath, APP_ROOT . '/app/views')) {
        throw new InvalidArgumentException('View tidak ditemukan.');
    }

    $data['content'] ??= site_content();
    if ($layout === 'admin') {
        $data['adminNotificationCounts'] = [
            'contact_messages' => db_available() ? contact_message_count('baru') : 0,
        ];
    }
    extract($data, EXTR_SKIP);
    $header = $layout === 'admin' ? 'admin_header.php' : 'header.php';
    $footer = $layout === 'admin' ? 'admin_footer.php' : 'footer.php';
    require APP_ROOT . '/app/views/layouts/' . $header;
    require $viewPath;
    require APP_ROOT . '/app/views/layouts/' . $footer;
}

if (!function_exists('home_quick_link_icon')) {
    function home_quick_link_icon(string $href): string
    {
        $path = '/' . trim(parse_url($href, PHP_URL_PATH) ?: $href, '/');
        $icons = [
            '/profil' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="9" y="14" width="46" height="38" rx="5" fill="#bfdbfe"/><circle cx="24" cy="29" r="7" fill="#f59e0b"/><path d="M14 48c2-10 18-10 20 0" fill="#0b4fa8"/><rect x="38" y="22" width="10" height="6" rx="2" fill="#06b6d4"/><rect x="38" y="34" width="12" height="5" rx="2" fill="#0284c7"/></svg>',
            '/fasilitas' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M12 52h40V26L32 12 12 26z" fill="#fb923c"/><path d="M20 52V31h24v21" fill="#0284c7"/><rect x="26" y="38" width="12" height="14" rx="2" fill="#dbeafe"/><path d="M28 25h8v8h-8z" fill="#fef3c7"/></svg>',
            '/akademik' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="14" y="10" width="34" height="44" rx="4" fill="#e0f2fe"/><path d="M22 20h18M22 29h20M22 38h14" stroke="#0b4fa8" stroke-width="4" stroke-linecap="round"/><path d="M44 13l8 8-8 8" fill="#f59e0b"/></svg>',
            '/ekstrakurikuler' => '<svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="24" cy="39" r="13" fill="#fb923c"/><path d="M13 39h22M24 26c5 6 5 20 0 26M16 30c6 4 10 4 16 0" stroke="#fff7ed" stroke-width="3" fill="none"/><path d="M39 16l13 13M52 16L39 29" stroke="#06b6d4" stroke-width="6" stroke-linecap="round"/><circle cx="46" cy="23" r="12" fill="none" stroke="#22c55e" stroke-width="4"/></svg>',
            '/ppdb' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="13" y="12" width="38" height="44" rx="5" fill="#dbeafe"/><path d="M22 25h20M22 34h20M22 43h12" stroke="#0b4fa8" stroke-width="4" stroke-linecap="round"/><path d="M42 10l8 8-8 8" fill="#eab308"/></svg>',
            '/dokumen' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M18 8h21l9 9v39H18z" fill="#e0f2fe"/><path d="M39 8v12h11" fill="#bfdbfe"/><path d="M24 29h18M24 38h18M24 47h12" stroke="#0b4fa8" stroke-width="4" stroke-linecap="round"/></svg>',
            '/prestasi' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M20 10h24v14c0 8-5 15-12 18-7-3-12-10-12-18z" fill="#eab308"/><path d="M16 16H8c0 10 5 16 13 18M48 16h8c0 10-5 16-13 18" stroke="#fb923c" stroke-width="5" fill="none" stroke-linecap="round"/><path d="M32 42v10M24 54h16" stroke="#0b4fa8" stroke-width="5" stroke-linecap="round"/></svg>',
            '/galeri' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="16" width="44" height="34" rx="5" fill="#bfdbfe"/><circle cx="24" cy="28" r="5" fill="#f59e0b"/><path d="M14 48l13-13 9 8 7-7 9 12z" fill="#0284c7"/></svg>',
            '/kontak' => '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="10" y="16" width="44" height="34" rx="6" fill="#e0f2fe"/><path d="M13 20l19 17 19-17" stroke="#0b4fa8" stroke-width="5" fill="none" stroke-linecap="round"/><path d="M18 47l12-12M46 47L34 35" stroke="#06b6d4" stroke-width="4" stroke-linecap="round"/></svg>',
            '/faq' => '<svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="24" fill="#dbeafe"/><path d="M24 25c1-6 7-9 13-6 6 4 4 11-1 14-3 2-4 3-4 7" stroke="#0b4fa8" stroke-width="5" fill="none" stroke-linecap="round"/><circle cx="32" cy="48" r="3" fill="#0284c7"/></svg>',
        ];

        return $icons[$path] ?? '<svg viewBox="0 0 64 64" aria-hidden="true"><rect x="12" y="12" width="40" height="40" rx="9" fill="#dbeafe"/><path d="M23 32h18M32 23v18" stroke="#0b4fa8" stroke-width="6" stroke-linecap="round"/></svg>';
    }
}
