<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Student;
use App\Models\Assessment;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Jobs\SendExportNotificationJob;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\EnsureExportDirectory;

class ExportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, EnsureExportDirectory;

    protected $studentId;
    protected $user;

    public function __construct($studentId, $user)
    {
        $this->studentId = $studentId;
        $this->user = $user;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '512M');
        Log::info('Starting PDF generation');

        try {
            $records = $this->fetchRecords();
            if ($records->isEmpty()) {
                $this->handleNoRecordsFound();
                return;
            }

            $this->ensureExportsFolderExists();

            $filename = $this->generateFilename();
            Log::info('Saving PDF', ['filename' => $filename]);

            $this->generatePdf($records, $filename);

            Log::info('PDF generated successfully');

            $this->dispatchNotificationJob($filename);
        } catch (\Exception $e) {
            Log::error('PDF generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function fetchRecords(): Collection
    {
        if (empty($this->studentIds)) {
            return Assessment::with(['student'])->get();
        }

        return Assessment::with(['student'])
            ->where('student_id', $this->studentId)
            ->get();
    }

    private function handleNoRecordsFound(): void
    {
        $studentId = empty($this->studentId) ? 'ALL_STUDENTS' : implode(', ', $this->studentId);

        Log::info('No records found for student ID: ' . $this->studentId);
        $this->user->notify(
            Notification::make()
                ->title('No Data Available')
                ->body('There is no data available to export for the selected student at this time.')
                ->icon('heroicon-o-x-circle')
                ->danger()
                ->toDatabase()
        );
    }

    private function generateFilename(): string
    {
        return 'exports/assessment-data-' . Carbon::now()->format('Y-m-d_H-i-s') . '.pdf';
    }

    private function generatePdf(Collection $records, string $filename): void
    {
        $pdf = Pdf::loadView('pdfs.data', ['records' => $records])
            ->setPaper('A4', 'landscape');

        Storage::disk('public')->put($filename, $pdf->output());
    }

    private function dispatchNotificationJob(string $filename): void
    {
        Bus::chain([
            new SendExportNotificationJob($this->user, $filename, 'pdf'),
        ])->dispatch();
    }
}
