<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

enum InitPreset: string
{
    case Book = 'book';
    case Docs = 'docs';

    public static function fromOption(string $value): self
    {
        return match (strtolower(trim($value))) {
            'book' => self::Book,
            'docs', 'doc', 'documentation' => self::Docs,
            default => throw new ConfigException(sprintf(
                'Unsupported init preset "%s". Use book or docs.',
                $value,
            )),
        };
    }

    public function defaultFormat(): ConfigFormat
    {
        return match ($this) {
            self::Book => ConfigFormat::Php,
            self::Docs => ConfigFormat::Yml,
        };
    }
}
