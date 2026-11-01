<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('callsign')->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('email')->sortable()->searchable(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::replicateAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Copy a user. The password is asked again and panel access is never copied.
     */
    public static function replicateAction(): ReplicateAction
    {
        return ReplicateAction::make()
            ->excludeAttributes(['password', 'remember_token', 'email_verified_at', 'is_admin'])
            ->schema([
                TextInput::make('callsign'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                UserForm::email()
                    ->rule(Rule::unique(User::class, 'email')),
                UserForm::password()
                    ->required(),
            ]);
    }
}
