<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiErrorMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that illegal method returns correct error message
     *
     * @return void
     */
    public function test_illegal_method_returns_correct_error_message()
    {
        $response = $this->delete('/api/references');

        $response
            ->assertStatus(405)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '405',
                        'source' => ['pointer' => 'http://localhost/api/references'],
                        'title' => 'Invalid method',
                        'detail' => 'Targeted resource does not support the requested HTTP method. Please check the documentation.',
                    ],
                ],
            ]);
    }

    /**
     * Test that non existing resource returns correct error message
     *
     * @return void
     */
    public function test_non_existing_resource_returns_correct_error_message()
    {
        Sanctum::actingAs(
            User::factory()->create(),
            ['auth_token']
        );

        $response = $this->get('/api/references/123456');

        $response
            ->assertStatus(404)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '404',
                        'source' => ['pointer' => 'http://localhost/api/references/123456'],
                        'title' => 'Resource not found',
                        'detail' => 'Targeted resource does not exist. Check the URL of the given resource ID.',
                    ],
                ],
            ]);
    }

    /**
     * Test resource requiring authentication returns error message
     */
    public function test_resource_requiring_authentication_returns_error_message()
    {
        $response = $this->get('/api/users/1');

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => 'http://localhost/api/users/1'],
                        'title' => 'Forbidden',
                        'detail' => 'The given resource requires authentication.',
                    ],
                ],
            ]);
    }
}
