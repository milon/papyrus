<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands\Kdp;

use Milon\Papyrus\Commands\BookCommand;
use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Kdp\KdpBuilder;
use Milon\Papyrus\Kdp\KdpException;
use Milon\Papyrus\Kdp\KdpRenderResult;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Epub\EpubException;
use Milon\Papyrus\Render\Pdf\PdfException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kdp', description: 'Build all enabled KDP outputs')]
final class KdpCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'require-epubcheck',
            null,
            InputOption::VALUE_NONE,
            'Fail if epubcheck is not available on PATH (ebook builds)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $project = $this->loadProject($input);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $builder = new KdpBuilder($project);

        if (! $builder->hasEnabledOutputs()) {
            $output->writeln('<error>No KDP outputs are enabled in papyrus.php.</error>');

            return Command::FAILURE;
        }

        $failed = false;
        $theme = $project->themes()[0] ?? 'light';
        $requireEpubcheck = (bool) $input->getOption('require-epubcheck');

        try {
            $result = $builder->buildEbook(requireEpubcheck: $requireEpubcheck);

            if ($result !== null) {
                $this->writeWarnings($output, $result);
                $output->writeln(sprintf('<info>✓</info> %s', $result->path));
            }
        } catch (KdpException|EpubException|MermaidException $e) {
            $output->writeln('<error>✗ kdp ebook: '.$e->getMessage().'</error>');
            $failed = true;
        }

        try {
            $path = $builder->buildPrint($theme);

            if ($path !== null) {
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            }
        } catch (KdpException|PdfException|MermaidException $e) {
            $output->writeln('<error>✗ kdp print: '.$e->getMessage().'</error>');
            $failed = true;
        }

        try {
            foreach ($builder->buildCovers() as $path) {
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            }
        } catch (KdpException $e) {
            $output->writeln('<error>✗ kdp cover: '.$e->getMessage().'</error>');
            $failed = true;
        }

        try {
            $path = $builder->buildMetadata();
            $output->writeln(sprintf('<info>✓</info> %s', $path));
        } catch (KdpException $e) {
            $output->writeln('<error>✗ kdp metadata: '.$e->getMessage().'</error>');
            $failed = true;
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    private function writeWarnings(OutputInterface $output, KdpRenderResult $result): void
    {
        foreach ($result->warnings as $warning) {
            $output->writeln('<comment>! '.$warning.'</comment>');
        }
    }
}
