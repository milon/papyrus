<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

final class MermaidCliCommand implements MermaidCli
{
    public function __construct(
        private readonly string $command,
    ) {}

    public function command(): string
    {
        return $this->command;
    }

    public function isAvailable(): bool
    {
        $version = $this->version();

        return $version !== null && version_compare($version, '8.0.0', '>=');
    }

    public function version(): ?string
    {
        $output = $this->run(['--version']);

        if ($output === null) {
            return null;
        }

        if (str_contains(strtolower($output), 'mermaidx')) {
            return null;
        }

        if (preg_match('/(\d+\.\d+\.\d+)/', $output, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public function render(string $inputPath, string $outputPath, ?string $theme = null, ?string $configPath = null): void
    {
        $arguments = [
            '-i', $inputPath,
            '-o', $outputPath,
            '-b', 'transparent',
        ];

        if ($theme !== null) {
            $arguments[] = '-t';
            $arguments[] = $theme;
        }

        if ($configPath !== null) {
            $arguments[] = '-c';
            $arguments[] = $configPath;
        }

        $this->run($arguments, mustSucceed: true, outputPath: $outputPath);
    }

    /**
     * @param  list<string>  $arguments
     */
    private function run(array $arguments, bool $mustSucceed = false, ?string $outputPath = null): ?string
    {
        $command = escapeshellcmd($this->command).' '.implode(' ', array_map('escapeshellarg', $arguments));

        $outputLines = [];
        $exitCode = 1;
        exec($command.' 2>&1', $outputLines, $exitCode);
        $output = implode("\n", $outputLines);

        if ($mustSucceed && ($exitCode !== 0 || ($outputPath !== null && ! is_file($outputPath)))) {
            throw new MermaidException(trim($output) !== '' ? trim($output) : 'mmdc failed');
        }

        return $output !== '' ? $output : null;
    }
}
