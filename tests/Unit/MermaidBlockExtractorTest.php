<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Mermaid\MermaidBlockExtractor;
use Milon\Papyrus\Mermaid\MermaidException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MermaidBlockExtractorTest extends TestCase
{
    #[Test]
    public function it_extracts_mermaid_fences_with_line_numbers(): void
    {
        $markdown = <<<'MD'
# Title

```mermaid
flowchart TD
  A --> B
```

Text
MD;

        $blocks = MermaidBlockExtractor::fromMarkdown($markdown);

        $this->assertCount(1, $blocks);
        $this->assertSame(3, $blocks[0]['line']);
        $this->assertStringContainsString('flowchart TD', $blocks[0]['body']);
    }

    #[Test]
    public function unclosed_fence_throws(): void
    {
        $this->expectException(MermaidException::class);

        MermaidBlockExtractor::fromMarkdown("```mermaid\nA --> B\n");
    }
}
