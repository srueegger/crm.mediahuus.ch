<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\InvoiceNumberGenerator;
use PHPUnit\Framework\TestCase;

class InvoiceNumberGeneratorTest extends TestCase
{
    private InvoiceNumberGenerator $generator;
    private \DateTimeImmutable $date;

    protected function setUp(): void
    {
        $this->generator = new InvoiceNumberGenerator();
        $this->date = new \DateTimeImmutable('2026-10-12');
    }

    public function testFirstNumberOfDay(): void
    {
        $this->assertSame('RE-2026-10-12-01', $this->generator->next($this->date, null));
    }

    public function testContinuesSequence(): void
    {
        $this->assertSame('RE-2026-10-12-10', $this->generator->next($this->date, 'RE-2026-10-12-09'));
    }

    public function testThreeDigitsAfter99(): void
    {
        $this->assertSame('RE-2026-10-12-100', $this->generator->next($this->date, 'RE-2026-10-12-99'));
        $this->assertSame('RE-2026-10-12-101', $this->generator->next($this->date, 'RE-2026-10-12-100'));
    }

    public function testReferenceBase(): void
    {
        $this->assertSame('RE2026101201', $this->generator->referenceBase('RE-2026-10-12-01'));
    }
}
