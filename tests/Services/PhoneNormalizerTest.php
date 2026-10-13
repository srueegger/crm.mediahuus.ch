<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    /**
     * @dataProvider phoneProvider
     */
    public function testNormalize(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::normalize($input));
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function phoneProvider(): array
    {
        return [
            'international with plus' => ['+41 79 123 45 67', '0791234567'],
            'national with spaces' => ['079 123 45 67', '0791234567'],
            'international with 00' => ['0041791234567', '0791234567'],
            'foreign number with plus' => ['+49 170 1234567', '00491701234567'],
            'punctuation removed' => ['(061) 711-22-33', '0617112233'],
            'empty string' => ['', null],
            'null' => [null, null],
            'whitespace only' => ['  ', null],
        ];
    }
}
