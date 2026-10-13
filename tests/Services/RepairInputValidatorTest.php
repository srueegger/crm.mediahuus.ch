<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\RepairInputValidator;
use PHPUnit\Framework\TestCase;

class RepairInputValidatorTest extends TestCase
{
    private function valid(array $override = []): array
    {
        return array_merge([
            'branch_id' => '1',
            'customer_name' => 'Hans Muster',
            'device_name' => 'iPhone 13',
            'issue_text' => 'Display gebrochen',
        ], $override);
    }

    public function testRequiredFields(): void
    {
        $errors = (new RepairInputValidator())->validate([]);

        $this->assertSame(['branch_id', 'customer_name', 'device_name', 'issue_text'], array_keys($errors));
    }

    public function testValidFormHasNoErrors(): void
    {
        $this->assertSame([], (new RepairInputValidator())->validate($this->valid()));
    }

    public function testPriceAcceptsCommaAndEmpty(): void
    {
        $v = new RepairInputValidator();

        $this->assertSame([], $v->validate($this->valid(['price_chf' => '120,50'])));
        $this->assertSame([], $v->validate($this->valid(['price_chf' => ''])));
        $this->assertArrayHasKey('price_chf', $v->validate($this->valid(['price_chf' => '-5'])));
        $this->assertArrayHasKey('price_chf', $v->validate($this->valid(['price_chf' => 'abc'])));
    }

    public function testInvalidDueDate(): void
    {
        $v = new RepairInputValidator();

        $this->assertArrayHasKey('due_date', $v->validate($this->valid(['due_date' => '31.12.2026'])));
        $this->assertArrayHasKey('due_date', $v->validate($this->valid(['due_date' => '2026-02-31'])));
        $this->assertSame([], $v->validate($this->valid(['due_date' => '2026-12-31'])));
    }

    public function testUnknownDamageType(): void
    {
        $v = new RepairInputValidator();

        $this->assertArrayHasKey('damage_type', $v->validate($this->valid(['damage_type' => 'xyz'])));
        $this->assertSame([], $v->validate($this->valid(['damage_type' => 'screen_damage'])));
    }

    public function testInvalidEmailAndLengths(): void
    {
        $v = new RepairInputValidator();

        $this->assertArrayHasKey('customer_email', $v->validate($this->valid(['customer_email' => 'foo'])));
        $this->assertArrayHasKey('imei_serial', $v->validate($this->valid(['imei_serial' => str_repeat('a', 65)])));
        $this->assertArrayHasKey('unlock_code', $v->validate($this->valid(['unlock_code' => str_repeat('a', 101)])));
    }
}
