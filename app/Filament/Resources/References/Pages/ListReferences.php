<?php

namespace App\Filament\Resources\References\Pages;

use App\Filament\Resources\References\ReferenceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReferences extends ListRecords
{
    public const NON_APPROVED_TAB = 'non-approved';

    protected static string $resource = ReferenceResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All references'),
            self::NON_APPROVED_TAB => Tab::make('Non-approved references')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approval_status', 'received')),
        ];
    }
}
