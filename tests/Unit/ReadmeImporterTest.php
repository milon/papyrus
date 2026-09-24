<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\ImportReadmeCommand;
use Milon\Papyrus\Import\ReadmeImporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ReadmeImporterTest extends TestCase
{
    #[Test]
    public function it_splits_readme_on_h2_headings(): void
    {
        $markdown = <<<'MD'
# Barcode

Intro paragraph.

## Installation

```bash
composer require milon/barcode
```

## Usage

Call `DNS1D::getBarcodeHTML()`.

## API

### One-dimensional

Code 39.
MD;

        $chapters = (new ReadmeImporter)->plan($markdown);

        $this->assertCount(4, $chapters);
        $this->assertSame('00-barcode.md', $chapters[0]['filename']);
        $this->assertSame('Barcode', $chapters[0]['title']);
        $this->assertStringContainsString('Intro paragraph.', $chapters[0]['body']);
        $this->assertSame('01-installation.md', $chapters[1]['filename']);
        $this->assertStringContainsString('composer require milon/barcode', $chapters[1]['body']);
        $this->assertSame('03-api.md', $chapters[3]['filename']);
        $this->assertStringContainsString('### One-dimensional', $chapters[3]['body']);
    }

    #[Test]
    public function it_ignores_hash_headings_inside_fences(): void
    {
        $markdown = <<<'MD'
## Real

Before.

```md
## Fake
```

After.
MD;

        $chapters = (new ReadmeImporter)->plan($markdown);

        $this->assertCount(1, $chapters);
        $this->assertSame('Real', $chapters[0]['title']);
        $this->assertStringContainsString('## Fake', $chapters[0]['body']);
    }

    #[Test]
    public function command_writes_chapters_from_readme(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-import-readme-'.uniqid('', true);
        mkdir($dir.'/content', 0755, true);
        file_put_contents($dir.'/README.md', "# Demo\n\n## Install\n\ncomposer require x\n\n## Usage\n\nUse it.\n");

        try {
            $tester = new CommandTester(new ImportReadmeCommand);
            $exit = $tester->execute(['--dir' => $dir]);

            $this->assertSame(0, $exit);
            $this->assertFileDoesNotExist($dir.'/content/00-demo.md');
            $this->assertFileExists($dir.'/content/00-install.md');
            $this->assertFileExists($dir.'/content/01-usage.md');

            $install = file_get_contents($dir.'/content/00-install.md');
            $this->assertIsString($install);
            $this->assertStringContainsString('title: Install', $install);
            $this->assertStringContainsString('composer require x', $install);
        } finally {
            $this->removeDir($dir);
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
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
