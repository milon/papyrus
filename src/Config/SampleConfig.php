<?php

declare(strict_types=1);

namespace Milon\Papyrus\Config;

final class SampleConfig
{
    /**
     * @param  list<array{from: int, to: int}>  $ranges
     */
    public function __construct(
        public readonly array $ranges,
        public readonly string $notice,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $sample = $config['sample'] ?? [];
        $sample = is_array($sample) ? $sample : [];

        $ranges = [];
        $rawRanges = $sample['ranges'] ?? [];

        if (is_array($rawRanges)) {
            foreach ($rawRanges as $range) {
                if (! is_array($range)) {
                    continue;
                }

                $from = (int) ($range['from'] ?? 0);
                $to = (int) ($range['to'] ?? 0);

                if ($from < 1 || $to < $from) {
                    continue;
                }

                $ranges[] = ['from' => $from, 'to' => $to];
            }
        }

        $notice = $config['sample_notice'] ?? $sample['notice'] ?? $sample['text'] ?? '';

        return new self(
            ranges: $ranges,
            notice: is_string($notice) ? $notice : '',
        );
    }

    public function hasRanges(): bool
    {
        return $this->ranges !== [];
    }
}
