<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstallTest extends TestCase
{
    public function test_install_is_not_reachable_over_http(): void
    {
        $this->postJson('/v1')->assertStatus(405);

        $this->assertFalse(Schema::hasTable('users'));
    }
}
