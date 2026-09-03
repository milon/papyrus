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
    /**
     * @var list<string>
     */
    private const GROUPS = ['themes', 'css', 'fonts'];

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing asset files');
        $this->addOption(
            'only',
            null,
            InputOption::VALUE_REQUIRED,
            'Comma-separated asset groups to publish: themes, css, fonts (default: all)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->loadProject($input);
        $force = (bool) $input->getOption('force');
        $repo = StubRepository::default();
        $written = [];

        try {
            $groups = $this->resolveGroups($input->getOption('only'));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        if (! is_dir($project->assetsDir) && ! mkdir($project->assetsDir, 0o755, true) && ! is_dir($project->assetsDir)) {
            $output->writeln('<error>Could not create assets directory: '.$project->assetsDir.'</error>');

            return self::FAILURE;
        }

        foreach ($repo->assetFiles() as $relative) {
            if (! $this->matchesGroups($relative, $groups)) {
                continue;
            }

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
            $output->writeln('<comment>No assets written. Use --force to overwrite, or check --only.</comment>');

            return self::SUCCESS;
        }

        $output->writeln('<info>Published assets into '.$project->assetsDir.':</info>');

        foreach ($written as $path) {
            $output->writeln('  '.$path);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveGroups(mixed $only): array
    {
        if (! is_string($only) || trim($only) === '') {
            return self::GROUPS;
        }

        $requested = array_values(array_filter(array_map(
            static fn (string $group): string => strtolower(trim($group)),
            explode(',', $only),
        )));

        if ($requested === []) {
            return self::GROUPS;
        }

        $unknown = array_values(array_diff($requested, self::GROUPS));

        if ($unknown !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown asset group(s): %s. Allowed: %s.',
                implode(', ', $unknown),
                implode(', ', self::GROUPS),
            ));
        }

        return array_values(array_unique($requested));
    }

    /**
     * @param  list<string>  $groups
     */
    private function matchesGroups(string $relative, array $groups): bool
    {
        $group = $this->groupFor($relative);

        return $group !== null && in_array($group, $groups, true);
    }

    private function groupFor(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if (str_starts_with($relative, 'fonts/')) {
            return 'fonts';
        }

        if (preg_match('/^theme(-[a-z0-9]+)?\.html$/i', $relative) === 1) {
            return 'themes';
        }

        if (preg_match('/\.(css)$/i', $relative) === 1) {
            return 'css';
        }

        return null;
    }
}
