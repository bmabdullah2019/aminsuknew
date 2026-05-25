<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedInstallationException extends Exception
{
    private string $domain;

    public function __construct(string $domain, string $message = 'Unauthorized Installation')
    {
        parent::__construct($message);
        $this->domain = $domain;
    }

    public function domain(): string
    {
        return $this->domain;
    }

    public static function forDomain(string $domain, string $message = 'Unauthorized Installation'): self
    {
        return new self($domain, $message);
    }

    public static function misconfigured(string $domain = 'unknown'): self
    {
        return new self($domain, 'Unauthorized Installation');
    }
}
