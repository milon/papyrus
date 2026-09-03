<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\KdpEbookRenderer;
use Milon\Papyrus\Kdp\KdpException;
use Milon\Papyrus\Kdp\KdpMetadataExporter;
use Milon\Papyrus\Kdp\Validation\EpubcheckRunner;
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
            $result = (new KdpEbookRenderer($project))->render($outputPath);

            $this->assertSame($outputPath, $result->path);
            $this->assertFileExists($result->path);

            $epubcheck = new EpubcheckRunner;
            if (! $epubcheck->isAvailable()) {
                $this->assertContains('epubcheck not found; skipped external validation.', $result->warnings);
            }

            $validation = (new KdpEpubValidator)->validate($result->path, $project);
            $this->assertTrue($validation->ok, $validation->message());

            try {
                $required = (new KdpEbookRenderer($project))->render($outputPath, requireEpubcheck: true);
                $this->assertSame($outputPath, $required->path);
            } catch (KdpException $e) {
                if ($epubcheck->isAvailable()) {
                    throw $e;
                }

                $this->assertStringContainsString('epubcheck', $e->getMessage());
            }
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
            $this->assertTrue($data['print']['margin_preset_known']);
            $this->assertArrayHasKey('width_mm', $data['print']['trim']);
            $this->assertArrayHasKey('height_mm', $data['print']['trim']);
            $this->assertSame('mini-book-kdp.epub', $data['artifacts']['ebook']);
            $this->assertSame('mini-book-kdp-print.pdf', $data['artifacts']['print']);
            $this->assertTrue($data['ebook']['enabled']);
            $this->assertTrue($data['print']['enabled']);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($outputDir);
        }
    }
}
