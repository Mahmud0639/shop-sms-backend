<?php

namespace App\Filament\Resources\SmsScheduleResource\Pages;

use App\Filament\Resources\SmsScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSmsSchedules extends ListRecords
{
    protected static string $resource = SmsScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
