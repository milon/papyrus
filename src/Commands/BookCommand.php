<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class BookCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Book root directory (contains papyrus.php)',
            (string) getcwd(),
        );

        $this->addOption(
            'export',
            'e',
            InputOption::VALUE_REQUIRED,
            'Override export directory (default: <book>/export)',
        );
    }

    protected function projectDir(InputInterface $input): string
    {
        return (string) $input->getOption('dir');
    }

    protected function loadProject(InputInterface $input): Project
    {
        $project = Project::load($this->projectDir($input));
        $export = $input->getOption('export');

        if (! is_string($export) || $export === '') {
            return $project;
        }

        return $project->withExportDir($this->resolveExportDir($export));
    }

    protected function resolveExportDir(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if ($this->isAbsolutePath($path)) {
            return Project::normalizePath($path);
        }

        $cwd = getcwd() ?: '.';

        return Project::normalizePath($cwd.'/'.$path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || (bool) preg_match('#^[A-Za-z]:/#', $path);
    }
}
