<?php

namespace App\Filament\Resources\RechargeHistoryResource\Pages;

use App\Filament\Resources\RechargeHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRechargeHistory extends EditRecord
{
    protected static string $resource = RechargeHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
