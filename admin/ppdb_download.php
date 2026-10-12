<?php

// Retired registration endpoint: never bootstrap, query, upload, or export applicant data.
http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
exit('Layanan pendaftaran lokal tidak tersedia. Pendaftaran resmi ditangani Dinas Pendidikan.');
