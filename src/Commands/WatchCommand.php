<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Watch\ProjectWatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'watch', description: 'Rebuild on file changes')]
final class WatchCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'interval',
            'i',
            InputOption::VALUE_REQUIRED,
            'Poll interval in seconds',
            '2',
        );
        $this->addOption(
            'with-site',
            null,
            InputOption::VALUE_NONE,
            'Also run build:site on each rebuild',
        );
        $this->addOption(
            'with-sample',
            null,
            InputOption::VALUE_NONE,
            'Also run build:sample on each rebuild',
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

        $interval = max(1, (int) $input->getOption('interval'));
        $watcher = new ProjectWatcher;
        $paths = $watcher->watchedPaths(
            $project->dir,
            $project->contentDir,
            $project->assetsDir,
            $project->configPath,
        );
        $snapshot = $watcher->snapshot($paths);

        $output->writeln('<info>Watching for changes. Press Ctrl+C to stop.</info>');

        while (true) {
            sleep($interval);

            $current = $watcher->snapshot($paths);
            $changed = $watcher->changedFiles($snapshot, $current);

            if ($changed === []) {
                continue;
            }

            $snapshot = $current;
            $output->writeln('');
            $output->writeln('<comment>Change detected:</comment> '.basename($changed[0]));

            $build = $this->getApplication()?->find('build');

            if ($build === null) {
                $output->writeln('<error>Build command not registered.</error>');

                return Command::FAILURE;
            }

            $buildInput = new ArrayInput($this->rebuildArguments($input, $project));
            $buildInput->setInteractive(false);

            $exitCode = $build->run($buildInput, $output);

            if ($exitCode !== Command::SUCCESS) {
                $output->writeln('<error>Build failed.</error>');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rebuildArguments(InputInterface $input, Project $project): array
    {
        $buildArgs = ['--dir' => $project->dir];
        $export = $input->getOption('export');

        if (is_string($export) && $export !== '') {
            $buildArgs['--export'] = $project->exportDir;
        }

        if ((bool) $input->getOption('with-site')) {
            $buildArgs['--with-site'] = true;
        }

        if ((bool) $input->getOption('with-sample')) {
            $buildArgs['--with-sample'] = true;
        }

        if ((bool) $input->getOption('include-drafts')) {
            $buildArgs['--include-drafts'] = true;
        }

        return $buildArgs;
    }
}
