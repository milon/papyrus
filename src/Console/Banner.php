<?php

declare(strict_types=1);

namespace Milon\Papyrus\Console;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

final class Banner
{
    public const string TAGLINE = 'Markdown books to PDF, EPUB, HTML';

    /**
     * Scroll-P monogram — terminal approximation of assets/papyrus-icon.png.
     *
     * @return list<string>
     */
    public static function logoLines(): array
    {
        return [
            '   ╭──╮  ',
            '  ╭│▓▓│╮ ',
            '  ││▓▓││ ',
            '  ╰│▓▓│╯ ',
            '   ╰──╯  ',
        ];
    }

    /**
     * @return list<string>
     */
    public static function lines(): array
    {
        return [
            ' ___    __    ___   _     ___   _     __  ',
            '| |_)  / /\\  | |_) \\ \\_/ | |_) | | | ( (` ',
            '|_|   /_/--\\ |_|    |_|  |_| \\ \\_\\_/ _)_) ',
        ];
    }

    public static function render(OutputInterface $output): void
    {
        foreach (self::logoLines() as $line) {
            $output->writeln(self::formatLogoLine($line));
        }

        $output->writeln('');

        foreach (self::lines() as $line) {
            $output->writeln('<comment>'.OutputFormatter::escape($line).'</comment>');
        }

        $output->writeln('');
        Styles::header($output, 'Papyrus', self::TAGLINE);
    }

    private static function formatLogoLine(string $line): string
    {
        $parts = preg_split('/(▓+)/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$line];

        $formatted = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (str_contains($part, '▓')) {
                $formatted .= Styles::sand($part);
            } else {
                $formatted .= Styles::umber($part);
            }
        }

        return $formatted;
    }
}
