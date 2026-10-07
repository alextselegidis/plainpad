<?php

namespace Tests\Feature;

use App\Mail\PasswordRecovered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTokenTest extends TestCase
{
    use RefreshDatabase;

    private function requestToken(string $email): string
    {
        $token = null;

        Mail::fake();
        $this->postJson('/v1/users/recovery', ['email' => $email])->assertStatus(200);
        Mail::assertSent(function (PasswordRecovered $mail) use (&$token) {
            return (bool) preg_match('/token=([A-Za-z0-9]+)/', $mail->resetUrl, $m) && ($token = $m[1]);
        });

        return $token;
    }

    public function test_new_reset_request_invalidates_the_previous_token(): void
    {
        $user = new User;
        $user->id = (string) Str::uuid();
        $user->name = 'Victim';
        $user->email = 'victim@example.com';
        $user->password = Hash::make('original-pw');
        $user->admin = false;
        $user->save();

        $old = $this->requestToken($user->email);
        $new = $this->requestToken($user->email);

        $this->assertSame(1, DB::table('password_resets')->where('email', $user->email)->count());

        $this->postJson('/v1/users/reset-password', ['email' => $user->email, 'token' => $old, 'password' => 'via-old-token'])
            ->assertStatus(422);
        $this->postJson('/v1/users/reset-password', ['email' => $user->email, 'token' => $new, 'password' => 'via-new-token'])
            ->assertStatus(200);
    }
}
