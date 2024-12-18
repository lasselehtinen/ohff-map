<?php

namespace Tests\Feature;

use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
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
                    'activations' => [],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'callsign' => $user->callsign,
            'name' => $user->name,
            'email' => $user->email,
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
            ->assertJson(fn (AssertableJson $json) => $json->whereType('token', 'string'));
    }

    /**
     * Test that logging out removes all tokens from the user
     *
     * @return void
     */
    public function test_logging_out_as_an_user()
    {
        $user = User::factory()->create();
        $user->createToken('*');
        Sanctum::actingAs($user, ['*']);

        $response = $this->post('/api/logout');
        $response->assertExactJson(['message' => 'Logged out.']);
        $this->assertCount(0, $user->tokens);
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
     * Test that user can only view their own information
     *
     * @return void
     */
    public function test_user_can_only_view_their_own_information()
    {
        // Create two users
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        Sanctum::actingAs($userOne, ['*']);

        // User should be able to view their own info
        $response = $this->get('/api/users/1');

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'id' => $userOne->id,
                    'callsign' => $userOne->callsign,
                    'name' => $userOne->name,
                    'email' => $userOne->email,
                    'activations' => [],
                ],
            ]);

        // User should not be able to see other users info
        $response = $this->get('/api/users/2');

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => 'http://localhost/api/users/2'],
                        'title' => 'Forbidden',
                        'detail' => 'You are only allowed to view your own user information.',
                    ],
                ],
            ]);
    }

    /**
     * Test that user can only edit their own information
     *
     * @return void
     */
    public function test_user_can_only_edit_their_own_information()
    {
        // Create two users
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        Sanctum::actingAs($userOne, ['*']);

        // User should be able to edit their own info
        $response = $this->put('/api/users/1', [
            'email' => 'newemail@domain.com',
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'id' => $userOne->id,
                    'callsign' => $userOne->callsign,
                    'name' => $userOne->name,
                    'email' => 'newemail@domain.com',
                    'activations' => [],
                ],
            ]);

        // User should not be able to edit other users info
        $response = $this->put('/api/users/2', [
            'email' => 'newemail@domain.com',
        ]);

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => 'http://localhost/api/users/2'],
                        'title' => 'Forbidden',
                        'detail' => 'You are only allowed to edit your own user information.',
                    ],
                ],
            ]);
    }

    /**
     * Test that user can mark WWFF reference as activated
     *
     * @return void
     */
    public function test_user_can_mark_reference_as_activated()
    {
        $user = User::factory()->create();

        $reference = Reference::factory()
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();

        Sanctum::actingAs(
            $user,
            ['auth_token']
        );

        $response = $this->get('/api/users/1');

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'id' => $user->id,
                    'callsign' => $user->callsign,
                    'name' => $user->name,
                    'email' => $user->email,
                    'activations' => [],
                ],
            ]);

        // Mark reference as activated
        $response = $this->put('/api/users/1/activations/1');

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'status' => 'success',
                'message' => 'Reference marked as activated for user.',
            ]);

        // The activation should now appear on the user
        $user->refresh();
        $response = $this->get('/api/users/1');

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'id' => $user->id,
                    'callsign' => $user->callsign,
                    'name' => $user->name,
                    'email' => $user->email,
                    'activations' => [
                        [
                            'id' => $reference->id,
                            'reference' => $reference->reference,
                            'status' => $reference->status,
                            'first_activation_date' => $reference->first_activation_date,
                            'latest_activation_date' => $reference->latest_activation_date,
                            'name' => $reference->name,
                            'latitude' => null,
                            'longitude' => null,
                            'iota_reference' => null,
                            'program' => [
                                'id' => $reference->program->id,
                                'name' => $reference->program->name,
                            ],
                            'dxcc' => [
                                'id' => $reference->dxcc->id,
                                'name' => $reference->dxcc->name,
                            ],
                            'continent' => [
                                'id' => $reference->continent->id,
                                'name' => $reference->continent->name,
                            ],
                            'activators' => [
                                [
                                    'id' => $user->id,
                                    'callsign' => $user->callsign,
                                    'activation_date' => '2021-01-01',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test that user cannot mark WWFF reference as activated for someone elase
     *
     * @return void
     */
    public function test_user_cannot_mark_reference_as_activated_for_another_user()
    {
        // Create two users
        $userOne = User::factory()->create();
        $userTwo = User::factory()->create();

        $reference = Reference::factory()
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();

        Sanctum::actingAs($userOne, ['*']);

        // User should not be able to edit other users info
        $response = $this->put('/api/users/2/activations/1');

        $response
            ->assertStatus(403)
            ->assertExactJson([
                'errors' => [
                    [
                        'status' => '403',
                        'source' => ['pointer' => 'http://localhost/api/users/2/activations/1'],
                        'title' => 'Forbidden',
                        'detail' => 'You are only allowed to edit your own user information.',
                    ],
                ],
            ]);
    }
}
