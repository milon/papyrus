<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\AssetPublishCommand;
use Milon\Papyrus\Commands\InitCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class AssetPublishCommandTest extends TestCase
{
    #[Test]
    public function it_publishes_bundled_assets_into_the_book_directory(): void
    {
        $target = sys_get_temp_dir().'/papyrus-assets-'.uniqid('', true);

        try {
            (new CommandTester(new InitCommand))->execute(['--dir' => $target]);

            $this->assertFileDoesNotExist($target.'/assets/theme-light.html');

            $tester = new CommandTester(new AssetPublishCommand);
            $exitCode = $tester->execute(['--dir' => $target]);

            $this->assertSame(0, $exitCode);
            $this->assertFileExists($target.'/assets/theme-light.html');
            $this->assertFileExists($target.'/assets/theme-html.html');
            $this->assertFileExists($target.'/assets/fonts/LinLibertine_R.ttf');
        } finally {
            $this->removeDir($target);
        }
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
