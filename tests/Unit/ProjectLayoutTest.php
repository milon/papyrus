<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectLayoutTest extends TestCase
{
    #[Test]
    public function cover_image_prefers_theme_specific_file(): void
    {
        $project = Project::load(dirname(__DIR__).'/fixtures/mini-book');

        $this->assertSame('cover-light.png', $project->coverImageForTheme('light'));
        $this->assertSame('cover-dark.png', $project->coverImageForTheme('dark'));
        $this->assertSame('cover.png', $project->coverImageForTheme('print'));
    }
}
