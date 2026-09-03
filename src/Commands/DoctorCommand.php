<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\KdpTrimBounds;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Mermaid\MermaidCliResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'doctor', description: 'Validate book project configuration')]
final class DoctorCommand extends BookCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir($input);

        try {
            $project = $this->loadProject($input);
        } catch (ConfigException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $checks = [
            ['Config', true, $project->configPath],
            ['Content dir', is_dir($project->contentDir), $project->contentDir],
            ['Assets dir', is_dir($project->assetsDir), $project->assetsDir],
        ];

        $ok = true;

        foreach ($checks as [$label, $pass, $path]) {
            if ($pass) {
                $output->writeln(sprintf('<info>✓</info> %s: %s', $label, $path));
            } else {
                $output->writeln(sprintf('<error>✗</error> %s: %s', $label, $path));
                $ok = false;
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('Title: %s', $project->title()));

        if ($project->subtitle() !== '') {
            $output->writeln(sprintf('Subtitle: %s', $project->subtitle()));
        }

        if ($project->author() !== '') {
            $output->writeln(sprintf('Author: %s', $project->author()));
        }

        $output->writeln('Themes: '.implode(', ', $project->themes()));

        $document = $project->documentSize();

        if (! KdpTrimBounds::isWithinBounds($document)) {
            $output->writeln(sprintf(
                '<comment>! Document trim %.3f × %.3f mm is outside typical KDP paperback bounds (%.1f–%.1f × %.1f–%.1f mm).</comment>',
                $document->widthMm,
                $document->heightMm,
                KdpTrimBounds::MIN_WIDTH_MM,
                KdpTrimBounds::MAX_WIDTH_MM,
                KdpTrimBounds::MIN_HEIGHT_MM,
                KdpTrimBounds::MAX_HEIGHT_MM,
            ));
        } else {
            $preset = DocumentSize::resolvePresetName($document->widthMm, $document->heightMm);
            $label = $preset ?? 'custom';

            $output->writeln(sprintf(
                'Document: %.3f × %.3f mm (%s)',
                $document->widthMm,
                $document->heightMm,
                $label,
            ));
        }

        $this->reportThemeSources($project, $output);
        $this->reportCoverAssets($project, $output);
        $this->reportSiteAssets($project, $output);
        $this->reportSiteLinkChapters($project, $output);

        if ($project->mermaidConfig()->enabled) {
            $cli = MermaidCliResolver::resolve($project->mermaidConfig()->command);

            if ($cli->isAvailable()) {
                $output->writeln(sprintf('<info>✓</info> Mermaid CLI: %s (%s)', $cli->command(), $cli->version()));
            } else {
                $output->writeln('<comment>! Mermaid is enabled but mmdc (@mermaid-js/mermaid-cli) was not found.</comment>');
            }
        }

        if (! $ok) {
            $output->writeln('');
            $output->writeln('<error>Doctor found problems.</error>');

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>Configuration OK.</info>');

        return Command::SUCCESS;
    }

    private function reportThemeSources(Project $project, OutputInterface $output): void
    {
        $usingBundled = [];

        foreach ($project->themes() as $theme) {
            $relative = 'theme-'.$theme.'.html';
            $projectPath = $project->assetsDir.'/'.$relative;

            if (is_file($projectPath)) {
                $output->writeln(sprintf('<info>✓</info> Theme %s: %s', $theme, $projectPath));

                continue;
            }

            if ($project->assetPath($relative) !== null) {
                $usingBundled[] = $theme;
            } else {
                $output->writeln(sprintf('<comment>! Theme %s: missing (no project or bundled theme-%s.html)</comment>', $theme, $theme));
            }
        }

        $htmlTheme = $project->assetsDir.'/theme-html.html';

        if (is_file($htmlTheme)) {
            $output->writeln(sprintf('<info>✓</info> HTML theme: %s', $htmlTheme));
        } elseif ($project->assetPath('theme-html.html') !== null) {
            $usingBundled[] = 'html';
        }

        if ($usingBundled !== []) {
            $output->writeln(sprintf(
                '<comment>! Using bundled defaults for: %s (run papyrus asset:publish to customize)</comment>',
                implode(', ', $usingBundled),
            ));
        }
    }

    private function reportCoverAssets(Project $project, OutputInterface $output): void
    {
        $cover = $project->config['cover'] ?? [];

        if (! is_array($cover)) {
            return;
        }

        $names = [];

        foreach (['image', 'light', 'dark'] as $key) {
            $name = $cover[$key] ?? null;

            if (is_string($name) && $name !== '') {
                $names[$name] = true;
            }
        }

        foreach (array_keys($names) as $name) {
            $path = $project->assetsDir.'/'.$name;

            if (is_file($path)) {
                $output->writeln(sprintf('<info>✓</info> Cover: %s', $path));
            } else {
                $output->writeln(sprintf('<comment>! Cover configured but missing: %s</comment>', $path));
            }
        }
    }

    private function reportSiteAssets(Project $project, OutputInterface $output): void
    {
        $banner = $project->siteBanner();

        if ($banner !== null) {
            $path = $project->assetsDir.'/'.$banner;

            if (is_file($path)) {
                $output->writeln(sprintf('<info>✓</info> Site banner: %s', $path));
            } else {
                $output->writeln(sprintf('<comment>! Site banner configured but missing: %s</comment>', $path));
            }
        }

        $basePath = $project->siteBasePath();

        if ($basePath !== '') {
            $output->writeln(sprintf('Site base_path: %s', $basePath));
        }

        $cname = $project->siteCname();

        if ($cname !== null) {
            $output->writeln(sprintf('Site cname: %s', $cname));
        }
    }

    private function reportSiteLinkChapters(Project $project, OutputInterface $output): void
    {
        $chapterNames = [];

        foreach ($project->siteLinks() as $link) {
            if (! isset($link['chapter'])) {
                continue;
            }

            $chapterNames[] = $link['chapter'];
        }

        if ($chapterNames === []) {
            return;
        }

        if (! is_dir($project->contentDir)) {
            return;
        }

        $book = $project->bookConverter(useCache: false)->convertDirectory($project->contentDir);
        $missing = $book->missingChapterNames($chapterNames);

        foreach ($missing as $name) {
            $output->writeln(sprintf('<comment>! site.links chapter not found: %s</comment>', $name));
        }
    }
}
