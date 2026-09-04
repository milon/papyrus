<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands\Kdp;

use Milon\Papyrus\Commands\BookCommand;
use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\KdpBuilder;
use Milon\Papyrus\Kdp\KdpException;
use Milon\Papyrus\Kdp\KdpRenderResult;
use Milon\Papyrus\Kdp\PdfPageCounter;
use Milon\Papyrus\Kdp\PrintCoverDimensions;
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
        $this->addOption(
            'package',
            null,
            InputOption::VALUE_NONE,
            'Also zip artifacts into export/<slug>-kdp-package.zip',
        );
        $this->addOption(
            'wrap',
            null,
            InputOption::VALUE_NONE,
            'Also generate a paperback wraparound cover PDF after print/covers',
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
                $this->writeCoverEstimate($output, $project, $path);
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

        if (! $failed && (bool) $input->getOption('wrap')) {
            try {
                $printPdf = sprintf(
                    '%s/%s-kdp-print.pdf',
                    $project->exportDir,
                    $project->outputSlug(),
                );
                $pages = PdfPageCounter::count($printPdf);

                if ($pages === null) {
                    $output->writeln('<error>✗ kdp wrap: unable to count pages in print PDF. Build kdp:print first.</error>');
                    $failed = true;
                } else {
                    foreach ($builder->buildWrapCover($pages, $theme) as $path) {
                        $output->writeln(sprintf('<info>✓</info> %s', $path));
                    }
                }
            } catch (KdpException $e) {
                $output->writeln('<error>✗ kdp wrap: '.$e->getMessage().'</error>');
                $failed = true;
            }
        }

        try {
            $path = $builder->buildMetadata();
            $output->writeln(sprintf('<info>✓</info> %s', $path));
        } catch (KdpException $e) {
            $output->writeln('<error>✗ kdp metadata: '.$e->getMessage().'</error>');
            $failed = true;
        }

        if (! $failed && (bool) $input->getOption('package')) {
            try {
                $path = $builder->buildPackage();
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            } catch (KdpException $e) {
                $output->writeln('<error>✗ kdp package: '.$e->getMessage().'</error>');
                $failed = true;
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    private function writeCoverEstimate(OutputInterface $output, Project $project, string $printPdfPath): void
    {
        $pages = PdfPageCounter::count($printPdfPath);

        if ($pages === null) {
            return;
        }

        $dims = PrintCoverDimensions::calculate(
            $project->documentSize(),
            $pages,
            $project->kdpConfig()->printPaper,
        );

        $output->writeln(sprintf(
            'Wrap cover estimate: %d pages → spine %.3f mm; full %.3f × %.3f mm (%s paper)',
            $dims['page_count'],
            $dims['spine_mm'],
            $dims['wrap_width_mm'],
            $dims['wrap_height_mm'],
            $dims['paper'],
        ));
    }

    private function writeWarnings(OutputInterface $output, KdpRenderResult $result): void
    {
        foreach ($result->warnings as $warning) {
            $output->writeln('<comment>! '.$warning.'</comment>');
        }
    }
}
