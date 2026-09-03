<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\BuildCommand;
use Milon\Papyrus\Commands\SampleCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class BuildCommandTest extends TestCase
{
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__).'/fixtures/mini-book';
    }

    #[Test]
    public function build_exits_zero_for_mini_book(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for PDF build');
        }

        $tester = new CommandTester(new BuildCommand);
        $exitCode = $tester->execute(['--dir' => $this->fixtureDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('mini-book-light.pdf', $tester->getDisplay());
        $this->assertStringContainsString('mini-book-dark.pdf', $tester->getDisplay());
        $this->assertStringContainsString('mini-book.epub', $tester->getDisplay());
        $this->assertStringContainsString('mini-book.html', $tester->getDisplay());
        $this->assertStringContainsString('mini-book-kdp.epub', $tester->getDisplay());
        $this->assertStringContainsString('mini-book-kdp-print.pdf', $tester->getDisplay());
        $this->assertStringContainsString('mini-book-kdp-metadata.json', $tester->getDisplay());
        $this->assertStringNotContainsString('mini-book-site', $tester->getDisplay());
        $this->assertStringNotContainsString('sample-mini-book-', $tester->getDisplay());
    }

    #[Test]
    public function build_with_site_and_sample_opt_in_flags(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for PDF build');
        }

        $tester = new CommandTester(new BuildCommand);
        $exitCode = $tester->execute([
            '--dir' => $this->fixtureDir,
            '--with-site' => true,
            '--with-sample' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('mini-book-site', $display);
        $this->assertStringContainsString('sample-mini-book-light.pdf', $display);
        $this->assertStringContainsString('sample-mini-book-dark.pdf', $display);
    }

    #[Test]
    public function sample_exits_zero_for_mini_book(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('ext-gd is required for sample PDF build');
        }

        $tester = new CommandTester(new SampleCommand);
        $exitCode = $tester->execute([
            '--dir' => $this->fixtureDir,
            '--theme' => 'light',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('sample-mini-book-light.pdf', $tester->getDisplay());
    }
}
