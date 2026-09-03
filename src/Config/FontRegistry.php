<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class FontRegistry
{
    /**
     * @param  array<string, array<string, mixed>>  $fontData
     * @param  list<array{match: list<string>, face: string}>  $scriptRules
     */
    public function __construct(
        public readonly string $defaultFont,
        public readonly array $fontData,
        public readonly array $scriptRules,
    ) {}

    public static function fromProject(Project $project): self
    {
        $fonts = $project->config['fonts'] ?? [];

        if (! is_array($fonts)) {
            return self::empty();
        }

        $faces = is_array($fonts['faces'] ?? null) ? $fonts['faces'] : [];
        $script = is_array($fonts['script'] ?? null) ? $fonts['script'] : [];
        $default = is_string($fonts['default'] ?? null) ? $fonts['default'] : '';

        $fontData = [];

        foreach ($faces as $face) {
            if (! is_array($face)) {
                continue;
            }

            $name = is_string($face['name'] ?? null) ? $face['name'] : '';

            if ($name === '') {
                continue;
            }

            $regular = is_string($face['regular'] ?? null) ? $face['regular'] : '';

            if ($regular === '' || $project->assetPath('fonts/'.$regular) === null) {
                continue;
            }

            $entry = ['R' => $regular];

            foreach (['bold' => 'B', 'italic' => 'I', 'bold_italic' => 'BI'] as $key => $mpdfKey) {
                $file = is_string($face[$key] ?? null) ? $face[$key] : '';

                if ($file !== '' && $project->assetPath('fonts/'.$file) !== null) {
                    $entry[$mpdfKey] = $file;
                }
            }

            if (! empty($face['otl'])) {
                $entry['useOTL'] = 0xFF;
            }

            $fontData[$name] = $entry;
        }

        $scriptRules = [];

        foreach ($script as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $face = is_string($rule['face'] ?? null) ? $rule['face'] : '';
            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if ($face === '' || $match === []) {
                continue;
            }

            $scriptRules[] = [
                'match' => array_values(array_map(
                    static fn (mixed $alias): string => strtolower((string) $alias),
                    $match,
                )),
                'face' => $face,
            ];
        }

        if ($default === '' || ! isset($fontData[$default])) {
            $default = array_key_first($fontData) ?: 'dejavusanscondensed';
        }

        return new self($default, $fontData, $scriptRules);
    }

    public static function empty(): self
    {
        return new self('dejavusanscondensed', [], []);
    }

    public function hasScriptRules(): bool
    {
        return $this->scriptRules !== [];
    }

    /**
     * @return list<array{match: list<string>, face: string}>
     */
    public function applicableScriptRules(): array
    {
        return array_values(array_filter(
            $this->scriptRules,
            fn (array $rule): bool => isset($this->fontData[$rule['face']]),
        ));
    }
}
