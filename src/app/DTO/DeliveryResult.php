<?php

declare(strict_types = 1);
namespace App\DTO;

use App\Contracts\DeliveryResultInterface;

final readonly class DeliveryResult implements DeliveryResultInterface
{
    private function __construct(
      private bool $success,
      private ?string $failureReason = null,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }

    public function isSuccess(): bool { return $this->success; }

    public function getFailureReason(): ?string { return $this->failureReason; }
}