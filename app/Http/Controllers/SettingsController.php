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
    $path = resource_path('views/settings/adminer/adminer.php');

    \Log::debug("Percorso Adminer: $path");

    if (!file_exists($path)) {
        \Log::debug('Adminer non trovato.');
        abort(404, 'Adminer non trovato.');
    }

    return response()->file($path);
}



}