<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Markdown\HtmlPostProcessor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HtmlPostProcessorTest extends TestCase
{
    #[Test]
    public function it_converts_explicit_break_marker(): void
    {
        $html = (new HtmlPostProcessor)->process('<p>Before</p>[break]<p>After</p>', 0);

        $this->assertStringContainsString('page-break-after: always', $html);
        $this->assertStringNotContainsString('[break]', $html);
    }

    #[Test]
    public function it_inserts_break_before_h1_on_later_chapters(): void
    {
        $html = (new HtmlPostProcessor(breakLevel: 1))->process('<h1>Chapter</h1>', 1);

        $this->assertStringContainsString('page-break-after: always', $html);
        $this->assertMatchesRegularExpression('/page-break-after.*<h1>/s', $html);
    }

    #[Test]
    public function it_skips_h1_break_on_first_chapter(): void
    {
        $html = (new HtmlPostProcessor(breakLevel: 1))->process('<h1>Chapter</h1>', 0);

        $this->assertSame('<h1>Chapter</h1>', $html);
    }

    #[Test]
    public function it_leaves_break_marker_inside_code_untouched(): void
    {
        $html = (new HtmlPostProcessor)->process(
            '<p>See <code>[break]</code> in docs.</p><pre><code>[break]</code></pre>',
            0,
        );

        $this->assertStringContainsString('<code>[break]</code>', $html);
        $this->assertStringContainsString('<pre><code>[break]</code></pre>', $html);
        $this->assertStringNotContainsString('page-break-after', $html);
    }
}
