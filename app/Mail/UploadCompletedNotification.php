<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UploadCompletedNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $upload;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
    }

    public function build()
    {
        return $this->markdown('emails.upload-completed')
            ->subject('Nuovo upload completato')
            ->with([
                'upload' => $this->upload,
                'details' => [
                    'original_filename' => $this->upload->original_filename,
                    'process_date' => $this->upload->process_date ? $this->upload->process_date->format('d/m/Y') : 'N/D',
                    'processed_at' => $this->upload->processed_at ? $this->upload->processed_at->format('d/m/Y H:i') : 'N/D',
                    'total_records' => $this->upload->total_records,
                    'user_name' => $this->upload->user->full_name
                ]
            ]);
    }
}