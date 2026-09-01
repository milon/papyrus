<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\MigrateIbisCommand;
use Milon\Papyrus\Commands\SizesCommand;
use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Migration\IbisMigrator;
use Milon\Papyrus\Migration\ThemeMigrator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class MigrateIbisCommandTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/ibis-book';
    }

    #[Test]
    public function migrator_converts_ibis_config_to_papyrus_shape(): void
    {
        /** @var array<string, mixed> $ibis */
        $ibis = require $this->fixtureDir.'/ibis.php';
        $config = (new IbisMigrator)->convert($ibis);

        $this->assertSame('Ibis Fixture Book', $config['title']);
        $this->assertSame('crown-quarto', $config['document']['size']);
        $this->assertSame(1, $config['toc']['h3']);
        $this->assertSame('cover.png', $config['cover']['image']);
        $this->assertSame([['from' => 1, 'to' => 4]], $config['sample']['ranges']);
        $this->assertCount(3, $config['fonts']['faces']);
        $this->assertSame(
            ['bn', 'ben', 'bengali'],
            $config['fonts']['script'][0]['match'],
        );
    }

    #[Test]
    public function theme_migrator_replaces_ibis_toc_marker(): void
    {
        $themePath = sys_get_temp_dir().'/papyrus-theme-'.uniqid('', true).'.html';
        file_put_contents($themePath, "<!-- IBIS:TOC -->\n");

        try {
            $this->assertTrue((new ThemeMigrator)->migrateFile($themePath));
            $this->assertStringContainsString('<!-- PAPYRUS:TOC -->', (string) file_get_contents($themePath));
        } finally {
            unlink($themePath);
        }
    }

    #[Test]
    public function migrate_ibis_command_writes_papyrus_php(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-migrate-'.uniqid('', true);
        mkdir($dir);
        mkdir($dir.'/assets');
        copy($this->fixtureDir.'/ibis.php', $dir.'/ibis.php');
        copy($this->fixtureDir.'/assets/theme-light.html', $dir.'/assets/theme-light.html');

        try {
            $tester = new CommandTester(new MigrateIbisCommand);
            $exitCode = $tester->execute(['--dir' => $dir]);

            $this->assertSame(0, $exitCode);
            $this->assertFileExists($dir.'/papyrus.php');

            $project = Project::load($dir);
            $this->assertSame('Ibis Fixture Book', $project->title());
            $this->assertSame('crown-quarto', DocumentSize::resolvePresetName(
                $project->documentSize()->widthMm,
                $project->documentSize()->heightMm,
            ));

            $theme = file_get_contents($dir.'/assets/theme-light.html');
            $this->assertStringContainsString('<!-- PAPYRUS:TOC -->', (string) $theme);
        } finally {
            $this->removeDirectory($dir);
        }
    }

    #[Test]
    public function sizes_command_lists_kdp_presets(): void
    {
        $tester = new CommandTester(new SizesCommand);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('crown-quarto', $display);
        $this->assertStringContainsString('6x9', $display);
        $this->assertStringContainsString('188.976', $display);
    }

    #[Test]
    public function resolve_preset_name_matches_crown_quarto_dimensions(): void
    {
        $this->assertSame('crown-quarto', DocumentSize::resolvePresetName(188.976, 246.126));
    }

    private function removeDirectory(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $path) {
            if (! is_string($path)) {
                continue;
            }

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
