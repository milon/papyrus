<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown\Extensions;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class AsideRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        Aside::assertInstanceOf($node);

        $attrs = $node->data->getData('attributes');
        $contents = $childRenderer->renderNodes($node->children());
        $body = new HtmlElement('div', $attrs->export(), $contents);

        $title = '';
        if ($node->getTitle() !== '') {
            $title = (string) new HtmlElement('div', ['class' => 'title'], $node->getTitle());
        }

        return new HtmlElement(
            'blockquote',
            ['class' => $node->getType()],
            $title.$body,
        );
    }
}
