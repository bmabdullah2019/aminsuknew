<?php

namespace App\Services\Licensing;

class DomainNormalizer
{
    public function normalize(string $host): string
    {
        $host = strtolower(trim($host));

        // Remove port if it ever leaks into host.
        $host = preg_replace('/:\\d+$/', '', $host) ?? $host;

        // Normalize common "www." prefix.
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host;
    }
}
