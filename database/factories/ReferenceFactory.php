<?php

namespace Database\Factories;

use App\Models\Reference;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reference>
 */
class ReferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Reference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->numerify('OHFF-####'),
            'status' => fake()->randomElement(['active', 'deleted', 'national', 'proposed']),
            'name' => $this->getRandomNationalPark(),
            'valid_from' => fake()->date(),
            'first_activation_date' => fake()->date(),
            'latest_activation_date' => fake()->date(),
            'location' => Point::makeGeodetic(fake()->latitude(60, 70), fake()->longitude(19, 32)),
            'iota_reference' => fake()->numerify(fake()->randomElement(['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA']).'-###'),
            'wdpa_id' => fake()->numberBetween(1, 100000),
            'natura_2000_area' => fake()->boolean(),
            'approval_status' => fake()->randomElement(['received', 'declined', 'approved', 'saved']),
        ];
    }

    /**
     * Indicate that the reference is active.
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'approval_status' => 'saved',
            ];
        });
    }

    /**
     * Get random Finnish national park
     */
    public function getRandomNationalPark(): string
    {
        return fake()->randomElement([
            'Lemmenjoki',
            'Urho Kekkonen',
            'Pallas–Ylläs',
            'Selkämeri',
            'Saaristomeri',
            'Syöte',
            'Oulanka',
            'Perämeri',
            'Pyhä–Luosto',
            'Hossa',
            'Patvinsuo',
            'Salla',
            'Linnansaari',
            'Riisitunturi',
            'Tiilikkajärvi',
            'Kauhaneva–Pohjankangas',
            'Salamajärvi',
            'Kolovesi',
            'Lauhanvuori',
            'Nuuksio',
            'Tammisaaren saaristo',
            'Helvetinjärvi',
            'Seitseminen',
            'Hiidenportti',
            'Teijo',
            'Leivonmäki',
            'Kurjenrahka',
            'Torronsuo',
            'Koli',
            'Puurijärvi–Isosuo',
            'Sipoonkorpi',
            'Isojärvi',
            'Liesjärvi',
            'Valkmusa',
            'Rokua',
            'Etelä-Konnevesi',
            'Päijänne',
            'Repovesi',
            'Pyhä-Häkki',
            'Itäinen Suomenlahti',
            'Petkeljärvi',
        ]);
    }
}
