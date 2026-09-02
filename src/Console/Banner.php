<?php

declare(strict_types=1);

namespace Milon\Papyrus\Console;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

final class Banner
{
    public const string TAGLINE = 'Markdown to Book (PDF, EPUB, HTML, and KDP)';

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
        foreach (self::lines() as $line) {
            $output->writeln('<comment>'.OutputFormatter::escape($line).'</comment>');
        }

        $output->writeln('');
        Styles::header($output, 'Papyrus', self::TAGLINE);
    }
}
