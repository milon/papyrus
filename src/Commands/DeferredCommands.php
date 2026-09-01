<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'build', description: 'Build PDF, EPUB, and HTML (not implemented)')]
final class BuildCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('build');
    }
}

#[AsCommand(name: 'epub', description: 'Build EPUB (not implemented)')]
final class EpubCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('epub');
    }
}

#[AsCommand(name: 'html', description: 'Build single-file HTML (not implemented)')]
final class HtmlCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('html');
    }
}

#[AsCommand(name: 'sample', description: 'Build sample PDF (not implemented)')]
final class SampleCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('sample');
    }
}

#[AsCommand(name: 'sort', description: 'Sort content files (not implemented)')]
final class SortCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('sort');
    }
}

#[AsCommand(name: 'sizes', description: 'List page-size presets (not implemented)')]
final class SizesCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('sizes');
    }
}

#[AsCommand(name: 'watch', description: 'Rebuild on file changes (not implemented)')]
final class WatchCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('watch');
    }
}

#[AsCommand(name: 'migrate-ibis', description: 'Migrate ibis.php to papyrus.php (not implemented)')]
final class MigrateIbisCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('migrate-ibis');
    }
}
