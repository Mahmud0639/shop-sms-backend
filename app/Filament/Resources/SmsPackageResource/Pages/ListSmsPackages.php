<?php

namespace App\Filament\Resources\SmsPackageResource\Pages;

use App\Filament\Resources\SmsPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSmsPackages extends ListRecords
{
    protected static string $resource = SmsPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
