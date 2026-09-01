<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
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

            $exitCode = $build->run(
                new ArrayInput(['--dir' => $project->dir]),
                $output,
            );

            if ($exitCode !== Command::SUCCESS) {
                $output->writeln('<error>Build failed.</error>');
            }
        }
    }
}
