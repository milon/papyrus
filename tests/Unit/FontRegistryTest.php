<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\FontRegistry;
use Milon\Papyrus\Config\Project;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FontRegistryTest extends TestCase
{
    #[Test]
    public function it_skips_faces_with_missing_font_files(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-fonts-'.uniqid('', true);
        mkdir($dir);
        mkdir($dir.'/assets');
        mkdir($dir.'/assets/fonts');
        mkdir($dir.'/content');

        file_put_contents($dir.'/papyrus.php', <<<'PHP'
<?php

return [
    'title' => 'Fonts',
    'fonts' => [
        'default' => 'missingface',
        'faces' => [
            ['name' => 'missingface', 'regular' => 'Missing.ttf'],
            ['name' => 'present', 'regular' => 'Present.ttf', 'otl' => true],
        ],
        'script' => [
            ['match' => ['bn', 'bengali'], 'face' => 'present'],
            ['match' => ['ar'], 'face' => 'missingface'],
        ],
    ],
];
PHP);

        file_put_contents($dir.'/assets/fonts/Present.ttf', 'font');

        try {
            $project = Project::load($dir);
            $registry = FontRegistry::fromProject($project);

            $this->assertSame('present', $registry->defaultFont);
            $this->assertArrayHasKey('present', $registry->fontData);
            $this->assertSame(0xFF, $registry->fontData['present']['useOTL']);
            $this->assertCount(1, $registry->applicableScriptRules());
            $this->assertSame('present', $registry->applicableScriptRules()[0]['face']);
        } finally {
            unlink($dir.'/assets/fonts/Present.ttf');
            rmdir($dir.'/assets/fonts');
            rmdir($dir.'/assets');
            rmdir($dir.'/content');
            unlink($dir.'/papyrus.php');
            rmdir($dir);
        }
    }
}
