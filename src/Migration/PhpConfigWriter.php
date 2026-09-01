<?php

declare(strict_types=1);

namespace Milon\Papyrus\Migration;

final class PhpConfigWriter
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function write(array $config, string $path): void
    {
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn ".$this->export($config).";\n";

        if (file_put_contents($path, $content) === false) {
            throw new MigrationException(sprintf('Unable to write config file: %s', $path));
        }
    }

    private function export(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('    ', $depth);

        if (is_array($value)) {
            if ($value === []) {
                return '[]';
            }

            $isList = array_is_list($value);
            $lines = [];

            foreach ($value as $key => $item) {
                $entry = $this->export($item, $depth + 1);

                if ($isList) {
                    $lines[] = str_repeat('    ', $depth + 1).$entry;
                } else {
                    $lines[] = str_repeat('    ', $depth + 1).$this->exportKey($key).$entry;
                }
            }

            if ($isList) {
                return "[\n".implode(",\n", $lines).",\n".$indent.']';
            }

            return "[\n".implode(",\n", $lines).",\n".$indent.']';
        }

        if (is_string($value)) {
            return var_export($value, true);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return 'null';
        }

        return var_export($value, true);
    }

    private function exportKey(int|string $key): string
    {
        if (is_int($key)) {
            return $key.' => ';
        }

        return var_export($key, true).' => ';
    }
}
