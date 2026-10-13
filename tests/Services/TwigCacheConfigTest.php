<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\TwigCacheConfig;
use PHPUnit\Framework\TestCase;

class TwigCacheConfigTest extends TestCase
{
    private const ROOT = '/var/www/html';

    /**
     * @dataProvider valueProvider
     */
    public function testResolve(?string $input, string|false $expected): void
    {
        $this->assertSame($expected, TwigCacheConfig::resolve($input, self::ROOT));
    }

    /**
     * @return array<string, array{0: ?string, 1: string|false}>
     */
    public static function valueProvider(): array
    {
        return [
            'nicht gesetzt' => [null, false],
            'leer' => ['', false],
            'false' => ['false', false],
            'FALSE mit Leerzeichen' => [' FALSE ', false],
            '0' => ['0', false],
            'true' => ['true', '/var/www/html/var/cache/twig'],
            'True' => ['True', '/var/www/html/var/cache/twig'],
            '1' => ['1', '/var/www/html/var/cache/twig'],
            'absoluter Pfad' => ['/tmp/twig', '/tmp/twig'],
            'relativer Pfad ab Projekt' => ['var/twig', '/var/www/html/var/twig'],
        ];
    }

    public function testDefaultDirLiegtAusserhalbVonPublic(): void
    {
        $this->assertSame('/var/www/html/var/cache/twig', TwigCacheConfig::defaultDir(self::ROOT . '/'));
    }
}
