<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Epub;

final class EpubOptions
{
    public function __construct(
        public readonly ?string $coverImageName = null,
        public readonly ?string $description = null,
    ) {}
}
