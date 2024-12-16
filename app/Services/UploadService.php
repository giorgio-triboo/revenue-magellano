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
                'memory_usage' => $this->formatBytes(memory_get_usage(true))
            ]);

            // Validazioni essenziali
            $this->validateBasicUpload($file, $processDate);

            // Carica il file
            $upload = $this->storeFile($file, $userId, $processDate);

            Log::channel('upload')->info('File stored successfully, dispatching processing job', [
                'upload_id' => $upload->id,
                'file_path' => $upload->stored_filename,
                'memory_usage' => $this->formatBytes(memory_get_usage(true))
            ]);

            // Dispatch del job di validazione e processing
            ProcessCsvUpload::dispatch($upload)->onQueue('csv-processing');

            return $upload;

        } catch (\Exception $e) {
            Log::channel('upload')->error('Error in handleFileUpload', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => $this->formatBytes(memory_get_usage(true))
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
            'size' => $this->formatBytes($file->getSize())
        ]);

        // 1. Validazione del file
        if (!$file->isValid()) {
            throw new \Exception('Il file caricato non è valido: ' . $file->getErrorMessage());
        }

        // 2. Validazione del mime type
        $allowedMimes = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Il formato del file non è valido. Accettiamo solo file CSV.');
        }

        // 3. Verifica dimensione massima (10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new \Exception('Il file supera la dimensione massima consentita di 10MB.');
        }

        // 4. Verifica duplicati per data
        $this->checkExistingUpload($processDate);

        $timeTaken = (microtime(true) - $startTime) * 1000;
        Log::channel('upload')->debug('Basic validation completed', [
            'time_taken_ms' => round($timeTaken, 2)
        ]);
    }

    protected function checkExistingUpload($processDate): void
    {
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
            Log::channel('upload')->warning('Duplicate upload attempt', [
                'process_date' => $processDate,
                'existing_upload_id' => $existingUpload->id,
                'existing_status' => $existingUpload->status
            ]);

            throw new \Exception(
                "Esiste già un file caricato per il mese di {$month}. " .
                "Stato attuale: " . $this->getStatusDescription($existingUpload->status)
            );
        }
    }

    protected function storeFile(UploadedFile $file, $userId, $processDate): FileUpload
    {
        $startTime = microtime(true);

        $uploadPath = sprintf('uploads/%s/%s', now()->year, now()->month);
        Storage::disk('private')->makeDirectory($uploadPath);

        $storedFilename = $this->generateFileName($file);
        $fullPath = Storage::disk('private')->putFileAs($uploadPath, $file, $storedFilename);

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
        Log::channel('upload')->info('File stored successfully', [
            'upload_id' => $upload->id,
            'stored_path' => $fullPath,
            'time_taken_ms' => round($timeTaken, 2)
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

