<?php

namespace App\Http\Controllers;

class SettingsController extends Controller
{
    public function index()
    {
        $this->authorize('view', 'App\Models\Settings');
        
        return view('settings.index', [
            'adminerUrl' => 'http://localhost:8082'
        ]);
    }
    public function adminer()
    {
        $path = base_path('adminer/adminer.php');

        if (!file_exists($path)) {
            abort(404, 'Adminer non trovato.');
        }

        return response()->file($path);
    }

}