<?php

namespace Tests\Unit;

use App\Services\PublicUrlSafetyService;
use PHPUnit\Framework\TestCase;

class PublicUrlSafetyServiceTest extends TestCase
{
    public function test_accepts_public_http_and_https_ip_addresses(): void
    {
        $service = new PublicUrlSafetyService;

        $this->assertTrue($service->isSafe('https://8.8.8.8/source'));
        $this->assertTrue($service->isSafe('http://1.1.1.1/news'));
    }

    public function test_rejects_private_reserved_and_local_addresses(): void
    {
        $service = new PublicUrlSafetyService;

        $this->assertFalse($service->isSafe('http://127.0.0.1/private'));
        $this->assertFalse($service->isSafe('http://10.0.0.1/private'));
        $this->assertFalse($service->isSafe('http://169.254.169.254/latest/meta-data'));
        $this->assertFalse($service->isSafe('http://[::1]/private'));
        $this->assertFalse($service->isSafe('http://localhost/private'));
    }

    public function test_rejects_credentials_and_non_http_schemes(): void
    {
        $service = new PublicUrlSafetyService;

        $this->assertFalse($service->isSafe('https://user:password@8.8.8.8/source'));
        $this->assertFalse($service->isSafe('file:///etc/passwd'));
        $this->assertFalse($service->isSafe('ftp://8.8.8.8/file'));
    }
}
