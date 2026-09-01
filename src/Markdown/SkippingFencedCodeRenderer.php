<?php

declare(strict_types=1);

namespace Milon\Papyrus\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;

final class SkippingFencedCodeRenderer implements NodeRendererInterface
{
    /**
     * @param  list<string>  $highlightLanguages
     * @param  list<string>  $skipLanguages
     */
    public function __construct(
        private readonly FencedCodeRenderer $inner,
        private readonly array $skipLanguages = ['mermaid', 'mmd'],
    ) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        FencedCode::assertInstanceOf($node);

        $language = $node->getInfo();
        if ($language !== null && in_array(strtolower($language), $this->skipLanguages, true)) {
            return new HtmlElement('pre', [], new HtmlElement('code', [
                'class' => 'language-'.$language,
            ], $node->getLiteral()));
        }

        return $this->inner->render($node, $childRenderer);
    }
}
