<?php

namespace App\Filament\Resources\References\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled(),
                TextInput::make('reference')->disabled(),
                TextInput::make('name')->disabled(),
                Select::make('approval_status')
                    ->label('Approval status')
                    ->options([
                        'received' => 'Received',
                        'declined' => 'Declined',
                        'approved' => 'Approved',
                        'saved' => 'Saved',
                    ])
                    ->selectablePlaceholder(false),
            ]);
    }
}
