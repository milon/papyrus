<?php

declare(strict_types=1);

namespace Milon\Papyrus\Migration;

final class JsonConfigWriter
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function write(array $config, string $path): void
    {
        try {
            $content = json_encode(
                $config,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new MigrationException('Unable to encode papyrus.json: '.$e->getMessage(), previous: $e);
        }

        if (file_put_contents($path, $content."\n") === false) {
            throw new MigrationException(sprintf('Unable to write config file: %s', $path));
        }
    }
}
