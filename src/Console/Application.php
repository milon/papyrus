<?php

declare(strict_types=1);

namespace Milon\Papyrus\Console;

use Milon\Papyrus\Commands\AssetPublishCommand;
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
use Milon\Papyrus\Commands\LintCommand;
use Milon\Papyrus\Commands\MigrateIbisCommand;
use Milon\Papyrus\Commands\PdfCommand;
use Milon\Papyrus\Commands\SampleCommand;
use Milon\Papyrus\Commands\SiteCommand;
use Milon\Papyrus\Commands\SizesCommand;
use Milon\Papyrus\Commands\WatchCommand;
use Milon\Papyrus\Render\VendorNotices;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Papyrus', '0.1.0');

        $this->addCommands([
            new InitCommand,
            new AssetPublishCommand,
            new DoctorCommand,
            new BuildCommand,
            new PdfCommand,
            new EpubCommand,
            new HtmlCommand,
            new SiteCommand,
            new SampleCommand,
            new LintCommand,
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

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if ($this->shouldRenderBanner($input)) {
            Banner::render($output);
            $output->writeln('');
        }

        $exitCode = parent::doRun($input, $output);

        $this->reportVendorNotices($output);

        return $exitCode;
    }

    private function reportVendorNotices(OutputInterface $output): void
    {
        $notices = VendorNotices::flush();

        if ($notices === [] || ! $output->isVerbose()) {
            return;
        }

        $output->writeln('');
        $output->writeln('<comment>Notices from third-party libraries (suppressed):</comment>');

        foreach ($notices as $notice) {
            $output->writeln('  '.$notice);
        }
    }

    private function shouldRenderBanner(InputInterface $input): bool
    {
        if ($input->hasParameterOption(['--quiet', '-q'], true)
            || $input->hasParameterOption('--silent', true)) {
            return false;
        }

        if ($input->hasParameterOption(['--version', '-V'], true)
            || $input->hasParameterOption(['--help', '-h'], true)) {
            return true;
        }

        $command = $input->getFirstArgument();

        return $command === null || $command === 'list' || $command === 'help';
    }

    public static function main(): int
    {
        $application = new self;

        return (int) $application->run();
    }
}
