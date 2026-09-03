<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Stubs\StubRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'asset:publish', description: 'Publish bundled themes, CSS, and fonts into assets/')]
final class AssetPublishCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing asset files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->loadProject($input);
        $force = (bool) $input->getOption('force');
        $repo = StubRepository::default();
        $written = [];

        if (! is_dir($project->assetsDir) && ! mkdir($project->assetsDir, 0o755, true) && ! is_dir($project->assetsDir)) {
            $output->writeln('<error>Could not create assets directory: '.$project->assetsDir.'</error>');

            return self::FAILURE;
        }

        foreach ($repo->assetFiles() as $relative) {
            $target = $project->assetsDir.'/'.$relative;
            $parent = dirname($target);

            if (! is_dir($parent) && ! mkdir($parent, 0o755, true) && ! is_dir($parent)) {
                $output->writeln('<error>Could not create directory: '.$parent.'</error>');

                return self::FAILURE;
            }

            if (is_file($target) && ! $force) {
                $output->writeln('<comment>Skipped (exists): assets/'.$relative.'</comment>');

                continue;
            }

            file_put_contents($target, $repo->read('assets/'.$relative));
            $written[] = 'assets/'.$relative;
        }

        if ($written === []) {
            $output->writeln('<comment>No assets written. Use --force to overwrite.</comment>');

            return self::SUCCESS;
        }

        $output->writeln('<info>Published assets into '.$project->assetsDir.':</info>');

        foreach ($written as $path) {
            $output->writeln('  '.$path);
        }

        return self::SUCCESS;
    }
}
