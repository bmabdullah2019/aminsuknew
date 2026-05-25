<?php

namespace App\Services\Licensing;

use Illuminate\Support\Carbon;

class LicenseValidationResult
{
    public string $status;

    public ?Carbon $expiresAt;

    public string $message;

    public Carbon $checkedAt;

    public function __construct(string $status, ?Carbon $expiresAt, string $message, ?Carbon $checkedAt = null)
    {
        $this->status = $status;
        $this->expiresAt = $expiresAt;
        $this->message = $message;
        $this->checkedAt = $checkedAt ?? now();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    public function toCacheArray(): array
    {
        return [
            'status' => $this->status,
            'expires_at' => $this->expiresAt ? $this->expiresAt->toIso8601String() : null,
            'message' => $this->message,
            'checked_at' => $this->checkedAt->toIso8601String(),
        ];
    }

    public static function fromCacheArray(array $data): self
    {
        $expiresAt = null;
        if (! empty($data['expires_at'])) {
            $expiresAt = Carbon::parse($data['expires_at']);
        }

        $checkedAt = ! empty($data['checked_at']) ? Carbon::parse($data['checked_at']) : now();

        return new self(
            (string) ($data['status'] ?? 'inactive'),
            $expiresAt,
            (string) ($data['message'] ?? ''),
            $checkedAt
        );
    }
}
