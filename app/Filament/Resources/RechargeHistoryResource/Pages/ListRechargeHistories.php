<?php

namespace App\Filament\Resources\RechargeHistoryResource\Pages;

use App\Filament\Resources\RechargeHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRechargeHistories extends ListRecords
{
    protected static string $resource = RechargeHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
