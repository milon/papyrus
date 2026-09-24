<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ComposerProjectHint;
use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\ConfigFormat;
use Milon\Papyrus\Config\ConfigWriter;
use Milon\Papyrus\Config\DocsPresetConfig;
use Milon\Papyrus\Config\InitPreset;
use Milon\Papyrus\Stubs\StubRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'init', description: 'Scaffold a new book or documentation site project')]
final class InitCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files');
        $this->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            'Config format: php, yml/yaml, or json (default: php for book, yml for docs)',
            null,
        );
        $this->addOption(
            'preset',
            null,
            InputOption::VALUE_REQUIRED,
            'Scaffold preset: book (default) or docs',
            'book',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);
        $force = (bool) $input->getOption('force');

        try {
            $preset = InitPreset::fromOption((string) $input->getOption('preset'));
            $format = $this->resolveFormat($input, $preset);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            $output->writeln('<error>Could not create directory: '.$dir.'</error>');

            return self::FAILURE;
        }

        $repo = StubRepository::default();

        try {
            $written = match ($preset) {
                InitPreset::Book => $this->scaffoldBook($dir, $force, $format, $repo, $output),
                InitPreset::Docs => $this->scaffoldDocs($dir, $force, $format, $repo, $output),
            };
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        $assetsDir = $dir.'/assets';

        if (! is_dir($assetsDir) && ! mkdir($assetsDir, 0o755, true) && ! is_dir($assetsDir)) {
            $output->writeln('<error>Could not create directory: assets</error>');

            return self::FAILURE;
        }

        if ($written === []) {
            $output->writeln('<comment>No files written. Use --force to overwrite.</comment>');

            return self::SUCCESS;
        }

        $label = $preset === InitPreset::Docs ? 'documentation site' : 'book project';
        $output->writeln(sprintf('<info>Scaffolded %s in %s:</info>', $label, $dir));
        foreach ($written as $path) {
            $output->writeln('  '.$path);
        }

        if ($preset === InitPreset::Docs) {
            $output->writeln('');
            $output->writeln('<comment>Next:</comment> edit content/, then <info>papyrus build:site</info> and <info>papyrus serve</info>.');
            $output->writeln('<comment>Tip:</comment> adjust <info>site.base_path</info> for project GitHub Pages (e.g. /barcode).');
        }

        return self::SUCCESS;
    }

    private function resolveFormat(InputInterface $input, InitPreset $preset): ConfigFormat
    {
        $formatOption = $input->getOption('format');

        if (! is_string($formatOption) || trim($formatOption) === '') {
            return $preset->defaultFormat();
        }

        return ConfigFormat::fromOption($formatOption);
    }

    /**
     * @return list<string>
     */
    private function scaffoldBook(
        string $dir,
        bool $force,
        ConfigFormat $format,
        StubRepository $repo,
        OutputInterface $output,
    ): array {
        $written = [];

        foreach ($repo->bookFiles() as $relative) {
            if ($relative === ConfigFormat::Php->filename()) {
                continue;
            }

            $path = $this->writeFile($dir, $relative, $repo->read($relative), $force, $output);

            if ($path !== null) {
                $written[] = $path;
            }
        }

        $configPath = $this->writeConfig(
            $dir,
            $format,
            $force,
            $output,
            static function (ConfigFormat $fmt) use ($repo): string|array {
                if ($fmt === ConfigFormat::Php) {
                    return $repo->read(ConfigFormat::Php->filename());
                }

                return ConfigWriter::stubConfig();
            },
        );

        if ($configPath !== null) {
            $written[] = $configPath;
        }

        return $written;
    }

    /**
     * @return list<string>
     */
    private function scaffoldDocs(
        string $dir,
        bool $force,
        ConfigFormat $format,
        StubRepository $repo,
        OutputInterface $output,
    ): array {
        $written = [];
        $hint = ComposerProjectHint::discover($dir);
        $config = DocsPresetConfig::build($hint);
        $title = is_string($config['title'] ?? null) ? $config['title'] : 'My Package';

        foreach ($repo->presetFiles('docs') as $relative) {
            $contents = $repo->readPreset('docs', $relative);
            $contents = str_replace('{{title}}', $title, $contents);
            $path = $this->writeFile($dir, $relative, $contents, $force, $output);

            if ($path !== null) {
                $written[] = $path;
            }
        }

        $configPath = $this->writeConfig(
            $dir,
            $format,
            $force,
            $output,
            static fn (ConfigFormat $_format): array => $config,
        );

        if ($configPath !== null) {
            $written[] = $configPath;
        }

        return $written;
    }

    /**
     * @param  callable(ConfigFormat): (string|array<string, mixed>)  $contents
     */
    private function writeConfig(
        string $dir,
        ConfigFormat $format,
        bool $force,
        OutputInterface $output,
        callable $contents,
    ): ?string {
        $relative = $format->filename();
        $target = $dir.'/'.$relative;

        if (is_file($target) && ! $force) {
            $output->writeln('<comment>Skipped (exists): '.$relative.'</comment>');

            return null;
        }

        $payload = $contents($format);

        if (is_string($payload)) {
            file_put_contents($target, $payload);
        } else {
            ConfigWriter::write($payload, $target, $format);
        }

        return $relative;
    }

    private function writeFile(
        string $dir,
        string $relative,
        string $contents,
        bool $force,
        OutputInterface $output,
    ): ?string {
        $target = $dir.'/'.$relative;
        $parent = dirname($target);

        if (! is_dir($parent) && ! mkdir($parent, 0o755, true) && ! is_dir($parent)) {
            throw new ConfigException('Could not create directory: '.$parent);
        }

        if (is_file($target) && ! $force) {
            $output->writeln('<comment>Skipped (exists): '.$relative.'</comment>');

            return null;
        }

        file_put_contents($target, $contents);

        return $relative;
    }
}
