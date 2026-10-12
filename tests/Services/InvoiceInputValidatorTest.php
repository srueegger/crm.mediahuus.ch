<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\InvoiceInputValidator;
use PHPUnit\Framework\TestCase;

class InvoiceInputValidatorTest extends TestCase
{
    private InvoiceInputValidator $validator;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../../config/invoice.php';
        $this->validator = new InvoiceInputValidator($config);
    }

    private function validPost(array $override = []): array
    {
        return array_replace([
            'company_name' => 'Muster AG',
            'contact_name' => '',
            'street' => 'Musterweg 3a',
            'zip' => '4153',
            'city' => 'Reinach',
            'invoice_date' => '2026-10-12',
            'payment_term_days' => '10',
            'items' => [
                ['description' => 'Display-Reparatur', 'quantity' => '1', 'unit_price' => '120.00', 'vat_rate' => '8.1'],
            ],
        ], $override);
    }

    public function testValidInputWithCompanyOnly(): void
    {
        $result = $this->validator->validate($this->validPost());

        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['data']['items']);
        $this->assertSame('Muster AG', $result['data']['company_name']);
        $this->assertSame('2026-10-12', $result['data']['invoice_date']->format('Y-m-d'));
        $this->assertSame(10, $result['data']['payment_term_days']);
        $this->assertSame('8.1', $result['data']['items'][0]['vat_rate']);
    }

    public function testAcceptsLatinExtendedNames(): void
    {
        $result = $this->validator->validate($this->validPost(['company_name' => 'Kovačević Bau GmbH', 'city' => 'Łódź']));

        $this->assertSame([], $result['errors']);
    }

    public function testRejectsNonRepresentableName(): void
    {
        $result = $this->validator->validate($this->validPost(['company_name' => '😀']));

        $this->assertSame(
            'Bitte nur lateinische Schrift verwenden (z. B. keine kyrillischen oder griechischen Zeichen).',
            $result['errors']['company_name']
        );
    }

    public function testRejectsTooLongItemDescription(): void
    {
        $post = $this->validPost();
        $post['items'][0]['description'] = str_repeat('a', 501);
        $result = $this->validator->validate($post);

        $this->assertSame('Beschreibung darf höchstens 500 Zeichen lang sein.', $result['errors']['items.0.description']);

        $post['items'][0]['description'] = str_repeat('ä', 500);
        $this->assertSame([], $this->validator->validate($post)['errors']);
    }

    public function testRequiresCompanyOrName(): void
    {
        $result = $this->validator->validate($this->validPost(['company_name' => ' ', 'contact_name' => '']));

        $this->assertArrayHasKey('contact_name', $result['errors']);
        $this->assertSame('Firma oder Name ist erforderlich', $result['errors']['contact_name']);
    }

    public function testRequiresAddress(): void
    {
        $result = $this->validator->validate($this->validPost(['street' => '', 'zip' => '', 'city' => '']));

        $this->assertArrayHasKey('street', $result['errors']);
        $this->assertArrayHasKey('zip', $result['errors']);
        $this->assertArrayHasKey('city', $result['errors']);
    }

    public function testRejectsUnknownVatRate(): void
    {
        $post = $this->validPost();
        $post['items'][0]['vat_rate'] = '7.7';
        $result = $this->validator->validate($post);

        $this->assertArrayHasKey('items.0.vat_rate', $result['errors']);
    }

    public function testNormalizesVatRateToCanonicalString(): void
    {
        $post = $this->validPost();
        $post['items'][0]['vat_rate'] = '8.10';
        $post['items'][] = ['description' => 'B', 'quantity' => '1', 'unit_price' => '5', 'vat_rate' => '0.0'];
        $result = $this->validator->validate($post);

        $this->assertSame([], $result['errors']);
        $this->assertSame('8.1', $result['data']['items'][0]['vat_rate']);
        $this->assertSame('0', $result['data']['items'][1]['vat_rate']);
    }

    public function testAcceptsCommaDecimal(): void
    {
        $post = $this->validPost();
        $post['items'][0]['unit_price'] = '49,90';
        $result = $this->validator->validate($post);

        $this->assertSame([], $result['errors']);
        $this->assertSame('49.90', $result['data']['items'][0]['unit_price_gross']);
    }

    public function testIgnoresEmptyRowsAndRequiresOne(): void
    {
        $post = $this->validPost(['items' => [
            ['description' => '', 'quantity' => '', 'unit_price' => '', 'vat_rate' => '8.1'],
            ['description' => ' ', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '8.1'],
        ]]);
        $result = $this->validator->validate($post);

        $this->assertArrayHasKey('items', $result['errors']);
    }

    public function testRejectsInvalidItemFields(): void
    {
        $post = $this->validPost(['items' => [
            ['description' => 'X', 'quantity' => '0', 'unit_price' => '-1', 'vat_rate' => '8.1'],
            ['description' => '', 'quantity' => '1', 'unit_price' => '5', 'vat_rate' => '8.1'],
        ]]);
        $result = $this->validator->validate($post);

        $this->assertArrayHasKey('items.0.quantity', $result['errors']);
        $this->assertArrayHasKey('items.0.unit_price', $result['errors']);
        $this->assertArrayHasKey('items.1.description', $result['errors']);
    }

    public function testPaymentTermBounds(): void
    {
        $this->assertArrayHasKey('payment_term_days', $this->validator->validate($this->validPost(['payment_term_days' => '-1']))['errors']);
        $this->assertArrayHasKey('payment_term_days', $this->validator->validate($this->validPost(['payment_term_days' => '366']))['errors']);

        $result = $this->validator->validate($this->validPost(['payment_term_days' => '']));
        $this->assertSame([], $result['errors']);
        $this->assertSame(10, $result['data']['payment_term_days']);
    }

    public function testFieldLengthLimits(): void
    {
        $result = $this->validator->validate($this->validPost(['city' => str_repeat('a', 36)]));
        $this->assertArrayHasKey('city', $result['errors']);

        $result = $this->validator->validate($this->validPost(['city' => str_repeat('ü', 35)]));
        $this->assertArrayNotHasKey('city', $result['errors']);
    }

    public function testRejectsInvalidDate(): void
    {
        $result = $this->validator->validate($this->validPost(['invoice_date' => '2026-02-31']));
        $this->assertArrayHasKey('invoice_date', $result['errors']);
    }

    public function testRejectsTotalOutOfRange(): void
    {
        $post = $this->validPost();
        $post['items'][0]['unit_price'] = '0';
        $this->assertArrayHasKey('items', $this->validator->validate($post)['errors']);

        $post['items'][0]['unit_price'] = '1000000000';
        $this->assertArrayHasKey('items', $this->validator->validate($post)['errors']);
    }

    public function testErrorKeysUseOriginalRowKeyWithLeadingEmptyRow(): void
    {
        $post = $this->validPost(['items' => [
            ['description' => '', 'quantity' => '1', 'unit_price' => '', 'vat_rate' => '8.1'],
            ['description' => '', 'quantity' => '1', 'unit_price' => '5', 'vat_rate' => '8.1'],
        ]]);
        $result = $this->validator->validate($post);

        $this->assertArrayHasKey('items.1.description', $result['errors']);
        $this->assertArrayNotHasKey('items.0.description', $result['errors']);
    }

    public function testErrorKeysUseOriginalKeysWithGaps(): void
    {
        $post = $this->validPost(['items' => [
            0 => ['description' => 'A', 'quantity' => '1', 'unit_price' => '5', 'vat_rate' => '8.1'],
            3 => ['description' => 'B', 'quantity' => '1', 'unit_price' => '5', 'vat_rate' => '7.7'],
        ]]);
        $result = $this->validator->validate($post);

        $this->assertArrayHasKey('items.3.vat_rate', $result['errors']);

        $post['items'][3]['vat_rate'] = '2.6';
        $ok = $this->validator->validate($post);
        $this->assertSame([], $ok['errors']);
        $this->assertSame([0, 1], array_keys($ok['data']['items']));
    }
}
