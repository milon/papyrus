<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\MermaidConfig;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Mermaid\MermaidCache;
use Milon\Papyrus\Mermaid\MermaidCli;
use Milon\Papyrus\Mermaid\MermaidException;
use Milon\Papyrus\Mermaid\MermaidRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MermaidRendererTest extends TestCase
{
    #[Test]
    public function it_replaces_mermaid_fences_with_figure_html(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-mermaid-'.uniqid('', true);
        mkdir($dir);
        mkdir($dir.'/content');
        mkdir($dir.'/assets');

        $markdown = <<<'MD'
---
title: Diagram
---

```mermaid
flowchart TD
  A --> B
```
MD;

        file_put_contents($dir.'/content/chapter.md', $markdown);
        file_put_contents($dir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Mermaid Book',
    'mermaid' => [
        'enabled' => true,
        'format' => 'svg',
        'theme' => 'default',
    ],
];
PHP);

        try {
            $project = Project::load($dir);
            $book = $project->bookConverter()->convertDirectory($project->contentDir);
            $renderer = new MermaidRenderer(
                project: $project,
                cli: new FakeMermaidCli,
                cache: new MermaidCache($dir.'/.papyrus/cache/mermaid'),
                config: MermaidConfig::fromConfig($project->config),
            );

            $processed = $renderer->processBook($book, 'default');
            $html = $processed->chapters[0]->html;

            $this->assertStringContainsString('<figure class="mermaid"', $html);
            $this->assertStringContainsString('<svg', $html);
            $this->assertStringNotContainsString('language-mermaid', $html);
        } finally {
            $this->removeDirectory($dir);
        }
    }

    #[Test]
    public function it_uses_cache_on_second_render(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-mermaid-cache-'.uniqid('', true);
        mkdir($dir);
        mkdir($dir.'/content');
        mkdir($dir.'/assets');

        $markdown = "```mermaid\nflowchart TD\n  A --> B\n```\n";
        file_put_contents($dir.'/content/chapter.md', $markdown);
        file_put_contents($dir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Cache',
    'mermaid' => ['enabled' => true, 'format' => 'svg', 'theme' => 'default'],
];
PHP);

        try {
            $project = Project::load($dir);
            $book = $project->bookConverter()->convertDirectory($project->contentDir);
            $cli = new FakeMermaidCli;
            $renderer = new MermaidRenderer(
                project: $project,
                cli: $cli,
                cache: new MermaidCache($dir.'/.papyrus/cache/mermaid'),
                config: MermaidConfig::fromConfig($project->config),
            );

            $renderer->processBook($book, 'default');
            $this->assertSame(1, $cli->renderCount);

            $renderer->processBook($book, 'default');
            $this->assertSame(1, $cli->renderCount);
        } finally {
            $this->removeDirectory($dir);
        }
    }

    #[Test]
    public function render_errors_include_source_file_and_line(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-mermaid-error-'.uniqid('', true);
        mkdir($dir);
        mkdir($dir.'/content');
        mkdir($dir.'/assets');

        file_put_contents($dir.'/content/chapter.md', "```mermaid\nbad diagram\n```\n");
        file_put_contents($dir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Error',
    'mermaid' => ['enabled' => true],
];
PHP);

        try {
            $project = Project::load($dir);
            $book = $project->bookConverter()->convertDirectory($project->contentDir);
            $renderer = new MermaidRenderer(
                project: $project,
                cli: new FailingMermaidCli,
                cache: new MermaidCache($dir.'/.papyrus/cache/mermaid'),
                config: MermaidConfig::fromConfig($project->config),
            );

            $this->expectException(MermaidException::class);
            $this->expectExceptionMessageMatches('/chapter\.md:\d+: invalid diagram/');

            $renderer->processBook($book, 'default');
        } finally {
            $this->removeDirectory($dir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}

final class FakeMermaidCli implements MermaidCli
{
    public int $renderCount = 0;

    public function command(): string
    {
        return 'fake-mmdc';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function version(): ?string
    {
        return '11.0.0';
    }

    public function render(string $inputPath, string $outputPath, string $theme): void
    {
        $this->renderCount++;
        file_put_contents($outputPath, '<svg xmlns="http://www.w3.org/2000/svg"><text>Diagram</text></svg>');
    }
}

final class FailingMermaidCli implements MermaidCli
{
    public function command(): string
    {
        return 'fake-mmdc';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function version(): ?string
    {
        return '11.0.0';
    }

    public function render(string $inputPath, string $outputPath, string $theme): void
    {
        throw new MermaidException('invalid diagram');
    }
}
