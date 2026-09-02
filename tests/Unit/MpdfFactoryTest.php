<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\Project;
use Milon\Papyrus\Render\Pdf\MpdfFactory;
use Mpdf\Mpdf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MpdfFactoryTest extends TestCase
{
    /** @var list<string> */
    private array $dirs = [];

    protected function tearDown(): void
    {
        foreach ($this->dirs as $dir) {
            $this->removeDirectory($dir);
        }

        $this->dirs = [];
    }

    #[Test]
    public function it_enables_script_detection_when_a_script_rule_applies(): void
    {
        $project = $this->projectWithFonts(<<<'PHP'
    'fonts' => [
        'faces' => [
            ['name' => 'bengali', 'regular' => 'Present.ttf', 'otl' => true],
        ],
        'script' => [
            ['match' => ['bn', 'bengali'], 'face' => 'bengali'],
        ],
    ],
PHP);

        $pdf = MpdfFactory::create($project, $project->documentSize());

        $this->assertTrue($pdf->autoScriptToLang);
        $this->assertTrue($pdf->autoLangToFont);
    }

    #[Test]
    public function it_leaves_script_detection_off_when_no_rule_can_be_applied(): void
    {
        $project = $this->projectWithFonts(<<<'PHP'
    'fonts' => [
        'faces' => [
            ['name' => 'bengali', 'regular' => 'Present.ttf'],
        ],
        'script' => [
            ['match' => ['ar'], 'face' => 'missingface'],
        ],
    ],
PHP);

        $pdf = MpdfFactory::create($project, $project->documentSize());

        $this->assertFalse($pdf->autoScriptToLang);
        $this->assertFalse($pdf->autoLangToFont);
    }

    private function projectWithFonts(string $fontsConfig): Project
    {
        $dir = sys_get_temp_dir().'/papyrus-mpdf-'.uniqid('', true);
        mkdir($dir.'/assets/fonts', 0777, true);
        mkdir($dir.'/content');
        $this->dirs[] = $dir;

        file_put_contents($dir.'/papyrus.php', "<?php\n\nreturn [\n    'title' => 'Fonts',\n".$fontsConfig."\n];\n");
        copy($this->bundledFont(), $dir.'/assets/fonts/Present.ttf');

        return Project::load($dir);
    }

    /**
     * mPDF parses the configured default face while booting, so the fixture
     * needs a font it can actually read.
     */
    private function bundledFont(): string
    {
        $mpdf = (new ReflectionClass(Mpdf::class))->getFileName();

        return dirname((string) $mpdf, 2).'/ttfonts/DejaVuSans.ttf';
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.'/'.$entry;

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
