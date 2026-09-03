<?php

declare(strict_types=1);

namespace Milon\Papyrus\Serve;

final class SiteRouter
{
    public function __construct(
        private readonly string $siteDir,
        private readonly string $basePath = '',
    ) {}

    public function emit(string $requestUri): void
    {
        $response = $this->response($requestUri);

        http_response_code($response['status']);

        foreach ($response['headers'] as $name => $value) {
            header($name.': '.$value);
        }

        if ($response['file'] !== null) {
            readfile($response['file']);
        }
    }

    /**
     * @return array{status: int, headers: array<string, string>, file: ?string}
     */
    public function response(string $requestUri): array
    {
        $path = parse_url($requestUri, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        $base = $this->basePath;

        if ($base !== '') {
            if ($path === '/' || $path === $base) {
                return $this->redirect($base.'/');
            }

            if (! str_starts_with($path, $base.'/')) {
                return $this->redirect($base.'/');
            }

            $path = substr($path, strlen($base)) ?: '/';
        }

        if (str_ends_with($path, '/')) {
            $path .= 'index.html';
        }

        $relative = ltrim($path, '/');

        if ($relative === '') {
            $relative = 'index.html';
        }

        $relative = str_replace('\\', '/', $relative);

        if ($relative === '..' || str_starts_with($relative, '../') || str_contains($relative, '/../')) {
            return $this->notFound();
        }

        $full = rtrim($this->siteDir, '/').'/'.$relative;
        $realSite = realpath($this->siteDir);
        $realFile = is_file($full) ? realpath($full) : false;

        if ($realSite === false || $realFile === false || ! $this->isInside($realSite, $realFile)) {
            return $this->notFound();
        }

        return [
            'status' => 200,
            'headers' => ['Content-Type' => $this->mimeType($realFile)],
            'file' => $realFile,
        ];
    }

    /**
     * @return array{status: int, headers: array<string, string>, file: ?string}
     */
    private function redirect(string $location): array
    {
        return [
            'status' => 302,
            'headers' => ['Location' => $location],
            'file' => null,
        ];
    }

    /**
     * @return array{status: int, headers: array<string, string>, file: ?string}
     */
    private function notFound(): array
    {
        $notFound = rtrim($this->siteDir, '/').'/404.html';

        if (is_file($notFound)) {
            return [
                'status' => 404,
                'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
                'file' => realpath($notFound) ?: $notFound,
            ];
        }

        return [
            'status' => 404,
            'headers' => ['Content-Type' => 'text/plain; charset=UTF-8'],
            'file' => null,
        ];
    }

    private function isInside(string $root, string $file): bool
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return $file === rtrim($root, DIRECTORY_SEPARATOR) || str_starts_with($file, $root);
    }

    private function mimeType(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'html', 'htm' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'xml' => 'application/xml; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
    }
}
