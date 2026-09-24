<?php

declare(strict_types=1);

namespace Milon\Papyrus\Import;

/**
 * Splits a README (or similar Markdown file) into Papyrus chapters on ## headings.
 */
final class ReadmeImporter
{
    /**
     * @return list<array{filename: string, title: string, body: string}>
     */
    public function plan(string $markdown): array
    {
        $sections = $this->splitSections($markdown);
        $chapters = [];
        $index = 0;

        foreach ($sections as $section) {
            $title = $section['title'];
            $body = trim($section['body']);

            if ($title === '' && $body === '') {
                continue;
            }

            if ($title === '') {
                $title = 'Welcome';
            }

            $filename = sprintf('%02d-%s.md', $index, $this->slug($title));
            $chapters[] = [
                'filename' => $filename,
                'title' => $title,
                'body' => $body,
            ];
            $index++;
        }

        return $chapters;
    }

    /**
     * @param  list<array{filename: string, title: string, body: string}>  $chapters
     * @return list<string> written relative paths under content/
     */
    public function write(string $contentDir, array $chapters, bool $force = false): array
    {
        if (! is_dir($contentDir) && ! mkdir($contentDir, 0o755, true) && ! is_dir($contentDir)) {
            throw new ImportException('Could not create content directory: '.$contentDir);
        }

        $written = [];

        foreach ($chapters as $chapter) {
            $path = $contentDir.'/'.$chapter['filename'];

            if (is_file($path) && ! $force) {
                throw new ImportException(sprintf(
                    'Refusing to overwrite existing chapter %s (use --force).',
                    $chapter['filename'],
                ));
            }

            $markdown = $this->formatChapter($chapter['title'], $chapter['body']);

            if (file_put_contents($path, $markdown) === false) {
                throw new ImportException('Could not write chapter: '.$path);
            }

            $written[] = 'content/'.$chapter['filename'];
        }

        return $written;
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    private function splitSections(string $markdown): array
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines = explode("\n", $markdown);
        $sections = [];
        $currentTitle = '';
        $currentBody = [];
        $inFence = false;

        foreach ($lines as $line) {
            if (preg_match('/^(`{3,}|~{3,})/', $line) === 1) {
                $inFence = ! $inFence;
                $currentBody[] = $line;

                continue;
            }

            if (! $inFence && preg_match('/^##\s+(.+?)\s*#*\s*$/', $line, $matches) === 1) {
                $sections[] = [
                    'title' => $currentTitle,
                    'body' => implode("\n", $currentBody),
                ];
                $currentTitle = trim($matches[1]);
                $currentBody = [];

                continue;
            }

            $currentBody[] = $line;
        }

        $sections[] = [
            'title' => $currentTitle,
            'body' => implode("\n", $currentBody),
        ];

        // Drop a leading empty preface when the file starts with # Title then ## soon after
        // and the preface is only an h1 / blank lines (common README shape).
        if ($sections !== [] && $sections[0]['title'] === '') {
            $preface = trim($sections[0]['body']);

            if ($preface === '' || preg_match('/^#\s+[^\n]+(?:\s*\n)*$/u', $preface) === 1) {
                array_shift($sections);
            } elseif (preg_match('/^#\s+([^\n]+?)\s*#*\s*\n([\s\S]*)$/u', $preface, $matches) === 1) {
                $sections[0]['title'] = trim($matches[1]);
                $sections[0]['body'] = $matches[2];
            }
        }

        return $sections;
    }

    private function formatChapter(string $title, string $body): string
    {
        $yamlTitle = $this->yamlScalar($title);
        $body = trim($body);
        $markdown = "---\ntitle: {$yamlTitle}\n---\n\n# {$title}\n";

        if ($body !== '') {
            $markdown .= "\n{$body}\n";
        } else {
            $markdown .= "\n";
        }

        return $markdown;
    }

    private function yamlScalar(string $value): string
    {
        if ($value === '' || preg_match('/[:#{}[\],&*?|>!%@`\'"\n]/', $value) === 1) {
            return "'".str_replace("'", "''", $value)."'";
        }

        return $value;
    }

    private function slug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'section';
    }
}
