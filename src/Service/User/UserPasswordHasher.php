<?php

declare(strict_types=1);

namespace App\Service\User;

final class UserPasswordHasher
{
    private const PREFIX = 'enc:';
    private const CIPHER = 'aes-256-cbc';

    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        $encrypted = openssl_encrypt(
            $plainPassword,
            self::CIPHER,
            $this->buildKey(),
            OPENSSL_RAW_DATA,
            $this->buildIv(),
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Unable to encrypt password.');
        }

        return self::PREFIX.base64_encode($encrypted);
    }

    public function decrypt(string $storedPassword): ?string
    {
        if (str_starts_with($storedPassword, self::PREFIX)) {
            $payload = substr($storedPassword, strlen(self::PREFIX));
            $decoded = base64_decode($payload, true);

            if ($decoded === false) {
                throw new \RuntimeException('Stored password payload is invalid.');
            }

            $decrypted = openssl_decrypt(
                $decoded,
                self::CIPHER,
                $this->buildKey(),
                OPENSSL_RAW_DATA,
                $this->buildIv(),
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Unable to decrypt password.');
            }

            return $decrypted;
        }

        $decoded = base64_decode($storedPassword, true);

        if ($decoded !== false) {
            return $decoded;
        }

        return null;
    }

    public function verify(string $plainPassword, string $storedPassword): bool
    {
        return hash_equals($storedPassword, $this->hash($plainPassword));
    }

    private function buildKey(): string
    {
        return hash('sha256', $this->secret, true);
    }

    private function buildIv(): string
    {
        return substr(hash('sha256', $this->secret.'|pass_iv', true), 0, openssl_cipher_iv_length(self::CIPHER));
    }
}
