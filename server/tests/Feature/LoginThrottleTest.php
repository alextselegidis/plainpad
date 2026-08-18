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
            $this->attempt('victim@example.com', 'wrong-guess-' . $i)->assertStatus(401);
        }

        $this->attempt('victim@example.com', 'wrong-guess-5')->assertStatus(429);
    }

    public function test_throttle_is_keyed_on_the_target_account_not_only_the_ip(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->attempt('victim@example.com', 'wrong-guess-' . $i);
        }

        // A different account is unaffected by the exhausted per-account bucket.
        $this->attempt('someone-else@example.com')->assertStatus(401);
    }
}
