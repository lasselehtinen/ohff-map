<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Sample data for local development. The reference numbers are made up.
 */
class DemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = $this->user('Demo Admin', 'OH0ADM', 'admin@example.com', true);
        $activator = $this->user('Demo Activator', 'OH0DEM', 'activator@example.com', false);

        $now = now();
        $programId = DB::table('programs')->insertGetId(['name' => 'OHFF', 'created_at' => $now, 'updated_at' => $now]);
        $dxccId = DB::table('dxccs')->insertGetId(['name' => 'OH', 'created_at' => $now, 'updated_at' => $now]);
        $continentId = DB::table('continents')->insertGetId(['name' => 'EU', 'created_at' => $now, 'updated_at' => $now]);

        // Reference, name, latitude, longitude, county, years since activation, status, approval status, Natura 2000
        $references = [
            ['OHFF-9001', 'Nuuksion kansallispuisto', 60.3065, 24.5069, 'OH-UU', 0, 'active', 'saved', true],
            ['OHFF-9002', 'Repoveden kansallispuisto', 61.1855, 26.8965, 'OH-KL', 0, 'active', 'saved', false],
            ['OHFF-9003', 'Liesjärven kansallispuisto', 60.6711, 23.8579, 'OH-KT', 1, 'active', 'saved', false],
            ['OHFF-9004', 'Helvetinjärven kansallispuisto', 62.0355, 23.8533, 'OH-PM', 1, 'active', 'saved', true],
            ['OHFF-9005', 'Koloveden kansallispuisto', 62.2559, 28.8240, 'OH-ES', 2, 'active', 'saved', false],
            ['OHFF-9006', 'Kolin kansallispuisto', 63.0964, 29.8061, 'OH-PK', 2, 'active', 'saved', true],
            ['OHFF-9007', 'Rokuan kansallispuisto', 64.5570, 26.5136, 'OH-PP', 3, 'active', 'saved', false],
            ['OHFF-9008', 'Hossan kansallispuisto', 65.4530, 29.5835, 'OH-KU', 3, 'active', 'saved', false],
            ['OHFF-9009', 'Oulangan kansallispuisto', 66.3690, 29.3247, 'OH-PP', 4, 'active', 'saved', true],
            ['OHFF-9010', 'Pyhä-Luoston kansallispuisto', 67.0660, 27.1530, 'OH-LA', 7, 'active', 'saved', false],
            ['OHFF-9011', 'Urho Kekkosen kansallispuisto', 68.1600, 28.2500, 'OH-LA', null, 'active', 'saved', true],
            ['OHFF-9012', 'Lemmenjoen kansallispuisto', 68.6500, 25.5000, 'OH-LA', null, 'active', 'saved', false],
            ['OHFF-9013', 'Saaristomeren kansallispuisto', 59.9200, 21.8700, 'OH-VA', null, 'active', 'saved', true],
            ['OHFF-9014', 'Pallas-Yllästunturin kansallispuisto', 68.0500, 24.0500, 'OH-LA', null, 'proposed', 'received', false],
            ['OHFF-9015', 'Seitsemisen kansallispuisto', 61.9400, 23.4300, 'OH-PM', null, 'proposed', 'approved', false],
            ['OHFF-9016', 'Patvinsuon kansallispuisto', 63.1100, 30.7000, 'OH-PK', null, 'active', 'received', false],
        ];

        foreach ($references as $index => [$reference, $name, $latitude, $longitude, $county, $yearsAgo, $status, $approvalStatus, $natura]) {
            $activationDate = is_null($yearsAgo) ? null : Carbon::now()->subYears($yearsAgo)->subDay()->toDateString();

            $id = DB::table('references')->insertGetId([
                'reference' => $reference,
                'name' => $name,
                'county' => $county,
                'status' => $status,
                'approval_status' => $approvalStatus,
                'natura_2000_area' => $natura,
                'wdpa_id' => 10000 + $index,
                'first_activation_date' => $activationDate,
                'latest_activation_date' => $activationDate,
                'program_id' => $programId,
                'dxcc_id' => $dxccId,
                'continent_id' => $continentId,
                'location' => DB::raw("ST_GeomFromText('POINT({$longitude} {$latitude})')"),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (! is_null($activationDate)) {
                DB::table('user_activations')->insert([
                    'user_id' => $index % 2 === 0 ? $activator->id : $admin->id,
                    'reference_id' => $id,
                    'activation_date' => $activationDate,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function user(string $name, string $callsign, string $email, bool $isAdmin): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $name,
            'callsign' => $callsign,
            'email' => $email,
            'password' => Hash::make('password'),
            'is_admin' => $isAdmin,
        ])->save();

        return $user;
    }
}
