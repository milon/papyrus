<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class ConfigLoader
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $path): array
    {
        if (! is_file($path)) {
            throw new ConfigException(sprintf('Missing book config: %s', $path));
        }

        $format = ConfigFormat::fromPath($path);

        $config = match ($format) {
            ConfigFormat::Php => self::loadPhp($path),
            ConfigFormat::Yml => self::loadYaml($path),
            ConfigFormat::Json => self::loadJson($path),
        };

        if (! is_array($config)) {
            throw new ConfigException(sprintf('%s must contain an object/array', basename($path)));
        }

        /** @var array<string, mixed> $config */
        return $config;
    }

    private static function loadPhp(string $path): mixed
    {
        return require $path;
    }

    private static function loadYaml(string $path): mixed
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException $e) {
            throw new ConfigException(sprintf('Invalid YAML in %s: %s', basename($path), $e->getMessage()), previous: $e);
        }
    }

    private static function loadJson(string $path): mixed
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new ConfigException(sprintf('Unable to read %s', $path));
        }

        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConfigException(sprintf('Invalid JSON in %s: %s', basename($path), $e->getMessage()), previous: $e);
        }
    }
}
