<?php

namespace Tests\Feature;

use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ProgramsApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listing programs
     *
     * @return void
     */
    public function test_listing_programs()
    {
        $program = Program::factory()->create();

        $response = $this->get('/api/programs');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'id' => $program->id,
                        'name' => $program->name,
                    ]
                ],
            ]);
    }

    /**
     * Test getting individual program
     *
     * @return void
     */
    public function test_getting_individual_program()
    {
        $program = Program::factory()->create();

        $response = $this->get('/api/programs/' . $program->id);

        $response
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'references' => [],
                ],
            ]);
    }

    /**
     * Test filtering programs by name
     *
     * @return void
     */
    public function test_filtering_programs_by_name()
    {
        $program = Program::factory()->create(['name' => 'OHFF']);

        // We should get match
        $response = $this->get('/api/programs?filter[name]=OHFF');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'id' => $program->id,
                        'name' => $program->name,
                    ]
                ],
            ]);

        // No matches should be found
        $response = $this->get('/api/programs?filter[name]=foobar');

        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 0)->etc());
    }
}
