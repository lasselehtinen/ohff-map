<?php

namespace App\Filament\Resources\References\Schemas;

use App\Filament\Resources\References\ReferenceResource;
use App\Models\Reference;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class ReferenceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('reference'),
                TextEntry::make('name'),
                TextEntry::make('county'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (?string $state) => ReferenceResource::statusColor($state)),
                TextEntry::make('approval_status')
                    ->label('Approval status')
                    ->badge()
                    ->color(fn (?string $state) => ReferenceResource::approvalStatusColor($state)),
                TextEntry::make('latitude')
                    ->state(fn (Reference $record) => ReferenceResource::formatCoordinate($record->location?->latitude))
                    ->copyable(),
                TextEntry::make('longitude')
                    ->state(fn (Reference $record) => ReferenceResource::formatCoordinate($record->location?->longitude))
                    ->copyable(),
                IconEntry::make('natura_2000_area')
                    ->label('Natura 2000')
                    ->boolean(),
                ViewEntry::make('admin_links')
                    ->label('Links')
                    ->view('filament.reference-links'),
            ]);
    }
}
