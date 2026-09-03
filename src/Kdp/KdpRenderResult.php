<?php

declare(strict_types=1);

namespace Milon\Papyrus\Kdp;

final class KdpRenderResult
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly string $path,
        public readonly array $warnings = [],
    ) {}
}
