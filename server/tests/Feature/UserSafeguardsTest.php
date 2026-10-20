<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, bool $admin): User
    {
        $user = new User;
        $user->id = (string) Str::uuid();
        $user->name = 'User';
        $user->email = $email;
        $user->password = Hash::make('password');
        $user->admin = $admin;
        $user->save();

        return $user;
    }

    private function token(string $email): string
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/v1/sessions', ['email' => $email, 'password' => 'password'])
            ->assertStatus(201)->json('token');
    }

    private function update(string $token, User $user, array $data)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->putJson('/v1/users/'.$user->id, $data + [
            'name' => 'User',
            'email' => $user->email,
        ]);
    }

    public function test_changing_own_email_or_password_needs_the_current_password(): void
    {
        $user = $this->user('user@example.com', false);
        $token = $this->token('user@example.com');

        $this->update($token, $user, ['password' => 'new-password'])->assertStatus(422);
        $this->update($token, $user, ['email' => 'thief@example.com'])->assertStatus(422);
        $this->update($token, $user, ['password' => 'new-password', 'current_password' => 'wrong'])->assertStatus(422);

        $this->update($token, $user, ['theme' => 'dark'])->assertStatus(200);
        $this->update($token, $user, ['email' => 'new@example.com', 'current_password' => 'password'])
            ->assertStatus(200)->assertJsonPath('email', 'new@example.com');
    }

    public function test_admin_can_change_another_users_password_without_theirs(): void
    {
        $this->user('admin@example.com', true);
        $user = $this->user('user@example.com', false);

        $this->update($this->token('admin@example.com'), $user, ['password' => 'reset-by-admin'])->assertStatus(200);
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $admin = $this->user('admin@example.com', true);
        $token = $this->token('admin@example.com');

        $this->update($token, $admin, ['admin' => false])->assertStatus(422);

        $other = $this->user('second@example.com', true);
        $this->update($token, $other, ['admin' => false])->assertStatus(200);
        $this->update($token, $admin, ['admin' => false])->assertStatus(422);
    }

    public function test_a_note_can_be_emptied(): void
    {
        $this->user('user@example.com', false);
        $token = $this->token('user@example.com');

        $id = $this->withToken($token)->postJson('/v1/notes', ['title' => 'NO-TITLE', 'content' => '', 'pinned' => false])
            ->assertStatus(201)->json('id');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->putJson('/v1/notes/'.$id, ['title' => 'NO-TITLE', 'content' => '', 'pinned' => false])
            ->assertStatus(200)->assertJsonPath('content', '');
    }
}
