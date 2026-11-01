<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['callsign' => 'OH0AA']);
        $this->admin->is_admin = true;
        $this->admin->save();

        $this->actingAs($this->admin);
    }

    public function testTableCanBeSearchedByCallsignNameAndEmail()
    {
        $user = User::factory()->create(['callsign' => 'OH1ZZ', 'name' => 'Tester', 'email' => 'tester@example.com']);

        foreach (['oh1zz', 'Tester', 'tester@example.com'] as $search) {
            Livewire::test(ListUsers::class)
                ->searchTable($search)
                ->assertCanSeeTableRecords([$user])
                ->assertCanNotSeeTableRecords([$this->admin]);
        }
    }

    public function testUserCanBeCreatedWithHashedPassword()
    {
        Livewire::test(CreateUser::class)
            ->fillForm(['callsign' => 'oh1ab', 'name' => 'New user', 'email' => 'new@example.com', 'password' => 'secret-password', 'is_admin' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'new@example.com')->firstOrFail();

        $this->assertSame('OH1AB', $user->callsign);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertTrue($user->is_admin);
    }

    public function testValidationRules()
    {
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => '', 'email' => 'not-an-email', 'password' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'email', 'password' => 'required']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Duplicate', 'email' => $this->admin->email, 'password' => 'secret-password'])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function testBlankPasswordKeepsTheExistingOneOnUpdate()
    {
        $user = User::factory()->create(['password' => Hash::make('original-password')]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSchemaStateSet(['password' => null])
            ->fillForm(['name' => 'Renamed', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Renamed', $user->name);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    public function testPasswordCanBeChangedAndEmailKeptOnUpdate()
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['password' => 'changed-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));
    }

    public function testPanelAccessCanBeGrantedAndRevoked()
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['is_admin' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($user->fresh()->is_admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['is_admin' => false])
            ->call('save');

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function testAdminCannotRevokeTheirOwnAccess()
    {
        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm(['is_admin' => false])
            ->call('save');

        $this->assertTrue($this->admin->fresh()->is_admin);
    }

    public function testUserCanBeDeleted()
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertNull($user->fresh());
    }

    public function testUserCanBeReplicatedWithoutCopyingPanelAccess()
    {
        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('replicate')->table($this->admin), [
                'name' => 'Copy',
                'callsign' => 'OH0BB',
                'email' => 'copy@example.com',
                'password' => 'secret-password',
            ])
            ->assertHasNoFormErrors();

        $copy = User::where('email', 'copy@example.com')->firstOrFail();

        $this->assertSame('Copy', $copy->name);
        $this->assertFalse((bool) $copy->is_admin);
        $this->assertTrue(Hash::check('secret-password', $copy->password));
    }

    public function testReplicatingRequiresANewEmail()
    {
        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('replicate')->table($this->admin), [
                'name' => 'Copy',
                'email' => $this->admin->email,
                'password' => 'secret-password',
            ])
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function testGlobalSearchFindsUsers()
    {
        $user = User::factory()->create(['name' => 'Findable', 'email' => 'findable@example.com']);

        $result = UserResource::getGlobalSearchResults('findable@example.com')->first();

        $this->assertSame('Findable', (string) $result->title);
        $this->assertStringContainsString((string) $user->id, $result->url);
    }
}
