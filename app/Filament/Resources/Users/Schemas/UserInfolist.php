<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('callsign'),
                TextEntry::make('name'),
                TextEntry::make('email'),
                IconEntry::make('is_admin')
                    ->label('Admin panel access')
                    ->boolean(),
            ]);
    }
}
