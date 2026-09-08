<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\ConfigFormat;
use Milon\Papyrus\Config\ConfigWriter;
use Milon\Papyrus\Stubs\StubRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'init', description: 'Scaffold a new book project')]
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
            'Config format: php, yml/yaml, or json (default: php)',
            'php',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);
        $force = (bool) $input->getOption('force');

        try {
            $format = ConfigFormat::fromOption((string) $input->getOption('format'));
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            $output->writeln('<error>Could not create directory: '.$dir.'</error>');

            return self::FAILURE;
        }

        $repo = StubRepository::default();
        $written = [];

        foreach ($repo->bookFiles() as $relative) {
            if ($relative === ConfigFormat::Php->filename()) {
                continue;
            }

            $target = $dir.'/'.$relative;
            $parent = dirname($target);

            if (! is_dir($parent) && ! mkdir($parent, 0o755, true) && ! is_dir($parent)) {
                $output->writeln('<error>Could not create directory: '.$parent.'</error>');

                return self::FAILURE;
            }

            if (is_file($target) && ! $force) {
                $output->writeln('<comment>Skipped (exists): '.$relative.'</comment>');

                continue;
            }

            file_put_contents($target, $repo->read($relative));
            $written[] = $relative;
        }

        $configRelative = $format->filename();
        $configTarget = $dir.'/'.$configRelative;

        if (is_file($configTarget) && ! $force) {
            $output->writeln('<comment>Skipped (exists): '.$configRelative.'</comment>');
        } else {
            try {
                if ($format === ConfigFormat::Php) {
                    file_put_contents($configTarget, $repo->read(ConfigFormat::Php->filename()));
                } else {
                    ConfigWriter::write(ConfigWriter::stubConfig(), $configTarget, $format);
                }
            } catch (ConfigException $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');

                return self::FAILURE;
            }

            $written[] = $configRelative;
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

        $output->writeln('<info>Scaffolded book project in '.$dir.':</info>');
        foreach ($written as $path) {
            $output->writeln('  '.$path);
        }

        return self::SUCCESS;
    }
}
