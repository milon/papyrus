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

    public function displayTitle(): string
    {
        $title = $this->title();

        if ($title !== '') {
            return $title;
        }

        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $this->html, $matches) === 1) {
            $fromHeading = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($fromHeading !== '') {
                return $fromHeading;
            }
        }

        return pathinfo($this->source, PATHINFO_FILENAME);
    }

    public function isDraft(): bool
    {
        return self::isTruthy($this->frontMatter['draft'] ?? false);
    }

    public function webSlug(): string
    {
        return pathinfo($this->source, PATHINFO_FILENAME);
    }

    public static function isTruthy(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
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
