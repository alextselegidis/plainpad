<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorResponsesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sent without "Accept: application/json", like a browser or a simple API client does.
     */
    private function login(string $body)
    {
        return $this->call('POST', '/v1/sessions', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
    }

    public static function invalidLoginProvider(): array
    {
        return [
            'username instead of email' => ['{"username":"a","password":"b"}'],
            'empty body' => [''],
            'malformed json' => ['{"email":'],
            'array email' => ['{"email":["a@example.com"],"password":"x"}'],
            'object email' => ['{"email":{"a":1},"password":"x"}'],
            'array password' => ['{"email":"a@example.com","password":["x"]}'],
            'missing password' => ['{"email":"a@example.com"}'],
        ];
    }

    /**
     * @dataProvider invalidLoginProvider
     */
    public function test_invalid_login_input_is_a_json_validation_error(string $body): void
    {
        $response = $this->login($body);

        $response->assertStatus(422);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertArrayHasKey('errors', $response->json());
    }

    public function test_wrong_credentials_return_a_json_message(): void
    {
        $this->login('{"email":"nobody@example.com","password":"wrong"}')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_errors_have_json_bodies(): void
    {
        $this->get('/v1/notes')->assertStatus(401)->assertJsonStructure(['message']);
        $this->get('/v1/does-not-exist')->assertStatus(404)->assertJsonStructure(['message']);
    }
}
