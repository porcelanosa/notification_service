<?php

declare(strict_types = 1);

namespace App\Contracts;

interface DeliveryResultInterface
{
    public function isSuccess(): bool;
    public function getFailureReason(): ?string;
}