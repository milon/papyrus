<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\WrapCoverRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WrapCoverRendererTest extends TestCase
{
    #[Test]
    public function wrap_cover_writes_pdf_and_png_preview(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required');
        }

        $root = sys_get_temp_dir().'/papyrus-wrap-'.uniqid('', true);
        $assets = $root.'/assets';
        $export = $root.'/export';
        $content = $root.'/content';
        mkdir($assets, 0755, true);
        mkdir($export, 0755, true);
        mkdir($content, 0755, true);

        $cover = $assets.'/cover.png';
        $image = imagecreatetruecolor(600, 900);
        $this->assertNotFalse($image);
        $red = imagecolorallocate($image, 180, 40, 40);
        $this->assertNotFalse($red);
        imagefilledrectangle($image, 0, 0, 600, 900, $red);
        imagepng($image, $cover);
        imagedestroy($image);

        file_put_contents($content.'/01-chapter.md', "---\ntitle: Chapter\n---\n\nHello.\n");
        file_put_contents($root.'/papyrus.php', <<<'PHP'
<?php
return [
    'title' => 'Wrap Test',
    'author' => 'Tester',
    'themes' => ['light'],
    'document' => [
        'size' => '6x9',
        'margin_left' => 12.7,
        'margin_right' => 12.7,
        'margin_top' => 12.7,
        'margin_bottom' => 12.7,
    ],
    'cover' => ['image' => 'cover.png'],
    'kdp' => [
        'print' => [
            'enabled' => true,
            'paper' => 'white',
            'spine_color' => '#222222',
        ],
        'metadata' => [
            'description' => 'A short blurb for the generated back cover.',
        ],
    ],
];
PHP);

        try {
            $project = Project::load($root)->withExportDir($export);
            $paths = (new WrapCoverRenderer($project))->render(120, 'light');

            $this->assertCount(2, $paths);
            $this->assertFileExists($export.'/wrap-test-kdp-print-wrap.pdf');
            $this->assertFileExists($export.'/wrap-test-kdp-print-wrap.png');
            $this->assertGreaterThan(1000, filesize($export.'/wrap-test-kdp-print-wrap.pdf'));
            $this->assertGreaterThan(1000, filesize($export.'/wrap-test-kdp-print-wrap.png'));
        } finally {
            foreach ([$export.'/wrap-test-kdp-print-wrap.pdf', $export.'/wrap-test-kdp-print-wrap.png', $cover, $content.'/01-chapter.md', $root.'/papyrus.php'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            @rmdir($export);
            @rmdir($assets);
            @rmdir($content);
            @rmdir($root);
        }
    }
}
