<?php
// UploadService.php

namespace App\Services;

use App\Models\FileUpload;
use App\Jobs\ProcessCsvUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UploadService
{
    public function handleFileUpload(UploadedFile $file, $userId, $processDate): FileUpload
    {
        try {
            Log::channel('upload')->info('Starting file upload process', [
                'user_id' => $userId,
                'file_name' => $file->getClientOriginalName(),
                'process_date' => $processDate,
                'session_id' => session()->getId(),
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'file_size' => $this->formatBytes($file->getSize()),
                'mime_type' => $file->getMimeType(),
                'error_code' => $file->getError(),
                'error_message' => $file->getErrorMessage()
            ]);

            // Validazioni essenziali
            Log::channel('upload')->debug('Starting basic file validation');
            $this->validateBasicUpload($file, $processDate);
            Log::channel('upload')->debug('Basic file validation completed successfully');

            // Carica il file
            Log::channel('upload')->debug('Starting file storage process');
            $upload = $this->storeFile($file, $userId, $processDate);
            Log::channel('upload')->debug('File storage completed successfully', [
                'upload_id' => $upload->id,
                'stored_path' => $upload->stored_filename
            ]);

            // Dispatch del job di validazione e processing
            Log::channel('upload')->info('Dispatching processing job', [
                'upload_id' => $upload->id,
                'queue' => 'csv-processing',
                'job_class' => ProcessCsvUpload::class
            ]);

            ProcessCsvUpload::dispatch($upload)->onQueue('csv-processing');

            Log::channel('upload')->info('Processing job dispatched successfully', [
                'upload_id' => $upload->id,
                'queue' => 'csv-processing'
            ]);

            return $upload;

        } catch (\Exception $e) {
            Log::channel('upload')->error('Error in handleFileUpload', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            throw $e;
        }
    }

    protected function validateBasicUpload(UploadedFile $file, $processDate): void
    {
        $startTime = microtime(true);
        
        Log::channel('upload')->debug('Starting basic file validation', [
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $this->formatBytes($file->getSize()),
            'process_date' => $processDate
        ]);

        // 1. Validazione del file
        if (!$file->isValid()) {
            Log::channel('upload')->error('File validation failed', [
                'error_message' => $file->getErrorMessage(),
                'error_code' => $file->getError()
            ]);
            throw new \Exception('Il file caricato non è valido: ' . $file->getErrorMessage());
        }
        Log::channel('upload')->debug('File validity check passed');

        // 2. Validazione del mime type
        $allowedMimes = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            Log::channel('upload')->error('Invalid mime type', [
                'mime_type' => $file->getMimeType(),
                'allowed_mimes' => $allowedMimes
            ]);
            throw new \Exception('Il formato del file non è valido. Accettiamo solo file CSV.');
        }
        Log::channel('upload')->debug('Mime type validation passed');

        // 3. Verifica dimensione massima (10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            Log::channel('upload')->error('File size exceeds limit', [
                'file_size' => $this->formatBytes($file->getSize()),
                'max_size' => $this->formatBytes($maxSize)
            ]);
            throw new \Exception('Il file supera la dimensione massima consentita di 10MB.');
        }
        Log::channel('upload')->debug('File size validation passed');

        // 4. Verifica duplicati per data
        Log::channel('upload')->debug('Checking for existing uploads for date', [
            'process_date' => $processDate
        ]);
        $this->checkExistingUpload($processDate);
        Log::channel('upload')->debug('Duplicate check passed');

        $timeTaken = (microtime(true) - $startTime) * 1000;
        Log::channel('upload')->debug('Basic validation completed', [
            'time_taken_ms' => round($timeTaken, 2),
            'memory_usage' => $this->formatBytes(memory_get_usage(true))
        ]);
    }

    protected function checkExistingUpload($processDate): void
    {
        Log::channel('upload')->debug('Checking for existing uploads', [
            'process_date' => $processDate
        ]);

        $existingUpload = FileUpload::where('process_date', $processDate)
            ->whereIn('status', [
                FileUpload::STATUS_PENDING,
                FileUpload::STATUS_PROCESSING,
                FileUpload::STATUS_COMPLETED,
                FileUpload::STATUS_PUBLISHED
            ])
            ->first();

        if ($existingUpload) {
            $month = Carbon::parse($processDate)->format('F Y');
            Log::channel('upload')->warning('Duplicate upload attempt detected', [
                'process_date' => $processDate,
                'existing_upload_id' => $existingUpload->id,
                'existing_status' => $existingUpload->status,
                'existing_created_at' => $existingUpload->created_at
            ]);

            throw new \Exception(
                "Esiste già un file caricato per il mese di {$month}. " .
                "Stato attuale: " . $this->getStatusDescription($existingUpload->status)
            );
        }
        Log::channel('upload')->debug('No existing uploads found for date', [
            'process_date' => $processDate
        ]);
    }

    protected function storeFile(UploadedFile $file, $userId, $processDate): FileUpload
    {
        $startTime = microtime(true);

        Log::channel('upload')->debug('Starting file storage process', [
            'user_id' => $userId,
            'process_date' => $processDate,
            'original_filename' => $file->getClientOriginalName()
        ]);

        $uploadPath = sprintf('uploads/%s/%s', now()->year, now()->month);
        Storage::disk('private')->makeDirectory($uploadPath);
        Log::channel('upload')->debug('Upload directory created', [
            'path' => $uploadPath
        ]);

        $storedFilename = $this->generateFileName($file);
        $fullPath = Storage::disk('private')->putFileAs($uploadPath, $file, $storedFilename);
        Log::channel('upload')->debug('File stored successfully', [
            'stored_filename' => $storedFilename,
            'full_path' => $fullPath
        ]);

        $upload = FileUpload::create([
            'user_id' => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $fullPath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'process_date' => $processDate,
            'status' => FileUpload::STATUS_PENDING,
            'processed_records' => 0,
            'progress_percentage' => 0,
            'processing_stats' => [
                'start_time' => now()->toDateTimeString(),
                'success' => 0,
                'errors' => 0,
                'error_details' => []
            ]
        ]);

        $timeTaken = (microtime(true) - $startTime) * 1000;
        Log::channel('upload')->info('File storage process completed', [
            'upload_id' => $upload->id,
            'stored_path' => $fullPath,
            'time_taken_ms' => round($timeTaken, 2),
            'memory_usage' => $this->formatBytes(memory_get_usage(true))
        ]);

        return $upload;
    }

    private function generateFileName(UploadedFile $file): string
    {
        return sprintf(
            '%s_%s.%s',
            Str::random(32),
            now()->format('YmdHis'),
            $file->getClientOriginalExtension()
        );
    }

    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    protected function getStatusDescription(string $status): string
    {
        return match ($status) {
            FileUpload::STATUS_PENDING => 'In attesa di elaborazione',
            FileUpload::STATUS_PROCESSING => 'In elaborazione',
            FileUpload::STATUS_COMPLETED => 'Elaborazione completata',
            FileUpload::STATUS_PUBLISHED => 'Pubblicato',
            FileUpload::STATUS_ERROR => 'Errore',
            default => $status
        };
    }
}

