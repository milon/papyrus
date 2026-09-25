<?php

declare(strict_types=1);

namespace Milon\Papyrus\Support;

/**
 * Resolve package-root paths for Composer installs and PHAR builds.
 */
final class PackagePaths
{
    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function stubs(): string
    {
        return self::root().'/stubs';
    }

    public static function stub(string $relative): string
    {
        return self::stubs().'/'.ltrim(str_replace('\\', '/', $relative), '/');
    }

    /**
     * CLI entry used to re-invoke Papyrus (e.g. parallel PDF workers).
     */
    public static function cliEntrypoint(): string
    {
        $phar = \Phar::running(false);

        if ($phar !== '') {
            return $phar;
        }

        return self::root().'/bin/papyrus';
    }

    public static function runningInPhar(): bool
    {
        return \Phar::running() !== '';
    }
}
