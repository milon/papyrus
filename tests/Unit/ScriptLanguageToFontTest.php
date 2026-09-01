<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Render\Pdf\ScriptLanguageToFont;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScriptLanguageToFontTest extends TestCase
{
    #[Test]
    public function first_matching_script_rule_wins(): void
    {
        $resolver = new ScriptLanguageToFont([
            ['match' => ['bn', 'bengali'], 'face' => 'notosansbengali'],
            ['match' => ['beng'], 'face' => 'otherbengali'],
        ]);

        [, $font] = $resolver->getLanguageOptions('und-beng', false);

        $this->assertSame('notosansbengali', $font);
    }

    #[Test]
    public function it_matches_language_aliases(): void
    {
        $resolver = new ScriptLanguageToFont([
            ['match' => ['bn'], 'face' => 'notosansbengali'],
        ]);

        [, $font] = $resolver->getLanguageOptions('bn', false);

        $this->assertSame('notosansbengali', $font);
    }

    #[Test]
    public function it_falls_back_to_parent_for_unmatched_scripts(): void
    {
        $resolver = new ScriptLanguageToFont([
            ['match' => ['bn'], 'face' => 'notosansbengali'],
        ]);

        [, $font] = $resolver->getLanguageOptions('en', false);

        $this->assertNotSame('notosansbengali', $font);
    }
}
