<?php

namespace App\Filament\Resources\SmsScheduleResource\Pages;

use App\Filament\Resources\SmsScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmsSchedule extends EditRecord
{
    protected static string $resource = SmsScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
