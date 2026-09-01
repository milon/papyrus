<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\DoctorCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DoctorCommandTest extends TestCase
{
    public function test_doctor_passes_for_mini_book(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';

        $tester = new CommandTester(new DoctorCommand);
        $exitCode = $tester->execute(['--dir' => $fixture]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Configuration OK', $tester->getDisplay());
        $this->assertStringContainsString('Mini Book', $tester->getDisplay());
    }

    public function test_doctor_fails_without_config(): void
    {
        $target = sys_get_temp_dir().'/papyrus-doctor-'.uniqid('', true);
        mkdir($target);

        $tester = new CommandTester(new DoctorCommand);
        $exitCode = $tester->execute(['--dir' => $target]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Missing papyrus.php', $tester->getDisplay());

        rmdir($target);
    }
}
