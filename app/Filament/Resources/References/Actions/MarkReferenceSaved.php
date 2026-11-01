<?php

namespace App\Filament\Resources\References\Actions;

use App\Models\Reference;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;

class MarkReferenceSaved
{
    private const LABEL = 'Mark reference saved';

    public static function single(): Action
    {
        return Action::make('markReferenceSaved')
            ->label(self::LABEL)
            ->icon('heroicon-o-check')
            ->requiresConfirmation()
            ->action(fn (Reference $record) => self::mark($record))
            ->successNotificationTitle('Reference marked as saved');
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('markReferencesSaved')
            ->label(self::LABEL)
            ->icon('heroicon-o-check')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => $records->each(fn (Reference $record) => self::mark($record)))
            ->successNotificationTitle('References marked as saved');
    }

    private static function mark(Reference $reference): void
    {
        $reference->approval_status = 'saved';
        $reference->save();
    }
}
