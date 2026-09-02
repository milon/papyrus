<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Epub\EpubException;
use Milon\Papyrus\Render\Epub\EpubRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'build:epub', description: 'Build EPUB')]
final class EpubCommand extends BookCommand
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
            $path = (new EpubRenderer($project))->render();
            $output->writeln(sprintf('<info>✓</info> %s', $path));
        } catch (EpubException|MermaidException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
