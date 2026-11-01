<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function testGuestIsRedirectedToLogin()
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/references')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function testPasswordResetPageIsAvailable()
    {
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function testUserWithoutAdminFlagIsForbidden()
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function testUserRegisteredThroughPublicApiHasNoPanelAccess()
    {
        $this->postJson('/api/users', [
            'name' => 'Someone',
            'callsign' => 'OH0XX',
            'email' => 'someone@example.com',
            'password' => 'secret-password',
        ])->assertCreated();

        $this->actingAs(User::where('email', 'someone@example.com')->firstOrFail())
            ->get('/admin')
            ->assertForbidden();
    }

    public function testApiCannotGrantPanelAccess()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/'.$user->id, ['is_admin' => true])
            ->assertOk();

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function testAdminCanOpenEveryPage()
    {
        $admin = $this->admin();

        foreach (['/admin', '/admin/references', '/admin/users', '/admin/users/create', '/admin/users/'.$admin->id, '/admin/users/'.$admin->id.'/edit'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function testAdminPagesAreNotResponseCached()
    {
        config(['responsecache.enabled' => true]);

        $this->actingAs($this->admin())->get('/admin/users')
            ->assertOk()
            ->assertHeaderMissing('laravel-responsecache');
    }

    public function testUserWithoutANameCanUseThePanel()
    {
        $admin = $this->admin(['name' => null, 'callsign' => 'OH0YY']);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('OH0YY');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->is_admin = true;
        $user->save();

        return $user;
    }
}
