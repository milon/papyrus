<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\KdpTrimBounds;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sizes', description: 'List page-size presets')]
final class SizesCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>KDP paperback presets</info>');
        $output->writeln('');
        $output->writeln(sprintf(
            '%-14s %-12s %s',
            'Preset',
            'Inches',
            'mm (W × H)',
        ));

        foreach (DocumentSize::presets() as $preset) {
            $size = new DocumentSize(
                widthMm: $preset->widthMm,
                heightMm: $preset->heightMm,
                marginLeft: 0,
                marginRight: 0,
                marginTop: 0,
                marginBottom: 0,
            );

            $kdp = KdpTrimBounds::isWithinBounds($size) ? 'yes' : 'no';
            $aliases = $preset->aliases !== [] ? ' ('.implode(', ', $preset->aliases).')' : '';

            $output->writeln(sprintf(
                '%-14s %-12s %.3f × %.3f  KDP: %s%s',
                $preset->name,
                $preset->inchesLabel,
                $preset->widthMm,
                $preset->heightMm,
                $kdp,
                $aliases,
            ));
        }

        $output->writeln('');
        $output->writeln(sprintf(
            'Custom trim: set document.format to [width_mm, height_mm]. KDP bounds: %.1f–%.1f × %.1f–%.1f mm.',
            KdpTrimBounds::MIN_WIDTH_MM,
            KdpTrimBounds::MAX_WIDTH_MM,
            KdpTrimBounds::MIN_HEIGHT_MM,
            KdpTrimBounds::MAX_HEIGHT_MM,
        ));

        return Command::SUCCESS;
    }
}
