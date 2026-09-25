<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

use Milon\Papyrus\Migration\JsonConfigWriter;
use Milon\Papyrus\Migration\PhpConfigWriter;
use Milon\Papyrus\Migration\YamlConfigWriter;
use Milon\Papyrus\Support\PackagePaths;

final class ConfigWriter
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function write(array $config, string $path, ConfigFormat $format): void
    {
        $config = self::forFormat($config, $format);

        match ($format) {
            ConfigFormat::Php => (new PhpConfigWriter)->write($config, $path),
            ConfigFormat::Yml => (new YamlConfigWriter)->write($config, $path),
            ConfigFormat::Json => (new JsonConfigWriter)->write($config, $path),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function forFormat(array $config, ConfigFormat $format): array
    {
        if ($format === ConfigFormat::Php) {
            return $config;
        }

        if (isset($config['configure_commonmark'])) {
            unset($config['configure_commonmark']);
        }

        return $config;
    }

    /**
     * Load the packaged PHP stub as an array for non-PHP scaffolds.
     *
     * @return array<string, mixed>
     */
    public static function stubConfig(): array
    {
        $path = PackagePaths::stub('papyrus.php');
        $config = require $path;

        if (! is_array($config)) {
            throw new ConfigException('stubs/papyrus.php must return an array.');
        }

        /** @var array<string, mixed> $config */
        return $config;
    }
}
