<?php

declare(strict_types=1);

namespace Milon\Papyrus\Tests\Unit;

use Milon\Papyrus\Config\ConfigException;
use Milon\Papyrus\Config\ConfigLocator;
use Milon\Papyrus\Config\Project;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigLocatorTest extends TestCase
{
    #[Test]
    public function discovers_yml_when_php_is_absent(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-cfg-yml-'.uniqid('', true);
        mkdir($dir);
        file_put_contents($dir.'/papyrus.yml', "title: From YAML\nauthor: Tester\n");

        try {
            $path = ConfigLocator::discover($dir);
            $this->assertSame($dir.'/papyrus.yml', $path);

            $project = Project::load($dir);
            $this->assertSame('From YAML', $project->title());
        } finally {
            unlink($dir.'/papyrus.yml');
            rmdir($dir);
        }
    }

    #[Test]
    public function discovers_json_when_php_is_absent(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-cfg-json-'.uniqid('', true);
        mkdir($dir);
        file_put_contents($dir.'/papyrus.json', "{\"title\":\"From JSON\",\"author\":\"Tester\"}\n");

        try {
            $project = Project::load($dir);
            $this->assertSame('From JSON', $project->title());
        } finally {
            unlink($dir.'/papyrus.json');
            rmdir($dir);
        }
    }

    #[Test]
    public function rejects_multiple_config_files(): void
    {
        $dir = sys_get_temp_dir().'/papyrus-cfg-many-'.uniqid('', true);
        mkdir($dir);
        file_put_contents($dir.'/papyrus.php', "<?php\nreturn ['title' => 'PHP'];\n");
        file_put_contents($dir.'/papyrus.yml', "title: YAML\n");

        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage('Multiple book config files');
            ConfigLocator::discover($dir);
        } finally {
            unlink($dir.'/papyrus.php');
            unlink($dir.'/papyrus.yml');
            rmdir($dir);
        }
    }
}
