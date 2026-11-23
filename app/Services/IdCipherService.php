<?php

namespace App\Services;

class IdCipherService
{
    /**
     * Derive a 16-byte key for AES-128 from the application key.
     */
    protected static function getKey(): string
    {
        $key = config('app.key') ?? '';
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return substr(hash('sha256', $key, true), 0, 16);
    }

    /**
     * Encrypt an integer ID into a compact URL-safe 43-character token using AES-128-GCM.
     */
    public static function encrypt(int $id): string
    {
        $key = self::getKey();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(pack('N', $id), 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        $payload = $iv.$tag.$ciphertext;

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /**
     * Decrypt a compact URL-safe token back to an integer ID, or return null if invalid.
     */
    public static function decrypt(string $token): ?int
    {
        // Support direct integer / numeric ID fallback (e.g. /sites/2)
        if (is_numeric($token) && (int) $token > 0) {
            return (int) $token;
        }

        if (strlen($token) < 40) {
            return null;
        }

        $remainder = strlen($token) % 4;
        if ($remainder > 0) {
            $token .= str_repeat('=', 4 - $remainder);
        }

        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false || strlen($raw) < 32) {
            return null;
        }

        $key = self::getKey();
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $plain = openssl_decrypt($ciphertext, 'aes-128-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false || strlen($plain) < 4) {
            return null;
        }

        $unpacked = unpack('N', $plain);

        return isset($unpacked[1]) ? (int) $unpacked[1] : null;
    }
}
