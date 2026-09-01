<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Pdf;

use Mpdf\Language\LanguageToFont;

final class ScriptLanguageToFont extends LanguageToFont
{
    /**
     * @param  list<array{match: list<string>, face: string}>  $rules
     */
    public function __construct(
        private readonly array $rules,
    ) {}

    public function getLanguageOptions($llcc, $adobeCJK)
    {
        $tags = explode('-', strtolower($llcc));
        $lang = $tags[0] ?? '';
        $script = '';

        if (! empty($tags[1]) && strlen($tags[1]) === 4) {
            $script = $tags[1];
        }

        foreach ($this->rules as $rule) {
            if ($this->ruleMatches($rule['match'], $lang, $script)) {
                return [false, $rule['face']];
            }
        }

        return parent::getLanguageOptions($llcc, $adobeCJK);
    }

    /**
     * @param  list<string>  $match
     */
    private function ruleMatches(array $match, string $lang, string $script): bool
    {
        foreach ($match as $alias) {
            if ($alias === $lang) {
                return true;
            }

            if ($script !== '' && ($alias === $script || self::aliasToScript($alias) === $script)) {
                return true;
            }

            if ($script === '' && self::langToScript($lang) === self::aliasToScript($alias)) {
                return true;
            }
        }

        return false;
    }

    private static function aliasToScript(string $alias): ?string
    {
        return match ($alias) {
            'bn', 'ben', 'beng', 'bengali' => 'beng',
            'ar', 'arab', 'arabic' => 'arab',
            'hi', 'hin', 'deva', 'devanagari', 'hindi' => 'deva',
            default => strlen($alias) === 4 ? $alias : null,
        };
    }

    private static function langToScript(string $lang): ?string
    {
        return self::aliasToScript($lang);
    }
}
