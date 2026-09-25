<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\InitCommand;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Stubs\StubRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InitCommandTest extends TestCase
{
    public function test_init_scaffolds_book_tree(): void
    {
        $target = sys_get_temp_dir().'/papyrus-init-'.uniqid('', true);

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute(['--dir' => $target]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target.'/papyrus.php');
        $this->assertFileExists($target.'/content/01-introduction.md');
        $this->assertDirectoryExists($target.'/assets');
        $this->assertFileDoesNotExist($target.'/assets/theme-light.html');
        $this->assertFileDoesNotExist($target.'/assets/theme-html.html');
        $this->assertFileDoesNotExist($target.'/assets/fonts/LinLibertine_R.ttf');
        $this->assertFileDoesNotExist($target.'/assets/fonts/0xProto-Regular.ttf');

        $this->removeDir($target);
    }

    public function test_init_can_scaffold_yml_config(): void
    {
        $target = sys_get_temp_dir().'/papyrus-init-yml-'.uniqid('', true);

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute(['--dir' => $target, '--format' => 'yml']);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target.'/papyrus.yml');
        $this->assertFileDoesNotExist($target.'/papyrus.php');

        $project = Project::load($target);
        $this->assertSame('My Book', $project->title());

        $this->removeDir($target);
    }

    public function test_init_accepts_yaml_alias_for_yml(): void
    {
        $target = sys_get_temp_dir().'/papyrus-init-yaml-'.uniqid('', true);

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute(['--dir' => $target, '--format' => 'yaml']);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target.'/papyrus.yml');
        $this->assertFileDoesNotExist($target.'/papyrus.yaml');

        $project = Project::load($target);
        $this->assertSame('My Book', $project->title());

        $this->removeDir($target);
    }

    public function test_init_can_scaffold_json_config(): void
    {
        $target = sys_get_temp_dir().'/papyrus-init-json-'.uniqid('', true);

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute(['--dir' => $target, '--format' => 'json']);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target.'/papyrus.json');
        $this->assertFileDoesNotExist($target.'/papyrus.php');

        $project = Project::load($target);
        $this->assertSame('My Book', $project->title());

        $this->removeDir($target);
    }

    public function test_init_docs_preset_scaffolds_yml_site(): void
    {
        $target = sys_get_temp_dir().'/papyrus-init-docs-'.uniqid('', true);
        mkdir($target);
        file_put_contents($target.'/composer.json', json_encode([
            'name' => 'milon/barcode',
            'description' => 'Barcode generator for PHP and Laravel',
            'support' => ['source' => 'https://github.com/milon/barcode'],
        ], JSON_THROW_ON_ERROR));

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute([
            '--dir' => $target.'/docs',
            '--preset' => 'docs',
        ]);

        $this->assertSame(0, $exitCode);
        $docs = $target.'/docs';
        $this->assertFileExists($docs.'/papyrus.yml');
        $this->assertFileDoesNotExist($docs.'/papyrus.php');
        $this->assertFileExists($docs.'/content/00-welcome.md');
        $this->assertFileExists($docs.'/content/01-install.md');
        $this->assertFileExists($docs.'/content/02-usage.md');
        $this->assertFileExists($docs.'/content/03-api.md');
        $this->assertFileExists($docs.'/content/04-changelog.md');
        $this->assertFileExists($docs.'/github/workflows/docs-site.yml');
        $this->assertFileDoesNotExist($docs.'/content/01-introduction.md');

        $project = Project::load($docs);
        $this->assertSame('Barcode', $project->title());
        $this->assertSame('Barcode generator for PHP and Laravel', $project->siteLead());
        $this->assertSame('/barcode', $project->siteBasePath());
        $this->assertSame('https://github.com/milon/barcode', $project->siteRepository());
        $this->assertSame('docs/content', $project->siteEditPath());
        $this->assertTrue($project->siteEditLinkEnabled());

        $yml = file_get_contents($docs.'/papyrus.yml');
        $this->assertIsString($yml);
        $this->assertStringContainsString('repository:', $yml);
        $this->assertStringContainsString('edit:', $yml);
        $this->assertStringContainsString('path: docs/content', $yml);
        $this->assertStringContainsString('link: true', $yml);
        $this->assertStringNotContainsString('mode:', $yml);
        $this->assertStringContainsString('build:site -e docs', (string) file_get_contents($docs.'/github/workflows/docs-site.yml'));
        $this->assertStringNotContainsString('kdp:', $yml);
        $this->assertStringNotContainsString('sample:', $yml);

        $welcome = file_get_contents($docs.'/content/00-welcome.md');
        $this->assertIsString($welcome);
        $this->assertStringContainsString('Barcode', $welcome);
        $this->assertStringNotContainsString('{{title}}', $welcome);

        $this->removeDir($target);
    }

    public function test_init_docs_preset_respects_format_override(): void
    {
        $target = sys_get_temp_dir().'/papyrus-init-docs-json-'.uniqid('', true);

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute([
            '--dir' => $target,
            '--preset' => 'docs',
            '--format' => 'json',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($target.'/papyrus.json');
        $this->assertFileDoesNotExist($target.'/papyrus.yml');

        $this->removeDir($target);
    }

    public function test_stub_repository_lists_book_files(): void
    {
        $repo = StubRepository::default();
        $files = $repo->bookFiles();

        $this->assertContains('papyrus.php', $files);
        $this->assertContains('content/01-introduction.md', $files);
        $this->assertNotContains('assets/theme-light.html', $files);
        $this->assertNotContains('presets/docs/content/00-welcome.md', $files);
    }

    public function test_stub_repository_lists_docs_preset_files(): void
    {
        $repo = StubRepository::default();
        $files = $repo->presetFiles('docs');

        $this->assertContains('content/00-welcome.md', $files);
        $this->assertContains('github/workflows/docs-site.yml', $files);
        $this->assertNotContains('papyrus.php', $files);
    }

    public function test_stub_repository_lists_publishable_assets(): void
    {
        $repo = StubRepository::default();
        $files = $repo->assetFiles();

        $this->assertContains('theme-light.html', $files);
        $this->assertContains('theme-html.html', $files);
        $this->assertContains('fonts/LinLibertine_R.ttf', $files);
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
