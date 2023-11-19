<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferenceResource\Pages;
use App\Models\Reference;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferenceResource extends Resource
{
    protected static ?string $model = Reference::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference'),
                TextColumn::make('name'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'national' => 'success',
                        'deleted' => 'danger',
                        'proposed' => 'warning',
                    }),
                TextColumn::make('first_activation_date')->date('j.n.Y'),
                TextColumn::make('latest_activation_date')->date('j.n.Y'),
                TextColumn::make('wdpa_id')->label('Protected Planet')->url(fn ($record) => 'https://www.protectedplanet.net/'.$record->wdpa_id, true),
                IconColumn::make('natura_2000_area')->boolean()->label('Natura 2000 area'),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->options([
                        'received' => 'Received',
                        'declined' => 'Declined',
                        'approved' => 'Approved',
                        'saved' => 'Saved',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //Tables\Actions\BulkActionGroup::make([
                //   Tables\Actions\DeleteBulkAction::make(),
                //]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferences::route('/'),
            //'create' => Pages\CreateReference::route('/create'),
            'edit' => Pages\EditReference::route('/{record}/edit'),
        ];
    }
}
