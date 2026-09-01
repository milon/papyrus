<?php

declare(strict_types=1);

namespace Milon\Papyrus\Console;

use Milon\Papyrus\Commands\BuildCommand;
use Milon\Papyrus\Commands\DoctorCommand;
use Milon\Papyrus\Commands\EpubCommand;
use Milon\Papyrus\Commands\HtmlCommand;
use Milon\Papyrus\Commands\InitCommand;
use Milon\Papyrus\Commands\Kdp\KdpCommand;
use Milon\Papyrus\Commands\Kdp\KdpCoverCommand;
use Milon\Papyrus\Commands\Kdp\KdpEbookCommand;
use Milon\Papyrus\Commands\Kdp\KdpMetadataCommand;
use Milon\Papyrus\Commands\Kdp\KdpPrintCommand;
use Milon\Papyrus\Commands\MigrateIbisCommand;
use Milon\Papyrus\Commands\PdfCommand;
use Milon\Papyrus\Commands\SampleCommand;
use Milon\Papyrus\Commands\SizesCommand;
use Milon\Papyrus\Commands\SortCommand;
use Milon\Papyrus\Commands\WatchCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Papyrus', '0.1.0-dev');

        $this->addCommands([
            new InitCommand,
            new DoctorCommand,
            new BuildCommand,
            new PdfCommand,
            new EpubCommand,
            new HtmlCommand,
            new SampleCommand,
            new SortCommand,
            new SizesCommand,
            new WatchCommand,
            new MigrateIbisCommand,
            new KdpCommand,
            new KdpEbookCommand,
            new KdpPrintCommand,
            new KdpCoverCommand,
            new KdpMetadataCommand,
        ]);
    }

    public static function main(): int
    {
        $application = new self;

        return (int) $application->run();
    }
}
