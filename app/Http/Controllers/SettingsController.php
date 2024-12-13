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
        $adminerPath = resource_path('views/settings/adminer/adminer.php');

        if (!file_exists($adminerPath)) {
            abort(404, 'Adminer non trovato.');
        }

        // Includi il contenuto di Adminer come parte della risposta
        require_once $adminerPath;
        exit; // Termina l'esecuzione del framework dopo aver caricato Adminer
    }



}