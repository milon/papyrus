<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Epub\EpubRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class EpubRendererTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function mini_book_builds_epub_with_all_chapters(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ext-zip is required for EPUB tests');
        }

        $project = Project::load($this->fixtureDir);
        $outputDir = sys_get_temp_dir().'/papyrus-epub-'.uniqid('', true);
        mkdir($outputDir);

        $outputPath = $outputDir.'/mini-book.epub';

        try {
            $renderer = new EpubRenderer($project);
            $path = $renderer->render($outputPath);

            $this->assertSame($outputPath, $path);
            $this->assertFileExists($path);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));

            try {
                $mimetype = $zip->getFromName('mimetype');
                $this->assertSame('application/epub+zip', $mimetype);

                $contents = $this->readArchiveText($zip);
                $this->assertStringContainsString('<strong>world</strong>', $contents);
                $this->assertStringContainsString('বন্ধন', $contents);
                $this->assertStringContainsString('Papyrus fixture', $contents);
            } finally {
                $zip->close();
            }
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
            rmdir($outputDir);
        }
    }

    private function readArchiveText(ZipArchive $zip): string
    {
        $text = '';

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || (! str_ends_with(strtolower($name), '.html') && ! str_ends_with(strtolower($name), '.xhtml'))) {
                continue;
            }

            $chunk = $zip->getFromIndex($index);
            $text .= is_string($chunk) ? $chunk : '';
        }

        return $text;
    }
}
