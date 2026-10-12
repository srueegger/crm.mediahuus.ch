<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\QrBillFactory;
use PHPUnit\Framework\TestCase;

class QrBillFactoryTest extends TestCase
{
    private QrBillFactory $factory;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../../config/invoice.php';
        $this->factory = new QrBillFactory($config['creditor']);
    }

    public function testCreatesValidQrBill(): void
    {
        $qrBill = $this->factory->create('RE-2026-10-12-01', '129.80', 'Max Muster', 'Musterweg 3a', '4051', 'Basel');

        $this->assertTrue($qrBill->isValid());
        $reference = $qrBill->getPaymentReference()->getReference();
        $this->assertStringStartsWith('RF', $reference);
        $this->assertStringEndsWith('RE2026101201', $reference);
    }

    public function testUmlautsAndAmpersand(): void
    {
        $qrBill = $this->factory->create('RE-2026-10-12-01', '10.00', 'Müller & Söhne AG', 'Zürichstrasse 1', '8001', 'Zürich');

        $this->assertTrue($qrBill->isValid());
    }

    public function testPostboxWithoutHouseNumber(): void
    {
        $qrBill = $this->factory->create('RE-2026-10-12-01', '10.00', 'Max Muster', 'Postfach 12', '4051', 'Basel');

        $this->assertTrue($qrBill->isValid());
    }

    public function testRejectsTooLongCity(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Die QR-Rechnung ist ungültig');

        $this->factory->create('RE-2026-10-12-01', '10.00', 'Max Muster', 'Musterweg 3', '4051', str_repeat('a', 36));
    }

    public function testRejectsZeroAmount(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->factory->create('RE-2026-10-12-01', '0.00', 'Max Muster', 'Musterweg 3', '4051', 'Basel');
    }
}
