<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\Kdp\KdpCommand;
use Milon\Papyrus\Commands\Kdp\KdpMetadataCommand;
use Milon\Papyrus\Commands\Kdp\KdpPrintCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class KdpCommandTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function kdp_exits_zero_for_mini_book(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for KDP print PDF');
        }

        $tester = new CommandTester(new KdpCommand);
        $exitCode = $tester->execute(['--dir' => $this->fixtureDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('mini-book-kdp.epub', $tester->getDisplay());
        $this->assertStringContainsString('mini-book-kdp-print.pdf', $tester->getDisplay());
        $this->assertStringContainsString('mini-book-kdp-metadata.json', $tester->getDisplay());
    }

    #[Test]
    public function kdp_print_exits_zero_for_mini_book(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for KDP print PDF');
        }

        $tester = new CommandTester(new KdpPrintCommand);
        $exitCode = $tester->execute([
            '--dir' => $this->fixtureDir,
            '--theme' => 'light',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('mini-book-kdp-print.pdf', $tester->getDisplay());
    }

    #[Test]
    public function kdp_metadata_exits_zero_for_mini_book(): void
    {
        $tester = new CommandTester(new KdpMetadataCommand);
        $exitCode = $tester->execute(['--dir' => $this->fixtureDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('mini-book-kdp-metadata.json', $tester->getDisplay());
    }
}
