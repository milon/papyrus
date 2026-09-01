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
    public function it_transforms_legacy_callout_blockquotes(): void
    {
        $processor = new HtmlPostProcessor;

        $notice = $processor->process("<blockquote>\n<p>{notice} Be careful.</p>\n</blockquote>", 0);
        $warning = $processor->process("<blockquote>\n<p>{warning} Stop.</p>\n</blockquote>", 0);
        $githubNote = $processor->process("<blockquote>\n<p>[!NOTE] Tip here.</p>\n</blockquote>", 0);

        $this->assertStringContainsString("class='notice'", $notice);
        $this->assertStringContainsString('<strong>Notice:</strong>', $notice);
        $this->assertStringContainsString("class='warning'", $warning);
        $this->assertStringContainsString('<strong>Warning:</strong>', $warning);
        $this->assertStringContainsString('<strong>Note:</strong>', $githubNote);
    }
}
