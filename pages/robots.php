<?php
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: " . base_url('admin/') . "\n";
echo "Disallow: " . base_url('storage/') . "\n";
echo "Sitemap: " . absolute_url('sitemap.xml') . "\n";
