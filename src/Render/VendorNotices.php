<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render;

/**
 * Scoped error handler for the third-party rendering stack.
 *
 * mPDF and the PHPePub/PHPZip chain raise warnings and deprecations that no
 * call site of ours can avoid (mPDF returns an undefined $shift while applying
 * GSUB lookups, PHPZip measures a null stream path when finalising an archive).
 * They are cosmetic, but they bury the build output, so they are collected here
 * instead and replayed on demand with -v. Diagnostics from Papyrus itself, and
 * anything more severe than a warning, are left to the surrounding handler.
 */
final class VendorNotices
{
    private const HANDLED = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED;

    /** @var array<string, array{message: string, file: string, line: int, count: int}> */
    private static array $collected = [];

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function silence(callable $callback): mixed
    {
        $previous = null;

        $handler = static function (int $severity, string $message, string $file = '', int $line = 0) use (&$previous): bool {
            if ((self::HANDLED & $severity) === 0 || ! self::isThirdParty($file)) {
                return is_callable($previous)
                    ? (bool) $previous($severity, $message, $file, $line)
                    : false;
            }

            // Anything the library already silenced with @ stays out of the report.
            if ((error_reporting() & $severity) !== 0) {
                self::record($message, $file, $line);
            }

            return true;
        };

        $previous = set_error_handler($handler);

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Formatted one-line summaries of everything collected so far, newest last.
     *
     * @return list<string>
     */
    public static function flush(): array
    {
        $lines = [];

        foreach (self::$collected as $notice) {
            $lines[] = sprintf(
                '%s:%d  %s%s',
                self::shortPath($notice['file']),
                $notice['line'],
                $notice['message'],
                $notice['count'] > 1 ? sprintf(' (×%d)', $notice['count']) : '',
            );
        }

        self::$collected = [];

        return $lines;
    }

    private static function record(string $message, string $file, int $line): void
    {
        $key = $file.':'.$line.':'.$message;

        if (isset(self::$collected[$key])) {
            self::$collected[$key]['count']++;

            return;
        }

        self::$collected[$key] = [
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'count' => 1,
        ];
    }

    private static function isThirdParty(string $file): bool
    {
        $separator = DIRECTORY_SEPARATOR;

        if (str_starts_with($file, dirname(__DIR__).$separator)) {
            return false;
        }

        return str_contains($file, $separator.'vendor'.$separator);
    }

    private static function shortPath(string $file): string
    {
        $marker = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR;
        $position = strrpos($file, $marker);

        return $position === false
            ? $file
            : substr($file, $position + strlen($marker));
    }
}
