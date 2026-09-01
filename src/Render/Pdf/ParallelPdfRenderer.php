<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Pdf;

use Milon\Papyrus\Config\Project;

final class ParallelPdfRenderer
{
    public function __construct(
        private readonly Project $project,
        private readonly string $papyrusBinary,
    ) {}

    /**
     * @param  list<string>  $themes
     * @return array<string, string|PdfException>
     */
    public function render(array $themes): array
    {
        $processes = [];

        foreach ($themes as $theme) {
            $command = [
                PHP_BINARY,
                $this->papyrusBinary,
                'pdf',
                '--dir',
                $this->project->dir,
                '--theme',
                $theme,
            ];

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptors, $pipes, $this->project->dir);

            if (! is_resource($process)) {
                return [$theme => new PdfException(sprintf('Unable to start PDF build for theme %s.', $theme))];
            }

            fclose($pipes[0]);
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $processes[$theme] = [
                'process' => $process,
                'stdout' => $pipes[1],
                'stderr' => $pipes[2],
            ];
        }

        $results = [];

        while ($processes !== []) {
            foreach ($processes as $theme => $state) {
                $status = proc_get_status($state['process']);

                if ($status['running']) {
                    continue;
                }

                $stdout = stream_get_contents($state['stdout']) ?: '';
                $stderr = stream_get_contents($state['stderr']) ?: '';
                fclose($state['stdout']);
                fclose($state['stderr']);
                proc_close($state['process']);
                unset($processes[$theme]);

                if ($status['exitcode'] !== 0) {
                    $results[$theme] = new PdfException(trim($stderr !== '' ? $stderr : $stdout) ?: 'PDF build failed.');

                    continue;
                }

                $results[$theme] = sprintf(
                    '%s/%s-%s.pdf',
                    $this->project->exportDir,
                    $this->project->outputSlug(),
                    $theme,
                );
            }

            if ($processes !== []) {
                usleep(100_000);
            }
        }

        return $results;
    }
}
