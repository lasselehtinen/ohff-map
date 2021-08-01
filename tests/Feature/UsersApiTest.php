<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UsersApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating an user
     *
     * @return void
     */
    public function test_creating_an_user()
    {
        // Use factory to create faker data
        $user = User::factory()->make();

        $response = $this->post('/api/users', [
           'name' => $user->name,
           'callsign' => $user->callsign,
           'email' => $user->email,
           'password' => $user->password,
        ]);

        $response
            ->assertStatus(201)
            ->assertExactJson([
                'data' => [
                    'id' => 1,
                    'callsign' => $user->callsign,
                    'name' => $user->name,
                    'email' => $user->email,
                    'activations' => []
                ]
            ]);
    }

    /**
     * Test logging in as an user
     *
     * @return void
     */
    public function test_logging_in_as_an_user()
    {
        $user = User::factory()->create();

        $response = $this->post('/api/login', [
           'email' => $user->email,
           'password' => 'password',
        ]);

        $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->whereType('token', 'string')
            );
    }

    /**
     * Test logging in with incorrect password returns error message
     *
     * @return void
     */
    public function test_logging_in_with_incorrect_password_returns_error_message()
    {
        $user = User::factory()->create();

        $response = $this->post('/api/login', [
           'email' => $user->email,
           'password' => 'foopassword',
        ]);

        $response
            ->assertStatus(401)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '401',
                        'source' => ['pointer' => 'http://localhost/api/login'],
                        'title' => 'Email and/or password is incorrect',
                        'detail' => 'The given credentials do not match. Check the email and password.',
                    ],
                ],
            ]);
    }

    /**
     * Test resource requiring authentication returns error message
     * 
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
