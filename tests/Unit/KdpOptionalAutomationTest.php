<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\KdpPackageBuilder;
use Milon\Papyrus\Kdp\PrintCoverDimensions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class KdpOptionalAutomationTest extends TestCase
{
    #[Test]
    public function print_cover_dimensions_match_amazon_white_paper_formula(): void
    {
        $trim = new DocumentSize(152.4, 228.6, 12.7, 12.7, 12.7, 12.7);
        $dims = PrintCoverDimensions::calculate($trim, 200, 'white');

        $this->assertSame(200, $dims['page_count']);
        $this->assertSame('white', $dims['paper']);
        $this->assertSame(0.4504, $dims['spine_in']);
        $this->assertTrue($dims['spine_text_allowed']);
        $this->assertEqualsWithDelta(12.7004, $dims['wrap_width_in'], 0.0001);
        $this->assertEqualsWithDelta(9.25, $dims['wrap_height_in'], 0.0001);
    }

    #[Test]
    public function package_builder_zips_artifacts_and_checklist(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ext-zip is required');
        }

        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $export = sys_get_temp_dir().'/papyrus-kdp-pkg-'.uniqid('', true);
        mkdir($export);

        file_put_contents($export.'/mini-book-kdp.epub', 'epub');
        file_put_contents($export.'/mini-book-kdp-print.pdf', '%PDF');
        file_put_contents($export.'/mini-book-kdp-metadata.json', '{}');

        try {
            $project = Project::load($fixture)->withExportDir($export);
            $path = (new KdpPackageBuilder($project))->build();

            $this->assertFileExists($path);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path) === true);
            $names = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $names[] = $zip->getNameIndex($i);
            }

            $zip->close();

            $this->assertContains('mini-book-kdp.epub', $names);
            $this->assertContains('mini-book-kdp-print.pdf', $names);
            $this->assertContains('mini-book-kdp-metadata.json', $names);
            $this->assertContains('KDP-CHECKLIST.txt', $names);
        } finally {
            foreach (glob($export.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($export);
        }
    }
}
