<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Assessment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendExportNotificationJob;
use Filament\Notifications\Notification;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use OpenSpout\Common\Entity\Style\CellAlignment;
use App\Traits\EnsureExportDirectory;

class ExportExcelJob implements ShouldQueue
{
    use Queueable, EnsureExportDirectory;

    protected array $studentIds;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(array $studentIds, $user)
    {
        $this->studentIds = $studentIds;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Excel export');

        try {
            $records = $this->fetchRecords();

            if ($records->isEmpty()) {
                $this->handleNoRecordsFound();
                return;
            }

            $this->ensureExportsFolderExists();

            $filename = $this->generateFilename();
            Log::info('Saving Excel file', ['filename' => $filename]);

            $header = $this->getHeader();
            $rows = $this->prepareRows($records);

            $this->createExcelFileWithOpenSpout(storage_path("app/public/{$filename}"), $header, $rows);

            Log::info('Excel file generated successfully');

            $this->dispatchNotificationJob($filename);
        } catch (\Exception $e) {
            Log::error('Excel export failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function fetchRecords(): Collection
    {
        if (empty($this->studentIds)) {
            return Assessment::with(['student'])->get();
        }

        return Assessment::with(['student'])
            ->whereIn('student_id', $this->studentIds)
            ->get();
    }

    private function handleNoRecordsFound(): void
    {
        $studentIds = empty($this->studentIds) ? 'ALL_STUDENTS' : implode(', ', $this->studentIds);

        Log::info('No records found for selected student IDs: ' . implode(', ', $this->studentIds));
        $this->user->notify(
            Notification::make()
                ->title('No Data Available')
                ->body('There is no data available to export for the selected students at this time.')
                ->icon('heroicon-o-x-circle')
                ->danger()
                ->toDatabase()
        );
    }

    private function generateFilename(): string
    {
        return 'exports/students_assessments-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
    }

    private function getHeader(): array
    {
        return ['Nama', 'NIM', 'Judul Proposal Tugas Akhir', 'Tema Rancangan', 'Kelompok', 'Tahap Penilaian', 'Dosen Penilai', 'Nilai', 'Catatan'];
    }

    private function prepareRows(Collection $records): Collection
    {
        return $records->map(function ($assessment) {
            return [
                optional($assessment->student)->name ?? '-',
                optional($assessment->student)->nim ?? '-',
                optional($assessment->student)->title_of_the_final_project_proposal ?? '-',
                optional($assessment->student)->design_theme ?? '-',
                optional($assessment->group)->name ?? '-',
                $assessment->assessment_stage ?? '-',
                optional($assessment->user)->name ?? '-',
                is_array($assessment->assessment) ? implode(', ', $assessment->assessment) : json_encode($assessment->assessment),
                $assessment->notes ?? '-',
            ];
        });
    }

    private function createExcelFileWithOpenSpout(string $filename, array $header, Collection $rows): void
    {
        $styleHeader = $this->getHeaderStyle();
        $styleCell = $this->getCellStyle();

        $options = new Options();
        $writer = new Writer($options);
        $writer->openToFile($filename);

        $headerRow = Row::fromValues($header);
        $headerRow->setStyle($styleHeader);
        $writer->addRow($headerRow);
        $options->setColumnWidth(30, 1);
        $options->setColumnWidth(30, 2);
        $options->setColumnWidth(30, 3);
        $options->setColumnWidth(30, 4);
        $options->setColumnWidth(30, 5);
        $options->setColumnWidth(30, 6);
        $options->setColumnWidth(30, 7);
        $options->setColumnWidthForRange(25, 1, 9);

        foreach ($rows as $rowValues) {
            $row = Row::fromValues($rowValues);
            $row->setStyle($styleCell);
            $writer->addRow($row);
        }

        $writer->close();
    }

    private function getHeaderStyle(): Style
    {
        return (new Style())
            ->setFontBold()
            ->setFontSize(14)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::BLACK);
    }

    private function getCellStyle(): Style
    {
        return (new Style())
            ->setFontSize(12);
    }

    private function dispatchNotificationJob(string $filename): void
    {
        Bus::chain([
            new SendExportNotificationJob($this->user, $filename, 'excel'),
        ])->dispatch();
    }
}
