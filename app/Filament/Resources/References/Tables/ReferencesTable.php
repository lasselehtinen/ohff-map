<?php

namespace App\Filament\Resources\References\Tables;

use App\Filament\Resources\References\Actions\MarkReferenceSaved;
use App\Filament\Resources\References\Pages\ListReferences;
use App\Filament\Resources\References\ReferenceResource;
use App\Models\Reference;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('reference')->sortable()->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('county')
                    ->hidden(self::isNonApprovedTab(...)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state) => ReferenceResource::statusColor($state)),
                TextColumn::make('approval_status')
                    ->label('Approval status')
                    ->badge()
                    ->color(fn (?string $state) => ReferenceResource::approvalStatusColor($state)),
                TextColumn::make('latitude')
                    ->state(fn (Reference $record) => ReferenceResource::formatCoordinate($record->location?->latitude))
                    ->copyable(),
                TextColumn::make('longitude')
                    ->state(fn (Reference $record) => ReferenceResource::formatCoordinate($record->location?->longitude))
                    ->copyable(),
                IconColumn::make('natura_2000_area')
                    ->label('Natura 2000')
                    ->boolean()
                    ->hidden(self::isNonApprovedTab(...)),
                ViewColumn::make('admin_links')
                    ->label('Links')
                    ->view('filament.reference-links'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'deleted' => 'Deleted',
                        'proposed' => 'Proposed',
                    ]),
                SelectFilter::make('approval_status')
                    ->label('Approval status')
                    ->options([
                        'received' => 'Received',
                        'declined' => 'Declined',
                        'approved' => 'Approved',
                        'saved' => 'Saved',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    MarkReferenceSaved::single(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                MarkReferenceSaved::bulk(),
            ]);
    }

    private static function isNonApprovedTab(mixed $livewire): bool
    {
        return $livewire instanceof ListReferences && $livewire->activeTab === ListReferences::NON_APPROVED_TAB;
    }
}
