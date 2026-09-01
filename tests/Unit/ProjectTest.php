<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\Project;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    public function test_loads_mini_book_config(): void
    {
        $project = Project::load($this->fixtureDir);

        $this->assertSame('Mini Book', $project->title());
        $this->assertSame('Papyrus fixture', $project->subtitle());
        $this->assertSame('Papyrus', $project->author());
        $this->assertSame(['light', 'dark'], $project->themes());
        $this->assertDirectoryExists($project->contentDir);
        $this->assertDirectoryExists($project->assetsDir);
    }

    public function test_missing_config_throws(): void
    {
        $this->expectException(ConfigException::class);

        Project::load(sys_get_temp_dir());
    }
}
