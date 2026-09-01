<?php

declare(strict_types=1);

namespace Milon\Papyrus\Mermaid;

final class MermaidCliResolver
{
    /**
     * @var list<string>
     */
    private const CANDIDATES = [
        'mmdc',
        'npx -y @mermaid-js/mermaid-cli',
    ];

    public static function resolve(?string $configured): MermaidCli
    {
        if ($configured !== null && $configured !== '') {
            return new MermaidCliCommand($configured);
        }

        foreach (self::CANDIDATES as $candidate) {
            $cli = new MermaidCliCommand($candidate);

            if ($cli->isAvailable()) {
                return $cli;
            }
        }

        return new MermaidCliCommand('mmdc');
    }
}
