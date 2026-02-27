<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorAuth
{
    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random base32-encoded secret (160-bit = 32 chars)
     */
    public static function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate a TOTP code for a given secret and time
     */
    public static function generateCode(string $secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, $period);

        $binaryKey = self::base32Decode($secret);
        $counterBytes = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $counterBytes, $binaryKey, true);

        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, $digits);

        return str_pad((string) $code, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a TOTP code with ±1 time window tolerance
     */
    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = $timestamp + ($i * 30);
            if (hash_equals(self::generateCode($secret, $checkTime), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate an otpauth:// URI for QR code scanning
     */
    public static function getOtpAuthUri(string $secret, string $email, string $issuer = 'Kick'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer)
        );
    }

    /**
     * Generate 10 backup codes (8-char alphanumeric)
     */
    public static function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Base32 decode helper
     */
    private static function base32Decode(string $input): string
    {
        $input = strtoupper($input);
        $input = rtrim($input, '=');
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos(self::$base32Chars, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
