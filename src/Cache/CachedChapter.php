<?php

declare(strict_types=1);

namespace Milon\Papyrus\Cache;

final class CachedChapter
{
    /**
     * @param  array<string, mixed>  $frontMatter
     */
    public function __construct(
        public readonly string $rawHtml,
        public readonly array $frontMatter,
        public readonly bool $pretoc,
    ) {}
}
