<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Assessment;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ExportExcelJob;
use App\Jobs\ExportPdfJob;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AssessmentResource\Pages;
use App\Models\AssessmentTemplate;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static ?string $navigationGroup = 'Data Management';

    protected static ?int $navigationSort = -4;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('student', 'student.group');

        if (Auth::check()) {
            $user = Auth::user();
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
                Forms\Components\Section::make(fn() => app()->getLocale() == 'id' ? 'Informasi Mahasiswa & Dosen' : 'Student & Lecturer Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('lecturer_id')
                            ->label('Lecturer')
                            ->relationship('lecturer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('group_id')
                            ->label(fn() => app()->getLocale() == 'id' ? 'Kelompok' : 'Group')
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('student_id')
                            ->label('Student')
                            ->relationship('student', 'name') // langsung pakai relasi di model Assessment
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('nim')
                            ->label('NIM')
                            ->readOnly()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($set, $state, $record) {
                                if ($record && $record->student) {
                                    $set('nim', $record->student->nim);
                                }
                            }),

                        Forms\Components\TextInput::make('title_of_the_final_project_proposal')
                            ->label('Title of the Final Project Proposal')
                            ->readOnly()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($set, $state, $record) {
                                if ($record && $record->student) {
                                    $set('title_of_the_final_project_proposal', $record->student->title_of_the_final_project_proposal);
                                }
                            }),

                        Forms\Components\TextInput::make('design_theme')
                            ->label('Design Theme')
                            ->readOnly()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($set, $state, $record) {
                                if ($record && $record->student) {
                                    $set('design_theme', $record->student->design_theme);
                                }
                            }),

                    ]),
                Forms\Components\Section::make(fn() => app()->getLocale() == 'id' ? 'Informasi Penilaian' : 'Assessment Information')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Assessment Type')
                            ->options([
                                'supervisor' => 'Supervisor',
                                'examiner' => 'Examiner',
                            ])
                            ->disabled(),
                        Forms\Components\Select::make('assessment_stage')
                            ->label(fn() => app()->getLocale() == 'id' ? 'Tahap Penilaian' : 'Assessment Stage')
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
                            ->disabled()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state, ?Assessment $record) {
                                if (empty($state)) {
                                    $set('assessment', []);
                                    return;
                                }
                                $templateData = self::getAssessmentData($state);
                                $set('assessment', $templateData);
                            }),
                        Forms\Components\Repeater::make('assessment')
                            ->label(fn() => app()->getLocale() == 'id' ? 'Penilaian' : 'Assessment')
                            ->relationship('items')
                            ->columnSpanFull()
                            ->addable(false)
                            ->deletable(false)
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('label')->label(fn() => app()->getLocale() == 'id' ? 'Kriteria' : 'Assessment Label')->required()->readOnly(),
                                Forms\Components\Textarea::make('criteria')->label(fn() => app()->getLocale() == 'id' ? 'Indikator' : 'Criteria')->rows(2)->columnSpan(2)->readOnly(),
                                Forms\Components\TextInput::make('score')->label(fn() => app()->getLocale() == 'id' ? 'Nilai' : 'Score')->numeric()->default(0)->required(),
                                Forms\Components\Textarea::make('description')->label(fn() => app()->getLocale() == 'id' ? 'Catatan' : 'Assessment Note')->default('')->rows(2)->columnSpan(2),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->label(fn() => app()->getLocale() == 'id' ? 'Catatan Keseluruhan' : 'Overall Notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }

    public static function getAssessmentData(string $stage): array
    {
        if (empty($stage)) {
            return [];
        }

        $template = AssessmentTemplate::where('stage', $stage)->first();
        $labels = [];

        if ($template && is_array($template->labels)) {
            $labels = $template->labels;
        } else {
            // Fallback data
            switch ($stage) {
                case 'Penilaian Tahap 1':
                    $labels = [
                        'ZONING' => ['criteria' => 'Tata letak guna lahan (makro/mezzo) yang tepat sesuai dengan konteks objek rancangan dan hasil analisis tapak', 'score' => 0],
                        'TATA MASA/BLOK PLAN' => ['criteria' => 'Penyelesaian tata massa/ blok plan/ bentuk massa bangunan berdasarkan analisis bentuk dengan pertimbangan tema, tapak,lingkungan dan aspek lainnya', 'score' => 0],
                        'INFRASTRUKTUR TAPAK' => ['criteria' => 'Sirkulasi dalam tapak yang efektif, letak dan pola distribusi infrastruktur tapak (drainaise, pemadaman, air kotor/ bersih, sampah, listrik dan jaringan) ke tiap massa bangunan', 'score' => 0],
                        'LANDSCAPE/RUANG LUAR' => ['criteria' => 'Tata ruang luar, pemilihan material hardscape dan softscape yang sesuai dengan tema atau konteks objek rancangan', 'score' => 0],
                        'ASPEK STANDAR/TEKNIS/PERATURAN' => ['criteria' => 'Penerapan aspek teknis/ peraturan/ standar yang sesuai dengan objek rancangan misalkan (sempadan, KDB, lebar sirkulasi kendaraan, kebutuhan parkir, dimensi parkir, dll)', 'score' => 0],
                        'TEMA RANCANGAN' => ['criteria' => 'Penerapan tema pada rancangan tapak', 'score' => 0],
                        'KUALITAS DAN KELENGKAPAN' => ['criteria' => 'Kualitas estetika visual gambar dan kelengkapan produk yang dipaparkan', 'score' => 0],
                        'TEKNIK PRESENTASI DAN KOMUNIKASI' => ['criteria' => 'Kemampuan mahasiswa untuk menyajikan materi presentasi yang menarik dan mampu menyampaikan subtansi materi paparan, dan menjawab pertanyaan dengan baik', 'score' => 0],
                    ];
                    break;
                case 'Penilaian Tahap 2':
                    $labels = [
                        'HASIL REVISI' => ['criteria' => 'Respon/ tindak lanjut dan hasil perbaikan dari catatan sidang review/ masukan dari pembimbing/ penguji', 'score' => 0],
                        'ZONING LANTAI' => ['criteria' => 'Tata letak ruang (vertical dan horizontal) yang sesuai dengan kelompok sifat dan fungsi ruang', 'score' => 0],
                        'SIRKULASI' => ['criteria' => 'Ketepatan pemilihan pola/ jenis sirkulasi untuk keselamatan dan kemudahan pola/alur sirkulasi vertikal dan horizontal', 'score' => 0],
                        'BENTUK, RUANG, STRUKTUR, UTILITAS' => ['criteria' => 'Kemampuan mahasiswa dalam menyelesaiakan dan menghubungkan antara bentuk, ruang, struktur dan utilitas', 'score' => 0],
                        'MATERIAL' => ['criteria' => 'Ketepatan pemilihan material/ penerapan material yang sesuai dengan tema pada elemen arsitektur', 'score' => 0],
                        'ASPEK STANDAR/TEKNIS/PERATURAN' => ['criteria' => 'Penerapan aspek teknis/ peraturan/ standar yang sesuai dengan objek rancangan misalkan (KLB, ketinggian lantai ruangan berdasarkan fungsi, jumlah ruang, spesifikasi ruang, kemiringan ramp, dll)', 'score' => 0],
                        'TEMA RANCANGAN' => ['criteria' => 'Penerapan tema pada rancangan bangunan', 'score' => 0],
                        'KUALITAS DAN KELENGKAPAN' => ['criteria' => 'Kualitas estetika visual gambar dan kelengkapan produk yang dipaparkan', 'score' => 0],
                        'TEKNIK PRESENTASI DAN KOMUNIKASI' => ['criteria' => 'Kemampuan mahasiswa untuk menyajikan materi presentasi yang menarik dan mampu menyampaikan subtansi materi paparan dan menjawab pertanyaan dengan baik', 'score' => 0],
                    ];
                    break;
                case 'Penilaian Tahap 3':
                    $labels = [
                        'HASIL REVISI' => ['criteria' => 'Respon/ tindak lanjut dan hasil perbaikan dari catatan sidang review/ masukan dari pembimbing/ penguji', 'score' => 0],
                        'KELENGKAPAN GAMBAR' => ['criteria' => 'Minimal memiliki gambar site plan, layout plan, denah, tampak, potongan (site dan bangunan), detail arsitektural, rencana struktur dan utilitas, serta rendering 3D interior dan eksterior', 'score' => 0],
                        'KUALITAS GAMBAR' => ['criteria' => 'Memenuhi standar gambar arsitektural (keterangan, dimensi, notasi, proporsi/skala gambar, dll.), gambar terbaca dan memberikan informasi yang jelas', 'score' => 0],
                        'HASIL RANCANGAN' => ['criteria' => 'Gambar rancangan yang dihasilkan telah sesuai/ sinkron antara gambar satu dengan yang lainnya, kesesuaian dengan proses tahapan sebelumnya (skematik tapak dan bangunan), dan memenuhi kaidah, standar teknis, pedoman perancangan arsitektur, serta penerapan tema rancangan', 'score' => 0],
                        'TEKNIK PRESENTASI DAN KOMUNIKASI' => ['criteria' => 'Kemampuan mahasiswa untuk menyajikan produk presentasi yang menarik, mampu menyampaikan subtansi materi paparan dan menjawab pertanyaan dengan baik, serta menunjukkan sikap perilaku yang baik', 'score' => 0],
                    ];
                    break;
                case 'Penilaian Tahap 4':
                    $labels = [
                        'HASIL REVISI' => ['criteria' => 'Respon/ tindak lanjut dan hasil perbaikan dari catatan sidang review/ masukan dari pembimbing/ penguji', 'score' => 0],
                        'PROSES DAN HASIL RANCANGAN TAPAK DAN BANGUNAN' => ['criteria' => 'Proses dan kesesuaian hasil rancangan tapak dan bangunan dari tiap tahap 1, 2, 3, dan mampu menjawab rumusan permasalahan, tema, dan program ruang yang telah dibuat pada konsep skripsi', 'score' => 0],
                        'KELENGKAPAN DAN KUALITAS GAMBAR RANCANGAN' => ['criteria' => 'Kelengkapan, kualitas dan teknik presentasi gambar rancangan yang baik, meliputi minimal: blok plan, site plan, layout plan, denah, tampak, potongan (site dan bangunan), detail arsitektural, rencana struktur dan utilitas', 'score' => 0],
                        'PROSES BIMBINGAN' => ['criteria' => 'Kemampuan mahasiswa untuk berdiskusi, menjelaskan hasil rancangan saat bimbingan, dan menjawab pertanyaan dengan baik serta kemampuan memahami saran dari pembimbing', 'score' => 0],
                        'SIKAP DAN ETIKA' => ['criteria' => 'Mampu menunjukkan sikap dan etika yang professional ', 'score' => 0],
                        'POSTER RANCANGAN' => ['criteria' => 'Poster berisi penjelasan konsep, proses dan hasil rancangan yang komunikatif serta tata atur layout yang baik', 'score' => 0],
                        'ANIMASI' => ['criteria' => 'Menunjukkan penjelasan proses desain mulai dari latar belakang issue, lokasi rancangan, objek rancangan, tema, konsep, proses skematik (seperti menampilkan proses transformasi bentuk), dan hasil rancangan serta animasi suasana ruang luar dan ruang dalam. Kreatif dan komunikatif (bukan hanya sekedar animasi 3d rendering saja) serta sesuai dengan gambar rancangan', 'score' => 0],
                        'MAKET' => ['criteria' => 'Sesuai dengan hasil rancangan, skalatis dan proporsional, rapi, kedetailan dan penggunaan material maket yang tepat', 'score' => 0],
                    ];
                    break;
                default:
                    $labels = [];
                    break;
            }
        }

        return collect($labels)->map(function ($value, $key) {
            return [
                'label' => $key,
                'criteria' => $value['criteria'] ?? $key,
                'score' => $value['score'] ?? 0,
                'description' => '',
            ];
        })->values()->toArray();
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
                    ->label('Total Score')
                    ->formatStateUsing(fn($state) => is_array($state) ? array_sum(array_map(fn($item) => (int) ($item['score'] ?? 0), $state)) : 0)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lecturer.name')
                    ->label('Lecturer')
                    ->hidden(fn() => !(Auth::user() instanceof User && Auth::user()->getRoleNames()->contains('super_admin')))
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
                                    ->relationship('student', 'name')
                                    ->live()
                                    ->disabled(fn($get) => $get('export_all'))
                                    ->required(fn($get) => !$get('export_all')),
                                Forms\Components\Checkbox::make('export_all')
                                    ->label('Export All Students')
                                    ->live()
                                    ->default(false),
                            ])
                    ])
                    ->action(function ($data) {
                        $studentIds = $data['export_all'] ? [] : [$data['student_id']];
                        $user = Auth::user();

                        // Mengirim Job ke antrian (queue)
                        ExportExcelJob::dispatch($studentIds, $user);

                        // Memberi notifikasi instan kepada pengguna
                        Notification::make()
                            ->title('Export Started')
                            ->body('Generating your Excel report. You will be notified when it is ready to download.')
                            ->success()
                            ->send();
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
                                    ->default(false),
                            ])
                    ])
                    ->action(function ($data) {
                        $studentIds = $data['export_all'] ? [] : [$data['student_id']];
                        $user = Auth::user();

                        // Mengirim Job ke antrian (queue)
                        ExportPdfJob::dispatch($studentIds, $user);

                        // Memberi notifikasi instan kepada pengguna
                        Notification::make()
                            ->title('Export Started')
                            ->body('Generating your PDF report. You will be notified when it is ready to download.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        $user = Auth::user();
        return $user instanceof User && $user->getRoleNames()->contains('super_admin');
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
            'view' => Pages\EditAssessment::route('/{record}'),
        ];
    }

    public static function getLabel(): ?string
    {
        $locale = app()->getLocale();
        return $locale == 'id' ? 'Penilaian' : 'Assessments';
    }
}
