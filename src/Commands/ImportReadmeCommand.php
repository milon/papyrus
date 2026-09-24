<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Import\ImportException;
use Milon\Papyrus\Import\ReadmeImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'import-readme', description: 'Split a README.md into content/ chapters on ## headings')]
final class ImportReadmeCommand extends BookCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'file',
            null,
            InputOption::VALUE_REQUIRED,
            'README path (default: README.md / readme.md in --dir or its parent)',
        );
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing chapter files');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show planned chapters without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);
        $readme = $this->resolveReadme($input, $dir);

        if ($readme === null) {
            $output->writeln('<error>No README found. Pass --file or place README.md in the project directory.</error>');

            return self::FAILURE;
        }

        $markdown = file_get_contents($readme);

        if ($markdown === false) {
            $output->writeln('<error>Could not read README: '.$readme.'</error>');

            return self::FAILURE;
        }

        $importer = new ReadmeImporter;
        $chapters = $importer->plan($markdown);

        if ($chapters === []) {
            $output->writeln('<error>No chapters found in '.$readme.'</error>');

            return self::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');

        $output->writeln(sprintf('<info>README:</info> %s', $readme));
        $output->writeln(sprintf('<info>Chapters (%d):</info>', count($chapters)));

        foreach ($chapters as $chapter) {
            $output->writeln(sprintf('  %s — %s', $chapter['filename'], $chapter['title']));
        }

        if ($dryRun) {
            $output->writeln('<comment>Dry run — nothing written.</comment>');

            return self::SUCCESS;
        }

        try {
            $written = $importer->write($dir.'/content', $chapters, $force);
        } catch (ImportException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return self::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>Wrote:</info>');
        foreach ($written as $path) {
            $output->writeln('  '.$path);
        }
        $output->writeln('<comment>Tip:</comment> update <info>site.nav</info> to match the new chapter list.');

        return self::SUCCESS;
    }

    private function resolveReadme(InputInterface $input, string $dir): ?string
    {
        $file = $input->getOption('file');

        if (is_string($file) && trim($file) !== '') {
            $path = $this->resolvePath(trim($file));

            return is_file($path) ? $path : null;
        }

        foreach ([$dir, dirname($dir)] as $base) {
            foreach (['README.md', 'readme.md', 'Readme.md'] as $name) {
                $candidate = $base.'/'.$name;

                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function resolvePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        if (str_starts_with($path, '/') || (bool) preg_match('#^[A-Za-z]:/#', $path)) {
            return $path;
        }

        $cwd = getcwd() ?: '.';

        return $cwd.'/'.$path;
    }
}
