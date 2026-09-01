<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Console\Styles;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class StylesTest extends TestCase
{
    #[Test]
    public function header_renders_rounded_box_and_subtitle(): void
    {
        $output = new BufferedOutput;
        $output->setDecorated(true);

        Styles::header($output, 'Papyrus', 'New book project');

        $display = $output->fetch();
        $this->assertStringContainsString('╭', $display);
        $this->assertStringContainsString('Papyrus', $display);
        $this->assertStringContainsString('New book project', $display);
    }
}
