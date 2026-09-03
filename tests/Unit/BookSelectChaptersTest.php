<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Book\Book;
use Milon\Papyrus\Book\Chapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BookSelectChaptersTest extends TestCase
{
    #[Test]
    public function it_selects_chapters_by_filename_aliases_in_config_order(): void
    {
        $book = new Book([
            new Chapter('00-copyright.md', '/tmp/00-copyright.md', ['title' => 'Copy'], '<p>c</p>', true),
            new Chapter('01-chapter-one.md', '/tmp/01-chapter-one.md', ['title' => 'One'], '<p>1</p>', false),
            new Chapter('02-chapter-two.md', '/tmp/02-chapter-two.md', ['title' => 'Two'], '<p>2</p>', false),
        ]);

        $selected = $book->selectInOrder([
            '02-chapter-two',
            '01-chapter-one.md',
            '02-chapter-two.md',
        ]);

        $this->assertSame(
            ['02-chapter-two.md', '01-chapter-one.md'],
            array_map(fn (Chapter $chapter): string => $chapter->source, $selected->chapters),
        );
        $this->assertSame([], $book->missingChapterNames(['01-chapter-one', '02-chapter-two.md']));
        $this->assertSame(['missing.md'], $book->missingChapterNames(['missing.md', '01-chapter-one']));
    }
}
