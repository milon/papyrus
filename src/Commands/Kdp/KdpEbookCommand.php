<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands\Kdp;

use Milon\Papyrus\Commands\BookCommand;
use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Kdp\KdpBuilder;
use Milon\Papyrus\Kdp\KdpException;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Epub\EpubException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kdp:ebook', description: 'Build KDP Kindle EPUB')]
final class KdpEbookCommand extends BookCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $project = $this->loadProject($input);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        if (! $project->kdpConfig()->ebookEnabled) {
            $output->writeln('<error>KDP eBook output is not enabled in papyrus.php.</error>');

            return Command::FAILURE;
        }

        try {
            $path = (new KdpBuilder($project))->buildEbook();
            $output->writeln(sprintf('<info>✓</info> %s', $path));

            return Command::SUCCESS;
        } catch (KdpException|EpubException|MermaidException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
