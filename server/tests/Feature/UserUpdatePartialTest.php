<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserUpdatePartialTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_without_encrypt_and_admin_keeps_them_and_notes_stay_readable(): void
    {
        $user = new User;
        $user->id = (string) Str::uuid();
        $user->name = 'Admin';
        $user->email = 'admin@example.com';
        $user->password = Hash::make('password');
        $user->admin = true;
        $user->save();

        $token = $this->postJson('/v1/sessions', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertStatus(201)->json('token');

        $this->withToken($token)->putJson('/v1/users/'.$user->id, [
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'encrypt' => true,
        ])->assertStatus(200);

        $this->withToken($token)->postJson('/v1/notes', [
            'title' => 'Secret',
            'content' => 'Body',
            'pinned' => false,
        ])->assertStatus(201);

        // Same payload shape as the admin users screen: no "encrypt", no "admin".
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->putJson('/v1/users/'.$user->id, [
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ])->assertStatus(200)->assertJson(['encrypt' => true, 'admin' => true]);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/v1/notes')->assertStatus(200)->assertJsonPath('0.title', 'Secret');
    }
}
