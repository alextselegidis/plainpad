<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User;
        $this->user->id = (string) Str::uuid();
        $this->user->name = 'Victim';
        $this->user->email = 'victim@example.com';
        $this->user->password = Hash::make('old-password');
        $this->user->admin = false;
        $this->user->save();
    }

    private function login(string $password = 'old-password'): string
    {
        return $this->postJson('/v1/sessions', [
            'email' => 'victim@example.com',
            'password' => $password,
        ])->assertStatus(201)->json('token');
    }

    private function canAccess(string $token): bool
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson('/v1/users/'.$this->user->id)->status() === 200;
    }

    public function test_password_change_revokes_other_sessions_but_keeps_the_current_one(): void
    {
        $stolen = $this->login();
        $current = $this->login();

        $this->withToken($current)->putJson('/v1/users/'.$this->user->id, [
            'name' => 'Victim',
            'email' => 'victim@example.com',
            'password' => 'new-password',
        ])->assertStatus(200);

        $this->assertFalse($this->canAccess($stolen));
        $this->assertTrue($this->canAccess($current));
    }

    public function test_password_reset_revokes_all_sessions(): void
    {
        $stolen = $this->login();

        DB::table('password_resets')->insert([
            'email' => 'victim@example.com',
            'token' => Hash::make('reset-token'),
            'created_at' => now(),
        ]);

        $this->postJson('/v1/users/reset-password', [
            'email' => 'victim@example.com',
            'token' => 'reset-token',
            'password' => 'reset-password',
        ])->assertStatus(200);

        $this->assertFalse($this->canAccess($stolen));
    }
}
