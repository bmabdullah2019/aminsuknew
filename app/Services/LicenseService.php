<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LicenseService
{
    protected $path;

    public function __construct()
    {
        $this->path = storage_path('app/.license');
    }

    public function verify()
    {
        if (app()->runningInConsole() || app()->environment(['local', 'testing'])) {
            return true;
        }

        $host = request()->getHost();
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test')) {
            return true;
        }

        if (file_exists($this->path)) {
            return $this->validateLocal();
        }

        return $this->activateOnline();
    }

    protected function activateOnline()
    {
        $domain = request()->getHost();
        $fingerprint = hash('sha256', $domain.config('app.key'));
        $serverUrl = rtrim((string) env('LICENSE_SERVER_URL'), '/');
        $licenseKey = (string) env('LICENSE_KEY');

        if ($serverUrl === '' || $licenseKey === '') {
            abort(403, 'License config missing');
        }

        $response = Http::asJson()->acceptJson()->timeout(10)->post($serverUrl.'/activate', [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'fingerprint' => $fingerprint,
        ]);

        if (! $response->ok()) {
            abort(403, 'License activation failed');
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['payload'], $data['signature'])) {
            abort(403, 'Invalid activation response');
        }

        if (! $this->verifySignature($data['payload'], $data['signature'])) {
            abort(403, 'Invalid license signature');
        }

        file_put_contents($this->path, encrypt(json_encode($data)));

        return true;
    }

    protected function validateLocal()
    {
        try {
            $data = json_decode(decrypt(file_get_contents($this->path)), true);
        } catch (\Exception $e) {
            abort(403, 'Corrupted license file');
        }

        if (! is_array($data) || ! isset($data['payload'], $data['signature'])) {
            abort(403, 'Corrupted license data');
        }

        if (! $this->verifySignature($data['payload'], $data['signature'])) {
            abort(403, 'Invalid signature');
        }

        $payload = json_decode($data['payload'], true);
        if (! is_array($payload) || ! isset($payload['fingerprint'])) {
            abort(403, 'Invalid payload');
        }

        $currentFingerprint = hash('sha256', request()->getHost().config('app.key'));

        if ($payload['fingerprint'] !== $currentFingerprint) {
            abort(403, 'License mismatch');
        }

        return true;
    }

    protected function verifySignature($payload, $signature)
    {
        if (! is_string($payload) || ! is_string($signature) || $payload === '' || $signature === '') {
            return false;
        }

        $secret = (string) env('LICENSE_SECRET');
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
