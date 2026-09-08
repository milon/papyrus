<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

enum ConfigFormat: string
{
    case Php = 'php';
    case Yml = 'yml';
    case Json = 'json';

    public static function fromOption(string $value): self
    {
        return match (strtolower(trim($value))) {
            'php' => self::Php,
            'yml', 'yaml' => self::Yml,
            'json' => self::Json,
            default => throw new ConfigException(sprintf(
                'Unsupported config format "%s". Use php, yml (or yaml), or json.',
                $value,
            )),
        };
    }

    public function filename(): string
    {
        return match ($this) {
            self::Php => 'papyrus.php',
            self::Yml => 'papyrus.yml',
            self::Json => 'papyrus.json',
        };
    }

    public static function fromPath(string $path): self
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'php' => self::Php,
            'yml', 'yaml' => self::Yml,
            'json' => self::Json,
            default => throw new ConfigException(sprintf('Unsupported config file: %s', $path)),
        };
    }
}
