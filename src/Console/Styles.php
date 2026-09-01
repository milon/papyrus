<?php

declare(strict_types=1);

namespace Milon\Papyrus\Console;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Terminal styling — palette matches the Go Lip Gloss implementation.
 */
final class Styles
{
    public const string COLOR_SAND = '#E8D5B5';

    public const string COLOR_UMBER = '#6B4F3B';

    public const string COLOR_INK = '#C4A574';

    public const string COLOR_OK = '#7D9B76';

    public const string COLOR_WARN = '#C4A35A';

    public const string COLOR_ERR = '#C17B6A';

    public const string COLOR_MUTED = '#8A8279';

    public const string COLOR_BRIGHT = '#F5E6D0';

    public static function title(string $text): string
    {
        return sprintf('<fg=%s;options=bold>%s</>', self::COLOR_BRIGHT, OutputFormatter::escape($text));
    }

    public static function subtitle(string $text): string
    {
        return sprintf('<fg=%s>%s</>', self::COLOR_INK, OutputFormatter::escape($text));
    }

    public static function sand(string $text, bool $bold = false): string
    {
        $options = $bold ? ';options=bold' : '';

        return sprintf('<fg=%s%s>%s</>', self::COLOR_SAND, $options, OutputFormatter::escape($text));
    }

    public static function umber(string $text, bool $bold = false): string
    {
        $options = $bold ? ';options=bold' : '';

        return sprintf('<fg=%s%s>%s</>', self::COLOR_UMBER, $options, OutputFormatter::escape($text));
    }

    public static function muted(string $text): string
    {
        return sprintf('<fg=%s>%s</>', self::COLOR_MUTED, OutputFormatter::escape($text));
    }

    public static function successMsg(string $text): string
    {
        return sprintf(
            '<fg=%s;options=bold>✓ </>%s',
            self::COLOR_OK,
            self::title($text),
        );
    }

    public static function errorMsg(string $text): string
    {
        return sprintf(
            '<fg=%s;options=bold>✗ </><fg=%s>%s</>',
            self::COLOR_ERR,
            self::COLOR_ERR,
            OutputFormatter::escape($text),
        );
    }

    public static function step(string $text): string
    {
        return self::umber('→ ', true).self::subtitle($text);
    }

    public static function item(string $path): string
    {
        return self::muted('  · ').self::title($path);
    }

    public static function header(OutputInterface $output, string $title, string $subtitle = ''): void
    {
        $inner = ' '.$title.' ';
        $width = mb_strlen($inner);
        $border = self::umber('╭'.str_repeat('─', $width).'╮');
        $line = self::umber('│').self::sand($inner, true).self::umber('│');
        $footer = self::umber('╰'.str_repeat('─', $width).'╯');

        $output->writeln($border);
        $output->writeln($line);
        $output->writeln($footer);

        if ($subtitle !== '') {
            $output->writeln(self::subtitle($subtitle));
        }
    }
}
