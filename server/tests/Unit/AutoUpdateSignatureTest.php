<?php

namespace Tests\Unit;

use App\Services\AutoUpdateService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AutoUpdateSignatureTest extends TestCase
{
    private string $file;

    private string $keyPair;

    protected function setUp(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'plainpad-update');
        file_put_contents($this->file, 'release archive contents');
        $this->keyPair = sodium_crypto_sign_keypair();
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    private function verify(array $publicKeys, $signature): bool
    {
        // The signature download is stubbed, $signature is what the server answers for "<url>.sig".
        $service = new class(sys_get_temp_dir(), sys_get_temp_dir()) extends AutoUpdateService
        {
            public $signature;

            protected function _downloadCurl($url, $timeout = 15)
            {
                return $this->signature;
            }
        };
        $service->signature = $signature;
        $service->setPublicKeys($publicKeys);

        $method = new ReflectionMethod(AutoUpdateService::class, '_verifySignature');
        $method->setAccessible(true);

        return $method->invoke($service, $this->file, 'https://cdn.example.com/1.2.0.zip');
    }

    private function sign(string $contents): string
    {
        return base64_encode(sodium_crypto_sign_detached($contents, sodium_crypto_sign_secretkey($this->keyPair)));
    }

    private function publicKey(): string
    {
        return base64_encode(sodium_crypto_sign_publickey($this->keyPair));
    }

    public function test_accepts_a_valid_signature(): void
    {
        $this->assertTrue($this->verify([$this->publicKey()], $this->sign('release archive contents')."\n"));
    }

    public function test_rejects_a_tampered_archive(): void
    {
        $this->assertFalse($this->verify([$this->publicKey()], $this->sign('different contents')));
    }

    public function test_rejects_a_missing_or_malformed_signature(): void
    {
        $this->assertFalse($this->verify([$this->publicKey()], false));
        $this->assertFalse($this->verify([$this->publicKey()], '<html>404 Not Found</html>'));
    }

    public function test_refuses_without_a_configured_public_key(): void
    {
        $this->assertFalse($this->verify([], $this->sign('release archive contents')));
        $this->assertFalse($this->verify(['not-a-key'], $this->sign('release archive contents')));
    }

    public function test_rejects_a_signature_from_another_key(): void
    {
        $other = base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));

        $this->assertFalse($this->verify([$other], $this->sign('release archive contents')));
    }

    public function test_accepts_a_signature_from_the_backup_key(): void
    {
        $main = base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));

        $this->assertTrue($this->verify([$main, $this->publicKey()], $this->sign('release archive contents')));
    }
}
