<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsScheduleResource\Pages;
use App\Models\SmsSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SmsScheduleResource extends Resource
{
    protected static ?string $model = SmsSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'SMS Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('shop_id')
                    ->relationship('shop', 'shop_name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Textarea::make('message_body')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('scheduled_date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'sent' => 'Sent',
                        'failed' => 'Failed',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.shop_name')
                    ->label('Shop'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('message_body')
                    ->limit(30),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'sent',
                        'danger' => 'failed',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsSchedules::route('/'),
            'create' => Pages\CreateSmsSchedule::route('/create'),
            'edit' => Pages\EditSmsSchedule::route('/{record}/edit'),
        ];
    }
}