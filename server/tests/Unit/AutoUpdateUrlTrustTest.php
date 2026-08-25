<?php

namespace Tests\Unit;

use App\Services\AutoUpdateService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AutoUpdateUrlTrustTest extends TestCase
{
    /**
     * Call the protected trust check of a service configured with the given repository url.
     *
     * @param string $repository
     * @param mixed $url
     * @return bool
     */
    private function isTrusted($repository, $url)
    {
        $service = new AutoUpdateService(sys_get_temp_dir(), sys_get_temp_dir());
        $service->setUpdateUrl($repository);

        $method = new ReflectionMethod(AutoUpdateService::class, '_isTrustedUpdateUrl');
        $method->setAccessible(true);

        return $method->invoke($service, $url);
    }

    public function testAcceptsHttpsUrlOnTheRepositoryHost()
    {
        $this->assertTrue($this->isTrusted(
            'https://cdn.example.com/plainpad/updates/stable',
            'https://cdn.example.com/plainpad/updates/stable/1.2.0.zip'
        ));
    }

    /**
     * @dataProvider untrustedUrlProvider
     */
    public function testRejectsUntrustedUrls($url)
    {
        $this->assertFalse($this->isTrusted('https://cdn.example.com/plainpad/updates/stable', $url));
    }

    public static function untrustedUrlProvider()
    {
        return [
            'foreign host' => ['https://evil.example.net/1.2.0.zip'],
            'plaintext http on the repository host' => ['http://cdn.example.com/1.2.0.zip'],
            'host suffix confusion' => ['https://cdn.example.com.evil.net/1.2.0.zip'],
            'no scheme' => ['cdn.example.com/1.2.0.zip'],
            'relative path' => ['/plainpad/updates/stable/1.2.0.zip'],
            'file scheme' => ['file:///etc/passwd'],
            'not a string' => [null],
        ];
    }
}
