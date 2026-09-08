<?php

declare(strict_types=1);

namespace Milon\Papyrus\Migration;

use Symfony\Component\Yaml\Yaml;

final class YamlConfigWriter
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function write(array $config, string $path): void
    {
        $content = Yaml::dump($config, 12, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        if (file_put_contents($path, $content) === false) {
            throw new MigrationException(sprintf('Unable to write config file: %s', $path));
        }
    }
}
