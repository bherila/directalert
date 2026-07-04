<?php

namespace Tests\Unit;

use App\Support\DirectAlertCrypto;
use RuntimeException;
use Tests\TestCase;

class DirectAlertCryptoTest extends TestCase
{
    public function test_blind_index_is_deterministic(): void
    {
        $this->assertSame(
            DirectAlertCrypto::blindIndex('1234567'),
            DirectAlertCrypto::blindIndex('1234567')
        );
    }

    public function test_blind_index_differs_for_different_input(): void
    {
        $this->assertNotSame(
            DirectAlertCrypto::blindIndex('1234567'),
            DirectAlertCrypto::blindIndex('7654321')
        );
    }

    public function test_account_number_round_trips(): void
    {
        $encrypted = DirectAlertCrypto::encryptAccountNumber('1234567');

        $this->assertNotSame('1234567', $encrypted);
        $this->assertSame('1234567', DirectAlertCrypto::decryptAccountNumber($encrypted));
    }

    public function test_bound_name_round_trips_for_matching_account_number(): void
    {
        $encrypted = DirectAlertCrypto::encryptBoundName('1234567', 'DOE, JANE');

        $this->assertSame('DOE, JANE', DirectAlertCrypto::decryptBoundName($encrypted, '1234567'));
    }

    public function test_bound_name_ciphertext_is_non_deterministic_for_duplicate_names(): void
    {
        $first = DirectAlertCrypto::encryptBoundName('1111111', 'SMITH, JOHN');
        $second = DirectAlertCrypto::encryptBoundName('2222222', 'SMITH, JOHN');

        $this->assertNotSame($first, $second);
    }

    public function test_bound_name_rejects_ciphertext_moved_to_a_different_account_number(): void
    {
        $encrypted = DirectAlertCrypto::encryptBoundName('1234567', 'DOE, JANE');

        $this->expectException(RuntimeException::class);

        DirectAlertCrypto::decryptBoundName($encrypted, '9999999');
    }
}
