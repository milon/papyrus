<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\KdpTrimBounds;
use Milon\Papyrus\Mermaid\MermaidCliResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'doctor', description: 'Validate book project configuration')]
final class DoctorCommand extends BookCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);

        try {
            $project = $this->loadProject($input);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $checks = [
            ['Config', true, $project->configPath],
            ['Content dir', is_dir($project->contentDir), $project->contentDir],
            ['Assets dir', is_dir($project->assetsDir), $project->assetsDir],
        ];

        $ok = true;

        foreach ($checks as [$label, $pass, $path]) {
            if ($pass) {
                $output->writeln(sprintf('<info>✓</info> %s: %s', $label, $path));
            } else {
                $output->writeln(sprintf('<error>✗</error> %s: %s', $label, $path));
                $ok = false;
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('Title: %s', $project->title()));

        if ($project->subtitle() !== '') {
            $output->writeln(sprintf('Subtitle: %s', $project->subtitle()));
        }

        if ($project->author() !== '') {
            $output->writeln(sprintf('Author: %s', $project->author()));
        }

        $output->writeln('Themes: '.implode(', ', $project->themes()));

        $document = $project->documentSize();

        if (! KdpTrimBounds::isWithinBounds($document)) {
            $output->writeln(sprintf(
                '<comment>! Document trim %.3f × %.3f mm is outside typical KDP paperback bounds (%.1f–%.1f × %.1f–%.1f mm).</comment>',
                $document->widthMm,
                $document->heightMm,
                KdpTrimBounds::MIN_WIDTH_MM,
                KdpTrimBounds::MAX_WIDTH_MM,
                KdpTrimBounds::MIN_HEIGHT_MM,
                KdpTrimBounds::MAX_HEIGHT_MM,
            ));
        } else {
            $preset = DocumentSize::resolvePresetName($document->widthMm, $document->heightMm);
            $label = $preset ?? 'custom';

            $output->writeln(sprintf(
                'Document: %.3f × %.3f mm (%s)',
                $document->widthMm,
                $document->heightMm,
                $label,
            ));
        }

        if ($project->mermaidConfig()->enabled) {
            $cli = MermaidCliResolver::resolve($project->mermaidConfig()->command);

            if ($cli->isAvailable()) {
                $output->writeln(sprintf('<info>✓</info> Mermaid CLI: %s (%s)', $cli->command(), $cli->version()));
            } else {
                $output->writeln('<comment>! Mermaid is enabled but mmdc (@mermaid-js/mermaid-cli) was not found.</comment>');
            }
        }

        if (! $ok) {
            $output->writeln('');
            $output->writeln('<error>Doctor found problems.</error>');

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>Configuration OK.</info>');

        return Command::SUCCESS;
    }
}
