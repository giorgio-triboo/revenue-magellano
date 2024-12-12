<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CsvProcessingCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public $upload;
    public $details;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
        $this->details = $this->getProcessingDetails();
    }

    public function build()
    {
        return $this->markdown('emails.uploads.processing-completed')
                    ->subject('Elaborazione Completata - ' . config('app.name'));
    }

    protected function getProcessingDetails(): array
    {
        return [
            'total_records' => $this->upload->statements()->count(),
            'process_date' => $this->upload->process_date,
            'processed_at' => $this->upload->processed_at->format('d/m/Y H:i'),
            'original_filename' => $this->upload->original_filename,
        ];
    }
}