<?php

declare(strict_types=1);

namespace Milon\Papyrus\Lint;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CodeFenceLinter
{
    public function __construct(
        private readonly int $maxWidth = 66,
    ) {}

    public function lintDirectory(string $contentDir, bool $fix = false): LintResult
    {
        $issues = [];
        $fixed = [];
        $skipped = [];

        foreach ($this->discoverMarkdownFiles($contentDir) as $file) {
            $result = $this->lintFile($file, $fix, $contentDir);
            $issues = [...$issues, ...$result->issues];
            $fixed = [...$fixed, ...$result->fixed];
            $skipped = [...$skipped, ...$result->skipped];
        }

        return new LintResult($issues, $fixed, $skipped);
    }

    public function lintFile(string $file, bool $fix = false, ?string $contentRoot = null): LintResult
    {
        $contentRoot ??= dirname($file);
        $original = file_get_contents($file);

        if ($original === false) {
            return new LintResult([
                new LintIssue($this->relativePath($contentRoot, $file), 0, 'Unable to read file.', 'error'),
            ]);
        }

        $updated = $original;
        $offset = 0;
        $issues = [];
        $fixed = [];
        $skipped = [];

        foreach ($this->findPhpFences($original) as $fence) {
            $body = $fence['body'];
            $lines = explode("\n", $body);

            [$lines, $removedOpenTag] = $this->fixOpenTag($lines);
            [$lines, $commentRunsFixed] = $this->fixCommentRuns($lines);

            foreach ($lines as $index => $line) {
                if (mb_strlen(rtrim($line)) > $this->maxWidth) {
                    $issues[] = new LintIssue(
                        file: $this->relativePath($contentRoot, $file),
                        line: $fence['startLine'] + $index,
                        message: sprintf('Line exceeds %d columns.', $this->maxWidth),
                    );
                }
            }

            $changed = $removedOpenTag || $commentRunsFixed > 0;

            if ($changed && ! $this->signaturesMatch($body, implode("\n", $lines))) {
                $skipped[] = sprintf(
                    '%s:%d (signature mismatch)',
                    $this->relativePath($contentRoot, $file),
                    $fence['startLine'],
                );
                $changed = false;
            }

            if ($changed) {
                $fixed[] = sprintf(
                    '%s:%d%s%s',
                    $this->relativePath($contentRoot, $file),
                    $fence['startLine'],
                    $removedOpenTag ? ' removed <?php tag' : '',
                    $commentRunsFixed > 0 ? " converted {$commentRunsFixed} comment run(s)" : '',
                );

                if ($fix) {
                    $newBody = implode("\n", $lines);
                    $updated = substr_replace(
                        $updated,
                        $newBody,
                        $fence['bodyStart'] + $offset,
                        $fence['bodyLength'],
                    );
                    $offset += strlen($newBody) - $fence['bodyLength'];
                }
            }
        }

        if ($fix && $updated !== $original) {
            file_put_contents($file, $updated);
        }

        return new LintResult($issues, $fixed, $skipped);
    }

    /**
     * @return list<string>
     */
    private function discoverMarkdownFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'md') {
                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files, SORT_NATURAL);

        return $files;
    }

    private function relativePath(string $root, string $file): string
    {
        $root = rtrim($root, '/');

        return ltrim(str_replace($root, '', $file), '/');
    }

    /**
     * @return list<array{body: string, bodyStart: int, bodyLength: int, startLine: int}>
     */
    private function findPhpFences(string $markdown): array
    {
        $fences = [];

        if (! preg_match_all(
            '/^```php\n(.*?)^```$/ms',
            $markdown,
            $matches,
            PREG_OFFSET_CAPTURE,
        )) {
            return $fences;
        }

        foreach ($matches[1] as $match) {
            [$body, $byteOffset] = $match;
            $startLine = 1 + substr_count(substr($markdown, 0, $byteOffset), "\n");
            $trimmedBody = rtrim($body, "\n");

            $fences[] = [
                'body' => $trimmedBody,
                'bodyStart' => $byteOffset,
                'bodyLength' => strlen($trimmedBody),
                'startLine' => $startLine,
            ];
        }

        return $fences;
    }

    /**
     * @param  list<string>  $lines
     * @return array{0: list<string>, 1: bool}
     */
    private function fixOpenTag(array $lines): array
    {
        if (! isset($lines[0]) || trim($lines[0]) !== '<?php') {
            return [$lines, false];
        }

        array_shift($lines);

        if (isset($lines[0]) && trim($lines[0]) === '') {
            array_shift($lines);
        }

        return [$lines, true];
    }

    /**
     * @param  list<string>  $lines
     * @return array{0: list<string>, 1: int}
     */
    private function fixCommentRuns(array $lines): array
    {
        $result = [];
        $runsFixed = 0;
        $index = 0;
        $count = count($lines);

        while ($index < $count) {
            $run = [];
            $indent = null;

            for ($cursor = $index; $cursor < $count; $cursor++) {
                if (! preg_match('/^(\s*)\/\/\s?(.*)$/', $lines[$cursor], $match)) {
                    break;
                }

                if ($indent === null) {
                    $indent = $match[1];
                } elseif ($match[1] !== $indent) {
                    break;
                }

                $run[] = $match[2];
            }

            if (count($run) >= 2) {
                $result[] = $indent.'/*';
                foreach ($run as $text) {
                    $result[] = rtrim($indent.' * '.$text);
                }
                $result[] = $indent.' */';
                $index = $cursor;
                $runsFixed++;

                continue;
            }

            $result[] = $lines[$index];
            $index++;
        }

        return [$result, $runsFixed];
    }

    private function signaturesMatch(string $before, string $after): bool
    {
        return $this->tokenSignature($before) === $this->tokenSignature($after);
    }

    /**
     * @return list<array{0: int, 1: string}>
     */
    private function tokenSignature(string $body): array
    {
        $hasOpenTag = str_starts_with(ltrim($body), '<?php');
        $source = $hasOpenTag ? $body : "<?php\n".$body;

        $skip = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG];
        $signature = [];

        foreach (token_get_all($source) as $token) {
            if (! is_array($token)) {
                $signature[] = [0, $token];

                continue;
            }

            if (in_array($token[0], $skip, true)) {
                continue;
            }

            $signature[] = [$token[0], $token[1]];
        }

        return $signature;
    }
}
