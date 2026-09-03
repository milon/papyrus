<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Render\Html\HtmlException;
use Milon\Papyrus\Render\Html\SiteRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'serve', description: 'Serve the generated site locally with php -S')]
final class ServeCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'host',
            null,
            InputOption::VALUE_REQUIRED,
            'Bind address',
            '127.0.0.1',
        );
        $this->addOption(
            'port',
            'p',
            InputOption::VALUE_REQUIRED,
            'Bind port',
            '8000',
        );
        $this->addOption(
            'build',
            null,
            InputOption::VALUE_NONE,
            'Run build:site before serving',
        );
        $this->addOption(
            'site',
            's',
            InputOption::VALUE_REQUIRED,
            'Site directory to serve (default: <export>/<slug>-site)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');
        $explicitSite = $this->explicitSiteOption($input);
        $wantsBuild = (bool) $input->getOption('build');

        if (! $this->isValidHost($host)) {
            $output->writeln('<error>Invalid --host value.</error>');

            return Command::FAILURE;
        }

        if ($port < 1 || $port > 65535) {
            $output->writeln('<error>Invalid --port value.</error>');

            return Command::FAILURE;
        }

        $project = null;

        if ($explicitSite === null || $wantsBuild) {
            try {
                $project = $this->loadProject($input);
            } catch (ConfigException $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');

                if ($explicitSite === null) {
                    $output->writeln('Pass <info>--site</info> to serve a built site folder without a book project.');
                }

                return Command::FAILURE;
            }
        }

        $siteDir = $this->resolveSiteDirectory($input, $project);

        if ($wantsBuild) {
            try {
                $path = (new SiteRenderer($project))->render($siteDir);
                $output->writeln(sprintf('<info>✓</info> %s', $path));
            } catch (HtmlException|MermaidException $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');

                return Command::FAILURE;
            }
        }

        if (! is_dir($siteDir) || ! is_file($siteDir.'/index.html')) {
            $output->writeln('<error>Site not found at '.$siteDir.'.</error>');
            $output->writeln('Run <info>papyrus build:site</info> first, or pass <info>--build</info>.');

            return Command::FAILURE;
        }

        $router = dirname(__DIR__).'/Serve/router.php';

        if (! is_file($router)) {
            $output->writeln('<error>Site router not found: '.$router.'</error>');

            return Command::FAILURE;
        }

        $basePath = $project?->siteBasePath() ?? '';
        $url = 'http://'.$host.':'.$port.($basePath === '' ? '/' : $basePath.'/');

        $output->writeln('<info>Serving '.$siteDir.'</info>');
        $output->writeln('Open <comment>'.$url.'</comment> (Ctrl+C to stop)');

        return $this->startServer(
            $this->serverCommand(PHP_BINARY, $host, $port, $router),
            $siteDir,
            $basePath,
        );
    }

    public function siteDirectory(Project $project): string
    {
        return sprintf('%s/%s-site', $project->exportDir, $project->outputSlug());
    }

    public function resolveSiteDirectory(InputInterface $input, ?Project $project): string
    {
        $site = $this->explicitSiteOption($input);

        if ($site !== null) {
            return $this->resolveExportDir($site);
        }

        if ($project === null) {
            throw new ConfigException('A book project is required when --site is not set.');
        }

        return $this->siteDirectory($project);
    }

    public function serverCommand(string $phpBinary, string $host, int $port, string $router): string
    {
        return sprintf(
            '%s -S %s %s',
            escapeshellarg($phpBinary),
            escapeshellarg($host.':'.$port),
            escapeshellarg($router),
        );
    }

    private function explicitSiteOption(InputInterface $input): ?string
    {
        $site = $input->getOption('site');

        if (! is_string($site) || $site === '') {
            return null;
        }

        return $site;
    }

    private function startServer(string $command, string $siteDir, string $basePath): int
    {
        $env = getenv();

        if (! is_array($env)) {
            $env = [];
        }

        $env['PAPYRUS_SITE_DIR'] = $siteDir;
        $env['PAPYRUS_SITE_BASE'] = $basePath;

        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $siteDir, $env);

        if (! is_resource($process)) {
            return Command::FAILURE;
        }

        return proc_close($process);
    }

    private function isValidHost(string $host): bool
    {
        return $host !== '' && (bool) preg_match('/^[A-Za-z0-9.-]+$/', $host);
    }
}
