<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SftpUploadNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $upload;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
    }

    public function build()
    {
        $subject = $this->upload->sftp_status === FileUpload::SFTP_STATUS_COMPLETED 
            ? 'Upload SFTP completato con successo'
            : 'Errore durante l\'upload SFTP';

        return $this->markdown('emails.sftp-upload-status')
            ->subject($subject)
            ->with([
                'upload' => $this->upload,
                'details' => [
                    'process_date' => $this->upload->process_date->format('d/m/Y'),
                    'status' => $this->upload->sftp_status,
                    'error_message' => $this->upload->sftp_error_message,
                    'uploaded_at' => $this->upload->sftp_uploaded_at?->format('d/m/Y H:i'),
                    'filename' => basename($this->upload->ax_export_path),
                    'user_name' => $this->upload->user->name
                ]
            ]);
    }
}