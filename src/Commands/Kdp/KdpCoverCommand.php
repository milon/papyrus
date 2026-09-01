<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands\Kdp;

use Milon\Papyrus\Commands\BookCommand;
use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Kdp\KdpBuilder;
use Milon\Papyrus\Kdp\KdpException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kdp:cover', description: 'Export KDP cover assets')]
final class KdpCoverCommand extends BookCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $project = $this->loadProject($input);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        try {
            $paths = (new KdpBuilder($project))->buildCovers();

            if ($paths === []) {
                $output->writeln('<comment>No cover assets were exported.</comment>');

                return Command::SUCCESS;
            }

            foreach ($paths as $path) {
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            }

            return Command::SUCCESS;
        } catch (KdpException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
