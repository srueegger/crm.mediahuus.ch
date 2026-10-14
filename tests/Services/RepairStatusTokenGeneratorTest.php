<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\RepairStatusTokenGenerator;
use PHPUnit\Framework\TestCase;

final class RepairStatusTokenGeneratorTest extends TestCase
{
    public function testTokenIs32Hex(): void
    {
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (new RepairStatusTokenGenerator())->generate());
    }

    public function testTokensAreUnique(): void
    {
        $generator = new RepairStatusTokenGenerator();
        $tokens = [];
        for ($i = 0; $i < 1000; $i++) {
            $tokens[] = $generator->generate();
        }
        $this->assertCount(1000, array_unique($tokens));
    }
}
