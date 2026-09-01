<?php

declare(strict_types=1);

namespace Milon\Papyrus\Commands\Kdp;

use Milon\Papyrus\Commands\NotImplementedCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'kdp', description: 'Build all enabled KDP outputs (not implemented)')]
final class KdpCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('kdp');
    }
}

#[AsCommand(name: 'kdp:ebook', description: 'Build KDP Kindle EPUB (not implemented)')]
final class KdpEbookCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('kdp ebook');
    }
}

#[AsCommand(name: 'kdp:print', description: 'Build KDP print PDF (not implemented)')]
final class KdpPrintCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('kdp print');
    }
}

#[AsCommand(name: 'kdp:cover', description: 'Build KDP cover assets (not implemented)')]
final class KdpCoverCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('kdp cover');
    }
}

#[AsCommand(name: 'kdp:metadata', description: 'Emit KDP metadata sidecar (not implemented)')]
final class KdpMetadataCommand extends NotImplementedCommand
{
    public function __construct()
    {
        parent::__construct('kdp metadata');
    }
}
