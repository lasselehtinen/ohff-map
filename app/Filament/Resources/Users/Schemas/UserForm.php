<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('callsign'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                self::email()
                    ->unique(ignoreRecord: true),
                self::password()
                    ->required(fn (string $operation) => $operation === 'create'),
                Toggle::make('is_admin')
                    ->label('Admin panel access')
                    // Keeps administrators from locking themselves out.
                    ->disabled(fn (?User $record) => $record?->is(Auth::user()) ?? false),
            ]);
    }

    public static function email(): TextInput
    {
        return TextInput::make('email')
            ->email()
            ->required()
            ->maxLength(254);
    }

    public static function password(): TextInput
    {
        return TextInput::make('password')
            ->password()
            ->rule(Password::defaults())
            ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
            ->dehydrated(fn (?string $state) => filled($state));
    }
}
