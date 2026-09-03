<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Commands\WatchCommand;
use Milon\Papyrus\Config\Project;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

final class WatchCommandTest extends TestCase
{
    #[Test]
    public function rebuild_arguments_forward_site_and_sample_flags(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $command = new WatchCommand;
        $input = new ArrayInput([
            '--dir' => $fixture,
            '--export' => '/tmp/papyrus-watch-export',
            '--with-site' => true,
            '--with-sample' => true,
        ]);
        $input->bind($command->getDefinition());

        $project = Project::load($fixture)->withExportDir('/tmp/papyrus-watch-export');
        $args = $command->rebuildArguments($input, $project);

        $this->assertSame($project->dir, $args['--dir']);
        $this->assertSame($project->exportDir, $args['--export']);
        $this->assertTrue($args['--with-site']);
        $this->assertTrue($args['--with-sample']);
    }

    #[Test]
    public function rebuild_arguments_omit_opt_in_flags_by_default(): void
    {
        $fixture = dirname(__DIR__).'/fixtures/mini-book';
        $command = new WatchCommand;
        $input = new ArrayInput(['--dir' => $fixture]);
        $input->bind($command->getDefinition());

        $args = $command->rebuildArguments($input, Project::load($fixture));

        $this->assertArrayNotHasKey('--with-site', $args);
        $this->assertArrayNotHasKey('--with-sample', $args);
        $this->assertArrayNotHasKey('--export', $args);
    }
}
