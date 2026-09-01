<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown\Extensions;

use League\CommonMark\Node\Block\AbstractBlock;

final class Aside extends AbstractBlock
{
    public const TYPE_NOTE = 'note';

    public const TYPE_CAUTION = 'caution';

    public const TYPE_TIP = 'tip';

    public const TYPE_DANGER = 'danger';

    private readonly string $title;

    public function __construct(
        private readonly string $type = self::TYPE_NOTE,
        string $title = '',
    ) {
        $this->title = $title === '' ? ucwords($this->type) : $title;

        parent::__construct();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
