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
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Pdf\PdfException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kdp:print', description: 'Build KDP print PDF with margin and bleed presets')]
final class KdpPrintCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'theme',
            't',
            InputOption::VALUE_REQUIRED,
            'PDF theme for interior layout (default: first configured theme)',
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

        if (! $project->kdpConfig()->printEnabled) {
            $output->writeln('<error>KDP print output is not enabled in papyrus.php.</error>');

            return Command::FAILURE;
        }

        $theme = $this->resolveTheme($input, $project);

        try {
            $path = (new KdpBuilder($project))->buildPrint($theme);
            $output->writeln(sprintf('<info>✓</info> %s', $path));

            if (is_string($path)) {
                $this->writeCoverEstimate($output, $project, $path);
            }

            return Command::SUCCESS;
        } catch (KdpException|PdfException|MermaidException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
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

    private function resolveTheme(InputInterface $input, Project $project): string
    {
        $option = $input->getOption('theme');

        if (is_string($option) && trim($option) !== '') {
            return trim($option);
        }

        return $project->themes()[0] ?? 'light';
    }
}
