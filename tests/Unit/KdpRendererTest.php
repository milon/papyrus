<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\KdpEbookRenderer;
use Milon\Papyrus\Kdp\KdpMetadataExporter;
use Milon\Papyrus\Kdp\Validation\KdpEpubValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class KdpRendererTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function mini_book_builds_kdp_epub(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ext-zip is required for EPUB tests');
        }

        $project = Project::load($this->fixtureDir);
        $outputDir = sys_get_temp_dir().'/papyrus-kdp-'.uniqid('', true);
        mkdir($outputDir);

        $outputPath = $outputDir.'/mini-book-kdp.epub';

        try {
            $path = (new KdpEbookRenderer($project))->render($outputPath);

            $this->assertSame($outputPath, $path);
            $this->assertFileExists($path);

            $result = (new KdpEpubValidator)->validate($path, $project);
            $this->assertTrue($result->ok, $result->message());
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($outputDir);
        }
    }

    #[Test]
    public function metadata_exporter_writes_json_sidecar(): void
    {
        $project = Project::load($this->fixtureDir);
        $outputDir = sys_get_temp_dir().'/papyrus-kdp-meta-'.uniqid('', true);
        mkdir($outputDir);

        $outputPath = $outputDir.'/mini-book-kdp-metadata.json';

        try {
            $path = (new KdpMetadataExporter($project))->export($outputPath);

            $this->assertSame($outputPath, $path);
            $this->assertFileExists($path);

            $data = json_decode((string) file_get_contents($path), true);
            $this->assertIsArray($data);
            $this->assertSame('Mini Book', $data['title']);
            $this->assertSame('Papyrus fixture', $data['subtitle']);
            $this->assertSame('Papyrus', $data['author']);
            $this->assertSame('A short fixture book for Papyrus tests.', $data['description']);
            $this->assertSame(['papyrus', 'fixture'], $data['keywords']);
            $this->assertSame('recommended', $data['print']['margin_preset']);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($outputDir);
        }
    }
}
