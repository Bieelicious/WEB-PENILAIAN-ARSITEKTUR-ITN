<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendExportNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $filename;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $filename, $type)
    {
        $this->user = $user;
        $this->filename = $filename;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $message = $this->type === 'pdf'
                ? 'The PDF report has been successfully generated. Click the button below to download it.'
                : 'The Excel report has been successfully generated. Click the button below to download it.';

            $this->user->notify(
                Notification::make()
                    ->title(ucfirst($this->type) . ' Report Ready')
                    ->body($message)
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('Download ' . strtoupper($this->type))
                            ->label('Download .' . $this->type)
                            ->color('success')
                            ->markAsRead()
                            ->url(route('download.' . $this->type, ['filename' => basename($this->filename)])),
                    ])
                    ->icon('heroicon-o-paper-clip')
                    ->success()
                    ->toDatabase()
            );

            Log::info(ucfirst($this->type) . ' notification sent successfully', ['user_id' => $this->user->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send ' . $this->type . ' notification', ['error' => $e->getMessage()]);
        }
    }
}
