<?php

declare(strict_types=1);

namespace Milon\Papyrus\Book;

final class Chapter
{
    /**
     * @param  array<string, mixed>  $frontMatter
     */
    public function __construct(
        public readonly string $source,
        public readonly string $path,
        public readonly array $frontMatter,
        public readonly string $html,
        public readonly bool $pretoc,
    ) {}

    public function title(): string
    {
        $title = $this->frontMatter['title'] ?? '';

        return is_string($title) ? $title : '';
    }

    /**
     * @return list<string>
     */
    public function imageReferences(): array
    {
        $markdown = file_get_contents($this->path);

        if ($markdown === false || ! preg_match_all('/!\[.*?\]\((.*?)\)/', $markdown, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $matches[1])));
    }
}
