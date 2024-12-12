<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AxFileReadyNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $upload;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
    }

    public function build()
    {
        return $this->markdown('emails.ax-file-ready')
            ->subject('File AX pronto per il download')
            ->with([
                'upload' => $this->upload,
                'details' => [
                    'process_date' => $this->upload->process_date->format('d/m/Y'),
                    'ax_export_path' => $this->upload->ax_export_path,
                    'completed_at' => now()->format('d/m/Y H:i'),
                    'user_name' => $this->upload->user->full_name
                ]
            ]);
    }
}