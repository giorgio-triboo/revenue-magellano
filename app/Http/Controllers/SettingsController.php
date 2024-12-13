<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        $this->authorize('view', 'App\Models\Settings');
        return view('settings.index', [
            'adminerUrl' => route('settings.adminer')
        ]);
    }

    public function adminer()
    {
        $this->authorize('view', 'App\Models\Settings');
        
        $adminerPath = resource_path('views/settings/adminer/adminer.php');
        
        if (!file_exists($adminerPath)) {
            abort(404, 'Adminer non trovato.');
        }

        // Create an isolated environment for Adminer
        return response()->stream(function () use ($adminerPath) {
            // Disable Laravel's error handling temporarily
            restore_error_handler();
            restore_exception_handler();
            
            // Clear any existing output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Create a new output buffer
            ob_start();
            
            // Define a function to check if a function exists in global namespace
            $functionExists = function($name) {
                try {
                    $reflection = new \ReflectionFunction($name);
                    return $reflection->getNamespaceName() === '';
                } catch (\ReflectionException $e) {
                    return false;
                }
            };
            
            // Only declare the cookie function if it doesn't exist in global namespace
            if (!$functionExists('cookie')) {
                function cookie($name, $value = '', $lifetime = 0, $path = '', $domain = '', $secure = false, $httponly = false) {
                    return \setcookie($name, $value, $lifetime ? time() + $lifetime : 0, $path, $domain, $secure, $httponly);
                }
            }
            
            // Include Adminer in isolated scope
            (function () use ($adminerPath) {
                include $adminerPath;
            })();
            
            // Flush the output buffer
            ob_end_flush();
            
            // Restore Laravel's error handling
            \App::make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
            set_error_handler([\Illuminate\Foundation\Bootstrap\HandleExceptions::class, 'handleError']);
            
        }, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff'
        ]);
    }
}