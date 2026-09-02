<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Html\HtmlException;
use Milon\Papyrus\Render\Html\SiteRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'build:site', description: 'Build multi-page HTML site with chapter sidebar')]
final class SiteCommand extends BookCommand
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
            $path = (new SiteRenderer($project))->render();
            $output->writeln(sprintf('<info>✓</info> %s', $path));
        } catch (HtmlException|MermaidException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
