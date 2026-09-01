<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown\Extensions;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class AsideParser implements BlockStartParserInterface
{
    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        $remainder = $cursor->getRemainder();
        $type = Aside::TYPE_NOTE;
        $title = 'Note';
        $isAside = false;

        if (str_starts_with($remainder, ':::note') || str_starts_with($remainder, ':::notice')) {
            $isAside = true;
        } elseif (str_starts_with($remainder, ':::warning') || str_starts_with($remainder, ':::caution')) {
            $type = Aside::TYPE_CAUTION;
            $title = 'Caution';
            $isAside = true;
        } elseif (str_starts_with($remainder, ':::tip')) {
            $type = Aside::TYPE_TIP;
            $title = 'Tip';
            $isAside = true;
        } elseif (str_starts_with($remainder, ':::danger')) {
            $type = Aside::TYPE_DANGER;
            $title = 'Danger';
            $isAside = true;
        }

        if (! $isAside) {
            return BlockStart::none();
        }

        if (preg_match('/\[([^]]+)\]/', $remainder, $matches) === 1) {
            $title = $matches[1];
        }

        $cursor->advanceToNextNonSpaceOrTab();
        $cursor->advanceToEnd();

        return BlockStart::of(new AsideBlockParser($type, $title))->at($cursor);
    }
}
