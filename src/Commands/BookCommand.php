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
    }

    protected function projectDir(InputInterface $input): string
    {
        return (string) $input->getOption('dir');
    }

    protected function loadProject(InputInterface $input): Project
    {
        return Project::load($this->projectDir($input));
    }
}
