<?php

declare(strict_types=1);

namespace Milon\Papyrus\Migration;

use Milon\Papyrus\Config\DocumentSize;

final class IbisMigrator
{
    public function __construct(
        private readonly PhpConfigWriter $writer = new PhpConfigWriter,
    ) {}

    /**
     * @return array{papyrus: string, themes: list<string>}
     */
    public function migrate(string $bookDir, bool $force = false): array
    {
        $ibisPath = $bookDir.'/ibis.php';

        if (! is_file($ibisPath)) {
            throw new MigrationException(sprintf('Missing ibis.php in %s', $bookDir));
        }

        $papyrusPath = $bookDir.'/papyrus.php';

        if (is_file($papyrusPath) && ! $force) {
            throw new MigrationException(sprintf('%s already exists. Use --force to overwrite.', $papyrusPath));
        }

        /** @var array<string, mixed> $ibis */
        $ibis = require $ibisPath;

        if (! is_array($ibis)) {
            throw new MigrationException('ibis.php must return an array.');
        }

        $config = $this->convert($ibis);
        $this->writer->write($config, $papyrusPath);

        $themes = (new ThemeMigrator)->migrateDirectory($bookDir.'/assets');

        return [
            'papyrus' => $papyrusPath,
            'themes' => $themes,
        ];
    }

    /**
     * @param  array<string, mixed>  $ibis
     * @return array<string, mixed>
     */
    public function convert(array $ibis): array
    {
        $config = [
            'title' => (string) ($ibis['title'] ?? 'Untitled'),
            'subtitle' => (string) ($ibis['subtitle'] ?? ''),
            'author' => (string) ($ibis['author'] ?? ''),
            'themes' => ['light', 'dark'],
        ];

        $config['document'] = $this->convertDocument($ibis['document'] ?? []);
        $config['toc'] = $this->convertToc($ibis['toc_levels'] ?? $ibis['toc'] ?? []);
        $config['cover'] = $this->convertCover($ibis['cover'] ?? [], $config['document']);
        $config['header'] = $this->convertHeader($ibis['header'] ?? null);
        $config['fonts'] = $this->convertFonts($ibis['fonts'] ?? []);

        if (isset($ibis['configure_commonmark'])) {
            $config['configure_commonmark'] = $ibis['configure_commonmark'];
        }

        if (isset($ibis['sample'])) {
            $config['sample'] = ['ranges' => $this->convertSampleRanges($ibis['sample'])];
        }

        if (isset($ibis['sample_notice'])) {
            $config['sample_notice'] = (string) $ibis['sample_notice'];
        }

        $config['mermaid'] = [
            'enabled' => false,
            'format' => 'svg',
            'theme' => 'auto',
            'max_width_mm' => 130,
        ];

        $config['kdp'] = [
            'ebook' => [
                'enabled' => true,
                'cover' => is_array($ibis['cover'] ?? null) && is_string($ibis['cover']['image'] ?? null)
                    ? $ibis['cover']['image']
                    : 'cover-ebook.jpg',
            ],
            'print' => [
                'enabled' => true,
                'bleed_mm' => 3,
                'margin_preset' => 'recommended',
                'paper' => 'white',
            ],
            'metadata' => [
                'description' => $this->defaultDescription($config),
                'keywords' => [],
                'language' => 'en',
            ],
        ];

        return $config;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function convertDocument(array $document): array
    {
        $converted = [];

        if (isset($document['format']) && is_array($document['format'])) {
            $width = (float) ($document['format'][0] ?? 210);
            $height = (float) ($document['format'][1] ?? 297);
            $preset = DocumentSize::resolvePresetName($width, $height);

            if ($preset !== null) {
                $converted['size'] = $preset;
            } else {
                $converted['format'] = [$width, $height];
            }
        } elseif (isset($document['size'])) {
            $converted['size'] = (string) $document['size'];
        } else {
            $converted['size'] = 'crown-quarto';
        }

        foreach (['margin_left', 'margin_right', 'margin_top', 'margin_bottom'] as $key) {
            if (isset($document[$key])) {
                $converted[$key] = $document[$key];
            }
        }

        return $converted;
    }

    /**
     * @param  array<string, mixed>  $toc
     * @return array<string, int>
     */
    private function convertToc(array $toc): array
    {
        return [
            'h1' => (int) ($toc['H1'] ?? $toc['h1'] ?? 0),
            'h2' => (int) ($toc['H2'] ?? $toc['h2'] ?? 0),
            'h3' => (int) ($toc['H3'] ?? $toc['h3'] ?? 1),
        ];
    }

    /**
     * @param  array<string, mixed>  $cover
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function convertCover(array $cover, array $document): array
    {
        $converted = [];

        if (isset($cover['image']) && is_string($cover['image']) && $cover['image'] !== '') {
            $converted['image'] = $cover['image'];
        }

        if (isset($cover['width'])) {
            $converted['width'] = $cover['width'];
        }

        if (isset($cover['height'])) {
            $converted['height'] = $cover['height'];
        }

        if (! isset($converted['width'], $converted['height'])) {
            $size = DocumentSize::fromConfig($document);
            $converted['width'] = $size->widthMm;
            $converted['height'] = $size->heightMm;
        }

        return $converted;
    }

    /**
     * @return array{style: string}|null
     */
    private function convertHeader(mixed $header): ?array
    {
        if (is_string($header) && $header !== '') {
            return ['style' => $header];
        }

        if (is_array($header) && isset($header['style']) && is_string($header['style'])) {
            return ['style' => $header['style']];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function convertFonts(mixed $fonts): array
    {
        if (! is_array($fonts)) {
            return [
                'faces' => [],
                'script' => [],
            ];
        }

        if (isset($fonts['faces']) || isset($fonts['script'])) {
            return $fonts;
        }

        $faces = [];
        $scriptRules = [];

        foreach ($fonts as $name => $definition) {
            if (! is_string($name) || ! is_array($definition)) {
                continue;
            }

            $face = ['name' => $name];

            foreach (['R' => 'regular', 'B' => 'bold', 'I' => 'italic', 'BI' => 'bold_italic'] as $from => $to) {
                if (isset($definition[$from]) && is_string($definition[$from]) && $definition[$from] !== '') {
                    $face[$to] = $definition[$from];
                }
            }

            if (! empty($definition['useOTL'])) {
                $face['otl'] = true;
            }

            $faces[] = $face;

            if ($name === 'notosansbengali') {
                $scriptRules[] = [
                    'match' => ['bn', 'ben', 'bengali'],
                    'face' => 'notosansbengali',
                ];
            }
        }

        return [
            'faces' => $faces,
            'script' => $scriptRules,
        ];
    }

    /**
     * @return list<array{from: int, to: int}>
     */
    private function convertSampleRanges(mixed $sample): array
    {
        if (! is_array($sample)) {
            return [];
        }

        $ranges = [];

        foreach ($sample as $range) {
            if (! is_array($range)) {
                continue;
            }

            if (array_is_list($range) && count($range) >= 2) {
                $ranges[] = ['from' => (int) $range[0], 'to' => (int) $range[1]];

                continue;
            }

            if (isset($range['from'], $range['to'])) {
                $ranges[] = ['from' => (int) $range['from'], 'to' => (int) $range['to']];
            }
        }

        return $ranges;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function defaultDescription(array $config): string
    {
        $title = (string) ($config['title'] ?? '');
        $subtitle = (string) ($config['subtitle'] ?? '');

        if ($subtitle !== '') {
            return $title.': '.$subtitle;
        }

        return $title;
    }
}
