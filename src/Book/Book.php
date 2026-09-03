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

    /**
     * Select chapters in the given config order. Names may be a source path,
     * basename (`01-intro.md`), or stem (`01-intro`).
     *
     * @param  list<string>  $names
     * @return list<string> Missing names (empty when every name resolved)
     */
    public function missingChapterNames(array $names): array
    {
        $index = $this->chapterAliasIndex();
        $missing = [];

        foreach ($names as $name) {
            $key = strtolower(trim($name));

            if ($key === '' || ! isset($index[$key])) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    /**
     * @param  list<string>  $names
     */
    public function selectInOrder(array $names): self
    {
        $index = $this->chapterAliasIndex();
        $selected = [];
        $seen = [];

        foreach ($names as $name) {
            $key = strtolower(trim($name));
            $chapter = $index[$key] ?? null;

            if ($chapter === null) {
                continue;
            }

            $id = spl_object_id($chapter);

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $selected[] = $chapter;
        }

        return new self($selected);
    }

    /**
     * @return array<string, Chapter>
     */
    private function chapterAliasIndex(): array
    {
        $index = [];

        foreach ($this->chapters as $chapter) {
            foreach ($this->aliasesFor($chapter) as $alias) {
                $index[strtolower($alias)] = $chapter;
            }
        }

        return $index;
    }

    /**
     * @return list<string>
     */
    private function aliasesFor(Chapter $chapter): array
    {
        $source = ltrim(str_replace('\\', '/', $chapter->source), '/');
        $base = basename($source);
        $stem = pathinfo($base, PATHINFO_FILENAME);

        return array_values(array_unique(array_filter([
            $source,
            $base,
            $stem,
            $stem.'.md',
        ])));
    }
}
