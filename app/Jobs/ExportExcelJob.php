<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Assessment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendExportNotificationJob;
use Filament\Notifications\Notification;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use Illuminate\Foundation\Queue\Queueable;
use OpenSpout\Common\Entity\Style\CellAlignment;
use App\Traits\EnsureExportDirectory;

class ExportExcelJob
{
    use Queueable, EnsureExportDirectory;

    protected array $studentIds;
    protected $user;

    public function __construct(array $studentIds, $user)
    {
        $this->studentIds = $studentIds;
        $this->user = $user;
    }

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

            // kirim notifikasi langsung (tidak pakai queue chain)
            (new SendExportNotificationJob($this->user, $filename, 'excel'))->handle();

        } catch (\Exception $e) {
            Log::error('Excel export failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function fetchRecords(): Collection
    {
        $query = Assessment::with(['student']);

        if (!empty($this->studentIds)) {
            $query->whereIn('student_id', $this->studentIds);
        }

        return $query->get();
    }

    private function handleNoRecordsFound(): void
    {
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
        return 'exports/assessments-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
    }

    private function getHeader(): array
{
    return [
        'Nama Mahasiswa',
        'NIM',
        'Judul Tugas Akhir',
        'Group',
        'Assessment Stage',
        'Kriteria',
        'Nilai',
        'Catatan',
    ];
}

private function prepareRows(Collection $records): Collection
{
    return $records->flatMap(function ($assessment) {
        return $assessment->items->map(function ($item) use ($assessment) {
            return [
                optional($assessment->student)->name ?? '-',
                optional($assessment->student)->nim ?? '-',
                optional($assessment->student)->title_of_the_final_project_proposal ?? '-',
                optional($assessment->student->group)->name ?? '-',
                $assessment->assessment_stage ?? '-',
                $item->label ?? '-',       // Kriteria
                $item->score ?? '-',       // Nilai
                $item->description ?? '-', // Catatan dari item
            ];
        });
    });
}


    private function createExcelFileWithOpenSpout(string $filename, array $header, Collection $rows): void
    {
        $styleHeader = $this->getHeaderStyle();
        $styleCell = $this->getCellStyle();

        $options = new Options();
        $writer = new Writer($options);
        $writer->openToFile($filename);

        // Header
        $headerRow = Row::fromValues($header);
        $headerRow->setStyle($styleHeader);
        $writer->addRow($headerRow);

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
            ->setFontSize(13)
            ->setCellAlignment(CellAlignment::CENTER)
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::DARK_BLUE);
    }

    private function getCellStyle(): Style
    {
        return (new Style())->setFontSize(12);
    }
}
