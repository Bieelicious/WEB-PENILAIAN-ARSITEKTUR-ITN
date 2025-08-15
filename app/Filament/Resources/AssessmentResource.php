<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Assessment;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Jobs\ExportExcelJob;
use App\Jobs\ExportPdfJob;
use Filament\Facades\Filament;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Exports\AssessmentExporter;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AssessmentResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use App\Filament\Resources\AssessmentResource\RelationManagers;
use App\Models\AssessmentTemplate;

class AssessmentResource extends Resource
{
    protected static ?string $tenantOwnershipRelationshipName = 'room';

    protected static ?string $model = Assessment::class;

    protected static ?string $navigationGroup = 'Data Management';

    protected static ?int $navigationSort = -4;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('student', 'student.group');

        if (Auth::check()) {
            $user = Auth::user();
            // Check if user is super_admin using the roles relationship
            if ($user instanceof User && $user->getRoleNames()->contains('super_admin')) {
                return $query;
            }
        }

        return $query
            ->where('lecturer_id', Auth::id());
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
                            ->default(Auth::id()),
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
                        $stages = \App\Models\AssessmentTemplate::pluck('stage', 'stage')->toArray();
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
                                        // Use maintainAssessmentOrder to preserve the correct order of keys
                                        $orderedAssessment = self::maintainAssessmentOrder(
                                            $existingAssessment->assessment,
                                            $assessmentData
                                        );
                                        $set('assessment', $orderedAssessment);
                                        return;
                                    }
                    }
                                // Otherwise use default assessment data
                                $set('assessment', $assessmentData);
                            }),
                        Forms\Components\KeyValue::make('assessment')
                            ->label(function () {
                                $locale = app()->getLocale();
                                return $locale == 'id' ? 'Penilaian' : 'Assessment';
                            })
                            ->required()
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
                    ->columnSpanFull()
                    ->addable(false),
                    ]),
            ]);
    }

    /**
     * Get assessment data based on assessment stage
     *
     * @param string $stage
     * @return array
     */
    public static function getAssessmentData(string $stage): array
    {
        if (empty($stage)) {
            return [];
        }
        // Cek di tabel assessment_templates
        $template = AssessmentTemplate::where('stage', $stage)->first();
        if ($template && is_array($template->labels)) {
            // labels: [label => default_value]
            return $template->labels;
        }
        // fallback lama
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

    /**
     * Maintain assessment order when loading from database
     *
     * @param array $existingData
     * @param array $template
     * @return array
     */
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
            ->defaultGroup('student.name', 'desc')
            ->columns([
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
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return array_sum($state);
                        }
                        return $state;
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Lecturer')
                    ->hidden(function() {
                        if (!Auth::check()) {
                            return true;
                        }
                        $user = Auth::user();
                        if (!($user instanceof User)) {
                            return true;
                        }
                        return !$user->getRoleNames()->contains('super_admin');
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-up')
                    ->modal()
                    ->modalWidth('sm')
                    ->form([
                        Forms\Components\Fieldset::make('Export Options')
                            ->columns(1)
                            ->schema([
                                Forms\Components\Select::make('student_id')
                                    ->label('Select Student')
                                    ->preload()
                                    ->searchable()
                                    ->placeholder('Select Student')
                                    ->helperText('Export specific students')
                                    ->relationship('student', 'name')
                                    ->live()
                                    ->disabled(fn($get) => $get('export_all'))
                                    ->required(fn($get) => !$get('export_all')),
                                Forms\Components\Checkbox::make('export_all')
                                    ->label('Export All Students')
                                    ->live()
                                    ->helperText('Export all students in the table')
                                    ->default(false),
                            ])
                    ])
                    ->action(function ($data) {
                        $user = Auth::check() ? Auth::user() : null;
                        $studentIds = $data['export_all'] ? [] : [$data['student_id']];

                        try {
                            Bus::chain([
                                new ExportExcelJob($studentIds, $user),
                            ])->dispatch();
                            Notification::make()
                                ->title('Export Process in Progress')
                                ->body('The Excel is being processed. You will receive a notification when it is finished.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            throw $e;
                            Notification::make()
                                ->title('Export Process Failed')
                                ->body('Error ' . $e)
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('danger')
                    ->modal()
                    ->modalWidth('sm')
                    ->form([
                        Forms\Components\Fieldset::make('Export Options')
                            ->columns(1)
                            ->schema([
                                Forms\Components\Select::make('student_id')
                                    ->label('Select Student')
                                    ->preload()
                                    ->searchable()
                                    ->placeholder('Select Student')
                                    ->relationship('student', 'name')
                                    ->helperText('Export specific students')
                                    ->live()
                                    ->disabled(fn($get) => $get('export_all'))
                                    ->required(fn($get) => !$get('export_all')),
                                Forms\Components\Checkbox::make('export_all')
                                    ->label('Export All Students')
                                    ->live()
                                    ->helperText('Export all students in the table')
                                    ->default(false),
                            ])
                    ])
                    ->action(function ($data) {
                        $user = Auth::check() ? Auth::user() : null;
                        $studentId = $data['export_all'] ? null : [$data['student_id']];

                        try {
                            Bus::chain([
                                new ExportPdfJob($studentId, $user),
                            ])->dispatch();

                            Notification::make()
                                ->title('Export Process in Progress')
                                ->body('The PDF is being processed. You will receive a notification when it is finished.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('PDF job error', ['error' => $e->getMessage()]);
                            throw $e;
                            Notification::make()
                                ->title('Export Process Failed')
                                ->body('Error ' . $e)
                                ->success()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListAssessments::route('/'),
            'create' => Pages\CreateAssessment::route('/create'),
            'edit' => Pages\EditAssessment::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        $locale = app()->getLocale();

        if ($locale == 'id') {
            return 'Penilaian';
        }

        return 'Assesments';
    }
}
