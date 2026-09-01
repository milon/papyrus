<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\SampleConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SampleConfigTest extends TestCase
{
    #[Test]
    public function it_parses_ranges_and_notice(): void
    {
        $config = SampleConfig::fromConfig([
            'sample' => [
                'ranges' => [
                    ['from' => 1, 'to' => 3],
                    ['from' => 0, 'to' => 5],
                    ['from' => 10, 'to' => 8],
                ],
            ],
            'sample_notice' => 'Sample text',
        ]);

        $this->assertTrue($config->hasRanges());
        $this->assertCount(1, $config->ranges);
        $this->assertSame(1, $config->ranges[0]['from']);
        $this->assertSame(3, $config->ranges[0]['to']);
        $this->assertSame('Sample text', $config->notice);
    }
}
