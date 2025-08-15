<?php

namespace App\Filament\Lecturer\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Assessment;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Lecturer\Resources\SupervisorResource\Pages;
use App\Filament\Lecturer\Resources\SupervisorResource\RelationManagers;
use App\Models\AssessmentTemplate;

class SupervisorResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Assessment Management';

    protected static ?string $navigationLabel = 'Supervisor Lecturer';

    protected static ?string $label = 'Assessment';

    protected static ?int $navigationSort = -1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('student');

        if (auth()->user()->hasRole('super_admin')) {
            return $query;
        }

        $role = static::$navigationLabel === 'Supervisor Lecturer' ? 'supervisor' : 'examiner';

        return $query
            ->where('lecturer_id', auth()->id())
            ->where('type', $role);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(function () {
                    $locale = app()->getLocale();

                    if ($locale == 'id') {
                        return 'Informasi Mahasiswa';
                    }
                    return 'Student Information';
                })
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('group_id')
                            ->label(function () {
                                return app()->getLocale() == 'id' ? 'Kelompok' : 'Group';
                            })
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\Select::make('student_id')
                            ->label(function () {
                                return app()->getLocale() == 'id' ? 'Mahasiswa' : 'Student';
                            })
                            ->options(function (Forms\Get $get) {
                                $groupId = $get('group_id');
                                if (!$groupId) return [];

                                return \App\Models\Student::query()
                                    ->where('group_id', $groupId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                if ($student = \App\Models\Student::find($state)) {
                                    $set('student.nim', $student->nim);
                                    $set('student.title_of_the_final_project_proposal', $student->title_of_the_final_project_proposal);
                                    $set('student.design_theme', $student->design_theme);
                                }
                            })
                            ->loadStateFromRelationshipsUsing(function (Forms\Get $get, Forms\Set $set, $state) {
                                if ($student = \App\Models\Student::find($state)) {
                                    $set('student.nim', $student->nim);
                                    $set('student.title_of_the_final_project_proposal', $student->title_of_the_final_project_proposal);
                                    $set('student.design_theme', $student->design_theme);
                                }
                            }),
                        Forms\Components\TextInput::make('student.nim')
                            ->label('NIM')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('student.title_of_the_final_project_proposal')
                            ->label('Title of the Final Project Proposal')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('student.design_theme')
                            ->label('Design Theme')
                            ->required()
                            ->disabled(),
                        Forms\Components\Hidden::make('lecturer_id')
                            ->default(auth()->user()->getKey()),
                    ]),
                Forms\Components\Section::make(function () {
                    $locale = app()->getLocale();

                    if ($locale == 'id') {
                        return 'Informasi Penilaian';
                    }
                    return 'Assessment Information';
                })
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('assessment_stage')
                            ->label(function () {
                                $locale = app()->getLocale();
                    return $locale == 'id' ? 'Tahap Penilaian' : 'Assessment Stage';
                            })
                            ->columnSpanFull()
                    ->options(function () {
                        $stages = AssessmentTemplate::pluck('stage', 'stage')->toArray();
                        if (empty($stages)) {
                            return [
                                'Penilaian Tahap 1' => 'Stage 1 Assessment (Penilaian Tahap 1)',
                                'Penilaian Tahap 2' => 'Stage 2 Assessment (Penilaian Tahap 2)',
                                'Penilaian Tahap 3' => 'Stage 3 Assessment (Penilaian Tahap 3)',
                                'Penilaian Tahap 4' => 'Stage 4 Assessment (Penilaian Tahap 4)',
                        ];
                    }
                    return $stages;
                })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state, $record) {
                    if (empty($state)) {
                        $set('assessment', []);
                        return;
                    }
                    $assessmentData = self::getAssessmentData($state);
                                // If record exists, try to load saved assessment data from database first
                                if ($record) {
                                    $existingAssessment = Assessment::where('student_id', $get('student_id'))
                                        ->where('assessment_stage', $state)
                            ->first();
                        if ($existingAssessment && !empty($existingAssessment->assessment)) {
                                        $orderedAssessment = self::maintainAssessmentOrder(
                                            $existingAssessment->assessment,
                                            $assessmentData
                                        );
                                        $set('assessment', $orderedAssessment);
                                        return;
                                    }
                    }
                                $set('assessment', $assessmentData);
                            }),
                        Forms\Components\KeyValue::make('assessment')
                            ->label(function () {
                                $locale = app()->getLocale();
                                return $locale == 'id' ? 'Penilaian' : 'Assessment';
                            })
                            ->required()
                            ->disableDeletingRows()
                            ->disableAddingRows()
                            ->disableEditingKeys()
                            ->disabledOn('edit')
                            ->helperText(function () {
                                $locale = app()->getLocale();
                                if ($locale == 'id') {
                                    return 'Label Nilai adalah untuk label nilai (misalnya, Nilai 1), dan Nilai adalah untuk nilai itu sendiri (misalnya, 100).';
                                }
                                return 'Score Label is for the label of the score (e.g., Score 1), and Value is for the score itself (e.g., 100).';
                            })
                            ->keyLabel(function () {
                                $locale = app()->getLocale();

                                if ($locale == 'id') {
                                    return 'Label Nilai';
                                }

                                return 'Score Label';
                            })
                            ->valueLabel(function () {
                                $locale = app()->getLocale();

                                if ($locale == 'id') {
                                    return 'Nilai';
                                }

                                return 'Score';
                            })
                            ->columnSpanFull()
                            ->addActionLabel(function () {
                                $locale = app()->getLocale();

                                if ($locale == 'id') {
                                    return 'Tambah Nilai';
                                }

                                return 'Add Score';
                            })
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('type')
                            ->default('supervisor'),
                    ]),
            ]);
    }

    public static function getAssessmentData(string $stage): array
    {
        if (empty($stage)) {
            return [];
        }
        $template = AssessmentTemplate::where('stage', $stage)->first();
        if ($template && is_array($template->labels)) {
            return $template->labels;
        }
        switch ($stage) {
            case 'Penilaian Tahap 1':
                return [
                    'ZONING' => 0,
                    'TATA MASA/BLOK PLAN' => 0,
                    'INFRASTRUKTUR TAPAK' => 0,
                    'LANDSCAPE/RUANG LUAR' => 0,
                    'ASPEK STANDAR/TEKNIS/PERATURAN' => 0,
                    'TEMA RANCANGAN' => 0,
                    'KUALITAS DAN KELENGKAPAN' => 0,
                    'TEKNIK PRESENTASI DAN KOMUNIKASI' => 0,
                ];
            case 'Penilaian Tahap 2':
                return [
                    'ZONING LANTAI' => 0,
                    'SIRKULASI' => 0,
                    'BENTUK, RUANG, STRUKTUR, UTILITAS' => 0,
                    'MATERIAL' => 0,
                    'ASPEK STANDAR/TEKNIS/PERATURAN' => 0,
                    'TEMA RANCANGAN' => 0,
                    'KUALITAS DAN KELENGKAPAN' => 0,
                    'TEKNIK PRESENTASI DAN KOMUNIKASI' => 0,
                ];
            case 'Penilaian Tahap 3':
                return [
                    'KRITERIA 1' => 0,
                    'KRITERIA 2' => 0,
                    'KRITERIA 3' => 0,
                    'KRITERIA 4' => 0,
                ];
            case 'Penilaian Tahap 4':
                return [
                    'KRITERIA 1' => 0,
                    'KRITERIA 2' => 0,
                    'KRITERIA 3' => 0,
                    'KRITERIA 4' => 0,
                ];
            default:
                return [
                    'ZONING' => 0,
                    'TATA MASA/BLOK PLAN' => 0,
                    'INFRASTRUKTUR TAPAK' => 0,
                    'LANDSCAPE/RUANG LUAR' => 0,
                ];
        }
    }

    private static function maintainAssessmentOrder(array $existingData, array $template): array
    {
        $result = [];
        // First add keys from template in their original order
        foreach ($template as $key => $defaultValue) {
            $result[$key] = array_key_exists($key, $existingData) ? $existingData[$key] : $defaultValue;
        }

        // Then add any extra keys from existing data that weren't in the template
        foreach ($existingData as $key => $value) {
            if (!array_key_exists($key, $result)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Lecturer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.nim')
                    ->label('NIM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.title_of_the_final_project_proposal')
                    ->label('Title of the Final Project Proposal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.design_theme')
                    ->label('Design Theme')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assessment_stage')
                    ->label('Assessment Stage')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assessment')
                    ->label('Assessment')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Lecturer')
                    ->hidden()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                //
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
            'index' => Pages\ListSupervisors::route('/'),
            'create' => Pages\CreateSupervisor::route('/create'),
            'edit' => Pages\EditSupervisor::route('/{record}/edit'),
        ];
    }
}
