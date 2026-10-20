<?php

namespace Tests\Unit;

use App\Services\AutoUpdateService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ZipArchive;

class AutoUpdateZipSlipTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        if (! function_exists('zip_open') || ! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip PHP extension is required.');
        }

        $this->dir = sys_get_temp_dir().'/plainpad-zipslip-'.uniqid();
        mkdir($this->dir.'/install/app', 0755, true);
    }

    protected function tearDown(): void
    {
        if (isset($this->dir)) {
            exec('rm -rf '.escapeshellarg($this->dir));
        }
    }

    private function install(array $entries, bool $simulate): mixed
    {
        $zipFile = $this->dir.'/update.zip';
        $zip = new ZipArchive;
        $zip->open($zipFile, ZipArchive::CREATE);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        $service = new AutoUpdateService($this->dir.'/tmp', $this->dir.'/install');
        $method = new ReflectionMethod(AutoUpdateService::class, '_install');
        $method->setAccessible(true);

        return @$method->invoke($service, $zipFile, $simulate, '9.9.9');
    }

    public function test_rejects_entries_escaping_the_install_directory(): void
    {
        foreach ([false, true] as $simulate) {
            $result = $this->install([
                'app/ok.txt' => 'ok',
                '../escaped.txt' => 'pwned',
            ], $simulate);

            $this->assertNotTrue($result);
            $this->assertFileDoesNotExist($this->dir.'/escaped.txt');
        }
    }

    public function test_installs_regular_entries(): void
    {
        $this->assertTrue($this->install(['app/ok.txt' => 'ok'], false));
        $this->assertSame('ok', file_get_contents($this->dir.'/install/app/ok.txt'));
    }

    public function testRejectsAnArchiveThatIsNotTheOfferedVersion(): void
    {
        $zipFile = $this->dir.'/update.zip';
        $zip = new ZipArchive;
        $zip->open($zipFile, ZipArchive::CREATE);
        $zip->addFromString('config/app.php', "<?php return ['version' => env('APP_VERSION', '1.1.0')];");
        $zip->close();

        $service = new AutoUpdateService($this->dir.'/tmp', $this->dir.'/install');
        $method = new ReflectionMethod(AutoUpdateService::class, '_verifyArchiveVersion');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, $zipFile, '99.0.0'));
        $this->assertTrue($method->invoke($service, $zipFile, '1.1.0'));
    }
}
