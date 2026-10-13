<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\AppUrl;
use PHPUnit\Framework\TestCase;

class AppUrlTest extends TestCase
{
    public function testJoinsBaseAndPath(): void
    {
        $url = new AppUrl('https://crm.mediahuus.ch/');
        $this->assertSame('https://crm.mediahuus.ch/r/abc', $url->absolute('/r/abc'));
        $this->assertSame('https://crm.mediahuus.ch/r/abc', $url->absolute('r/abc'));
    }

    public function testRepairStatusUrl(): void
    {
        $token = str_repeat('a', 32);
        $url = new AppUrl('https://crm.mediahuus.ch');
        $this->assertStringEndsWith('/r/' . $token, $url->repairStatus($token));
    }
}
