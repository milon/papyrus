<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\ConfigFormat;
use Milon\Papyrus\Migration\IbisMigrator;
use Milon\Papyrus\Migration\MigrationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate-ibis', description: 'Migrate ibis.php to papyrus.php/.yml/.json; update TOC markers in local assets/ themes')]
final class MigrateIbisCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing Papyrus config file');
        $this->addOption(
            'format',
            null,
            InputOption::VALUE_REQUIRED,
            'Output config format: php, yml/yaml, or json (default: php)',
            'php',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);
        $force = (bool) $input->getOption('force');

        try {
            $format = ConfigFormat::fromOption((string) $input->getOption('format'));
            $result = (new IbisMigrator)->migrate($dir, $force, $format);
        } catch (ConfigException|MigrationException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>✓</info> '.$result['papyrus']);

        foreach ($result['themes'] as $themePath) {
            $output->writeln('<info>✓</info> Updated TOC marker in '.$themePath);
        }

        if ($result['themes'] === []) {
            $output->writeln('<comment>No local assets/theme*.html files needed TOC updates.</comment>');
            $output->writeln('<comment>Builds will use bundled Papyrus themes unless you keep custom files in assets/.</comment>');
        }

        if ($result['dropped_commonmark_hook']) {
            $output->writeln('<comment>Note: configure_commonmark was dropped (callables need papyrus.php). Re-add it in PHP if required.</comment>');
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Migration complete.</info> Review %s, then remove ibis.php and ibis-next from composer.json.',
            basename($result['papyrus']),
        ));

        return Command::SUCCESS;
    }
}
