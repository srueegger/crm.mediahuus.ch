<?php

declare(strict_types=1);

namespace Tests\Repositories;

use App\Repositories\RepairEventRepository;
use PHPUnit\Framework\TestCase;

class RepairEventRepositoryTest extends TestCase
{
    public function testTruncatesLongMessageTo500Chars(): void
    {
        $result = RepairEventRepository::truncateMessage(str_repeat('ä', 800));
        $this->assertSame(500, mb_strlen($result));
    }

    public function testKeepsShortAndNullMessages(): void
    {
        $this->assertSame('kurz', RepairEventRepository::truncateMessage('kurz'));
        $this->assertNull(RepairEventRepository::truncateMessage(null));
    }
}
