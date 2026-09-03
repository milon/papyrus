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

    #[Test]
    public function it_can_publish_only_selected_asset_groups(): void
    {
        $target = sys_get_temp_dir().'/papyrus-assets-only-'.uniqid('', true);

        try {
            (new CommandTester(new InitCommand))->execute(['--dir' => $target]);

            $tester = new CommandTester(new AssetPublishCommand);
            $exitCode = $tester->execute([
                '--dir' => $target,
                '--only' => 'themes,css',
            ]);

            $this->assertSame(0, $exitCode);
            $this->assertFileExists($target.'/assets/theme-light.html');
            $this->assertFileExists($target.'/assets/theme-html.html');
            $this->assertFileExists($target.'/assets/style.css');
            $this->assertFileDoesNotExist($target.'/assets/fonts/LinLibertine_R.ttf');
        } finally {
            $this->removeDir($target);
        }
    }

    #[Test]
    public function it_rejects_unknown_only_groups(): void
    {
        $target = sys_get_temp_dir().'/papyrus-assets-bad-'.uniqid('', true);

        try {
            (new CommandTester(new InitCommand))->execute(['--dir' => $target]);

            $tester = new CommandTester(new AssetPublishCommand);
            $exitCode = $tester->execute([
                '--dir' => $target,
                '--only' => 'themes,images',
            ]);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Unknown asset group(s): images', $tester->getDisplay());
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
