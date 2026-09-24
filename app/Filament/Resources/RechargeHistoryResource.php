<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RechargeHistoryResource\Pages;
use App\Models\RechargeHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RechargeHistoryResource extends Resource
{
    protected static ?string $model = RechargeHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Financial Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recharge Details')
                    ->schema([
                        Forms\Components\Select::make('shop_id')
                            ->relationship('shop', 'shop_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('package_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sms_amount')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('৳')
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'rocket' => 'Rocket',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('transaction_id')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'success' => 'Success',
                                'failed' => 'Failed',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shop.shop_name')
                    ->label('Shop Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package_name')
                    ->label('Package'),
                Tables\Columns\TextColumn::make('sms_amount')
                    ->label('SMS Count')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('BDT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('TrxID')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRechargeHistories::route('/'),
            'create' => Pages\CreateRechargeHistory::route('/create'),
            'edit' => Pages\EditRechargeHistory::route('/{record}/edit'),
        ];
    }
}
