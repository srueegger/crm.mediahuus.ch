<?php

declare(strict_types=1);

namespace App\Services;

class RepairStatusTokenGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
