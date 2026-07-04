<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Chiffrement authentifié (AEAD) des tokens de lecture, via `sodium_crypto_secretbox`.
 * La clé est dérivée d'`APP_SECRET` par HKDF : aucune variable d'environnement dédiée.
 *
 * En D2, seul {@see encrypt()} est consommé ; {@see decrypt()} existe pour le connecteur D3.
 */
final class TokenCipher
{
    private const KEY_CONTEXT = 'project-read-token';

    private readonly string $key;

    public function __construct(#[Autowire('%kernel.secret%')] string $secret)
    {
        if ('' === $secret) {
            throw new \LogicException('APP_SECRET doit être défini pour chiffrer les tokens de projet.');
        }

        $this->key = hash_hkdf('sha256', $secret, \SODIUM_CRYPTO_SECRETBOX_KEYBYTES, self::KEY_CONTEXT);
    }

    public function encrypt(string $plain): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->key));
    }

    public function decrypt(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);

        if (false === $decoded || \strlen($decoded) <= \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('Token chiffré illisible.');
        }

        $nonce = substr($decoded, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($decoded, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->key);

        if (false === $plain) {
            throw new \RuntimeException('Échec du déchiffrement du token.');
        }

        return $plain;
    }
}
