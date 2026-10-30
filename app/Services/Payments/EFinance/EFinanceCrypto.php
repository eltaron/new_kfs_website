<?php

namespace App\Services\Payments\EFinance;

use App\Services\Payments\PaymentException;

/**
 * Cryptographic primitives required by the e-Finance "Internet Payment"
 * protocol (ported from the vendor PHP sample, behaviour-preserving):
 *
 *  - RSA (PKCS#1 v1.5) encryption with the gateway's DER certificate
 *  - AES-256-CBC with a random 32-byte key + 16-byte IV, shared with the
 *    gateway as base64(key || iv) ("RandomSecret")
 *  - lowercase hex SHA-256 of the encrypted request ("HasedRequestObject")
 */
final class EFinanceCrypto
{
    private const CIPHER = 'aes-256-cbc';

    public function encryptByCertificate(string $clear, string $certificatePath): string
    {
        $der = @file_get_contents($certificatePath);
        if ($der === false || $der === '') {
            throw new PaymentException('تعذر تهيئة الاتصال الآمن ببوابة الدفع.', ['certificate_unreadable' => $certificatePath]);
        }

        $pem = str_contains($der, '-----BEGIN CERTIFICATE-----')
            ? $der
            : "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($der), 64, "\n") . '-----END CERTIFICATE-----';

        $encrypted = '';
        if (! openssl_public_encrypt($clear, $encrypted, $pem)) {
            throw new PaymentException('تعذر تهيئة الاتصال الآمن ببوابة الدفع.', ['openssl_error' => openssl_error_string()]);
        }

        return base64_encode($encrypted);
    }

    /**
     * @return array{encrypted:string, secret:string} base64 ciphertext and base64(key||iv)
     */
    public function aesEncrypt(string $plaintext): array
    {
        $key = random_bytes(32);
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));

        $cipher = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new PaymentException('تعذر تشفير طلب الدفع.', ['openssl_error' => openssl_error_string()]);
        }

        return [
            'encrypted' => base64_encode($cipher),
            'secret' => base64_encode($key . $iv),
        ];
    }

    /** Returns null when the secret/ciphertext do not match (wrong key, bad padding, garbage). */
    public function aesDecrypt(string $encryptedBase64, string $secretBase64): ?string
    {
        $material = base64_decode($secretBase64, true);
        $cipher = base64_decode($encryptedBase64, true);

        if ($material === false || strlen($material) < 48 || $cipher === false || $cipher === '') {
            return null;
        }

        $plain = openssl_decrypt($cipher, self::CIPHER, substr($material, 0, 32), OPENSSL_RAW_DATA, substr($material, 32, 16));

        return $plain === false ? null : $plain;
    }

    /** Lowercase hex SHA-256 of the (ISO-8859-1 -> UTF-8 converted) input, as the vendor sample did with utf8_encode(). */
    public function hashSha256Hex(string $input): string
    {
        return hash('sha256', mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1'));
    }

    /** Random lowercase hex string ($length chars). */
    public function randomHex(int $length): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}
