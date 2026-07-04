<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TokenCipher;
use PHPUnit\Framework\TestCase;

final class TokenCipherTest extends TestCase
{
    private TokenCipher $cipher;

    protected function setUp(): void
    {
        $this->cipher = new TokenCipher('une-clé-secrète-de-test');
    }

    public function testRoundTrip(): void
    {
        $token = 'ghp_readOnlyToken1234567890';

        self::assertSame($token, $this->cipher->decrypt($this->cipher->encrypt($token)));
    }

    public function testEncryptionIsNonDeterministic(): void
    {
        $token = 'ghp_readOnlyToken1234567890';

        self::assertNotSame($this->cipher->encrypt($token), $this->cipher->encrypt($token));
    }

    public function testDecryptRejectsTamperedCiphertext(): void
    {
        $decoded = base64_decode($this->cipher->encrypt('ghp_token'), true);
        self::assertNotFalse($decoded);
        $decoded[\strlen($decoded) - 1] = $decoded[\strlen($decoded) - 1] ^ "\xff";

        $this->expectException(\RuntimeException::class);
        $this->cipher->decrypt(base64_encode($decoded));
    }

    public function testEmptySecretIsRejected(): void
    {
        $this->expectException(\LogicException::class);
        new TokenCipher('');
    }
}
