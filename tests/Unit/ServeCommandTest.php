<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\ServeCommand;
use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Serve\SiteRouter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;

final class ServeCommandTest extends TestCase
{
    #[Test]
    public function it_fails_when_the_site_has_not_been_built(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-serve-missing-'.uniqid('', true);
        mkdir($bookDir.'/content', 0755, true);
        file_put_contents($bookDir.'/content/01.md', "---\ntitle: One\n---\n\nHello.\n");
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Serve Missing',
    'author' => 'Author',
    'themes' => ['light'],
    'export_dir' => 'export',
    'mermaid' => ['enabled' => false],
];
PHP);

        try {
            $tester = new CommandTester(new ServeCommand);
            $exitCode = $tester->execute(['--dir' => $bookDir]);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Site not found', $tester->getDisplay());
            $this->assertStringContainsString('build:site', $tester->getDisplay());
        } finally {
            $this->removeDir($bookDir);
        }
    }

    #[Test]
    public function it_builds_the_expected_php_server_command(): void
    {
        $command = new ServeCommand;

        $this->assertSame(
            escapeshellarg('/usr/bin/php').' -S '.escapeshellarg('127.0.0.1:8000').' '.escapeshellarg('/tmp/router.php'),
            $command->serverCommand('/usr/bin/php', '127.0.0.1', 8000, '/tmp/router.php'),
        );
    }

    #[Test]
    public function site_directory_matches_build_site_output(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $project = Project::load($fixture);
        $command = new ServeCommand;

        $this->assertSame(
            $project->exportDir.'/'.$project->outputSlug().'-site',
            $command->siteDirectory($project),
        );
    }

    #[Test]
    public function resolve_site_directory_accepts_explicit_site_path(): void
    {
        $command = new ServeCommand;
        $input = new ArrayInput([
            '--site' => 'docs/the-papyrus-handbook-site',
        ]);
        $input->bind($command->getDefinition());

        $resolved = $command->resolveSiteDirectory($input, null);
        $cwd = getcwd() ?: '.';

        $this->assertSame(Project::normalizePath($cwd.'/docs/the-papyrus-handbook-site'), $resolved);
    }

    #[Test]
    public function it_does_not_require_papyrus_php_when_site_is_set(): void
    {
        $missing = sys_get_temp_dir().'/papyrus-serve-missing-site-'.uniqid('', true);
        $tester = new CommandTester(new ServeCommand);
        $exitCode = $tester->execute(['--site' => $missing]);

        $this->assertSame(1, $exitCode);
        $this->assertStringNotContainsString('Missing papyrus.php', $tester->getDisplay());
        $this->assertStringContainsString('Site not found', $tester->getDisplay());
    }

    #[Test]
    public function resolve_site_directory_falls_back_to_export_slug_site(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $command = new ServeCommand;
        $project = Project::load($fixture)->withExportDir('/tmp/papyrus-docs');
        $input = new ArrayInput(['--dir' => $fixture]);
        $input->bind($command->getDefinition());

        $this->assertSame(
            $command->siteDirectory($project),
            $command->resolveSiteDirectory($input, $project),
        );
    }

    #[Test]
    public function router_serves_search_index_and_rewrites_base_path(): void
    {
        $siteDir = sys_get_temp_dir().'/papyrus-serve-site-'.uniqid('', true);
        mkdir($siteDir.'/assets', 0755, true);
        file_put_contents($siteDir.'/index.html', '<html>home</html>');
        file_put_contents($siteDir.'/404.html', '<html>missing</html>');
        file_put_contents($siteDir.'/assets/search.json', '[{"file":"index.html","title":"Home"}]');

        try {
            $router = new SiteRouter($siteDir);
            $search = $router->response('/assets/search.json');

            $this->assertSame(200, $search['status']);
            $this->assertSame('application/json; charset=UTF-8', $search['headers']['Content-Type']);
            $this->assertSame(realpath($siteDir.'/assets/search.json'), $search['file']);

            $prefixed = new SiteRouter($siteDir, '/docs/book');
            $redirect = $prefixed->response('/');
            $this->assertSame(302, $redirect['status']);
            $this->assertSame('/docs/book/', $redirect['headers']['Location']);

            $prefixedSearch = $prefixed->response('/docs/book/assets/search.json');
            $this->assertSame(200, $prefixedSearch['status']);
            $this->assertSame(realpath($siteDir.'/assets/search.json'), $prefixedSearch['file']);

            $missing = $router->response('/nope.html');
            $this->assertSame(404, $missing['status']);
            $this->assertSame(realpath($siteDir.'/404.html'), $missing['file']);

            $escape = $router->response('/../'.basename($siteDir).'/index.html');
            $this->assertSame(404, $escape['status']);
        } finally {
            $this->removeDir($siteDir);
        }
    }

    #[Test]
    public function php_s_serves_search_json_through_the_router(): void
    {
        $siteDir = sys_get_temp_dir().'/papyrus-serve-http-'.uniqid('', true);
        mkdir($siteDir.'/assets', 0755, true);
        file_put_contents($siteDir.'/index.html', '<html>home</html>');
        file_put_contents($siteDir.'/assets/search.json', '[{"file":"index.html","title":"Home"}]');

        $port = random_int(18000, 18999);
        $router = dirname(__DIR__, 2).'/src/Serve/router.php';
        $command = (new ServeCommand)->serverCommand(PHP_BINARY, '127.0.0.1', $port, $router);
        $env = getenv();

        if (! is_array($env)) {
            $env = [];
        }

        $env['PAPYRUS_SITE_DIR'] = $siteDir;
        $env['PAPYRUS_SITE_BASE'] = '';

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['file', sys_get_temp_dir().'/papyrus-serve-stdout-'.uniqid('', true), 'w'],
                2 => ['file', sys_get_temp_dir().'/papyrus-serve-stderr-'.uniqid('', true), 'w'],
            ],
            $pipes,
            $siteDir,
            $env,
        );

        $this->assertIsResource($process);

        try {
            $body = $this->waitForUrl('http://127.0.0.1:'.$port.'/assets/search.json');
            $this->assertIsString($body);
            $this->assertStringContainsString('"title":"Home"', $body);
        } finally {
            fclose($pipes[0]);
            proc_terminate($process);
            proc_close($process);
            $this->removeDir($siteDir);
        }
    }

    private function waitForUrl(string $url): ?string
    {
        $context = stream_context_create(['http' => ['timeout' => 0.5, 'ignore_errors' => true]]);

        for ($i = 0; $i < 20; $i++) {
            usleep(100_000);
            $body = @file_get_contents($url, false, $context);

            if (is_string($body)) {
                return $body;
            }
        }

        return null;
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

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
