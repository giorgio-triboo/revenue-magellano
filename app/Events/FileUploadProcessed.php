<?php
namespace App\Events;

use App\Models\FileUpload;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileUploadProcessed
{
    use Dispatchable, SerializesModels;

    public $upload;

    public function __construct(FileUpload $upload)
    {
        $this->upload = $upload;
    }
}
