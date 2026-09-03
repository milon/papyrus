<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands\Kdp;

use Milon\Papyrus\Commands\BookCommand;
use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Kdp\KdpBuilder;
use Milon\Papyrus\Kdp\KdpException;
use Milon\Papyrus\Kdp\PdfPageCounter;
use Milon\Papyrus\Kdp\PrintCoverDimensions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kdp:cover', description: 'Export KDP cover assets')]
final class KdpCoverCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'dimensions',
            null,
            InputOption::VALUE_NONE,
            'Print wrap-cover size estimates (needs page count)',
        );
        $this->addOption(
            'pages',
            null,
            InputOption::VALUE_REQUIRED,
            'Page count for --dimensions (default: count pages in export/<slug>-kdp-print.pdf)',
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

        try {
            $paths = (new KdpBuilder($project))->buildCovers();

            if ($paths === []) {
                $output->writeln('<comment>No cover assets were exported.</comment>');
            } else {
                foreach ($paths as $path) {
                    $output->writeln(sprintf('<info>✓</info> %s', $path));
                }
            }

            if ((bool) $input->getOption('dimensions')) {
                $this->writeDimensions($input, $output, $project);
            }

            return Command::SUCCESS;
        } catch (KdpException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }

    private function writeDimensions(InputInterface $input, OutputInterface $output, Project $project): void
    {
        $pagesOption = $input->getOption('pages');
        $pages = null;

        if (is_string($pagesOption) && trim($pagesOption) !== '') {
            $pages = (int) $pagesOption;
        } else {
            $printPdf = sprintf(
                '%s/%s-kdp-print.pdf',
                $project->exportDir,
                $project->outputSlug(),
            );
            $pages = PdfPageCounter::count($printPdf);

            if ($pages === null) {
                $output->writeln('<comment>! Unable to determine page count. Pass --pages=N or build kdp:print first.</comment>');

                return;
            }
        }

        if ($pages < 1) {
            $output->writeln('<error>Page count must be at least 1.</error>');

            return;
        }

        $dims = PrintCoverDimensions::calculate(
            $project->documentSize(),
            $pages,
            $project->kdpConfig()->printPaper,
        );

        $output->writeln(sprintf(
            'Wrap cover estimate (%d pages, %s paper): spine %.3f mm (%.4f in); full %.3f × %.3f mm (%.4f × %.4f in)',
            $dims['page_count'],
            $dims['paper'],
            $dims['spine_mm'],
            $dims['spine_in'],
            $dims['wrap_width_mm'],
            $dims['wrap_height_mm'],
            $dims['wrap_width_in'],
            $dims['wrap_height_in'],
        ));

        if (! $dims['spine_text_allowed']) {
            $output->writeln(sprintf(
                '<comment>! Spine text needs at least %d pages (currently %d).</comment>',
                PrintCoverDimensions::SPINE_TEXT_MIN_PAGES,
                $dims['page_count'],
            ));
        }
    }
}
