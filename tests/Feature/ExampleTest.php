<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
    public function test_api_token_creation_flashes_a_success_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('api-tokens.store'), ['name' => 'ESP32 Test'])
            ->assertRedirect()
            ->assertSessionHas('status', 'Token API berhasil dibuat.')
            ->assertSessionHas('plain_token');
    }
}
