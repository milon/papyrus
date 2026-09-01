<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class NotImplementedCommand extends BookCommand
{
    public function __construct(
        private readonly string $feature,
        private readonly string $milestone,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf(
            '<comment>%s is not implemented yet (planned %s).</comment>',
            $this->feature,
            $this->milestone,
        ));

        return Command::FAILURE;
    }
}
