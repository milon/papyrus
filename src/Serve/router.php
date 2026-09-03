<?php

declare(strict_types=1);

$autoloadCandidates = [
    dirname(__DIR__, 2).'/vendor/autoload.php',
    dirname(__DIR__, 3).'/autoload.php',
];

$autoload = null;

foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Papyrus serve: Composer autoloader not found.\n";

    return;
}

require $autoload;

use Milon\Papyrus\Serve\SiteRouter;

$siteDir = getenv('PAPYRUS_SITE_DIR');
$basePath = getenv('PAPYRUS_SITE_BASE');

if (! is_string($siteDir) || $siteDir === '' || ! is_dir($siteDir)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Papyrus serve: PAPYRUS_SITE_DIR is missing or not a directory.\n";

    return;
}

if (! is_string($basePath)) {
    $basePath = '';
}

(new SiteRouter($siteDir, $basePath))->emit($_SERVER['REQUEST_URI'] ?? '/');
