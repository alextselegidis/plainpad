<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstallTest extends TestCase
{
    public function test_install_works_on_an_empty_database(): void
    {
        $this->assertFalse(Schema::hasTable('sessions'));

        $this->postJson('/v1')->assertStatus(200);

        $this->postJson('/v1/sessions', [
            'email' => 'admin@example.org',
            'password' => '12345678',
        ])->assertStatus(201);
    }
}
