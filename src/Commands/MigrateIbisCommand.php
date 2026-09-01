<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Migration\IbisMigrator;
use Milon\Papyrus\Migration\MigrationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'migrate-ibis', description: 'Migrate ibis.php to papyrus.php and update theme TOC markers')]
final class MigrateIbisCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite an existing papyrus.php');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);
        $force = (bool) $input->getOption('force');

        try {
            $result = (new IbisMigrator)->migrate($dir, $force);
        } catch (MigrationException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>✓</info> '.$result['papyrus']);

        foreach ($result['themes'] as $themePath) {
            $output->writeln('<info>✓</info> Updated TOC marker in '.$themePath);
        }

        if ($result['themes'] === []) {
            $output->writeln('<comment>No theme files required TOC marker updates.</comment>');
        }

        $output->writeln('');
        $output->writeln('<info>Migration complete.</info> Review papyrus.php, then remove ibis.php and ibis-next from composer.json.');

        return Command::SUCCESS;
    }
}
