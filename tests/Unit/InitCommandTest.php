<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\InitCommand;
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

    public function test_stub_repository_lists_book_files(): void
    {
        $repo = StubRepository::default();
        $files = $repo->bookFiles();

        $this->assertContains('papyrus.php', $files);
        $this->assertContains('content/01-introduction.md', $files);
        $this->assertNotContains('assets/theme-light.html', $files);
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
