<?php

namespace App\Filament\Resources\References\Pages;

use App\Filament\Resources\References\Actions\MarkReferenceSaved;
use App\Filament\Resources\References\ReferenceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReference extends ViewRecord
{
    protected static string $resource = ReferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MarkReferenceSaved::single(),
            EditAction::make(),
        ];
    }
}
