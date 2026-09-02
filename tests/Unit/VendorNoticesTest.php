<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Render\VendorNotices;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class VendorNoticesTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function setUp(): void
    {
        VendorNotices::flush();
    }

    protected function tearDown(): void
    {
        VendorNotices::flush();

        foreach ($this->tempDirs as $dir) {
            $this->removeDirectory($dir);
        }

        $this->tempDirs = [];
    }

    #[Test]
    public function it_returns_the_callback_result(): void
    {
        $this->assertSame('done', VendorNotices::silence(fn (): string => 'done'));
    }

    #[Test]
    public function it_collects_third_party_notices_instead_of_printing_them(): void
    {
        $noisy = $this->thirdPartyTrigger(E_USER_DEPRECATED);

        ob_start();
        $this->withFullErrorReporting(function () use ($noisy): void {
            VendorNotices::silence(function () use ($noisy): void {
                $noisy();
                $noisy();
            });
        });
        $printed = (string) ob_get_clean();

        $this->assertSame('', $printed);

        $notices = VendorNotices::flush();

        $this->assertCount(1, $notices);
        $this->assertStringContainsString('acme/lib/Noisy.php', $notices[0]);
        $this->assertStringContainsString('noisy third-party call', $notices[0]);
        $this->assertStringContainsString('×2', $notices[0]);
    }

    #[Test]
    public function it_forwards_papyrus_diagnostics_to_the_surrounding_handler(): void
    {
        $seen = [];

        set_error_handler(function (int $severity, string $message) use (&$seen): bool {
            $seen[] = $message;

            return true;
        });

        try {
            VendorNotices::silence(function (): void {
                trigger_error('our own problem', E_USER_WARNING);
            });
        } finally {
            restore_error_handler();
        }

        $this->assertSame(['our own problem'], $seen);
        $this->assertSame([], VendorNotices::flush());
    }

    #[Test]
    public function it_ignores_diagnostics_that_error_reporting_has_masked(): void
    {
        $noisy = $this->thirdPartyTrigger(E_USER_NOTICE);

        $this->withFullErrorReporting(function () use ($noisy): void {
            VendorNotices::silence(function () use ($noisy): void {
                @$noisy();
            });
        });

        $this->assertSame([], VendorNotices::flush());
    }

    #[Test]
    public function it_restores_the_previous_handler_when_the_callback_throws(): void
    {
        $seen = [];

        set_error_handler(function (int $severity, string $message) use (&$seen): bool {
            $seen[] = $message;

            return true;
        });

        try {
            VendorNotices::silence(function (): void {
                throw new RuntimeException('render failed');
            });

            $this->fail('Expected the exception to bubble up.');
        } catch (RuntimeException $e) {
            $this->assertSame('render failed', $e->getMessage());
        }

        trigger_error('after the failure', E_USER_WARNING);
        restore_error_handler();

        $this->assertSame(['after the failure'], $seen);
    }

    /**
     * PHPUnit masks the levels it reports itself, so tests that care about
     * silenced calls have to run at the level a real CLI build runs at.
     *
     * @param  callable(): void  $callback
     */
    private function withFullErrorReporting(callable $callback): void
    {
        $level = error_reporting(E_ALL);

        try {
            $callback();
        } finally {
            error_reporting($level);
        }
    }

    /**
     * Builds a throwaway package under a vendor/ directory so the raised
     * diagnostic looks like it came from a Composer dependency.
     *
     * @return callable(): void
     */
    private function thirdPartyTrigger(int $severity): callable
    {
        $root = sys_get_temp_dir().'/papyrus-notices-'.uniqid('', true);
        $dir = $root.'/vendor/acme/lib';
        mkdir($dir, 0777, true);
        $this->tempDirs[] = $root;

        $function = 'papyrus_noisy_'.bin2hex(random_bytes(8));
        $file = $dir.'/Noisy.php';

        file_put_contents($file, sprintf(
            "<?php\n\nfunction %s(): void\n{\n    trigger_error('noisy third-party call', %d);\n}\n",
            $function,
            $severity,
        ));

        require $file;

        return $function;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.'/'.$entry;

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
