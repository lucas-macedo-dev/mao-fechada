<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

interface Arrayable
{
    public function toArray(): array;
}
