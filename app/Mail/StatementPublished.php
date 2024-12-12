<?php

namespace App\Mail;

use App\Models\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;



class StatementPublished extends Mailable
{
    use Queueable, SerializesModels;

    public $upload;
    public $publishDetails;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
        $this->publishDetails = $this->getPublishDetails();
    }

    public function build()
    {
        return $this->markdown('emails.uploads.statement-published')
        ->subject(config('app.name') . ' - Consuntivo Pubblicato' );
    }

    protected function getPublishDetails(): array
    {
        return [
            'total_records' => $this->upload->statements()->count(),
            'process_date' => $this->upload->process_date ? $this->upload->process_date->format('d/m/Y') : 'N/D',
            'published_at' => $this->upload->published_at ? $this->upload->published_at->format('d/m/Y H:i') : 'N/D',
            'year' => $this->upload->process_date ? $this->upload->process_date->format('Y') : date('Y'),
            'month' => $this->upload->process_date ? $this->upload->process_date->locale('it')->isoFormat('MMMM') : Carbon::now()->locale('it')->isoFormat('MMMM') ,
        ];
    }
}