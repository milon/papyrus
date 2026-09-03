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

#[AsCommand(name: 'kdp:package', description: 'Zip enabled KDP artifacts with an upload checklist')]
final class KdpPackageCommand extends BookCommand
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
            $path = (new KdpBuilder($project))->buildPackage();
            $output->writeln(sprintf('<info>✓</info> %s', $path));

            return Command::SUCCESS;
        } catch (KdpException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
