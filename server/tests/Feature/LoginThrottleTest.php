<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(string $email, string $password = 'wrong-guess')
    {
        return $this->postJson('/v1/sessions', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function test_login_is_throttled_after_five_failed_attempts_against_an_account(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->attempt('victim@example.com', 'wrong-guess-'.$i)->assertStatus(401);
        }

        $this->attempt('victim@example.com', 'wrong-guess-5')->assertStatus(429);
    }

    public function test_failed_attempts_from_one_address_do_not_lock_the_owner_out_elsewhere(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->attempt('victim@example.com', 'wrong-guess-'.$i);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->attempt('victim@example.com', 'wrong-guess')->assertStatus(401);
    }

    public function test_attempts_spread_across_addresses_still_hit_the_account_limit(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.'.$i])->attempt('victim@example.com', 'wrong-guess-'.$i);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->attempt('victim@example.com', 'wrong-guess')->assertStatus(429);
    }

    public function test_throttle_is_keyed_on_the_target_account_not_only_the_ip(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->attempt('victim@example.com', 'wrong-guess-'.$i);
        }

        // A different account is unaffected by the exhausted per-account bucket.
        $this->attempt('someone-else@example.com')->assertStatus(401);
    }
}
