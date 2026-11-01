<?php

namespace App\Filament\Resources\References;

use App\Filament\Resources\References\Pages\EditReference;
use App\Filament\Resources\References\Pages\ListReferences;
use App\Filament\Resources\References\Pages\ViewReference;
use App\Filament\Resources\References\Schemas\ReferenceForm;
use App\Filament\Resources\References\Schemas\ReferenceInfolist;
use App\Filament\Resources\References\Tables\ReferencesTable;
use App\Models\Reference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReferenceResource extends Resource
{
    protected static ?string $model = Reference::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'reference';

    /**
     * References only come from the WWFF import and the public suggestion form.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReferenceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReferenceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferencesTable::configure($table);
    }

    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'reference', 'name'];
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Reference $record */
        return ['Name' => (string) $record->name];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferences::route('/'),
            'view' => ViewReference::route('/{record}'),
            'edit' => EditReference::route('/{record}/edit'),
        ];
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'deleted' => 'danger',
            'proposed' => 'info',
            default => 'gray',
        };
    }

    public static function approvalStatusColor(?string $status): string
    {
        return match ($status) {
            'received' => 'info',
            'declined' => 'danger',
            'approved' => 'success',
            'saved' => 'warning',
            default => 'gray',
        };
    }

    public static function formatCoordinate(?float $coordinate): ?string
    {
        return is_null($coordinate) ? null : number_format($coordinate, 5);
    }
}
