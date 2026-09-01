<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Console\Application;
use Milon\Papyrus\Console\Banner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

final class BannerTest extends TestCase
{
    #[Test]
    public function banner_contains_papyrus_figlet_lines(): void
    {
        $lines = Banner::lines();

        $this->assertNotEmpty($lines);
        $this->assertStringContainsString('___', $lines[0]);
        $this->assertStringContainsString('|_|', $lines[array_key_last($lines)]);
    }

    #[Test]
    public function banner_contains_scroll_logo(): void
    {
        $lines = Banner::logoLines();

        $this->assertNotEmpty($lines);
        $this->assertStringContainsString('╭', $lines[0]);
        $this->assertStringContainsString('▓', implode("\n", $lines));
    }

    #[Test]
    public function list_command_renders_banner(): void
    {
        $application = new Application;
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);
        $tester->run(['command' => 'list', '--ansi' => true]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('___', $display);
        $this->assertStringContainsString('Papyrus', $display);
        $this->assertStringContainsString(Banner::TAGLINE, $display);
        $this->assertStringContainsString('╭', $display);
    }

    #[Test]
    public function build_command_does_not_render_banner(): void
    {
        $application = new Application;
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'build',
            '--dir' => dirname(__DIR__).'/fixtures/mini-book',
            '--no-interaction' => true,
        ]);

        $this->assertStringNotContainsString('| |_)  / /\\', $tester->getDisplay());
    }
}
