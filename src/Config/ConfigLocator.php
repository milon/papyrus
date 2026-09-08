<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class ConfigLocator
{
    /**
     * @var list<string>
     */
    public const CANDIDATES = [
        'papyrus.php',
        'papyrus.yml',
        'papyrus.yaml',
        'papyrus.json',
    ];

    public static function discover(string $dir): string
    {
        $found = [];

        foreach (self::CANDIDATES as $name) {
            $path = $dir.'/'.$name;

            if (is_file($path)) {
                $found[] = $path;
            }
        }

        if ($found === []) {
            throw new ConfigException(sprintf(
                'Missing book config in %s (expected papyrus.php, papyrus.yml, or papyrus.json)',
                $dir,
            ));
        }

        if (count($found) > 1) {
            $names = array_map(static fn (string $path): string => basename($path), $found);

            throw new ConfigException(sprintf(
                'Multiple book config files found in %s (%s). Keep only one.',
                $dir,
                implode(', ', $names),
            ));
        }

        return $found[0];
    }
}
