<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\References\Pages\EditReference;
use App\Filament\Resources\References\Pages\ListReferences;
use App\Filament\Resources\References\Pages\ViewReference;
use App\Filament\Resources\References\ReferenceResource;
use App\Models\Continent;
use App\Models\Dxcc;
use App\Models\Program;
use App\Models\Reference;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReferenceResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $this->actingAs($admin);
    }

    public function testListShowsReferencesWithLinks()
    {
        $reference = $this->reference(['reference' => 'OHFF-0001', 'wdpa_id' => 555]);

        Livewire::test(ListReferences::class)
            ->assertCanSeeTableRecords([$reference])
            ->assertSee('OHFF-0001')
            ->assertSee('https://www.protectedplanet.net/555')
            ->assertSee('https://wwff.co/directory/?showRef=OHFF-0001');
    }

    public function testTableCanBeSearchedByReferenceAndName()
    {
        $first = $this->reference(['reference' => 'OHFF-0001', 'name' => 'Alpha park']);
        $second = $this->reference(['reference' => 'OHFF-0002', 'name' => 'Beta park']);

        Livewire::test(ListReferences::class)
            ->searchTable('OHFF-0002')
            ->assertCanSeeTableRecords([$second])
            ->assertCanNotSeeTableRecords([$first])
            ->searchTable('Alpha')
            ->assertCanSeeTableRecords([$first])
            ->assertCanNotSeeTableRecords([$second]);
    }

    public function testStatusFilters()
    {
        $active = $this->reference(['status' => 'active', 'approval_status' => 'saved']);
        $proposed = $this->reference(['status' => 'proposed', 'approval_status' => 'received']);

        Livewire::test(ListReferences::class)
            ->filterTable('status', 'proposed')
            ->assertCanSeeTableRecords([$proposed])
            ->assertCanNotSeeTableRecords([$active])
            ->resetTableFilters()
            ->filterTable('approval_status', 'saved')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$proposed]);
    }

    public function testNonApprovedTabOnlyListsReceivedReferences()
    {
        $received = $this->reference(['approval_status' => 'received']);
        $saved = $this->reference(['approval_status' => 'saved']);

        Livewire::test(ListReferences::class)
            ->set('activeTab', ListReferences::NON_APPROVED_TAB)
            ->assertCanSeeTableRecords([$received])
            ->assertCanNotSeeTableRecords([$saved])
            ->assertTableColumnHidden('county')
            ->assertTableColumnHidden('natura_2000_area');
    }

    public function testBulkActionMarksReferencesSaved()
    {
        $selected = $this->reference(['approval_status' => 'received']);
        $other = $this->reference(['approval_status' => 'received']);

        Livewire::test(ListReferences::class)
            ->selectTableRecords([$selected->id])
            ->callAction(TestAction::make('markReferencesSaved')->table()->bulk());

        $this->assertSame('saved', $selected->fresh()->approval_status);
        $this->assertSame('received', $other->fresh()->approval_status);
    }

    public function testRowActionMarksReferenceSaved()
    {
        $reference = $this->reference(['approval_status' => 'received']);

        Livewire::test(ListReferences::class)
            ->callAction(TestAction::make('markReferenceSaved')->table($reference));

        $this->assertSame('saved', $reference->fresh()->approval_status);
    }

    public function testViewPageCanMarkReferenceSaved()
    {
        $reference = $this->reference(['approval_status' => 'received', 'county' => 'OH-AL']);

        Livewire::test(ViewReference::class, ['record' => $reference->getRouteKey()])
            ->assertSee('OH-AL')
            ->callAction('markReferenceSaved');

        $this->assertSame('saved', $reference->fresh()->approval_status);
    }

    public function testEditOnlyChangesApprovalStatus()
    {
        $reference = $this->reference(['reference' => 'OHFF-0001', 'name' => 'Original', 'approval_status' => 'received']);

        Livewire::test(EditReference::class, ['record' => $reference->getRouteKey()])
            ->assertSchemaStateSet(['reference' => 'OHFF-0001', 'name' => 'Original', 'approval_status' => 'received'])
            ->fillForm(['reference' => 'OHFF-9999', 'name' => 'Changed', 'approval_status' => 'declined'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(ReferenceResource::getUrl('view', ['record' => $reference]));

        $reference->refresh();

        $this->assertSame('declined', $reference->approval_status);
        $this->assertSame('OHFF-0001', $reference->reference);
        $this->assertSame('Original', $reference->name);
    }

    public function testReferencesCannotBeCreatedOrDeleted()
    {
        $reference = $this->reference();

        $this->assertFalse(ReferenceResource::canCreate());
        $this->assertFalse(ReferenceResource::canDelete($reference));
        $this->assertFalse(ReferenceResource::canDeleteAny());
        $this->assertArrayNotHasKey('create', ReferenceResource::getPages());
        $this->get('/admin/references/create')->assertNotFound();
    }

    public function testGlobalSearchFindsReferenceWithNameAsDetail()
    {
        $reference = $this->reference(['reference' => 'OHFF-1234', 'name' => 'Searchable park']);

        $result = ReferenceResource::getGlobalSearchResults('OHFF-1234')->first();

        $this->assertSame('OHFF-1234', (string) $result->title);
        $this->assertSame(['Name' => 'Searchable park'], $result->details);
        $this->assertStringContainsString((string) $reference->id, $result->url);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function reference(array $attributes = []): Reference
    {
        return Reference::factory($attributes)
            ->for(Program::factory())
            ->for(Dxcc::factory())
            ->for(Continent::factory())
            ->create();
    }
}
