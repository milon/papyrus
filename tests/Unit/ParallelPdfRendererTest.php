<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Pdf\ParallelPdfRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParallelPdfRendererTest extends TestCase
{
    #[Test]
    public function parallel_renderer_builds_all_themes(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for PDF build');
        }

        $fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
        $project = Project::load($fixtureDir);
        $binary = dirname(__DIR__, 2).'/bin/papyrus';

        $results = (new ParallelPdfRenderer($project, $binary))->render(['light', 'dark']);

        $this->assertCount(2, $results);

        foreach ($results as $theme => $path) {
            $this->assertIsString($path, $theme);
            $this->assertFileExists($path);
        }
    }
}
