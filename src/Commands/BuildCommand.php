<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Kdp\KdpBuilder;
use Milon\Papyrus\Kdp\KdpException;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Epub\EpubException;
use Milon\Papyrus\Render\Epub\EpubRenderer;
use Milon\Papyrus\Render\Html\HtmlException;
use Milon\Papyrus\Render\Html\HtmlRenderer;
use Milon\Papyrus\Render\Html\SiteRenderer;
use Milon\Papyrus\Render\Pdf\PdfException;
use Milon\Papyrus\Render\Pdf\PdfRenderer;
use Milon\Papyrus\Render\Pdf\SampleException;
use Milon\Papyrus\Render\Pdf\SamplePdfRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'build', description: 'Build PDF, EPUB, HTML, and KDP')]
final class BuildCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'with-site',
            null,
            InputOption::VALUE_NONE,
            'Also run build:site',
        );
        $this->addOption(
            'with-sample',
            null,
            InputOption::VALUE_NONE,
            'Also run build:sample for all themes',
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

        $failed = false;

        foreach ($project->themes() as $theme) {
            try {
                $path = (new PdfRenderer($project))->render($theme);
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            } catch (PdfException|MermaidException $e) {
                $output->writeln(sprintf('<error>✗ pdf %s: %s</error>', $theme, $e->getMessage()));
                $failed = true;
            }
        }

        try {
            $path = (new EpubRenderer($project))->render();
            $output->writeln(sprintf('<info>✓</info> %s', $path));
        } catch (EpubException|MermaidException $e) {
            $output->writeln('<error>✗ epub: '.$e->getMessage().'</error>');
            $failed = true;
        }

        try {
            $path = (new HtmlRenderer($project))->render();
            $output->writeln(sprintf('<info>✓</info> %s', $path));
        } catch (HtmlException|MermaidException $e) {
            $output->writeln('<error>✗ html: '.$e->getMessage().'</error>');
            $failed = true;
        }

        if ($project->kdpConfig()->hasEnabledOutputs()) {
            $builder = new KdpBuilder($project);
            $theme = $project->themes()[0] ?? 'light';

            try {
                $path = $builder->buildEbook();

                if ($path !== null) {
                    $output->writeln(sprintf('<info>✓</info> %s', $path));
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
        }

        if ((bool) $input->getOption('with-site')) {
            try {
                $path = (new SiteRenderer($project))->render();
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            } catch (HtmlException|MermaidException $e) {
                $output->writeln('<error>✗ site: '.$e->getMessage().'</error>');
                $failed = true;
            }
        }

        if ((bool) $input->getOption('with-sample')) {
            $renderer = new SamplePdfRenderer($project);

            foreach ($project->themes() as $theme) {
                try {
                    $path = $renderer->render($theme);
                    $output->writeln(sprintf('<info>✓</info> %s', $path));
                } catch (SampleException|PdfException|MermaidException $e) {
                    $output->writeln(sprintf('<error>✗ sample %s: %s</error>', $theme, $e->getMessage()));
                    $failed = true;
                }
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
