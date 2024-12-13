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
}