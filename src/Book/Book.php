<?php

declare(strict_types=1);

namespace Milon\Papyrus\Book;

final class Book
{
    /**
     * @param  list<Chapter>  $chapters
     */
    public function __construct(
        public readonly array $chapters,
    ) {}

    /**
     * @return list<Chapter>
     */
    public function preToc(): array
    {
        return array_values(array_filter(
            $this->chapters,
            fn (Chapter $chapter): bool => $chapter->pretoc,
        ));
    }

    /**
     * @return list<Chapter>
     */
    public function body(): array
    {
        return array_values(array_filter(
            $this->chapters,
            fn (Chapter $chapter): bool => ! $chapter->pretoc,
        ));
    }
}
