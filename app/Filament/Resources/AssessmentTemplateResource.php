<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentTemplateResource\Pages;
use App\Filament\Resources\AssessmentTemplateResource\RelationManagers;
use App\Models\AssessmentTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssessmentTemplateResource extends Resource
{
    protected static ?string $model = AssessmentTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Data Management';

    protected static ?int $navigationSort = -2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('stage')
                    ->label('Assessment Stage')
                    ->required(),
                Forms\Components\KeyValue::make('labels')
                    ->label('Score Labels')
                    ->keyLabel('Label')
                    ->valueLabel('Default Value')
                    ->addActionLabel('Add Label')
                    ->required()
                    ->helperText('Masukkan label penilaian dan nilai default (biasanya 0).'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->label('Assessment Stage')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('labels')
                    ->label('Score Labels')
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', array_keys($state)) : $state),
            ])
            ->filters([
                //
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
            'index' => Pages\ListAssessmentTemplates::route('/'),
            'create' => Pages\CreateAssessmentTemplate::route('/create'),
            'edit' => Pages\EditAssessmentTemplate::route('/{record}/edit'),
        ];
    }
}
