<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Markdown\HeadingAnchors;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HeadingAnchorsTest extends TestCase
{
    #[Test]
    public function it_adds_ids_and_permalinks_to_h2(): void
    {
        [$html, $headings] = HeadingAnchors::process('<h1>Chapter</h1><h2>Options</h2><p>Body</p><h2>Options</h2>');

        $this->assertStringContainsString('<h2 id="options"><a class="heading-permalink" href="#options"', $html);
        $this->assertStringContainsString('<h2 id="options-2"><a class="heading-permalink" href="#options-2"', $html);
        $this->assertStringNotContainsString('heading-permalink', explode('</h1>', $html, 2)[0]);
        $this->assertSame([
            ['id' => 'options', 'title' => 'Options'],
            ['id' => 'options-2', 'title' => 'Options'],
        ], $headings);
    }

    #[Test]
    public function it_preserves_existing_ids_and_permalinks(): void
    {
        $source = '<h2 id="custom"><a class="heading-permalink" href="#custom">#</a>Already</h2>';
        [$html, $headings] = HeadingAnchors::process($source);

        $this->assertSame($source, $html);
        $this->assertSame([['id' => 'custom', 'title' => 'Already']], $headings);
    }
}
