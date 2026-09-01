<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Pdf\ParallelPdfRenderer;
use Milon\Papyrus\Render\Pdf\PdfException;
use Milon\Papyrus\Render\Pdf\PdfRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'pdf', description: 'Build PDF themes')]
final class PdfCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'theme',
            't',
            InputOption::VALUE_REQUIRED,
            'Comma-separated theme names (default: all configured themes)',
        );

        $this->addOption(
            'parallel',
            'p',
            InputOption::VALUE_NONE,
            'Build PDF themes in parallel (one process per theme)',
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

        $themes = $this->resolveThemes($input, $project);

        if ($themes === []) {
            $output->writeln('<error>No themes to build.</error>');

            return Command::FAILURE;
        }

        $renderer = new PdfRenderer($project);
        $failed = false;

        if ((bool) $input->getOption('parallel') && count($themes) > 1) {
            $results = (new ParallelPdfRenderer($project, $this->papyrusBinary()))->render($themes);

            foreach ($results as $theme => $result) {
                if ($result instanceof PdfException) {
                    $output->writeln(sprintf('<error>✗ %s: %s</error>', $theme, $result->getMessage()));
                    $failed = true;

                    continue;
                }

                $output->writeln(sprintf('<info>✓</info> %s', $result));
            }

            return $failed ? Command::FAILURE : Command::SUCCESS;
        }

        foreach ($themes as $theme) {
            try {
                $path = $renderer->render($theme);
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            } catch (PdfException $e) {
                $output->writeln(sprintf('<error>✗ %s: %s</error>', $theme, $e->getMessage()));
                $failed = true;
            } catch (MermaidException $e) {
                $output->writeln(sprintf('<error>✗ %s: %s</error>', $theme, $e->getMessage()));
                $failed = true;
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveThemes(InputInterface $input, Project $project): array
    {
        $option = $input->getOption('theme');

        if (! is_string($option) || trim($option) === '') {
            return $project->themes();
        }

        return array_values(array_filter(array_map(
            trim(...),
            explode(',', $option),
        )));
    }

    private function papyrusBinary(): string
    {
        return dirname(__DIR__, 2).'/bin/papyrus';
    }
}
