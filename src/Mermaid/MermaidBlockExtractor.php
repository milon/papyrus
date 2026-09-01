<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

final class MermaidBlockExtractor
{
    /**
     * @return list<array{line: int, body: string}>
     */
    public static function fromMarkdown(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $blocks = [];
        $inBlock = false;
        $startLine = 0;
        $body = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            if (! $inBlock && preg_match('/^```\s*mermaid\s*$/i', $line) === 1) {
                $inBlock = true;
                $startLine = $lineNumber;
                $body = [];

                continue;
            }

            if ($inBlock && preg_match('/^```\s*$/', $line) === 1) {
                $blocks[] = [
                    'line' => $startLine,
                    'body' => implode("\n", $body),
                ];
                $inBlock = false;

                continue;
            }

            if ($inBlock) {
                $body[] = $line;
            }
        }

        if ($inBlock) {
            throw new MermaidException(sprintf('Unclosed mermaid fence starting at line %d', $startLine));
        }

        return $blocks;
    }
}
