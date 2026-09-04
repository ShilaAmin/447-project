<?php

namespace App\Services;

use phpseclib4\Crypt\DH;
use phpseclib4\Crypt\EC;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Crypt\RSA;
use RuntimeException;

/**
 * One-time migration helper to decrypt previous AES-hybrid ciphertexts.
 * Not used for new application writes.
 */
class LegacyHybridDecryptor
{
    public function decryptRsaHybrid(string $payload): string
    {
        $privatePem = @file_get_contents(storage_path('app/keys/system_rsa_private.pem'));
        if (!$privatePem) {
            throw new RuntimeException('Legacy RSA private key missing.');
        }

        $decoded = json_decode(base64_decode($payload, true) ?: '', true);
        if (!is_array($decoded) || !isset($decoded['key'], $decoded['iv'], $decoded['tag'], $decoded['data'])) {
            throw new RuntimeException('Not legacy RSA hybrid.');
        }

        $wrappedKey = base64_decode($decoded['key'], true);
        $iv = base64_decode($decoded['iv'], true);
        $tag = base64_decode($decoded['tag'], true);
        $data = base64_decode($decoded['data'], true);

        /** @var \phpseclib4\Crypt\RSA\PrivateKey $private */
        $private = PublicKeyLoader::load($privatePem);
        $aesKey = $private->withPadding(RSA::ENCRYPTION_OAEP)->decrypt($wrappedKey);
        $plaintext = openssl_decrypt($data, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new RuntimeException('Legacy RSA hybrid decrypt failed.');
        }
        return $plaintext;
    }

    public function decryptEccHybrid(string $payload): string
    {
        $privatePem = @file_get_contents(storage_path('app/keys/system_ecc_private.pem'));
        if (!$privatePem) {
            throw new RuntimeException('Legacy ECC private key missing.');
        }

        $decoded = json_decode(base64_decode($payload, true) ?: '', true);
        if (!is_array($decoded) || !isset($decoded['epk'], $decoded['iv'], $decoded['tag'], $decoded['data'])) {
            throw new RuntimeException('Not legacy ECC hybrid.');
        }

        /** @var \phpseclib4\Crypt\EC\PrivateKey $private */
        $private = PublicKeyLoader::load($privatePem);
        /** @var \phpseclib4\Crypt\EC\PublicKey $ephemeralPublic */
        $ephemeralPublic = PublicKeyLoader::load($decoded['epk']);
        $shared = DH::computeSecret($private, $ephemeralPublic);
        $aesKey = hash('sha256', $shared, true);
        $iv = base64_decode($decoded['iv'], true);
        $tag = base64_decode($decoded['tag'], true);
        $data = base64_decode($decoded['data'], true);
        $plaintext = openssl_decrypt($data, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new RuntimeException('Legacy ECC hybrid decrypt failed.');
        }
        return $plaintext;
    }

    public function looksLikeLegacyHybrid(string $value): bool
    {
        if (!str_starts_with($value, 'eyJ')) {
            return false;
        }
        $decoded = json_decode(base64_decode($value, true) ?: '', true);
        return is_array($decoded) && isset($decoded['alg']);
    }
}
