<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'sort', description: 'Sort content files (not implemented)')]
final class SortCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('sort');
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
