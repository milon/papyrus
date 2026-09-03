<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\DoctorCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorCommandTest extends TestCase
{
    #[Test]
    public function doctor_passes_for_mini_book(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';

        $tester = new CommandTester(new DoctorCommand);
        $exitCode = $tester->execute(['--dir' => $fixture]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Configuration OK', $tester->getDisplay());
        $this->assertStringContainsString('Mini Book', $tester->getDisplay());
        $this->assertStringContainsString('Theme light:', $tester->getDisplay());
    }

    #[Test]
    public function doctor_fails_without_config(): void
    {
        $target = sys_get_temp_dir().'/papyrus-doctor-'.uniqid('', true);
        mkdir($target);

        $tester = new CommandTester(new DoctorCommand);
        $exitCode = $tester->execute(['--dir' => $target]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Missing papyrus.php', $tester->getDisplay());

        rmdir($target);
    }

    #[Test]
    public function doctor_warns_about_missing_cover_and_site_link_chapters(): void
    {
        $bookDir = sys_get_temp_dir().'/papyrus-doctor-warn-'.uniqid('', true);
        mkdir($bookDir.'/content', 0755, true);
        mkdir($bookDir.'/assets', 0755, true);
        file_put_contents($bookDir.'/content/01.md', "---\ntitle: One\n---\n\nHello.\n");
        file_put_contents($bookDir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Doctor Warn Book',
    'author' => 'Papyrus',
    'themes' => ['light'],
    'cover' => [
        'image' => 'missing-cover.jpg',
    ],
    'site' => [
        'banner' => 'missing-banner.jpg',
        'base_path' => '/docs',
        'links' => [
            ['label' => 'Missing', 'chapter' => '99-missing.md'],
        ],
    ],
    'mermaid' => ['enabled' => false],
];
PHP);

        try {
            $tester = new CommandTester(new DoctorCommand);
            $exitCode = $tester->execute(['--dir' => $bookDir]);
            $display = $tester->getDisplay();

            $this->assertSame(0, $exitCode);
            $this->assertStringContainsString('Cover configured but missing', $display);
            $this->assertStringContainsString('Site banner configured but missing', $display);
            $this->assertStringContainsString('site.links chapter not found: 99-missing.md', $display);
            $this->assertStringContainsString('Site base_path: /docs', $display);
            $this->assertStringContainsString('Using bundled defaults', $display);
        } finally {
            $this->removeDir($bookDir);
        }
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
