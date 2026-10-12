<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\QrTextNormalizer;
use PHPUnit\Framework\TestCase;

class QrTextNormalizerTest extends TestCase
{
    private QrTextNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new QrTextNormalizer();
    }

    public function testTransliteratesCharactersOutsideCp1252(): void
    {
        $this->assertSame('Kovacevic Šimic Duric Lódz', $this->normalizer->normalize('Kovačević Šimić Đurić Łódź'));
    }

    public function testKeepsCp1252Characters(): void
    {
        $this->assertSame('Müller & Söhne AG', $this->normalizer->normalize('Müller & Söhne AG'));
        $this->assertSame('Zürich', $this->normalizer->normalize('Zürich'));
        $this->assertSame('Genève é è', $this->normalizer->normalize('Genève é è'));
    }

    public function testCyrillicIsTransliterated(): void
    {
        $this->assertSame('Ivan', $this->normalizer->normalize('Иван'));
    }

    public function testEmojiOnlyBecomesEmpty(): void
    {
        $this->assertSame('', $this->normalizer->normalize('😀'));
        $this->assertFalse($this->normalizer->isRepresentable('😀'));
        $this->assertTrue($this->normalizer->isRepresentable('Kovačević'));
    }
}
