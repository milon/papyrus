<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Lint\CodeFenceLinter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'lint', description: 'Lint PHP code fences in content/')]
final class LintCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('fix', 'f', InputOption::VALUE_NONE, 'Apply auto-fixes for open tags and comment runs')
            ->addOption('max-width', null, InputOption::VALUE_REQUIRED, 'Maximum line width for PHP fences', '66');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $project = $this->loadProject($input);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $maxWidth = (int) $input->getOption('max-width');
        $fix = (bool) $input->getOption('fix');
        $result = (new CodeFenceLinter($maxWidth))->lintDirectory($project->contentDir, $fix);

        foreach ($result->fixed as $message) {
            $output->writeln('<info>FIXED:</info> '.$message);
        }

        foreach ($result->skipped as $message) {
            $output->writeln('<comment>SKIPPED:</comment> '.$message);
        }

        foreach ($result->issues as $issue) {
            $output->writeln(sprintf(
                '<comment>WARN:</comment> %s:%d %s',
                $issue->file,
                $issue->line,
                $issue->message,
            ));
        }

        $output->writeln('');
        $output->writeln(sprintf(
            '%d fence(s) fixed, %d fence(s) skipped, %d line(s) over width.',
            count($result->fixed),
            count($result->skipped),
            count($result->issues),
        ));

        if (! $fix && $result->fixed !== []) {
            $output->writeln('Run with --fix to apply auto-fixes.');
        }

        return $result->hasProblems($fix) ? Command::FAILURE : Command::SUCCESS;
    }
}
