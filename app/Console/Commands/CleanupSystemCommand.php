<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupSystemCommand extends Command
{
    protected $signature = 'system:cleanup';
    protected $description = 'Clean up old logs, files and database records';

    public function handle()
    {
        $this->info('Starting system cleanup...');
        
        // 1. Cleanup logs
        $this->cleanupLogs();
        
        // 2. Cleanup upload files
        $this->cleanupUploadFiles();
        
        // 3. Cleanup export files
        $this->cleanupExportFiles();
        
        // 4. Cleanup unpublished statements
        $this->cleanupUnpublishedStatements();
        
        $this->info('System cleanup completed.');
    }

    private function cleanupLogs()
    {
        $this->info('Cleaning up logs...');
        $logChannels = ['upload', 'event', 'job', 'ax_export', 'listener'];
        
        foreach ($logChannels as $channel) {
            $logPath = storage_path("logs/{$channel}.log-*");
            $oldLogs = glob($logPath);
            
            foreach ($oldLogs as $log) {
                // Extract date from filename (format: channel.log-YYYY-MM-DD)
                preg_match('/\d{4}-\d{2}-\d{2}/', $log, $matches);
                if (!empty($matches)) {
                    $logDate = Carbon::createFromFormat('Y-m-d', $matches[0]);
                    if ($logDate->addDays(7)->isPast()) {
                        unlink($log);
                        Log::info("Deleted old log file: {$log}");
                    }
                }
            }
        }
    }

    private function cleanupUploadFiles()
    {
        $this->info('Cleaning up upload files...');
        $uploadPath = 'private/upload';
        $disk = Storage::disk('local');
        
        if ($disk->exists($uploadPath)) {
            foreach ($disk->files($uploadPath) as $file) {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
                if ($lastModified->addDays(14)->isPast()) {
                    $disk->delete($file);
                    Log::info("Deleted old upload file: {$file}");
                }
            }
        }
    }

    private function cleanupExportFiles()
    {
        $this->info('Cleaning up export files...');
        $exportPath = 'private/export';
        $disk = Storage::disk('local');
        
        if ($disk->exists($exportPath)) {
            foreach ($disk->files($exportPath) as $file) {
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));
                if ($lastModified->addDays(14)->isPast()) {
                    $disk->delete($file);
                    Log::info("Deleted old export file: {$file}");
                }
            }
        }
    }

    private function cleanupUnpublishedStatements()
    {
        $this->info('Cleaning up unpublished statements...');
        
        // Get file_upload_ids for unpublished statements older than 14 days
        $fileUploadIds = DB::table('statements')
            ->where('is_published', false)
            ->where('created_at', '<', Carbon::now()->subDays(14))
            ->distinct()
            ->pluck('file_upload_id')
            ->toArray();

        // Delete unpublished statements
        $deletedStatements = DB::table('statements')
            ->where('is_published', false)
            ->where('created_at', '<', Carbon::now()->subDays(14))
            ->delete();
            
        Log::info("Deleted {$deletedStatements} unpublished statements");

        // Delete related file_uploads
        if (!empty($fileUploadIds)) {
            $deletedUploads = DB::table('file_uploads')
                ->whereIn('id', $fileUploadIds)
                ->delete();
                
            Log::info("Deleted {$deletedUploads} related file uploads");
        }
    }
}